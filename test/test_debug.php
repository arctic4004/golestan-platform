<?php
// test_debug.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 عیب‌یابی سریع</h2>";

$files = [
    'config/constants.php',
    'config/database.php',
    'includes/functions.php',
    'includes/header.php',
    'includes/navbar.php',
    'includes/footer.php',
    'assets/css/style.css',
];

echo "<h3>📁 فایل‌ها:</h3>";
foreach ($files as $f) {
    echo file_exists($f) ? "✅ $f<br>" : "❌ $f پیدا نشد<br>";
}

// تست require
echo "<h3>⚙️ تست require:</h3>";
try {
    require_once 'config/constants.php';
    echo "✅ constants.php<br>";
    require_once 'config/database.php';
    echo "✅ database.php<br>";
    require_once 'includes/functions.php';
    echo "✅ functions.php<br>";
    
    // تست دیتابیس
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ دیتابیس وصل شد<br>";
    
    // ست کردن سشن تستی
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['full_name'] = 'تست';
    $_SESSION['phone'] = '09177418286';
    $_SESSION['credits'] = 1000;
    $_SESSION['is_admin'] = true;
    
    // تست include header (بدون خروجی)
    ob_start();
    require_once 'includes/header.php';
    $header_output = ob_get_clean();
    
    echo "✅ header.php اجرا شد (طول خروجی: " . strlen($header_output) . " کاراکتر)<br>";
    
    // چک کلیدهای مهم در header
    if (strpos($header_output, 'nav-menu') !== false) {
        echo "✅ navbar در header هست<br>";
    } else {
        echo "❌ navbar در header نیست!<br>";
    }
    
    if (strpos($header_output, 'style.css') !== false) {
        echo "✅ style.css لینک شده<br>";
    } else {
        echo "❌ style.css لینک نشده!<br>";
    }
    
} catch (Throwable $e) {
    echo "❌ خطا: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}

// لاگ‌های خطا
echo "<h3>📋 آخرین خطاها:</h3>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $lines = file($error_log);
    $lines = array_slice($lines, -15);
    echo "<pre>";
    foreach ($lines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<p>error_log پیدا نشد</p>";
}

// تست مستقیم index.php
echo "<h3>🧪 تست index.php:</h3>";
echo "<a href='/test_index.php' style='padding:10px;background:#6366f1;color:white;border-radius:8px;text-decoration:none;'>تست index.php</a>";
?>

<?php
// test_index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 تست مستقیم index.php</h2>";

try {
    ob_start();
    include 'index.php';
    $output = ob_get_clean();
    
    if (empty(trim($output))) {
        echo "❌ صفحه کاملاً خالی!<br>";
    } else {
        echo "✅ خروجی: " . strlen($output) . " کاراکتر<br>";
        echo "شامل nav-menu: " . (strpos($output, 'nav-menu') !== false ? '✅' : '❌') . "<br>";
        echo "شامل style.css: " . (strpos($output, 'style.css') !== false ? '✅' : '❌') . "<br>";
    }
} catch (Throwable $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}
?>