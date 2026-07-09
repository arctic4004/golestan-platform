<?php
// api/image/edit.php - نسخه قدرتمند و کامل
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'لطفاً وارد شوید']);
    exit;
}

// توکن و تنظیمات
$db = (new Database())->getConnection();
$token = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'deepseek_api_key'")->fetchColumn();
$account_id = '66b43b4fe65858aebd524af96cd93d54';

if (empty($token)) {
    echo json_encode(['success' => false, 'error' => 'API Token تنظیم نشده']);
    exit;
}

$action = $_POST['action'] ?? '';

// =============================================
// نگاشت مدل‌ها
// =============================================
$models_map = [
    'sd-xl' => '@cf/stabilityai/stable-diffusion-xl-base-1.0',
    'sd-lightning' => '@cf/bytedance/stable-diffusion-xl-lightning',
    'dreamshaper' => '@cf/lykon/dreamshaper-8-lcm',
    'flux' => '@cf/bytedance/stable-diffusion-xl-lightning', // Flux → Lightning
    'stable-diffusion-xl' => '@cf/stabilityai/stable-diffusion-xl-base-1.0',
];

// =============================================
// تابع کمکی: call Cloudflare
// =============================================
function callCF($model, $data, $token, $account_id, $timeout = 60, $is_json = true) {
    $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model}";
    $ch = curl_init($url);
    
    $headers = ['Authorization: Bearer ' . $token];
    if ($is_json) {
        $headers[] = 'Content-Type: application/json';
        $body = json_encode($data);
    } else {
        $headers[] = 'Content-Type: application/octet-stream';
        $body = $data;
    }
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [$response, $http, $error];
}

// =============================================
// تابع کمکی: ذخیره عکس
// =============================================
function saveImage($data, $prefix = 'gen') {
    $dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads';
    if (!file_exists($dir)) mkdir($dir, 0755, true);
    
    $filename = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.png';
    $filepath = $dir . '/' . $filename;
    $written = file_put_contents($filepath, $data);
    chmod($filepath, 0644);
    
    return ($written > 0) ? ['url' => '/uploads/' . $filename, 'path' => $filepath] : null;
}

// =============================================
// تابع کمکی: ترجمه فارسی به انگلیسی
// =============================================
function translateToEnglish($text, $token, $account_id) {
    if (!preg_match('/[\x{0600}-\x{06FF}]/u', $text)) return $text;
    
    list($res, $http) = callCF(
        '@cf/meta/llama-4-scout-17b-16e-instruct',
        ['messages' => [['role' => 'user', 'content' => "Translate to English for image generation. Output ONLY the translation: $text"]], 'max_tokens' => 100],
        $token, $account_id, 10
    );
    
    if ($http === 200) {
        $data = json_decode($res, true);
        return trim($data['result']['response'] ?? $text);
    }
    return $text;
}

// =============================================
// UPLOAD
// =============================================
if ($action === 'upload') {
    $file = $_FILES['image'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'فایلی آپلود نشده']);
        exit;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
        echo json_encode(['success' => false, 'error' => 'فرمت مجاز: JPG, PNG, WEBP, GIF']);
        exit;
    }
    
    $saved = saveImage(file_get_contents($file['tmp_name']), 'up');
    if ($saved) {
        echo json_encode(['success' => true, 'image_url' => $saved['url']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'خطا در ذخیره فایل']);
    }
    exit;
}

// =============================================
// TEXT TO IMAGE (قدرتمند)
// =============================================
if ($action === 'text_to_image') {
    $prompt = trim($_POST['prompt'] ?? '');
    $model_key = $_POST['model'] ?? 'sd-xl';
    $width = max(256, min(1024, intval($_POST['width'] ?? 512)));
    $height = max(256, min(1024, intval($_POST['height'] ?? 512)));
    
    if (empty($prompt)) {
        echo json_encode(['success' => false, 'error' => 'لطفاً توضیح عکس را وارد کنید']);
        exit;
    }
    
    // ترجمه فارسی
    $prompt = translateToEnglish($prompt, $token, $account_id);
    
    // بهبود کیفیت پرامپت
    if (!str_contains(strtolower($prompt), 'photorealistic') && !str_contains(strtolower($prompt), 'art')) {
        $prompt .= ', photorealistic, highly detailed, 8k, sharp focus, professional lighting';
    }
    
    // انتخاب مدل
    $cf_model = $models_map[$model_key] ?? $models_map['sd-xl'];
    
    // پارامترها بر اساس مدل
    $payload = ['prompt' => $prompt];
    
    if (str_contains($cf_model, 'flux')) {
        $payload['num_steps'] = 4;
        $payload['width'] = $width;
        $payload['height'] = $height;
    } else {
        $payload['num_steps'] = 20;
        $payload['width'] = $width;
        $payload['height'] = $height;
    }
    
    // ۳ بار تلاش
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        list($response, $http, $error) = callCF($cf_model, $payload, $token, $account_id, 90);
        
        if ($error) {
            echo json_encode(['success' => false, 'error' => "خطای اتصال (تلاش $attempt)"]);
            exit;
        }
        
        $is_image = (strlen($response) > 1000 && $response[0] !== '{');
        
        if ($http === 200 && $is_image) {
            // ساخت slug از پرامپت
            $prompt_slug = substr(preg_replace('/[^a-z0-9]+/', '_', strtolower(substr($prompt, 0, 60))), 0, 50);
            $timestamp = time();
            $random = rand(1000, 9999);
            $filename = 'gen_' . $timestamp . '_' . $random . '_' . $prompt_slug . '.png';
            $filepath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $filename;
            
            $written = file_put_contents($filepath, $response);
            chmod($filepath, 0644);
            
            if ($written > 0) {
                echo json_encode([
                    'success' => true,
                    'image_url' => '/uploads/' . $filename,
                    'model' => $model_key,
                    'size' => $written,
                    'filename' => $filename,
                    'message' => '✅ عکس با موفقیت ساخته شد'
                ]);
                exit;
            }
        }
        
        // خطای API
        if ($http !== 200) {
            $err = json_decode($response, true);
            $msg = $err['errors'][0]['message'] ?? "HTTP $http";
            
            if (str_contains($msg, 'NSFW')) {
                echo json_encode(['success' => false, 'error' => '⚠️ محتوای نامناسب. لطفاً پرامپت دیگری بنویسید.']);
                exit;
            }
            
            if ($attempt < 3) sleep(2); // صبر کن و دوباره تلاش کن
        }
    }
    
    echo json_encode(['success' => false, 'error' => 'خطا در ساخت عکس. لطفاً دوباره تلاش کنید.']);
    exit;
}

// =============================================
// ANALYZE IMAGE
// =============================================
if ($action === 'analyze') {
    $image_url = $_POST['image_url'] ?? '';
    if (empty($image_url)) {
        echo json_encode(['success' => false, 'error' => 'لطفاً عکس را آپلود کنید']);
        exit;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($full_path)) {
        echo json_encode(['success' => false, 'error' => 'فایل پیدا نشد']);
        exit;
    }
    
    $image_data = file_get_contents($full_path);
    
    // تشخیص اشیا
    list($res1, $http1) = callCF('@cf/facebook/detr-resnet-50', $image_data, $token, $account_id, 30, false);
    $objects = [];
    if ($http1 === 200) {
        foreach (json_decode($res1, true)['result'] ?? [] as $obj) {
            if ($obj['score'] > 0.5) $objects[] = $obj['label'] . ' (' . round($obj['score'] * 100) . '%)';
        }
    }
    
    // دسته‌بندی
    list($res2, $http2) = callCF('@cf/microsoft/resnet-50', $image_data, $token, $account_id, 30, false);
    $categories = [];
    if ($http2 === 200) {
        foreach (json_decode($res2, true)['result'] ?? [] as $cat) {
            if ($cat['score'] > 0.1) $categories[] = $cat['label'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'objects' => $objects ? implode('، ', $objects) : 'شیء خاصی تشخیص داده نشد',
        'categories' => $categories ? implode('، ', array_slice(array_unique($categories), 0, 5)) : '---'
    ]);
    exit;
}

// =============================================
// IMAGE TO PROMPT
// =============================================
if ($action === 'image_to_prompt') {
    $image_url = $_POST['image_url'] ?? '';
    if (empty($image_url)) {
        echo json_encode(['success' => false, 'error' => 'لطفاً عکس را آپلود کنید']);
        exit;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($full_path)) {
        echo json_encode(['success' => false, 'error' => 'فایل پیدا نشد']);
        exit;
    }
    
    $image_data = file_get_contents($full_path);
    
    // تشخیص محتوا
    list($res1) = callCF('@cf/facebook/detr-resnet-50', $image_data, $token, $account_id, 30, false);
    list($res2) = callCF('@cf/microsoft/resnet-50', $image_data, $token, $account_id, 30, false);
    
    $tags = [];
    foreach (json_decode($res1, true)['result'] ?? [] as $o) {
        if ($o['score'] > 0.3) $tags[] = $o['label'];
    }
    foreach (json_decode($res2, true)['result'] ?? [] as $c) {
        if ($c['score'] > 0.05) $tags[] = $c['label'];
    }
    $tags = array_unique($tags);
    
    // ساخت پرامپت با Llama
    list($res3) = callCF('@cf/meta/llama-4-scout-17b-16e-instruct', [
        'messages' => [['role' => 'user', 'content' => "Generate a creative English image prompt based on: " . implode(', ', $tags) . ". Include photorealistic, 8k, detailed. Output ONLY the prompt."]],
        'max_tokens' => 100
    ], $token, $account_id, 15);
    
    $prompt = trim(json_decode($res3, true)['result']['response'] ?? implode(', ', $tags));
    
    echo json_encode([
        'success' => true,
        'prompt' => $prompt,
        'tags' => implode(', ', $tags)
    ]);
    exit;
}

// =============================================
// GENERATE ALT TEXT
// =============================================
if ($action === 'generate_alt') {
    $image_url = $_POST['image_url'] ?? '';
    if (empty($image_url)) {
        echo json_encode(['success' => false, 'error' => 'لطفاً عکس را آپلود کنید']);
        exit;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
    if (!file_exists($full_path)) {
        echo json_encode(['success' => false, 'error' => 'فایل پیدا نشد']);
        exit;
    }
    
    $image_data = file_get_contents($full_path);
    
    // تشخیص
    list($res1) = callCF('@cf/facebook/detr-resnet-50', $image_data, $token, $account_id, 30, false);
    $objects = [];
    foreach (json_decode($res1, true)['result'] ?? [] as $o) {
        if ($o['score'] > 0.3) $objects[] = $o['label'];
    }
    
    // ساخت Alt Text
    list($res2) = callCF('@cf/meta/llama-4-scout-17b-16e-instruct', [
        'messages' => [['role' => 'user', 'content' => "Write SEO-friendly alt text (max 125 chars) for an image containing: " . implode(', ', $objects) . ". Output ONLY the alt text."]],
        'max_tokens' => 60
    ], $token, $account_id, 10);
    
    $alt = trim(json_decode($res2, true)['result']['response'] ?? "تصویر شامل " . implode('، ', $objects));
    
    echo json_encode([
        'success' => true,
        'alt_text' => $alt
    ]);
    exit;
}

// =============================================
// ACTION نامعتبر
// =============================================
echo json_encode(['success' => false, 'error' => 'عملیات نامشخص']);