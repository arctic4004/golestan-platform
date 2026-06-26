<?php
// test_image.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 تست ساخت عکس با Cloudflare AI</h2>";

$account_id = '3ff489207f28602e2652ae16b157e89f';
$api_token = 'cfut_fHdZnWM2SgapvNgrKfXX4l7Cb1bLTyclGPHpHnWb8674ddd2';

// چک پوشه uploads
echo "<h3>📁 چک پوشه uploads:</h3>";
$upload_dir = __DIR__ . '/uploads';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    echo "<p style='color:orange;'>پوشه uploads ساخته شد</p>";
}
echo "<p>مسیر: $upload_dir</p>";
echo "<p>قابل نوشتن: " . (is_writable($upload_dir) ? '✅ بله' : '❌ خیر') . "</p>";

// تست ساخت عکس
echo "<h3>🎨 تست ساخت عکس:</h3>";

$models = [
    '@cf/stabilityai/stable-diffusion-xl-base-1.0',
    '@cf/bytedance/stable-diffusion-xl-lightning',
];

foreach ($models as $model) {
    echo "<p><strong>مدل:</strong> " . basename($model) . "</p>";
    
    $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model}";
    
    $data = [
        'prompt' => 'a beautiful sunset over mountains, highly detailed, 4k',
        'num_steps' => 20,
        'width' => 512,
        'height' => 512
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
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    echo "<p>HTTP: $http_code | Content-Type: $content_type</p>";
    echo "<p>حجم پاسخ: " . strlen($response) . " بایت</p>";
    
    if ($http_code === 200 && strlen($response) > 1000) {
        // چک کن JSON یا مستقیم عکس
        $json = json_decode($response, true);
        
        if ($json && isset($json['result']['image'])) {
            // base64 توی JSON
            $image_data = base64_decode($json['result']['image']);
            echo "<p style='color:green;'>✅ عکس base64 توی JSON</p>";
        } else {
            // مستقیم باینری
            $image_data = $response;
            echo "<p style='color:green;'>✅ عکس مستقیم (binary)</p>";
        }
        
        $image_name = 'test_' . time() . '.png';
        $image_path = $upload_dir . '/' . $image_name;
        file_put_contents($image_path, $image_data);
        chmod($image_path, 0644);
        
        $size = filesize($image_path);
        echo "<p style='color:green;font-size:18px;'>✅ عکس ذخیره شد: $size بایت</p>";
        
        if ($size > 0) {
            echo "<img src='/uploads/$image_name' style='max-width:300px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.2);'>";
        }
        
        echo "<hr>";
        break;
    } else {
        echo "<p style='color:red;'>❌ خطا</p>";
        echo "<pre style='background:#ffe6e6;padding:10px;font-size:11px;'>" . htmlspecialchars(substr($response, 0, 300)) . "</pre>";
    }
}
?>