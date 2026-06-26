<?php
// test_new_account.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 تست با Account ID جدید</h2>";

$account_id = '66b43b4fe65858aebd524af96cd93d54';
$api_token = 'cfat_m9nUNSo3ePotyoRLfTIKFywGsvp6LDfThhQTLusZc106a711';

// تست چت
echo "<h3>💬 تست چت:</h3>";
$url1 = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/meta/llama-4-scout-17b-16e-instruct";

$data1 = [
    'messages' => [
        ['role' => 'user', 'content' => 'Say hello in Persian in 3 words']
    ],
    'max_tokens' => 50
];

$ch = curl_init($url1);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($data1), CURLOPT_TIMEOUT => 30
]);
$res1 = curl_exec($ch);
$code1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code1 === 200) {
    $result = json_decode($res1, true);
    echo "<p style='color:green;'>✅ چت: " . ($result['result']['response'] ?? 'OK') . "</p>";
} else {
    echo "<p style='color:red;'>❌ خطا: HTTP $code1</p>";
    echo "<pre>" . substr($res1, 0, 200) . "</pre>";
}

// تست ساخت عکس
echo "<h3>🎨 تست ساخت عکس:</h3>";
$url2 = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/stabilityai/stable-diffusion-xl-base-1.0";

$data2 = ['prompt' => 'a cute cat, cartoon style', 'num_steps' => 20, 'width' => 256, 'height' => 256];

$ch = curl_init($url2);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($data2), CURLOPT_TIMEOUT => 60
]);
$res2 = curl_exec($ch);
$code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code2 === 200 && strlen($res2) > 1000) {
    file_put_contents(__DIR__ . '/uploads/test_new_account.png', $res2);
    echo "<p style='color:green;'>✅ عکس ساخته شد</p>";
    echo "<img src='/uploads/test_new_account.png' style='max-width:200px;'>";
} else {
    echo "<p style='color:red;'>❌ خطا: HTTP $code2</p>";
    echo "<pre>" . substr($res2, 0, 200) . "</pre>";
}
?>