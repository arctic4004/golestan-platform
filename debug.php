<?php
// debug.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 عیب‌یابی سایت</h2>";

// 1. چک فایل‌های ضروری
$files = [
    'config/constants.php',
    'config/database.php',
    'includes/functions.php',
    'includes/auth.php',
    'includes/header.php',
    'includes/footer.php',
    'includes/navbar.php',
    'assets/css/style.css',
    'assets/css/theme-gold.css',
    'assets/css/theme-emerald.css',
    'assets/css/theme-sapphire.css',
    'index.php',
    'login.php',
    'signup.php'
];

echo "<h3>📁 فایل‌های ضروری:</h3>";
echo "<ul>";
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<li style='color:green'>✅ $file</li>";
    } else {
        echo "<li style='color:red'>❌ $file - وجود نداره!</li>";
    }
}
echo "</ul>";

// 2. تست بارگذاری constants
echo "<h3>⚙️ تست constants.php:</h3>";
try {
    require_once 'config/constants.php';
    echo "<p style='color:green'>✅ SITE_NAME: " . SITE_NAME . "</p>";
    echo "<p style='color:green'>✅ SITE_URL: " . SITE_URL . "</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ خطا: " . $e->getMessage() . "</p>";
}

// 3. تست functions.php
echo "<h3>⚙️ تست functions.php:</h3>";
try {
    require_once 'includes/functions.php';
    echo "<p style='color:green'>✅ functions.php بارگذاری شد</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ خطا: " . $e->getMessage() . "</p>";
}

// 4. تست header.php
echo "<h3>⚙️ تست header.php (بدون include کامل):</h3>";
try {
    // فقط بررسی می‌کنیم که خطای parse نده
    $content = file_get_contents('includes/header.php');
    if (strpos($content, 'session_start()') !== false) {
        echo "<p style='color:green'>✅ header.php قابل خواندن است</p>";
    }
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ خطا: " . $e->getMessage() . "</p>";
}

// 5. تست دیتابیس
echo "<h3>🗄️ تست دیتابیس:</h3>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color:green'>✅ اتصال به دیتابیس برقرار شد</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ خطا: " . $e->getMessage() . "</p>";
}

// 6. نمایش آخرین خطاهای PHP
echo "<h3>📋 خطاهای ثبت شده:</h3>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $errors = file($error_log);
    $errors = array_slice($errors, -20); // ۲۰ خط آخر
    echo "<pre style='background:#ffe6e6; padding:10px;'>";
    foreach ($errors as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<p>فایل error_log پیدا نشد.</p>";
}
?>