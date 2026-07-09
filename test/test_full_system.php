<?php
// test_full_system.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><title>تست کامل سیستم</title>";
echo "<style>
    body { font-family: Tahoma; background: #1a1a2e; color: #eee; padding: 20px; }
    .box { background: #16213e; border-radius: 12px; padding: 20px; margin: 15px 0; border: 1px solid #2a2a4a; }
    .ok { color: #4caf50; } .err { color: #f44336; } .warn { color: #ff9800; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 8px 12px; border-bottom: 1px solid #333; text-align: right; }
    th { background: #2a2a4a; }
    pre { background: #0f0f1a; padding: 10px; border-radius: 8px; font-size: 11px; overflow-x: auto; }
    button { padding: 8px 16px; background: #6366f1; color: white; border: none; border-radius: 6px; cursor: pointer; font-family: Tahoma; }
</style></head><body>";

echo "<h1>🧪 تست کامل سیستم</h1>";

// =============================================
// ۱. فایل‌های ضروری
// =============================================
echo "<div class='box'><h2>📁 فایل‌های ضروری</h2><table>";
$files = [
    'config/constants.php', 'config/database.php', 'includes/functions.php',
    'includes/header.php', 'includes/navbar.php', 'includes/footer.php',
    'api/chat/send.php', 'api/chat/DeepSeekAPI.php', 'api/chat/CloudflareAPI.php',
    'api/image/edit.php', 'user/dashboard/v2/chat.php', 'user/dashboard/v2/image.php',
    'assets/css/style.css', 'assets/js/theme.js'
];
foreach ($files as $f) {
    $exists = file_exists($f);
    echo "<tr><td>$f</td><td class='" . ($exists ? 'ok' : 'err') . "'>" . ($exists ? '✅' : '❌') . "</td></tr>";
}
echo "</table></div>";

// =============================================
// ۲. تست require و دیتابیس
// =============================================
echo "<div class='box'><h2>⚙️ تست require و دیتابیس</h2>";
try {
    require_once 'config/constants.php';
    echo "<p class='ok'>✅ constants.php</p>";
    require_once 'config/database.php';
    echo "<p class='ok'>✅ database.php</p>";
    require_once 'includes/functions.php';
    echo "<p class='ok'>✅ functions.php</p>";
    
    $db = new Database();
    $conn = $db->getConnection();
    echo "<p class='ok'>✅ اتصال دیتابیس</p>";
    
    // تست جداول
    $tables = ['users', 'conversations', 'messages', 'tasks', 'settings', 'activity_logs'];
    foreach ($tables as $t) {
        try {
            $conn->query("SELECT 1 FROM $t LIMIT 1");
            echo "<p class='ok'>✅ جدول $t</p>";
        } catch (Exception $e) {
            echo "<p class='err'>❌ جدول $t</p>";
        }
    }
    
    // تست کاربر
    $stmt = $conn->query("SELECT COUNT(*) FROM users");
    echo "<p class='ok'>✅ تعداد کاربران: " . $stmt->fetchColumn() . "</p>";
    
} catch (Throwable $e) {
    echo "<p class='err'>❌ خطا: " . $e->getMessage() . " (خط {$e->getLine()})</p>";
}
echo "</div>";

// =============================================
// ۳. تست APIهای Cloudflare
// =============================================
echo "<div class='box'><h2>🤖 تست APIهای Cloudflare</h2>";

$account_id = '66b43b4fe65858aebd524af96cd93d54';
$stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
$stmt->execute(['deepseek_api_key']);
$api_token = $stmt->fetch()['setting_value'] ?? '';

// تست چت
echo "<h3>💬 چت:</h3>";
$url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/meta/llama-4-scout-17b-16e-instruct";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['messages' => [['role' => 'user', 'content' => 'Say hi']], 'max_tokens' => 10]),
    CURLOPT_TIMEOUT => 15
]);
$res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
echo "<p>HTTP $code - " . ($code === 200 ? "<span class='ok'>✅ کار میکنه</span>" : "<span class='err'>❌ خطا</span>") . "</p>";

// تست ساخت عکس
echo "<h3>🎨 ساخت عکس:</h3>";
$url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/black-forest-labs/flux-1-schnell";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['prompt' => 'test', 'num_steps' => 1, 'width' => 128, 'height' => 128]),
    CURLOPT_TIMEOUT => 30
]);
$res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
echo "<p>HTTP $code - " . ($code === 200 ? "<span class='ok'>✅ کار میکنه</span>" : "<span class='err'>❌ خطا</span>") . "</p>";

// تست تحلیل عکس
echo "<h3>🔍 تحلیل عکس:</h3>";
$url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/facebook/detr-resnet-50";
$test_img = file_get_contents('uploads/test_flux_1780958805.png'); // یه عکس موجود
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/octet-stream'],
    CURLOPT_POSTFIELDS => $test_img ?: '', CURLOPT_TIMEOUT => 15
]);
$res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
echo "<p>HTTP $code - " . ($code === 200 ? "<span class='ok'>✅ کار میکنه</span>" : "<span class='warn'>⚠️ کد $code</span>") . "</p>";

echo "</div>";

// =============================================
// ۴. تست سشن و کوکی
// =============================================
echo "<div class='box'><h2>🍪 تست سشن و کوکی</h2>";
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'تست';
echo "<p class='ok'>✅ سشن ست شد (user_id: 1)</p>";

setcookie('golestan_user', '1', time() + 60, '/');
setcookie('golestan_token', md5('1golestan_salt_2024'), time() + 60, '/');
echo "<p class='ok'>✅ کوکی ست شد</p>";

echo "<p>isLoggedIn(): " . (isLoggedIn() ? "<span class='ok'>✅ true</span>" : "<span class='err'>❌ false</span>") . "</p>";
echo "</div>";

// =============================================
// ۵. لاگ خطاها
// =============================================
echo "<div class='box'><h2>📋 آخرین خطاها</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $lines = file($error_log);
    $lines = array_slice($lines, -10);
    echo "<pre>";
    foreach ($lines as $line) {
        if (strpos($line, 'Fatal') !== false || strpos($line, 'syntax') !== false) {
            echo "<span class='err'>" . htmlspecialchars($line) . "</span>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p>error_log پیدا نشد</p>";
}
echo "</div>";

// =============================================
// جمع‌بندی
// =============================================
echo "<div class='box'><h2>📊 جمع‌بندی</h2>";
echo "<p>همه تست‌ها انجام شد. موارد ❌ را بررسی کنید.</p>";
echo "<p><a href='/' style='color:#6366f1;'>🏠 صفحه اصلی</a> | <a href='/user/dashboard/v2/chat.php' style='color:#6366f1;'>💬 چت</a> | <a href='/user/dashboard/v2/image.php' style='color:#6366f1;'>🎨 ساخت عکس</a></p>";
echo "</div>";

echo "</body></html>";
?>