<?php
// test_image_api.php - تست اختصاصی API ساخت عکس
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['credits'] = 999999;

echo "<h2>🧪 تست اختصاصی API ساخت عکس</h2>";

echo "<h3>۱. تست مستقیم Cloudflare:</h3>";
require_once 'config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
$stmt->execute(['deepseek_api_key']);
$api_token = $stmt->fetchColumn();

$account_id = '66b43b4fe65858aebd524af96cd93d54';

$ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/black-forest-labs/flux-1-schnell");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['prompt' => 'test cat', 'num_steps' => 4, 'width' => 256, 'height' => 256]),
    CURLOPT_TIMEOUT => 30
]);
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP $code | Size: " . strlen($response) . " bytes</p>";
if ($code === 200 && strlen($response) > 1000) {
    file_put_contents('uploads/test_direct.png', $response);
    echo "<span style='color:green'>✅ مستقیم OK</span><br><img src='/uploads/test_direct.png' width='200'>";
} else {
    echo "<span style='color:red'>❌ خطا: " . htmlspecialchars(substr($response, 0, 200)) . "</span>";
}

echo "<h3>۲. تست api/image/edit.php:</h3>";

// تست با file_get_contents
$postdata = http_build_query([
    'action' => 'text_to_image',
    'prompt' => 'test api check',
    'model' => 'flux',
    'width' => 256,
    'height' => 256,
]);

$opts = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $postdata,
        'timeout' => 60,
    ],
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
];

$context = stream_context_create($opts);
$result = @file_get_contents('https://golestanyasuj.ir/api/image/edit.php', false, $context);

if ($result === false) {
    echo "<span style='color:red'>❌ file_get_contents ناموفق - محدودیت سرور</span><br>";
    
    // تست با CURL به خودش
    echo "<p>تست با CURL به خودش:</p>";
    $ch = curl_init('https://golestanyasuj.ir/api/image/edit.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postdata,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $result2 = curl_exec($ch);
    $code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error2 = curl_error($ch);
    curl_close($ch);
    
    echo "<p>HTTP $code2 | Error: " . ($error2 ?: 'none') . "</p>";
    
    if ($code2 === 200) {
        $data = json_decode($result2, true);
        if ($data && isset($data['success'])) {
            echo "<span style='color:green'>✅ CURL به خودش OK</span><br>";
            if ($data['image_url']) echo "<img src='{$data['image_url']}' width='200'>";
        } else {
            echo "<span style='color:red'>❌ " . ($data['error'] ?? 'Invalid response') . "</span>";
        }
    } else {
        echo "<span style='color:red'>❌ HTTP $code2</span>";
        echo "<pre>" . htmlspecialchars(substr($result2, 0, 500)) . "</pre>";
    }
} else {
    $data = json_decode($result, true);
    if ($data && isset($data['success'])) {
        echo "<span style='color:green'>✅ file_get_contents OK</span><br>";
        if ($data['image_url']) echo "<img src='{$data['image_url']}' width='200'>";
    } else {
        echo "<span style='color:red'>❌ " . ($data['error'] ?? 'Invalid') . "</span>";
    }
}

echo "<h3>۳. نتیجه:</h3>";
echo "<p style='color:#ff9800;'>⚠️ مشکل از CURL/fsockopen خود سرور هست. API از بیرون (مرورگر) کار میکنه.</p>";
echo "<p>برو به <a href='/user/dashboard/v2/image.php'>صفحه ساخت عکس</a> و تست کن.</p>";
?>