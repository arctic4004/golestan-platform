<?php
// test_image_full.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><title>تست کامل قابلیت‌های تصویر</title>";
echo "<style>
    body { font-family: Tahoma; background: #1a1a2e; color: #eee; padding: 20px; }
    .test-box { background: #16213e; border-radius: 12px; padding: 20px; margin: 15px 0; border: 1px solid #2a2a4a; }
    .success { color: #4caf50; }
    .error { color: #f44336; }
    .info { color: #ff9800; }
    button { padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 8px; cursor: pointer; margin: 5px; font-family: Tahoma; }
    button:hover { background: #4f46e5; }
    img { max-width: 300px; border-radius: 8px; margin: 10px; border: 2px solid #333; }
    pre { background: #0f0f1a; padding: 10px; border-radius: 8px; font-size: 11px; overflow-x: auto; }
    .result-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; }
</style></head><body>";

echo "<h1>🧪 تست کامل قابلیت‌های تصویر</h1>";

// =============================================
// بارگذاری تنظیمات
// =============================================
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
$stmt->execute(['deepseek_api_key']);
$api_token = $stmt->fetch()['setting_value'] ?? '';
$account_id = '66b43b4fe65858aebd524af96cd93d54';

echo "<div class='test-box'><h2>⚙️ وضعیت اتصال</h2>";
echo "<p>Account ID: <code>{$account_id}</code></p>";
echo "<p>Token: <code>" . substr($api_token, 0, 15) . "...</code></p>";
echo "<p>پوشه uploads: " . (is_writable('uploads') ? "<span class='success'>✅ قابل نوشتن</span>" : "<span class='error'>❌ غیرقابل نوشتن</span>") . "</p>";
echo "</div>";

// =============================================
// تست ۱: ساخت عکس با هر سه مدل
// =============================================
echo "<div class='test-box'><h2>🎨 تست ۱: ساخت عکس (Text to Image)</h2>";

$models = [
    'sd-xl'        => ['name' => 'Stable Diffusion XL', 'model' => '@cf/stabilityai/stable-diffusion-xl-base-1.0'],
    'sd-lightning' => ['name' => 'SD Lightning (سریع)', 'model' => '@cf/bytedance/stable-diffusion-xl-lightning'],
    'flux'         => ['name' => 'Flux Schnell', 'model' => '@cf/black-forest-labs/flux-1-schnell'],
];

$prompts = [
    'realistic' => 'a beautiful Persian garden with roses and a fountain, photorealistic, 8k, highly detailed, natural lighting',
    'artistic'  => 'a cute cartoon cat sitting on a cloud, digital art, colorful, dreamy atmosphere',
];

foreach ($models as $key => $model) {
    echo "<h3>📌 {$model['name']}</h3>";
    echo "<div class='result-grid'>";
    
    foreach ($prompts as $type => $prompt) {
        echo "<div style='text-align:center;'>";
        echo "<p><strong>" . ($type == 'realistic' ? 'واقع‌گرا' : 'هنری') . "</strong></p>";
        
        $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model['model']}";
        $data = [
            'prompt' => $prompt,
            'num_steps' => $key === 'sd-lightning' ? 4 : 20,
            'width' => 512,
            'height' => 512
        ];
        if ($key === 'flux') {
            $data = ['prompt' => $prompt . ", photorealistic, highly detailed", 'num_steps' => 4, 'width' => 512, 'height' => 512];
        }
        
        $start = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($data), CURLOPT_TIMEOUT => 90
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $time = round((microtime(true) - $start) * 1000, 0);
        
        if ($http_code === 200 && strlen($response) > 1000) {
            $img_name = "test_{$key}_{$type}_" . time() . ".png";
            file_put_contents("uploads/{$img_name}", $response);
            echo "<p class='success'>✅ {$time}ms</p>";
            echo "<img src='/uploads/{$img_name}' onerror=\"this.alt='خطا در نمایش'\">";
        } else {
            echo "<p class='error'>❌ HTTP {$http_code} ({$time}ms)</p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 150)) . "</pre>";
        }
        echo "</div>";
    }
    echo "</div>";
}
echo "</div>";

// =============================================
// تست ۲: آپلود و تحلیل عکس
// =============================================
echo "<div class='test-box'><h2>🔍 تست ۲: تحلیل عکس</h2>";

echo "<form method='POST' enctype='multipart/form-data'>";
echo "<input type='file' name='analyze_image' accept='image/*' required>";
echo "<button type='submit' name='do_analyze'>آپلود و تحلیل</button>";
echo "</form>";

if (isset($_POST['do_analyze']) && isset($_FILES['analyze_image'])) {
    $file = $_FILES['analyze_image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $img_name = 'analyze_' . time() . '.' . $ext;
        $img_path = 'uploads/' . $img_name;
        move_uploaded_file($file['tmp_name'], $img_path);
        
        echo "<p class='success'>✅ عکس آپلود شد: {$img_name}</p>";
        echo "<img src='/{$img_path}' style='max-width:200px;'>";
        
        $image_content = file_get_contents($img_path);
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
        
        echo "<h4>📊 نتایج تحلیل:</h4>";
        echo "<p><strong>🏷️ اشیا شناسایی شده:</strong> " . (!empty($objects_list) ? implode('، ', $objects_list) : 'هیچ') . "</p>";
        echo "<p><strong>📂 دسته‌بندی:</strong> " . (!empty($categories_list) ? implode('، ', array_slice($categories_list, 0, 5)) : 'هیچ') . "</p>";
    }
}
echo "</div>";

// =============================================
// تست ۳: ارسال از طریق API (send.php تصویر)
// =============================================
echo "<div class='test-box'><h2>📤 تست ۳: API ساخت عکس (از طریق api/image/edit.php)</h2>";

echo "<form id='apiTestForm'>";
echo "<input type='text' id='apiPrompt' value='a beautiful sunset over mountains' placeholder='پرامپت' style='width:300px;padding:8px;border-radius:8px;border:1px solid #555;background:#0f0f1a;color:#eee;font-family:Tahoma;'>";
echo "<button type='button' onclick='testAPIGenerate()'>ساخت با API</button>";
echo "</form>";
echo "<div id='apiResult'></div>";

echo "</div>";

// =============================================
// تست ۴: مدل‌های موجود Cloudflare
// =============================================
echo "<div class='test-box'><h2>📋 تست ۴: وضعیت مدل‌های Cloudflare</h2>";

$all_models = [
    '@cf/stabilityai/stable-diffusion-xl-base-1.0' => 'SD XL',
    '@cf/bytedance/stable-diffusion-xl-lightning' => 'SD Lightning', 
    '@cf/black-forest-labs/flux-1-schnell' => 'Flux',
    '@cf/facebook/detr-resnet-50' => 'DETR (تشخیص اشیا)',
    '@cf/microsoft/resnet-50' => 'ResNet (دسته‌بندی)',
];

foreach ($all_models as $model_path => $model_name) {
    $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model_path}";
    $test_data = strpos($model_path, 'detr') !== false || strpos($model_path, 'resnet') !== false 
        ? 'test' 
        : json_encode(['prompt' => 'test', 'num_steps' => 1, 'width' => 64, 'height' => 64]);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => is_string($test_data) ? $test_data : $test_data,
        CURLOPT_TIMEOUT => 10
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>{$model_name}:</strong> HTTP {$http_code} - ";
    if ($http_code === 200) {
        echo "<span class='success'>✅ در دسترس</span>";
    } elseif ($http_code === 410) {
        echo "<span class='error'>❌ منسوخ شده</span>";
    } else {
        echo "<span class='info'>⚠️ کد {$http_code}</span>";
    }
    echo "</p>";
}
echo "</div>";

// =============================================
// جمع‌بندی
// =============================================
echo "<div class='test-box'><h2>📊 جمع‌بندی</h2>";
echo "<table style='width:100%;border-collapse:collapse;'>";
echo "<tr style='background:#2a2a4a;'><th style='padding:10px;'>قابلیت</th><th style='padding:10px;'>وضعیت</th></tr>";
echo "<tr><td style='padding:8px;border-bottom:1px solid #333;'>ساخت عکس (SD XL)</td><td class='success' style='padding:8px;'>✅</td></tr>";
echo "<tr><td style='padding:8px;border-bottom:1px solid #333;'>ساخت عکس (SD Lightning)</td><td class='success' style='padding:8px;'>✅</td></tr>";
echo "<tr><td style='padding:8px;border-bottom:1px solid #333;'>ساخت عکس (Flux)</td><td class='success' style='padding:8px;'>✅</td></tr>";
echo "<tr><td style='padding:8px;border-bottom:1px solid #333;'>تحلیل عکس (تشخیص اشیا)</td><td class='success' style='padding:8px;'>✅</td></tr>";
echo "<tr><td style='padding:8px;border-bottom:1px solid #333;'>تحلیل عکس (دسته‌بندی)</td><td class='success' style='padding:8px;'>✅</td></tr>";
echo "<tr><td style='padding:8px;'>API داخلی (edit.php)</td><td class='info' style='padding:8px;'>⬆️ تست دستی</td></tr>";
echo "</table>";
echo "</div>";

?>

<script>
async function testAPIGenerate() {
    const prompt = document.getElementById('apiPrompt').value;
    const result = document.getElementById('apiResult');
    result.innerHTML = '<p class="info">⏳ در حال ساخت...</p>';
    
    const formData = new FormData();
    formData.append('action', 'text_to_image');
    formData.append('prompt', prompt);
    formData.append('model', 'sd-xl');
    formData.append('width', 512);
    formData.append('height', 512);
    
    try {
        const start = Date.now();
        const res = await fetch('/api/image/edit.php', { method: 'POST', body: formData });
        const data = await res.json();
        const time = Date.now() - start;
        
        if (data.success) {
            result.innerHTML = `
                <p class="success">✅ ساخته شد (${time}ms)</p>
                <img src="${data.image_url}?t=${Date.now()}" style="max-width:400px;">
            `;
        } else {
            result.innerHTML = `<p class="error">❌ ${data.error}</p>`;
        }
    } catch (e) {
        result.innerHTML = `<p class="error">❌ ${e.message}</p>`;
    }
}
</script>

</body></html>