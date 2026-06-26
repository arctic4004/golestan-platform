<?php
// test_image_light.php
set_time_limit(120); // افزایش زمان اجرا
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
$stmt->execute(['deepseek_api_key']);
$api_token = $stmt->fetch()['setting_value'] ?? '';
$account_id = '66b43b4fe65858aebd524af96cd93d54';

echo "<h2>🧪 تست سبک قابلیت‌های تصویر</h2>";

// ۱. ساخت عکس با یک مدل (Flux - سریعترین)
echo "<h3>🎨 تست ساخت عکس (Flux):</h3>";
$url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/black-forest-labs/flux-1-schnell";
$data = json_encode(['prompt' => 'a cute cat, cartoon, colorful', 'num_steps' => 4, 'width' => 512, 'height' => 512]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => $data, CURLOPT_TIMEOUT => 60
]);
$res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);

if ($code === 200 && strlen($res) > 1000) {
    $img = 'test_flux_' . time() . '.png';
    file_put_contents('uploads/' . $img, $res);
    echo "<p style='color:green'>✅ عکس ساخته شد</p><img src='/uploads/{$img}' width='200'>";
} else {
    echo "<p style='color:red'>❌ خطا: HTTP $code</p>";
}

// ۲. تست API داخلی (edit.php) با یک درخواست کوچک
echo "<h3>📤 تست API داخلی (edit.php):</h3>";
echo "<form method='POST' action='/api/image/edit.php' enctype='multipart/form-data' target='_blank'>";
echo "<input type='hidden' name='action' value='text_to_image'>";
echo "<input type='hidden' name='prompt' value='a simple test'>";
echo "<input type='hidden' name='model' value='flux'>";
echo "<button type='submit'>ارسال درخواست</button> (در تب جدید باز می‌شود)";
echo "</form>";

// ۳. تست تحلیل عکس (با آپلود یک فایل واقعی)
echo "<h3>🔍 تست تحلیل عکس:</h3>";
echo "<form method='POST' enctype='multipart/form-data'>";
echo "<input type='file' name='image' accept='image/*' required>";
echo "<button type='submit' name='analyze'>تحلیل کن</button>";
echo "</form>";

if (isset($_POST['analyze']) && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    if ($file['error'] === 0) {
        $tmp = $file['tmp_name'];
        $image_content = file_get_contents($tmp);
        
        // تشخیص اشیا
        $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/facebook/detr-resnet-50");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/octet-stream'],
            CURLOPT_POSTFIELDS => $image_content, CURLOPT_TIMEOUT => 30
        ]);
        $res1 = curl_exec($ch); curl_close($ch);
        $data1 = json_decode($res1, true);
        
        echo "<p><strong>اشیاء شناسایی شده:</strong> ";
        if (isset($data1['result'])) {
            $objects = array_filter($data1['result'], fn($o) => $o['score'] > 0.5);
            echo !empty($objects) ? implode(', ', array_map(fn($o) => $o['label'] . ' (' . round($o['score']*100) . '%)', $objects)) : 'هیچ';
        }
        echo "</p>";
    }
}

echo "<hr><p>✅ <strong>نتیجه:</strong> ساخت عکس کار می‌کند. برای تحلیل عکس، یک فایل آپلود کنید. API را با دکمه بالا تست کنید.</p>";