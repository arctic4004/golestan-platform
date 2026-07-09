<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 تست API تصویر</h2>";

// چک فایل
echo "<h3>۱. چک فایل API:</h3>";
$api_file = __DIR__ . '/api/image/edit.php';
echo file_exists($api_file) ? "✅ edit.php هست<br>" : "❌ edit.php نیست!<br>";

// چک توکن
echo "<h3>۲. چک توکن:</h3>";
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("SELECT setting_key, LEFT(setting_value, 20) as v FROM settings WHERE setting_key LIKE '%api%' OR setting_key LIKE '%token%' OR setting_key LIKE '%key%'");
while ($row = $stmt->fetch()) {
    echo "🔑 {$row['setting_key']}: {$row['v']}...<br>";
}

// تست مستقیم API
echo "<h3>۳. تست HuggingFace API:</h3>";
$token = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'huggingface_token'")->fetchColumn();

if ($token) {
    $ch = curl_init('https://api-inference.huggingface.co/models/stabilityai/stable-diffusion-xl-base-1.0');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['inputs' => 'a cat']),
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) echo "❌ Curl error: $error<br>";
    else echo "HTTP: $http | Size: " . strlen($response) . " bytes | Type: " . substr($response, 0, 3) . "<br>";
    
    if ($http === 200 && strlen($response) > 1000) {
        file_put_contents(__DIR__ . '/uploads/test_api.png', $response);
        echo "✅ عکس ذخیره شد: <img src='/uploads/test_api.png' style='max-width:300px;border-radius:8px;margin-top:10px'><br>";
    }
} else {
    echo "❌ توکن HuggingFace پیدا نشد<br>";
}

// تست Cloudflare
echo "<h3>۴. تست Cloudflare AI:</h3>";
$cf_token = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'deepseek_api_key'")->fetchColumn();
if ($cf_token) {
    $ch = curl_init('https://api.cloudflare.com/client/v4/accounts/66b43b4fe65858aebd524af96cd93d54/ai/run/@cf/stabilityai/stable-diffusion-xl-base-1.0');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $cf_token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['prompt' => 'a cat']),
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) echo "❌ Curl error: $error<br>";
    else echo "HTTP: $http | Response: " . substr($response, 0, 200) . "<br>";
} else {
    echo "❌ توکن Cloudflare پیدا نشد<br>";
}
?>