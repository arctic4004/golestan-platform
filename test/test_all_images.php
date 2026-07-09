<?php
// test_all_images.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 تست کامل همه قابلیت‌های عکس</h1>";
echo "<style>
    body { font-family: Tahoma; background: #1a1a2e; color: #eee; padding: 20px; }
    .test-box { background: #16213e; border-radius: 12px; padding: 20px; margin: 15px 0; border: 1px solid #2a2a4a; }
    .success { color: #4caf50; }
    .error { color: #f44336; }
    .info { color: #ff9800; }
    img { max-width: 300px; border-radius: 8px; margin: 10px; border: 2px solid #333; }
    button { padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 8px; cursor: pointer; margin: 5px; font-family: Tahoma; }
    button:hover { background: #4f46e5; }
    pre { background: #0f0f1a; padding: 10px; border-radius: 8px; font-size: 11px; overflow-x: auto; }
    .result-img { background: #0f0f1a; padding: 15px; border-radius: 8px; display: inline-block; text-align: center; margin: 10px; }
    .loading { color: #ff9800; }
</style>";

// =============================================
// تنظیمات
// =============================================
$account_id = '3ff489207f28602e2652ae16b157e89f';
$api_token = 'cfut_fHdZnWM2SgapvNgrKfXX4l7Cb1bLTyclGPHpHnWb8674ddd2';
$upload_dir = __DIR__ . '/uploads';

// ساخت پوشه اگر نیست
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    echo "<p class='info'>📁 پوشه uploads ساخته شد</p>";
}

echo "<p>📁 uploads: " . (is_writable($upload_dir) ? "<span class='success'>✅ قابل نوشتن</span>" : "<span class='error'>❌ قابل نوشتن نیست</span>") . "</p>";

// =============================================
// تابع کمکی برای CURL
// =============================================
function callCloudflareAI($model, $data) {
    global $account_id, $api_token;
    
    $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model}";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_token,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 90
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['http_code' => $http_code, 'response' => $response];
}

// =============================================
// تست ۱: ساخت عکس با مدل‌های مختلف
// =============================================
echo "<div class='test-box'><h2>🎨 تست ۱: ساخت عکس (Text to Image)</h2>";

$models_test = [
    'SD XL' => '@cf/stabilityai/stable-diffusion-xl-base-1.0',
    'SD Lightning' => '@cf/bytedance/stable-diffusion-xl-lightning',
    'Flux' => '@cf/black-forest-labs/flux-1-schnell',
];

$prompts_test = [
    'realistic' => 'a beautiful mountain lake at sunset, photorealistic, 8k, highly detailed, professional photography, natural lighting',
    'artistic' => 'a fantasy castle in the clouds, digital art, colorful, magical atmosphere, trending on artstation',
];

foreach ($models_test as $model_name => $model_path) {
    echo "<h3>📌 مدل: $model_name</h3>";
    
    foreach ($prompts_test as $prompt_type => $prompt) {
        echo "<p><strong>نوع:</strong> $prompt_type</p>";
        echo "<p><strong>پرامپت:</strong> " . htmlspecialchars(substr($prompt, 0, 80)) . "...</p>";
        
        $data = ['prompt' => $prompt, 'num_steps' => 20, 'width' => 512, 'height' => 512];
        
        if ($model_name === 'Flux') {
            $data = ['prompt' => $prompt, 'num_steps' => 4, 'width' => 512, 'height' => 512];
        }
        
        $result = callCloudflareAI($model_path, $data);
        
        echo "<p>HTTP: " . $result['http_code'] . " | حجم: " . strlen($result['response']) . " بایت</p>";
        
        if ($result['http_code'] === 200 && strlen($result['response']) > 1000) {
            $img_name = "test_{$model_name}_{$prompt_type}_" . time() . ".png";
            $img_path = $upload_dir . '/' . $img_name;
            file_put_contents($img_path, $result['response']);
            
            echo "<span class='success'>✅ موفق | سایز: " . filesize($img_path) . " بایت</span>";
            echo "<br><img src='/uploads/{$img_name}' onerror=\"this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22><rect fill=%22%23333%22 width=%22200%22 height=%22200%22/><text fill=%22%23fff%22 x=%2250%22 y=%22100%22>خطا</text></svg>'\">";
        } else {
            echo "<span class='error'>❌ خطا</span>";
            $err = json_decode($result['response'], true);
            echo "<pre>" . htmlspecialchars(substr(($err['errors'][0]['message'] ?? $result['response']), 0, 200)) . "</pre>";
        }
        echo "<hr>";
    }
}

echo "</div>";

// =============================================
// تست ۲: آپلود عکس
// =============================================
echo "<div class='test-box'><h2>📤 تست ۲: آپلود عکس</h2>";

echo "<form method='POST' enctype='multipart/form-data'>";
echo "<input type='file' name='test_upload' accept='image/*'>";
echo "<button type='submit' name='do_upload'>آپلود عکس تست</button>";
echo "</form>";

if (isset($_POST['do_upload']) && isset($_FILES['test_upload'])) {
    $file = $_FILES['test_upload'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $img_name = 'test_upload_' . time() . '.' . $ext;
        $img_path = $upload_dir . '/' . $img_name;
        
        if (move_uploaded_file($file['tmp_name'], $img_path)) {
            echo "<span class='success'>✅ آپلود موفق: {$img_name} (" . filesize($img_path) . " بایت)</span>";
            echo "<br><img src='/uploads/{$img_name}'>";
            
            $uploaded_image_url = '/uploads/' . $img_name;
        } else {
            echo "<span class='error'>❌ خطا در آپلود</span>";
        }
    } else {
        echo "<span class='error'>❌ خطای آپلود: کد " . $file['error'] . "</span>";
    }
}

echo "</div>";

// =============================================
// تست ۳: ویرایش عکس (Image to Image)
// =============================================
echo "<div class='test-box'><h2>✏️ تست ۳: ویرایش عکس (img2img)</h2>";

if (isset($uploaded_image_url) && file_exists(__DIR__ . $uploaded_image_url)) {
    $img_content = file_get_contents(__DIR__ . $uploaded_image_url);
    $img_base64 = base64_encode($img_content);
    
    echo "<p>عکس ورودی:</p>";
    echo "<img src='{$uploaded_image_url}' style='max-width:200px;'>";
    
    $edit_prompts = [
        'add snow' => 'add snow to this scene, winter, snowy mountains, cold atmosphere',
        'sunset' => 'make it sunset, golden hour, warm colors, dramatic sky',
        'enhance' => 'enhance this image, improve quality, sharpen, 8k, professional photography',
    ];
    
    foreach ($edit_prompts as $edit_name => $edit_prompt) {
        echo "<p><strong>ویرایش:</strong> $edit_name</p>";
        
        $data = [
            'prompt' => $edit_prompt . ', photorealistic, highly detailed',
            'image' => $img_base64,
            'num_steps' => 30,
            'strength' => 0.5,
            'guidance' => 7.5
        ];
        
        $result = callCloudflareAI('@cf/runwayml/stable-diffusion-v1-5-img2img', $data);
        
        if ($result['http_code'] === 200 && strlen($result['response']) > 1000) {
            $edit_name_file = 'test_edit_' . str_replace(' ', '_', $edit_name) . '_' . time() . '.png';
            file_put_contents($upload_dir . '/' . $edit_name_file, $result['response']);
            
            echo "<span class='success'>✅ ویرایش شد</span>";
            echo "<br><img src='/uploads/{$edit_name_file}'>";
        } else {
            echo "<span class='error'>❌ خطا در ویرایش</span>";
            echo "<pre>" . htmlspecialchars(substr($result['response'], 0, 150)) . "</pre>";
        }
        echo "<hr>";
    }
} else {
    echo "<p class='info'>⚠️ اول یه عکس آپلود کن (تست ۲) تا ویرایش تست بشه</p>";
}

echo "</div>";

// =============================================
// تست ۴: حذف بکگراند
// =============================================
echo "<div class='test-box'><h2>🔲 تست ۴: حذف بکگراند</h2>";

if (isset($uploaded_image_url) && file_exists(__DIR__ . $uploaded_image_url)) {
    echo "<p>عکس ورودی:</p>";
    echo "<img src='{$uploaded_image_url}' style='max-width:200px;'>";
    
    $img_content = file_get_contents(__DIR__ . $uploaded_image_url);
    
    $ch = curl_init("https://api-inference.huggingface.co/models/briaai/RMBG-1.4");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/octet-stream'],
        CURLOPT_POSTFIELDS => $img_content,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP: $http_code | حجم: " . strlen($response) . " بایت</p>";
    
    if ($http_code === 200 && strlen($response) > 1000) {
        $nobg_name = 'test_nobg_' . time() . '.png';
        file_put_contents($upload_dir . '/' . $nobg_name, $response);
        
        echo "<span class='success'>✅ بکگراند حذف شد</span>";
        echo "<br><img src='/uploads/{$nobg_name}' style='background:#f0f0f0;'>";
    } else {
        echo "<span class='error'>❌ خطا در حذف بکگراند</span>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 150)) . "</pre>";
    }
} else {
    echo "<p class='info'>⚠️ اول یه عکس آپلود کن (تست ۲) تا حذف بکگراند تست بشه</p>";
}

echo "</div>";

// =============================================
// جمع‌بندی
// =============================================
echo "<div class='test-box'><h2>📊 جمع‌بندی</h2>";
echo "<p>فایل‌های ساخته شده در پوشه uploads:</p>";
echo "<ul>";
foreach (glob($upload_dir . '/test_*') as $file) {
    $name = basename($file);
    $size = filesize($file);
    echo "<li><a href='/uploads/{$name}' target='_blank'>$name</a> - " . round($size/1024, 1) . " KB</li>";
}
echo "</ul>";
echo "</div>";
?>