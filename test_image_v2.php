<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 تست دقیق API تصویر</h2>";

// ۱. توکن
require_once 'config/database.php';
$db = (new Database())->getConnection();
$token = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'deepseek_api_key'")->fetchColumn();
echo "توکن: " . ($token ? "✅ " . substr($token,0,15) . "..." : "❌ نیست") . "<br><br>";

// ۲. تست مدل‌های مختلف
$account_id = '66b43b4fe65858aebd524af96cd93d54';
$models = [
    '@cf/stabilityai/stable-diffusion-xl-base-1.0' => 'SDXL',
    '@cf/bytedance/stable-diffusion-xl-lightning' => 'SD Lightning', 
    '@cf/lykon/dreamshaper-8-lcm' => 'DreamShaper',
];

foreach ($models as $model => $name) {
    echo "<h3>🎨 تست $name:</h3>";
    
    $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode(['prompt' => 'a cute cat']),
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ Curl error: $error<br>";
    } elseif ($http === 200) {
        $size = strlen($response);
        $type = substr($response, 0, 3);
        echo "✅ HTTP 200 | Size: $size bytes | Type: $type<br>";
        
        if ($size > 1000) {
            $filename = "test_" . strtolower(str_replace(['@','/'], '_', $model)) . ".png";
            file_put_contents(__DIR__ . '/uploads/' . $filename, $response);
            echo "✅ ذخیره شد: <img src='/uploads/$filename' style='max-width:200px;border-radius:8px;margin:5px'><br>";
        } else {
            echo "⚠️ حجم کم: " . substr($response, 0, 200) . "<br>";
        }
    } else {
        $err = json_decode($response, true);
        echo "❌ HTTP $http: " . ($err['errors'][0]['message'] ?? $response) . "<br>";
    }
    echo "<hr>";
}

// ۳. چک فایل edit.php
echo "<h3>📁 چک api/image/edit.php:</h3>";
$edit = file_get_contents('api/image/edit.php');
echo "حجم فایل: " . strlen($edit) . " bytes<br>";

// ببین از کدوم API استفاده می‌کنه
if (strpos($edit, 'CloudflareAPI') !== false) echo "✅ از CloudflareAPI استفاده می‌کنه<br>";
if (strpos($edit, 'HuggingFace') !== false) echo "⚠️ از HuggingFace هم استفاده می‌کنه<br>";
if (strpos($edit, 'cloudflare') !== false) echo "✅ رفرنس به cloudflare داره<br>";
?>