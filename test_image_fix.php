<?php
// test_image_save.php - تست دقیق ذخیره عکس
echo "<h2>🔍 تست دقیق ذخیره عکس</h2>";

require_once 'config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
$stmt->execute(['deepseek_api_key']);
$api_token = $stmt->fetchColumn();
$account_id = '66b43b4fe65858aebd524af96cd93d54';

// =============================================
// تست ۱: گرفتن عکس از Cloudflare
// =============================================
echo "<h3>۱. دریافت عکس از Cloudflare:</h3>";

$prompt = 'a beautiful sunset over mountains, photorealistic';
$data = json_encode([
    'prompt' => $prompt,
    'num_steps' => 4,
    'width' => 512,
    'height' => 512,
]);

$ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/black-forest-labs/flux-1-schnell");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => $data, CURLOPT_TIMEOUT => 60
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

echo "<p>HTTP: $http_code | Content-Type: $content_type | Size: " . strlen($response) . " bytes</p>";

// چک ۲۰ بایت اول
$first_20 = substr($response, 0, 20);
$hex_20 = bin2hex($first_20);
echo "<p>۲۰ بایت اول (hex): <code style='word-break:break-all;'>$hex_20</code></p>";

// تشخیص نوع محتوا
if (strpos($hex_20, '89504e47') === 0) {
    echo "<p style='color:green;font-size:18px;'>✅ این یک فایل PNG واقعی است!</p>";
    
    // ذخیره
    $image_name = 'sunset_test_' . time() . '.png';
    $image_path = __DIR__ . '/uploads/' . $image_name;
    
    echo "<h3>۲. ذخیره فایل:</h3>";
    echo "<p>مسیر: $image_path</p>";
    
    $written = file_put_contents($image_path, $response);
    echo "<p>بایت نوشته شده: $written</p>";
    echo "<p>حجم فایل: " . filesize($image_path) . " bytes</p>";
    
    // چک مجدد
    $check = file_get_contents($image_path, false, null, 0, 4);
    echo "<p>۴ بایت اول فایل ذخیره شده: " . bin2hex($check) . "</p>";
    
    if (bin2hex($check) === '89504e47') {
        echo "<p style='color:green;font-size:18px;'>✅ فایل سالم ذخیره شد!</p>";
        
        // نمایش
        $url = '/uploads/' . $image_name . '?t=' . time();
        echo "<h3>۳. نمایش:</h3>";
        echo "<img src='$url' width='300' style='border:3px solid green;border-radius:12px;'>";
        echo "<p><a href='$url' download>📥 دانلود</a></p>";
        echo "<p>اگر عکس رو اینجا می‌بینی، مشکل از api/image/edit.php هست که response رو درست پردازش نمی‌کنه.</p>";
    } else {
        echo "<p style='color:red;'>❌ فایل ذخیره شده خراب است!</p>";
    }
    
} elseif (strpos($response, '{') === 0) {
    echo "<p style='color:red;font-size:18px;'>❌ JSON برگشته - API خطا داده!</p>";
    $err = json_decode($response, true);
    echo "<pre style='background:#ffe6e6;padding:10px;'>" . htmlspecialchars(print_r($err, true)) . "</pre>";
    
    // تست با پرامپت دیگه
    echo "<h3>🔄 تست با پرامپت ساده‌تر:</h3>";
    $simple_prompts = [
        'a blue sky with white clouds',
        'a red flower in a garden',
        'a wooden table with a book',
        'a mountain landscape at sunrise',
    ];
    
    foreach ($simple_prompts as $sp) {
        $data2 = json_encode(['prompt' => $sp, 'num_steps' => 4, 'width' => 512, 'height' => 512]);
        $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/black-forest-labs/flux-1-schnell");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $data2, CURLOPT_TIMEOUT => 60
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $first4 = bin2hex(substr($res, 0, 4));
        $is_png = ($first4 === '89504e47');
        echo "<p>پرامپت: '$sp' → HTTP $code | PNG: " . ($is_png ? '✅' : '❌ (' . substr($res, 0, 100) . ')') . "</p>";
        
        if ($is_png) {
            $img = 'test_simple_' . time() . '.png';
            file_put_contents(__DIR__ . '/uploads/' . $img, $res);
            echo "<img src='/uploads/$img' width='150'>";
            break;
        }
    }
    
} else {
    echo "<p style='color:red;'>❌ فرمت نامشخص</p>";
    echo "<pre>" . htmlspecialchars(substr($response, 0, 200)) . "</pre>";
}

// =============================================
// نتیجه
// =============================================
echo "<hr><h2>📊 نتیجه:</h2>";
echo "<p>اگر عکس بالا رو می‌بینی → Cloudflare API سالم کار میکنه.</p>";
echo "<p>اگر JSON برگشته → پرامپت مشکل داره (NSFW یا نامعتبر).</p>";
echo "<p>حالا برو به <a href='/user/dashboard/v2/image.php'>صفحه ساخت عکس</a> و با پرامپت‌های ساده تست کن.</p>";
?>