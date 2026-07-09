<?php
// api/image/tools.php - نسخه قدرتمند ابزارها
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'لطفاً وارد شوید']);
    exit;
}

$db = (new Database())->getConnection();
$token = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'deepseek_api_key'")->fetchColumn();
$account_id = '66b43b4fe65858aebd524af96cd93d54';
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads';

if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);

$action = $_POST['action'] ?? '';

// =============================================
// تابع کمکی
// =============================================
function callAPI($model, $data, $token, $account_id, $is_json = true) {
    $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: ' . ($is_json ? 'application/json' : 'application/octet-stream')
        ],
        CURLOPT_POSTFIELDS => $is_json ? json_encode($data) : $data,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$res, $http];
}

// =============================================
// ۱. MAGIC COLOR / INPAINTING
// =============================================
if ($action === 'magic_color') {
    $image_url = $_POST['image_url'] ?? '';
    $prompt = $_POST['prompt'] ?? 'change color';
    $mask = $_POST['mask'] ?? '';
    
    if (empty($image_url) || empty($mask)) {
        echo json_encode(['success' => false, 'error' => 'عکس و ناحیه مورد نظر الزامی است']);
        exit;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($full_path)) {
        echo json_encode(['success' => false, 'error' => 'فایل پیدا نشد']);
        exit;
    }
    
    $image_b64 = base64_encode(file_get_contents($full_path));
    $mask_b64 = str_replace('data:image/png;base64,', '', $mask);
    
    list($response, $http) = callAPI(
        '@cf/runwayml/stable-diffusion-v1-5-inpainting',
        ['prompt' => $prompt . ', photorealistic, 8k', 'image' => $image_b64, 'mask' => $mask_b64, 'num_steps' => 25],
        $token, $account_id
    );
    
    if ($http === 200 && strlen($response) > 1000) {
        $filename = 'magic_' . time() . '.png';
        file_put_contents($upload_dir . '/' . $filename, $response);
        echo json_encode(['success' => true, 'image_url' => '/uploads/' . $filename]);
    } else {
        echo json_encode(['success' => false, 'error' => 'ویرایش انجام نشد']);
    }
    exit;
}

// =============================================
// ۲. REMOVE BACKGROUND (BLUR)
// =============================================
if ($action === 'remove_bg_blur') {
    $image_url = $_POST['image_url'] ?? '';
    
    if (empty($image_url)) {
        echo json_encode(['success' => false, 'error' => 'عکس الزامی است']);
        exit;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($full_path)) {
        echo json_encode(['success' => false, 'error' => 'فایل پیدا نشد']);
        exit;
    }
    
    $img = imagecreatefromstring(file_get_contents($full_path));
    if (!$img) {
        echo json_encode(['success' => false, 'error' => 'فرمت عکس پشتیبانی نمیشود']);
        exit;
    }
    
    $w = imagesx($img);
    $h = imagesy($img);
    
    // تشخیص سوژه با Cloudflare
    list($res) = callAPI('@cf/facebook/detr-resnet-50', file_get_contents($full_path), $token, $account_id, false);
    $objects = json_decode($res, true)['result'] ?? [];
    
    // پیدا کردن بزرگترین شیء
    $main = null;
    $max_area = 0;
    foreach ($objects as $obj) {
        if ($obj['score'] > 0.5) {
            $area = ($obj['box']['xmax'] - $obj['box']['xmin']) * ($obj['box']['ymax'] - $obj['box']['ymin']);
            if ($area > $max_area) { $max_area = $area; $main = $obj; }
        }
    }
    
    // Blur کل عکس
    $blurred = imagecreatetruecolor($w, $h);
    imagecopy($blurred, $img, 0, 0, 0, 0, $w, $h);
    for ($i = 0; $i < 15; $i++) imagefilter($blurred, IMG_FILTER_GAUSSIAN_BLUR);
    
    // اگر سوژه پیدا شد، جایگذاری کن
    if ($main) {
        $x1 = max(0, $main['box']['xmin'] - 10);
        $y1 = max(0, $main['box']['ymin'] - 10);
        $x2 = min($w, $main['box']['xmax'] + 10);
        $y2 = min($h, $main['box']['ymax'] + 10);
        imagecopy($blurred, $img, $x1, $y1, $x1, $y1, $x2 - $x1, $y2 - $y1);
    }
    
    $filename = 'blurred_' . time() . '.png';
    imagepng($blurred, $upload_dir . '/' . $filename);
    imagedestroy($img);
    imagedestroy($blurred);
    
    echo json_encode(['success' => true, 'image_url' => '/uploads/' . $filename, 'objects_found' => count($objects)]);
    exit;
}

// =============================================
// ۳. IMAGE TO PDF
// =============================================
if ($action === 'image_to_pdf') {
    $urls = json_decode($_POST['image_urls'] ?? '[]', true) ?: [$_POST['image_url'] ?? ''];
    $urls = array_filter($urls);
    
    if (empty($urls)) {
        echo json_encode(['success' => false, 'error' => 'حداقل یک عکس انتخاب کنید']);
        exit;
    }
    
    if (!class_exists('Imagick')) {
        // روش جایگزین با GD
        echo json_encode(['success' => false, 'error' => 'کتابخانه Imagick نصب نیست. لطفاً با پشتیبانی تماس بگیرید.']);
        exit;
    }
    
    $pdf = new Imagick();
    foreach ($urls as $url) {
        $path = $_SERVER['DOCUMENT_ROOT'] . $url;
        if (file_exists($path)) $pdf->readImage($path);
    }
    
    $filename = 'pdf_' . time() . '.pdf';
    $pdf->writeImages($upload_dir . '/' . $filename, true);
    $pdf->clear();
    
    echo json_encode(['success' => true, 'pdf_url' => '/uploads/' . $filename]);
    exit;
}

// =============================================
// ۴. PDF TO IMAGE
// =============================================
if ($action === 'pdf_to_image') {
    $pdf_url = $_POST['pdf_url'] ?? '';
    
    if (empty($pdf_url) || !class_exists('Imagick')) {
        echo json_encode(['success' => false, 'error' => 'امکان تبدیل وجود ندارد']);
        exit;
    }
    
    $path = $_SERVER['DOCUMENT_ROOT'] . $pdf_url;
    if (!file_exists($path)) {
        echo json_encode(['success' => false, 'error' => 'فایل پیدا نشد']);
        exit;
    }
    
    $imagick = new Imagick();
    $imagick->readImage($path);
    $pages = $imagick->getNumberImages();
    
    $images = [];
    for ($i = 0; $i < min($pages, 10); $i++) {
        $imagick->setIteratorIndex($i);
        $fname = 'pdfpage_' . time() . '_' . $i . '.jpg';
        $imagick->writeImage($upload_dir . '/' . $fname);
        $images[] = '/uploads/' . $fname;
    }
    $imagick->clear();
    
    echo json_encode(['success' => true, 'images' => $images, 'pages' => $pages]);
    exit;
}

// =============================================
// ۵. CROP
// =============================================
if ($action === 'crop') {
    $image_url = $_POST['image_url'] ?? '';
    $x = intval($_POST['x'] ?? 0);
    $y = intval($_POST['y'] ?? 0);
    $w = intval($_POST['w'] ?? 100);
    $h = intval($_POST['h'] ?? 100);
    
    if (empty($image_url)) {
        echo json_encode(['success' => false, 'error' => 'عکس الزامی است']);
        exit;
    }
    
    $path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($path)) {
        echo json_encode(['success' => false, 'error' => 'فایل پیدا نشد']);
        exit;
    }
    
    $img = imagecreatefromstring(file_get_contents($path));
    $cropped = imagecrop($img, ['x' => $x, 'y' => $y, 'width' => $w, 'height' => $h]);
    
    if (!$cropped) {
        echo json_encode(['success' => false, 'error' => 'مختصات برش نامعتبر است']);
        exit;
    }
    
    $filename = 'crop_' . time() . '.png';
    imagepng($cropped, $upload_dir . '/' . $filename);
    imagedestroy($img);
    imagedestroy($cropped);
    
    echo json_encode(['success' => true, 'image_url' => '/uploads/' . $filename]);
    exit;
}

// =============================================
// ۶. ROTATE
// =============================================
if ($action === 'rotate') {
    $image_url = $_POST['image_url'] ?? '';
    $angle = intval($_POST['angle'] ?? 90);
    
    if (empty($image_url)) {
        echo json_encode(['success' => false, 'error' => 'عکس الزامی است']);
        exit;
    }
    
    $path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($path)) {
        echo json_encode(['success' => false, 'error' => 'فایل پیدا نشد']);
        exit;
    }
    
    $img = imagecreatefromstring(file_get_contents($path));
    $rotated = imagerotate($img, $angle, 0);
    
    $filename = 'rotate_' . time() . '.png';
    imagepng($rotated, $upload_dir . '/' . $filename);
    imagedestroy($img);
    imagedestroy($rotated);
    
    echo json_encode(['success' => true, 'image_url' => '/uploads/' . $filename]);
    exit;
}

// =============================================
// ۷. WATERMARK
// =============================================
if ($action === 'watermark') {
    $image_url = $_POST['image_url'] ?? '';
    $text = $_POST['text'] ?? SITE_NAME;
    
    if (empty($image_url)) {
        echo json_encode(['success' => false, 'error' => 'عکس الزامی است']);
        exit;
    }
    
    $path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($path)) {
        echo json_encode(['success' => false, 'error' => 'فایل پیدا نشد']);
        exit;
    }
    
    $img = imagecreatefromstring(file_get_contents($path));
    $w = imagesx($img);
    $h = imagesy($img);
    
    $color = imagecolorallocatealpha($img, 255, 255, 255, 70);
    $fontSize = max(10, intval($w / 25));
    $x = intval($w / 2 - strlen($text) * $fontSize / 3);
    $y = $h - 30;
    
    $fontPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/fonts/Vazir.ttf';
    if (file_exists($fontPath)) {
        imagettftext($img, $fontSize, 0, $x, $y, $color, $fontPath, $text);
    } else {
        imagestring($img, 5, $x, $y, $text, $color);
    }
    
    $filename = 'wm_' . time() . '.png';
    imagepng($img, $upload_dir . '/' . $filename);
    imagedestroy($img);
    
    echo json_encode(['success' => true, 'image_url' => '/uploads/' . $filename]);
    exit;
}

// =============================================
// ۸. COMPRESS
// =============================================
if ($action === 'compress') {
    $image_url = $_POST['image_url'] ?? '';
    $quality = max(10, min(100, intval($_POST['quality'] ?? 70)));
    
    if (empty($image_url)) {
        echo json_encode(['success' => false, 'error' => 'عکس الزامی است']);
        exit;
    }
    
    $path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($path)) {
        echo json_encode(['success' => false, 'error' => 'فایل پیدا نشد']);
        exit;
    }
    
    $originalSize = filesize($path);
    $img = imagecreatefromstring(file_get_contents($path));
    
    $filename = 'compressed_' . time() . '.jpg';
    imagejpeg($img, $upload_dir . '/' . $filename, $quality);
    $newSize = filesize($upload_dir . '/' . $filename);
    imagedestroy($img);
    
    echo json_encode([
        'success' => true,
        'image_url' => '/uploads/' . $filename,
        'original_size' => $originalSize,
        'new_size' => $newSize,
        'saved_percent' => $originalSize > 0 ? round((1 - $newSize / $originalSize) * 100) : 0
    ]);
    exit;
}

// =============================================
// ACTION نامعتبر
// =============================================
echo json_encode(['success' => false, 'error' => 'عملیات نامشخص']);