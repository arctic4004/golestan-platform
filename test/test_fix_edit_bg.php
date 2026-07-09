<?php
// test_fix_edit_bg.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 تست اصلاح‌شده: ویرایش عکس و حذف بکگراند</h1>";
echo "<style>
    body { font-family: Tahoma; background: #1a1a2e; color: #eee; padding: 20px; }
    .test-box { background: #16213e; border-radius: 12px; padding: 20px; margin: 15px 0; border: 1px solid #2a2a4a; }
    .success { color: #4caf50; }
    .error { color: #f44336; }
    .info { color: #ff9800; }
    img { max-width: 300px; border-radius: 8px; margin: 10px; border: 2px solid #333; }
    button { padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 8px; cursor: pointer; margin: 5px; font-family: Tahoma; }
    pre { background: #0f0f1a; padding: 10px; border-radius: 8px; font-size: 11px; overflow-x: auto; }
</style>";

$account_id = '3ff489207f28602e2652ae16b157e89f';
$api_token = 'cfut_fHdZnWM2SgapvNgrKfXX4l7Cb1bLTyclGPHpHnWb8674ddd2';
$upload_dir = __DIR__ . '/uploads';

if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);

// آپلود عکس تست
echo "<div class='test-box'><h2>📤 آپلود عکس تست</h2>";
echo "<form method='POST' enctype='multipart/form-data'>";
echo "<input type='file' name='test_upload' accept='image/*' required>";
echo "<button type='submit' name='do_upload'>آپلود و تست همه چیز</button>";
echo "</form>";

if (isset($_POST['do_upload']) && isset($_FILES['test_upload']) && $_FILES['test_upload']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['test_upload'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $img_name = 'source_' . time() . '.' . $ext;
    $img_path = $upload_dir . '/' . $img_name;
    
    move_uploaded_file($file['tmp_name'], $img_path);
    echo "<span class='success'>✅ عکس آپلود شد: $img_name</span><br>";
    echo "<img src='/uploads/{$img_name}' style='max-width:200px;'>";
    
    $image_url = '/uploads/' . $img_name;
    $full_path = __DIR__ . $image_url;
    
    // =============================================
    // تست ویرایش (img2img) - با base64 صحیح
    // =============================================
    echo "</div><div class='test-box'><h2>✏️ تست ویرایش عکس (img2img) - اصلاح‌شده</h2>";
    
    $image_content = file_get_contents($full_path);
    $image_base64 = base64_encode($image_content);
    
    echo "<p>حجم عکس: " . strlen($image_content) . " بایت</p>";
    echo "<p>طول base64: " . strlen($image_base64) . " کاراکتر</p>";
    
    $model = '@cf/runwayml/stable-diffusion-v1-5-img2img';
    $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model}";
    
    $edits = [
        'برفی' => 'add snow, winter scene, snowy mountains, cold atmosphere',
        'غروب' => 'sunset golden hour, warm colors, dramatic sky, cinematic lighting',
        'ارتقا' => 'enhance quality, sharpen, 8k, professional photography, highly detailed',
    ];
    
    foreach ($edits as $edit_name => $edit_prompt) {
        echo "<h3>$edit_name</h3>";
        
        // فرمت صحیح: image باید base64 string باشه، نه آرایه
        $data = [
            'prompt' => $edit_prompt,
            'image' => $image_base64,  // ← مستقیماً base64 string
            'num_steps' => 30,
            'strength' => 0.5,
            'guidance' => 7.5
        ];
        
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
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        echo "<p>HTTP: $http_code | حجم پاسخ: " . strlen($response) . " بایت</p>";
        if ($curl_error) echo "<p class='error'>CURL Error: $curl_error</p>";
        
        if ($http_code === 200 && strlen($response) > 1000) {
            $edited_name = 'edited_' . str_replace(' ', '_', $edit_name) . '_' . time() . '.png';
            file_put_contents($upload_dir . '/' . $edited_name, $response);
            
            echo "<span class='success'>✅ موفق!</span>";
            echo "<br><img src='/uploads/{$edited_name}'>";
        } else {
            echo "<span class='error'>❌ خطا</span>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 300)) . "</pre>";
        }
    }
    
    // =============================================
    // تست حذف بکگراند - روش جایگزین
    // =============================================
    echo "</div><div class='test-box'><h2>🔲 تست حذف بکگراند - روش‌های مختلف</h2>";
    
    // روش ۱: Hugging Face
    echo "<h3>روش ۱: Hugging Face</h3>";
    $ch = curl_init("https://api-inference.huggingface.co/models/briaai/RMBG-1.4");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/octet-stream'],
        CURLOPT_POSTFIELDS => $image_content,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "<p>HTTP: $http_code | حجم: " . strlen($response) . " بایت</p>";
    
    if ($http_code === 200 && strlen($response) > 1000) {
        $nobg_name = 'nobg_hf_' . time() . '.png';
        file_put_contents($upload_dir . '/' . $nobg_name, $response);
        echo "<span class='success'>✅ موفق!</span>";
        echo "<br><img src='/uploads/{$nobg_name}' style='background:#f0f0f0;'>";
    } else {
        echo "<span class='error'>❌ Hugging Face در دسترس نیست (تحریم؟)</span>";
    }
    
    // روش ۲: Remove.bg (نیاز به API key داره - رایگان ۵۰ تا در ماه)
    echo "<h3>روش ۲: Remove.bg</h3>";
    echo "<p class='info'>نیاز به API key از remove.bg دارد. <a href='https://www.remove.bg/api' target='_blank' style='color:#ff9800;'>ثبت‌نام رایگان</a></p>";
    
    // روش ۳: حذف بکگراند با Cloudflare (اگر مدلش موجود باشه)
    echo "<h3>روش ۳: تست مدل‌های Cloudflare برای حذف بکگراند</h3>";
    
    $bg_models = [
        '@cf/facebook/detr-resnet-50',  // تشخیص اشیا
        '@cf/microsoft/resnet-50',       // دسته‌بندی
    ];
    
    foreach ($bg_models as $bg_model) {
        echo "<p>تست $bg_model...</p>";
        $result = callCloudflareAIImage($bg_model, $image_content, $account_id, $api_token);
        echo "<pre>" . htmlspecialchars(substr($result['response'], 0, 300)) . "</pre>";
    }
}

echo "</div>";

// =============================================
// تابع کمکی
// =============================================
function callCloudflareAIImage($model, $image_binary, $account_id, $api_token) {
    $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model}";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_token,
            'Content-Type: application/octet-stream'
        ],
        CURLOPT_POSTFIELDS => $image_binary,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['http_code' => $http_code, 'response' => $response];
}

// =============================================
// راهنما
// =============================================
echo "<div class='test-box'><h2>📝 نتیجه‌گیری</h2>";
echo "<table style='width:100%;border-collapse:collapse;'>";
echo "<tr style='background:#2a2a4a;'><th style='padding:10px;'>قابلیت</th><th style='padding:10px;'>وضعیت</th><th style='padding:10px;'>توضیح</th></tr>";
echo "<tr><td style='padding:10px;border-bottom:1px solid #333;'>🎨 ساخت عکس</td><td class='success' style='padding:10px;'>✅ عالی</td><td style='padding:10px;'>SD XL بهترین کیفیت، Flux سریعتر</td></tr>";
echo "<tr><td style='padding:10px;border-bottom:1px solid #333;'>✏️ ویرایش عکس</td><td class='info' style='padding:10px;'>⚠️ باید تست بشه</td><td style='padding:10px;'>با اصلاح فرمت base64 باید کار کنه</td></tr>";
echo "<tr><td style='padding:10px;border-bottom:1px solid #333;'>🔲 حذف بکگراند</td><td class='error' style='padding:10px;'>❌ نیاز به API</td><td style='padding:10px;'>remove.bg یا Cloudflare مدل جدید</td></tr>";
echo "</table>";

echo "<h3>🚀 مراحل بعدی:</h3>";
echo "<ol>";
echo "<li>همین الان با آپلود عکس، ویرایش img2img رو تست کن</li>";
echo "<li>برای حذف بکگراند، API رایگان <a href='https://www.remove.bg/api' target='_blank' style='color:#ff9800;'>remove.bg</a> رو بگیر (۵۰ عدد رایگان)</li>";
echo "<li>یا از Cloudflare مدل جدیدتر برای حذف بکگراند استفاده کن</li>";
echo "</ol>";
echo "</div>";
?>