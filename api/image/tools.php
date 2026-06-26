<?php
// api/image/tools.php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'لطفاً وارد شوید']);
    exit;
}

$account_id = '66b43b4fe65858aebd524af96cd93d54';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
$stmt->execute(['deepseek_api_key']);
$api_token = $stmt->fetch()['setting_value'] ?? '';

$action = $_POST['action'] ?? '';
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads';

// =============================================
// ۱. MAGIC COLOR (Inpainting - تغییر رنگ/محتوای ناحیه)
// =============================================
if ($action === 'magic_color') {
    $image_url = $_POST['image_url'] ?? '';
    $prompt = $_POST['prompt'] ?? 'change color to red';
    $mask_data = $_POST['mask'] ?? ''; // base64 mask image
    
    if (empty($image_url) || empty($mask_data)) {
        echo json_encode(['error' => 'لطفاً عکس و ناحیه مورد نظر را مشخص کنید']);
        exit;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($full_path)) {
        echo json_encode(['error' => 'فایل پیدا نشد']);
        exit;
    }
    
    // خواندن عکس اصلی
    $image_content = file_get_contents($full_path);
    $image_base64 = base64_encode($image_content);
    
    // Decode mask
    $mask_base64 = str_replace('data:image/png;base64,', '', $mask_data);
    
    // ارسال به Cloudflare Inpainting
    $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/runwayml/stable-diffusion-v1-5-inpainting";
    
    $data = [
        'prompt' => $prompt . ", photorealistic, highly detailed, 8k",
        'image' => $image_base64,
        'mask' => $mask_base64,
        'num_steps' => 25,
        'strength' => 0.7
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($data), CURLOPT_TIMEOUT => 90
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && strlen($response) > 1000) {
        $image_name = 'magic_' . time() . '.png';
        $image_path = $upload_dir . '/' . $image_name;
        file_put_contents($image_path, $response);
        
        echo json_encode(['success' => true, 'image_url' => '/uploads/' . $image_name]);
    } else {
        echo json_encode(['error' => 'ویرایش انجام نشد. دوباره تلاش کنید.']);
    }
    exit;
}

// =============================================
// ۲. حذف بک‌گراند (Background Blur/Remove)
// =============================================
if ($action === 'remove_bg_blur') {
    $image_url = $_POST['image_url'] ?? '';
    $blur_level = intval($_POST['blur'] ?? 10);
    
    if (empty($image_url)) {
        echo json_encode(['error' => 'لطفاً عکس را آپلود کنید']);
        exit;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($full_path)) {
        echo json_encode(['error' => 'فایل پیدا نشد']);
        exit;
    }
    
    // استفاده از GD برای محو کردن بک‌گراند (تشخیص سوژه با DETR)
    $image_content = file_get_contents($full_path);
    
    // تشخیص اشیاء اصلی
    $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/facebook/detr-resnet-50");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/octet-stream'],
        CURLOPT_POSTFIELDS => $image_content, CURLOPT_TIMEOUT => 30
    ]);
    $res = curl_exec($ch); curl_close($ch);
    $objects = json_decode($res, true)['result'] ?? [];
    
    // پیدا کردن بزرگترین شیء (سوژه اصلی)
    $main_object = null;
    $max_area = 0;
    foreach ($objects as $obj) {
        if ($obj['score'] > 0.5) {
            $area = ($obj['box']['xmax'] - $obj['box']['xmin']) * ($obj['box']['ymax'] - $obj['box']['ymin']);
            if ($area > $max_area) {
                $max_area = $area;
                $main_object = $obj;
            }
        }
    }
    
    // ایجاد عکس با بک‌گراند محو
    $img = imagecreatefromstring($image_content);
    $width = imagesx($img);
    $height = imagesy($img);
    
    // اگر شیء اصلی پیدا شد، اطرافش رو blur کن
    if ($main_object) {
        $box = $main_object['box'];
        $x1 = max(0, $box['xmin'] - 20);
        $y1 = max(0, $box['ymin'] - 20);
        $x2 = min($width, $box['xmax'] + 20);
        $y2 = min($height, $box['ymax'] + 20);
        
        // ایجاد نسخه blur شده از کل عکس
        $blurred = imagecreatefromstring($image_content);
        for ($i = 0; $i < $blur_level; $i++) {
            imagefilter($blurred, IMG_FILTER_GAUSSIAN_BLUR);
        }
        
        // جایگذاری سوژه اصلی روی عکس blur شده
        imagecopy($blurred, $img, $x1, $y1, $x1, $y1, $x2 - $x1, $y2 - $y1);
        $img = $blurred;
    }
    
    $image_name = 'blurred_' . time() . '.png';
    $image_path = $upload_dir . '/' . $image_name;
    imagepng($img, $image_path);
    imagedestroy($img);
    
    echo json_encode([
        'success' => true,
        'image_url' => '/uploads/' . $image_name,
        'objects_found' => count($objects)
    ]);
    exit;
}

// =============================================
// ۳. تبدیل عکس به PDF
// =============================================
if ($action === 'image_to_pdf') {
    $image_urls = $_POST['image_urls'] ?? ''; // می‌تواند JSON array باشد
    
    if (empty($image_urls)) {
        echo json_encode(['error' => 'لطفاً حداقل یک عکس انتخاب کنید']);
        exit;
    }
    
    $urls = json_decode($image_urls, true);
    if (!is_array($urls)) $urls = [$image_urls];
    
    // استفاده از Imagick برای تبدیل
    if (!class_exists('Imagick')) {
        echo json_encode(['error' => 'کتابخانه Imagick نصب نیست']);
        exit;
    }
    
    $pdf = new Imagick();
    foreach ($urls as $url) {
        $full_path = $_SERVER['DOCUMENT_ROOT'] . $url;
        if (file_exists($full_path)) {
            $pdf->readImage($full_path);
        }
    }
    
    $pdf_name = 'converted_' . time() . '.pdf';
    $pdf_path = $upload_dir . '/' . $pdf_name;
    $pdf->writeImages($pdf_path, true);
    $pdf->clear();
    
    echo json_encode(['success' => true, 'pdf_url' => '/uploads/' . $pdf_name]);
    exit;
}

// =============================================
// ۴. تبدیل PDF به عکس
// =============================================
if ($action === 'pdf_to_image') {
    $pdf_url = $_POST['pdf_url'] ?? '';
    
    if (empty($pdf_url)) {
        echo json_encode(['error' => 'لطفاً فایل PDF را آپلود کنید']);
        exit;
    }
    
    if (!class_exists('Imagick')) {
        echo json_encode(['error' => 'کتابخانه Imagick نصب نیست']);
        exit;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $pdf_url;
    if (!file_exists($full_path)) {
        echo json_encode(['error' => 'فایل PDF پیدا نشد']);
        exit;
    }
    
    $imagick = new Imagick();
    $imagick->readImage($full_path);
    $pages = $imagick->getNumberImages();
    
    $images = [];
    for ($i = 0; $i < $pages; $i++) {
        $imagick->setIteratorIndex($i);
        $image_name = 'pdf_page_' . time() . '_' . $i . '.jpg';
        $image_path = $upload_dir . '/' . $image_name;
        $imagick->writeImage($image_path);
        $images[] = '/uploads/' . $image_name;
    }
    $imagick->clear();
    
    echo json_encode(['success' => true, 'images' => $images, 'pages' => $pages]);
    exit;
}

// =============================================
// ۵. CROP (برش عکس)
// =============================================
if ($action === 'crop') {
    $image_url = $_POST['image_url'] ?? '';
    $x = intval($_POST['x'] ?? 0);
    $y = intval($_POST['y'] ?? 0);
    $w = intval($_POST['w'] ?? 100);
    $h = intval($_POST['h'] ?? 100);
    
    if (empty($image_url)) {
        echo json_encode(['error' => 'لطفاً عکس را مشخص کنید']);
        exit;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($full_path)) {
        echo json_encode(['error' => 'فایل پیدا نشد']);
        exit;
    }
    
    $img = imagecreatefromstring(file_get_contents($full_path));
    $cropped = imagecrop($img, ['x' => $x, 'y' => $y, 'width' => $w, 'height' => $h]);
    
    $image_name = 'crop_' . time() . '.png';
    $image_path = $upload_dir . '/' . $image_name;
    imagepng($cropped, $image_path);
    imagedestroy($img);
    imagedestroy($cropped);
    
    echo json_encode(['success' => true, 'image_url' => '/uploads/' . $image_name]);
    exit;
}

// =============================================
// ۶. ROTATE/FLIP
// =============================================
if ($action === 'rotate') {
    $image_url = $_POST['image_url'] ?? '';
    $angle = intval($_POST['angle'] ?? 90);
    
    if (empty($image_url)) {
        echo json_encode(['error' => 'لطفاً عکس را مشخص کنید']);
        exit;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($full_path)) {
        echo json_encode(['error' => 'فایل پیدا نشد']);
        exit;
    }
    
    $img = imagecreatefromstring(file_get_contents($full_path));
    $rotated = imagerotate($img, $angle, 0);
    
    $image_name = 'rotate_' . time() . '.png';
    $image_path = $upload_dir . '/' . $image_name;
    imagepng($rotated, $image_path);
    
    echo json_encode(['success' => true, 'image_url' => '/uploads/' . $image_name]);
    exit;
}

// =============================================
// ۷. WATERMARK
// =============================================
if ($action === 'watermark') {
    $image_url = $_POST['image_url'] ?? '';
    $text = $_POST['text'] ?? SITE_NAME;
    
    if (empty($image_url)) {
        echo json_encode(['error' => 'لطفاً عکس را مشخص کنید']);
        exit;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($full_path)) {
        echo json_encode(['error' => 'فایل پیدا نشد']);
        exit;
    }
    
    $img = imagecreatefromstring(file_get_contents($full_path));
    $width = imagesx($img);
    $height = imagesy($img);
    
    // تنظیمات واترمارک
    $color = imagecolorallocatealpha($img, 255, 255, 255, 80);
    $font_size = max(12, $width / 20);
    $x = $width / 2;
    $y = $height - 20;
    
    // استفاده از فونت پیش‌فرض
    imagettftext($img, $font_size, 0, $x - (strlen($text) * $font_size / 3), $y, $color, '/home/golestanyasujir/public_html/assets/fonts/Vazir.ttf', $text);
    
    $image_name = 'watermark_' . time() . '.png';
    $image_path = $upload_dir . '/' . $image_name;
    imagepng($img, $image_path);
    
    echo json_encode(['success' => true, 'image_url' => '/uploads/' . $image_name]);
    exit;
}

// =============================================
// ۸. COMPRESS
// =============================================
if ($action === 'compress') {
    $image_url = $_POST['image_url'] ?? '';
    $quality = intval($_POST['quality'] ?? 60);
    
    if (empty($image_url)) {
        echo json_encode(['error' => 'لطفاً عکس را مشخص کنید']);
        exit;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($full_path)) {
        echo json_encode(['error' => 'فایل پیدا نشد']);
        exit;
    }
    
    $img = imagecreatefromstring(file_get_contents($full_path));
    $image_name = 'compress_' . time() . '.jpg';
    $image_path = $upload_dir . '/' . $image_name;
    imagejpeg($img, $image_path, $quality);
    
    $original_size = filesize($full_path);
    $new_size = filesize($image_path);
    
    echo json_encode([
        'success' => true,
        'image_url' => '/uploads/' . $image_name,
        'original_size' => $original_size,
        'new_size' => $new_size,
        'saved' => round((1 - $new_size / $original_size) * 100)
    ]);
    exit;
}

echo json_encode(['error' => 'عملیات نامشخص']);