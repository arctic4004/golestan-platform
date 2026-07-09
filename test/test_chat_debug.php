<?php
// test_chat_debug.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 عیب‌یابی صفحه چت</h2>";

// ۱. چک فایل‌ها
echo "<h3>۱. فایل‌های ضروری:</h3>";
$files = [
    'config/constants.php',
    'config/database.php', 
    'includes/functions.php',
    'includes/header.php',
    'includes/footer.php',
    'api/chat/DeepSeekAPI.php',
    'user/dashboard/v2/chat.php',
    'user/dashboard/v2/assets/css/dashboard.css',
    'assets/css/style.css'
];
foreach ($files as $f) {
    echo file_exists($f) ? "✅ $f<br>" : "❌ $f وجود نداره<br>";
}

// ۲. تست دیتابیس و سشن
echo "<h3>۲. تست سشن و دیتابیس:</h3>";
session_start();
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ دیتابیس وصل شد<br>";
    
    // تست کاربر
    $_SESSION['user_id'] = 1;
    $_SESSION['full_name'] = 'تست';
    $_SESSION['phone'] = '09177418286';
    $_SESSION['credits'] = 1000;
    $_SESSION['is_admin'] = true;
    $_SESSION['theme'] = 'light';
    
    echo "✅ سشن ست شد (user_id: 1)<br>";
    
    // تست کوئری
    $stmt = $conn->query("SELECT COUNT(*) FROM conversations WHERE user_id = 1");
    echo "✅ تعداد چت‌ها: " . $stmt->fetchColumn() . "<br>";
    
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage() . "<br>";
}

// ۳. تست مستقیم chat.php
echo "<h3>۳. تست اجرای مستقیم chat.php:</h3>";
echo "<p>با msg=test...</p>";

$_GET['msg'] = 'سلام تست';
$_GET['conversation'] = null;

try {
    ob_start();
    include 'user/dashboard/v2/chat.php';
    $output = ob_get_clean();
    
    if (empty(trim($output))) {
        echo "<p style='color:red;'>❌ خروجی خالیه! یه fatal error داریم</p>";
    } else {
        echo "<p style='color:green;'>✅ خروجی تولید شد (" . strlen($output) . " کاراکتر)</p>";
        echo "<p>شامل 'chat-messages': " . (strpos($output, 'chat-messages') !== false ? '✅' : '❌') . "</p>";
        echo "<p>شامل 'dashboard.css': " . (strpos($output, 'dashboard.css') !== false ? '✅' : '❌') . "</p>";
    }
} catch (Throwable $e) {
    echo "<p style='color:red;'>❌ Exception: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}

// ۴. چک error log
echo "<h3>۴. آخرین خطاهای PHP:</h3>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $lines = file($error_log);
    $lines = array_slice($lines, -10);
    echo "<pre style='background:#ffe6e6;padding:10px;font-size:11px;'>";
    foreach ($lines as $line) {
        if (strpos($line, 'chat.php') !== false || strpos($line, 'DeepSeekAPI') !== false) {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p>error_log پیدا نشد. مسیر: " . ($error_log ?: 'تنظیم نشده') . "</p>";
}

// ۵. لینک تست
echo "<h3>۵. لینک تست مستقیم:</h3>";
echo "<a href='/user/dashboard/v2/chat.php?msg=سلام' style='display:inline-block;padding:10px 20px;background:var(--primary,#10a37f);color:white;border-radius:8px;text-decoration:none;'>تست چت با msg=سلام</a>";
?>