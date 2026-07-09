<?php
// test_comprehensive.php
// پردازش اولیه - قبل از هر خروجی
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$results = [];

// ===================== تست ۱: فایل‌ها =====================
$files = [
    'config/constants.php', 'config/database.php', 'includes/functions.php',
    'includes/header.php', 'includes/navbar.php', 'includes/footer.php',
    'api/chat/send.php', 'api/chat/DeepSeekAPI.php', 'api/chat/CloudflareAPI.php',
    'api/image/edit.php', 'user/dashboard/v2/chat.php', 'user/dashboard/v2/image.php',
    'user/dashboard/v2/tasks.php', 'user/dashboard/v2/history.php',
    'assets/css/style.css', 'assets/js/theme.js'
];
$results['files'] = [];
foreach ($files as $f) {
    $results['files'][$f] = file_exists($f);
}

// ===================== تست ۲: دیتابیس =====================
try {
    $db = new Database();
    $conn = $db->getConnection();
    $results['database'] = 'OK';
    
    $tables = ['users', 'conversations', 'messages', 'tasks', 'settings', 'activity_logs'];
    $results['tables'] = [];
    foreach ($tables as $t) {
        try {
            $conn->query("SELECT 1 FROM $t LIMIT 1");
            $results['tables'][$t] = true;
        } catch (Exception $e) {
            $results['tables'][$t] = false;
        }
    }
    
    $stmt = $conn->query("SELECT COUNT(*) FROM users");
    $results['user_count'] = $stmt->fetchColumn();
    
    // گرفتن API token
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute(['deepseek_api_key']);
    $api_token = $stmt->fetch()['setting_value'] ?? '';
    $results['api_token'] = !empty($api_token);
    
} catch (Exception $e) {
    $results['database'] = 'ERROR: ' . $e->getMessage();
}

// ===================== تست ۳: APIهای Cloudflare =====================
$account_id = '66b43b4fe65858aebd524af96cd93d54';

if (!empty($api_token)) {
    // تست چت
    $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/meta/llama-4-scout-17b-16e-instruct";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['messages' => [['role' => 'user', 'content' => 'Say hi']], 'max_tokens' => 10]),
        CURLOPT_TIMEOUT => 15
    ]);
    curl_exec($ch);
    $results['chat_api'] = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    
    // تست ساخت عکس
    $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/black-forest-labs/flux-1-schnell";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['prompt' => 'test', 'num_steps' => 1, 'width' => 128, 'height' => 128]),
        CURLOPT_TIMEOUT => 30
    ]);
    curl_exec($ch);
    $results['image_api'] = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    
    // تست تحلیل عکس (با یک عکس واقعی از پوشه uploads)
    $test_images = glob('uploads/gen_*.png');
    if (!empty($test_images)) {
        $test_img = file_get_contents($test_images[0]);
        $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/facebook/detr-resnet-50";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/octet-stream'],
            CURLOPT_POSTFIELDS => $test_img, CURLOPT_TIMEOUT => 15
        ]);
        curl_exec($ch);
        $results['analyze_api'] = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
    } else {
        $results['analyze_api'] = 'SKIP (no test image)';
    }
} else {
    $results['chat_api'] = false;
    $results['image_api'] = false;
    $results['analyze_api'] = false;
}

// ===================== تست ۴: سشن و لاگین =====================
$_SESSION['test_user_id'] = 1;
$results['session'] = isset($_SESSION['test_user_id']);

setcookie('test_cookie', '1', time() + 60, '/');
$results['cookie'] = isset($_COOKIE['test_cookie']);

$results['is_logged_in'] = isLoggedIn();

// ===================== تست ۵: پوشه uploads =====================
$results['uploads_writable'] = is_writable('uploads');
$results['uploads_count'] = count(glob('uploads/*'));

// ===================== پایان پردازش =====================
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تست جامع سیستم</title>
    <style>
        body { font-family: Tahoma; background: #1a1a2e; color: #eee; padding: 20px; }
        .box { background: #16213e; border-radius: 12px; padding: 20px; margin: 15px 0; border: 1px solid #2a2a4a; }
        .ok { color: #4caf50; } .err { color: #f44336; } .warn { color: #ff9800; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 12px; border-bottom: 1px solid #333; text-align: right; }
        th { background: #2a2a4a; }
        .summary { display: flex; gap: 20px; flex-wrap: wrap; }
        .stat { background: #2a2a4a; padding: 15px; border-radius: 8px; text-align: center; min-width: 100px; }
        .stat .num { font-size: 2rem; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🧪 تست جامع سیستم</h1>
    
    <!-- خلاصه -->
    <div class="summary">
        <div class="stat"><div class="num"><?php echo count(array_filter($results['files'])); ?>/<?php echo count($results['files']); ?></div>فایل‌ها</div>
        <div class="stat"><div class="num"><?php echo count(array_filter($results['tables'] ?? [])); ?>/6</div>جداول</div>
        <div class="stat"><div class="num"><?php echo $results['user_count']; ?></div>کاربران</div>
        <div class="stat"><div class="num"><?php echo $results['uploads_count']; ?></div>فایل در uploads</div>
    </div>
    
    <!-- فایل‌ها -->
    <div class="box">
        <h2>📁 فایل‌ها</h2>
        <table>
            <?php foreach ($results['files'] as $file => $exists): ?>
            <tr><td><?php echo $file; ?></td><td class="<?php echo $exists ? 'ok' : 'err'; ?>"><?php echo $exists ? '✅' : '❌'; ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <!-- دیتابیس -->
    <div class="box">
        <h2>🗄️ دیتابیس</h2>
        <p>وضعیت: <span class="<?php echo $results['database'] === 'OK' ? 'ok' : 'err'; ?>"><?php echo $results['database']; ?></span></p>
        <p>API Token: <span class="<?php echo $results['api_token'] ? 'ok' : 'err'; ?>"><?php echo $results['api_token'] ? '✅ تنظیم شده' : '❌ خالی'; ?></span></p>
        <table>
            <?php foreach ($results['tables'] as $table => $ok): ?>
            <tr><td><?php echo $table; ?></td><td class="<?php echo $ok ? 'ok' : 'err'; ?>"><?php echo $ok ? '✅' : '❌'; ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <!-- APIها -->
    <div class="box">
        <h2>🤖 APIهای Cloudflare</h2>
        <table>
            <tr><td>💬 چت (Llama 4)</td><td class="<?php echo $results['chat_api'] ? 'ok' : 'err'; ?>"><?php echo $results['chat_api'] ? '✅' : '❌'; ?></td></tr>
            <tr><td>🎨 ساخت عکس (Flux)</td><td class="<?php echo $results['image_api'] ? 'ok' : 'err'; ?>"><?php echo $results['image_api'] ? '✅' : '❌'; ?></td></tr>
            <tr><td>🔍 تحلیل عکس (DETR)</td><td class="<?php echo $results['analyze_api'] === true ? 'ok' : 'warn'; ?>"><?php echo $results['analyze_api'] === true ? '✅' : (is_string($results['analyze_api']) ? $results['analyze_api'] : '❌'); ?></td></tr>
        </table>
    </div>
    
    <!-- سشن و کوکی -->
    <div class="box">
        <h2>🍪 سشن و کوکی</h2>
        <table>
            <tr><td>سشن</td><td class="<?php echo $results['session'] ? 'ok' : 'err'; ?>"><?php echo $results['session'] ? '✅' : '❌'; ?></td></tr>
            <tr><td>کوکی</td><td class="<?php echo $results['cookie'] ? 'ok' : 'err'; ?>"><?php echo $results['cookie'] ? '✅' : '❌'; ?></td></tr>
            <tr><td>isLoggedIn()</td><td class="<?php echo $results['is_logged_in'] ? 'ok' : 'err'; ?>"><?php echo $results['is_logged_in'] ? '✅ true' : '❌ false'; ?></td></tr>
        </table>
    </div>
    
    <!-- پوشه uploads -->
    <div class="box">
        <h2>📂 پوشه uploads</h2>
        <p>قابل نوشتن: <span class="<?php echo $results['uploads_writable'] ? 'ok' : 'err'; ?>"><?php echo $results['uploads_writable'] ? '✅' : '❌'; ?></span></p>
        <p>تعداد فایل‌ها: <?php echo $results['uploads_count']; ?></p>
    </div>
    
    <!-- لینک‌ها -->
    <div class="box">
        <h2>🔗 لینک‌های سریع</h2>
        <a href="/" style="color:#6366f1;">🏠 صفحه اصلی</a> |
        <a href="/user/dashboard/v2/chat.php" style="color:#6366f1;">💬 چت</a> |
        <a href="/user/dashboard/v2/image.php" style="color:#6366f1;">🎨 ساخت عکس</a> |
        <a href="/user/dashboard/v2/tasks.php" style="color:#6366f1;">📋 تسک‌ها</a> |
        <a href="/admin/" style="color:#6366f1;">🛡️ مدیریت</a>
    </div>
</body>
</html>