<?php
// api/image/edit.php - نسخه کامل و بهینه‌شده
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../config/database.php';

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

// =============================================
// UPLOAD
// =============================================
if ($action === 'upload') {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'فایلی آپلود نشده']);
        exit;
    }

    $file = $_FILES['image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(['error' => 'فرمت مجاز: JPG, PNG, WEBP, GIF']);
        exit;
    }

    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);

    $image_name = 'up_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $image_path = $upload_dir . '/' . $image_name;

    if (move_uploaded_file($file['tmp_name'], $image_path)) {
        chmod($image_path, 0644);
        echo json_encode(['success' => true, 'image_url' => '/uploads/' . $image_name]);
    } else {
        echo json_encode(['error' => 'خطا در آپلود']);
    }
    exit;
}

// =============================================
// TEXT TO IMAGE
// =============================================
if ($action === 'text_to_image') {
    $prompt = trim($_POST['prompt'] ?? 'beautiful landscape');
    $model_choice = $_POST['model'] ?? 'flux';
    $width = intval($_POST['width'] ?? 512);
    $height = intval($_POST['height'] ?? 512);

    // ترجمه فارسی
    if (preg_match('/[\x{0600}-\x{06FF}]/u', $prompt)) {
        $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/meta/llama-4-scout-17b-16e-instruct");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'messages' => [['role' => 'user', 'content' => "Translate to English for image generation: $prompt"]],
                'max_tokens' => 100
            ]),
            CURLOPT_TIMEOUT => 10
        ]);
        $trans = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if (isset($trans['result']['response'])) {
            $prompt = trim($trans['result']['response']);
        }
    }

    // افزودن کلمات کلیدی کیفیت
    $prompt .= ", photorealistic, highly detailed, professional photography, 8k, sharp focus, natural lighting";

    $model_url = '@cf/black-forest-labs/flux-1-schnell';
    $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model_url}";

    $data = [
        'prompt' => $prompt,
        'num_steps' => 4,
        'width' => max(256, min(1024, $width)),
        'height' => max(256, min(1024, $height)),
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 90
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // چک معتبر بودن پاسخ
    $is_json_response = (strpos($response, '{') === 0 || strpos($response, '{"') === 0);
    $is_image = (strlen($response) > 1000 && !$is_json_response);

    if ($http_code === 200 && $is_image) {
        // چک ۴ بایت اول برای اطمینان از PNG
        $header = substr($response, 0, 4);
        $is_png = (bin2hex($header) === '89504e47');

        if ($is_png || strlen($response) > 5000) {
            $image_name = 'gen_' . time() . '_' . rand(1000, 9999) . '.png';
            $image_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $image_name;

            $written = file_put_contents($image_path, $response);
            chmod($image_path, 0644);

            // تأیید نهایی
            if ($written > 0 && filesize($image_path) === $written) {
                $verify = file_get_contents($image_path, false, null, 0, 4);
                if (bin2hex($verify) === '89504e47') {
                    echo json_encode([
                        'success' => true,
                        'image_url' => '/uploads/' . $image_name,
                        'file_size' => $written,
                        'model' => 'flux',
                        'message' => '✅ عکس با موفقیت ساخته شد'
                    ]);
                    exit;
                }
            }
            
            // اگر به اینجا رسید، ذخیره ناموفق بوده
            if (file_exists($image_path)) unlink($image_path);
            echo json_encode(['error' => 'خطا در ذخیره عکس. لطفاً دوباره تلاش کنید.']);
            exit;
        }
    }

    // خطا
    $error_msg = 'خطا در ساخت عکس';
    if ($is_json_response) {
        $error_data = json_decode($response, true);
        $api_error = $error_data['errors'][0]['message'] ?? '';
        if (strpos($api_error, 'NSFW') !== false) {
            $error_msg = '⚠️ محتوای نامناسب در پرامپت تشخیص داده شد. لطفاً پرامپت دیگری بنویسید.';
        } else {
            $error_msg = $api_error ?: "خطای API (HTTP $http_code)";
        }
    }
    
    echo json_encode(['error' => $error_msg]);
    exit;
}

// =============================================
// ANALYZE IMAGE
// =============================================
if ($action === 'analyze') {
    $image_url = $_POST['image_url'] ?? '';
    if (empty($image_url)) { echo json_encode(['error' => 'لطفاً عکس را آپلود کنید']); exit; }

    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($full_path)) { echo json_encode(['error' => 'فایل پیدا نشد']); exit; }

    $image_content = file_get_contents($full_path);
    $objects_list = [];
    $categories_list = [];

    // تشخیص اشیا
    $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/facebook/detr-resnet-50");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/octet-stream'],
        CURLOPT_POSTFIELDS => $image_content, CURLOPT_TIMEOUT => 30
    ]);
    $res1 = curl_exec($ch); curl_close($ch);
    $data1 = json_decode($res1, true);
    if (isset($data1['result'])) {
        foreach ($data1['result'] as $obj) {
            if ($obj['score'] > 0.5) $objects_list[] = $obj['label'] . ' (' . round($obj['score'] * 100) . '%)';
        }
    }

    // دسته‌بندی
    $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/microsoft/resnet-50");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/octet-stream'],
        CURLOPT_POSTFIELDS => $image_content, CURLOPT_TIMEOUT => 30
    ]);
    $res2 = curl_exec($ch); curl_close($ch);
    $data2 = json_decode($res2, true);
    if (isset($data2['result'])) {
        foreach ($data2['result'] as $cat) {
            if ($cat['score'] > 0.1) $categories_list[] = $cat['label'] . ' (' . round($cat['score'] * 100) . '%)';
        }
    }

    echo json_encode([
        'success' => true,
        'objects' => !empty($objects_list) ? implode('، ', $objects_list) : 'شیء خاصی تشخیص داده نشد',
        'categories' => !empty($categories_list) ? implode('، ', array_slice($categories_list, 0, 5)) : '---',
        'labels' => !empty($categories_list) ? implode('، ', array_slice(array_column($data2['result'] ?? [], 'label'), 0, 5)) : '---'
    ]);
    exit;
}

// =============================================
// IMAGE TO PROMPT
// =============================================
if ($action === 'image_to_prompt') {
    $image_url = $_POST['image_url'] ?? '';
    if (empty($image_url)) { echo json_encode(['error' => 'لطفاً عکس را آپلود کنید']); exit; }

    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($full_path)) { echo json_encode(['error' => 'فایل پیدا نشد']); exit; }

    $image_content = file_get_contents($full_path);
    $objects = [];
    $categories = [];

    $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/facebook/detr-resnet-50");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/octet-stream'],
        CURLOPT_POSTFIELDS => $image_content, CURLOPT_TIMEOUT => 30
    ]);
    $res1 = curl_exec($ch); curl_close($ch);
    $data1 = json_decode($res1, true);
    if (isset($data1['result'])) {
        foreach ($data1['result'] as $obj) {
            if ($obj['score'] > 0.3) $objects[] = $obj['label'];
        }
    }

    $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/microsoft/resnet-50");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/octet-stream'],
        CURLOPT_POSTFIELDS => $image_content, CURLOPT_TIMEOUT => 30
    ]);
    $res2 = curl_exec($ch); curl_close($ch);
    $data2 = json_decode($res2, true);
    if (isset($data2['result'])) {
        foreach ($data2['result'] as $cat) {
            if ($cat['score'] > 0.05) $categories[] = $cat['label'];
        }
    }

    $all_tags = array_unique(array_merge($objects, array_slice($categories, 0, 5)));
    $tags_string = implode(', ', $all_tags);

    $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/meta/llama-4-scout-17b-16e-instruct");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'messages' => [
                ['role' => 'system', 'content' => 'Generate ONE creative English prompt for AI image generation based on the given tags. Include photorealistic, 8k, highly detailed. Output ONLY the prompt.'],
                ['role' => 'user', 'content' => "Tags: $tags_string"]
            ],
            'max_tokens' => 100
        ]),
        CURLOPT_TIMEOUT => 15
    ]);
    $res3 = curl_exec($ch); curl_close($ch);
    $data3 = json_decode($res3, true);

    echo json_encode([
        'success' => true,
        'objects' => implode('، ', $objects),
        'categories' => implode('، ', array_slice($categories, 0, 5)),
        'prompt' => trim($data3['result']['response'] ?? $tags_string),
        'tags' => $tags_string
    ]);
    exit;
}

// =============================================
// GENERATE ALT TEXT
// =============================================
if ($action === 'generate_alt') {
    $image_url = $_POST['image_url'] ?? '';
    if (empty($image_url)) { echo json_encode(['error' => 'لطفاً عکس را آپلود کنید']); exit; }

    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($full_path)) { echo json_encode(['error' => 'فایل پیدا نشد']); exit; }

    $image_content = file_get_contents($full_path);
    $objects = [];

    $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/facebook/detr-resnet-50");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/octet-stream'],
        CURLOPT_POSTFIELDS => $image_content, CURLOPT_TIMEOUT => 30
    ]);
    $res = curl_exec($ch); curl_close($ch);
    $data = json_decode($res, true);
    if (isset($data['result'])) {
        foreach ($data['result'] as $obj) {
            if ($obj['score'] > 0.3) $objects[] = $obj['label'];
        }
    }

    $objects_str = implode(', ', array_unique($objects));

    $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/meta/llama-4-scout-17b-16e-instruct");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'messages' => [
                ['role' => 'system', 'content' => 'Generate SEO-friendly alt text for an image. Max 125 characters. Describe main objects and mood. Output ONLY the alt text.'],
                ['role' => 'user', 'content' => "Image contains: $objects_str"]
            ],
            'max_tokens' => 50
        ]),
        CURLOPT_TIMEOUT => 10
    ]);
    $res2 = curl_exec($ch); curl_close($ch);
    $data2 = json_decode($res2, true);

    echo json_encode([
        'success' => true,
        'alt_text' => trim($data2['result']['response'] ?? "تصویر شامل $objects_str"),
        'objects' => $objects_str
    ]);
    exit;
}

// =============================================
// ACTION نامعتبر
// =============================================
echo json_encode(['error' => 'عملیات نامشخص']);
?>