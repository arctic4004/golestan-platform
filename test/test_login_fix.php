<?php
// test_login_fix.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 تست کامل سیستم لاگین</h1>";

// ۱. تست سشن
echo "<h3>مرحله ۱: وضعیت سشن</h3>";
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Save Path: " . session_save_path() . "</p>";

// ۲. تست فایل‌ها
echo "<h3>مرحله ۲: فایل‌های ضروری</h3>";
$files = [
    'config/constants.php',
    'config/database.php', 
    'includes/functions.php',
    'includes/auth.php',
    'login.php'
];
foreach ($files as $f) {
    if (file_exists($f)) {
        echo "<p>✅ $f</p>";
    } else {
        echo "<p style='color:red'>❌ $f پیدا نشد!</p>";
    }
}

// ۳. تست دیتابیس
echo "<h3>مرحله ۳: تست دیتابیس</h3>";
require_once 'config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color:green'>✅ اتصال به دیتابیس برقرار شد</p>";
    
    // چک کاربر ادمین
    $stmt = $db->prepare("SELECT id, phone, full_name, is_admin, is_active FROM users WHERE phone = ?");
    $stmt->execute(['09177418286']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p style='color:green'>✅ کاربر ادمین پیدا شد</p>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
    } else {
        echo "<p style='color:red'>❌ کاربر 09177418286 پیدا نشد!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ خطای دیتابیس: " . $e->getMessage() . "</p>";
}

// ۴. تست لاگین
echo "<h3>مرحله ۴: تست لاگین سریع</h3>";
echo "<form method='POST' style='background:#f5f5f5;padding:20px;border-radius:10px;'>";
echo "<input type='text' name='phone' value='09177418286' placeholder='موبایل' style='padding:10px;width:100%;margin:5px 0;'>";
echo "<input type='password' name='password' placeholder='رمز عبور' style='padding:10px;width:100%;margin:5px 0;'>";
echo "<button type='submit' name='do_login' style='padding:10px 20px;background:blue;color:white;border:none;border-radius:5px;cursor:pointer;'>ورود تستی</button>";
echo "</form>";

if (isset($_POST['do_login'])) {
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    
    require_once 'includes/functions.php';
    
    $stmt = $db->prepare("SELECT * FROM users WHERE phone = ? AND is_active = 1");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p>🔍 کاربر پیدا شد: {$user['full_name']}</p>";
        echo "<p>🔍 Hash: " . substr($user['password_hash'], 0, 20) . "...</p>";
        
        if (password_verify($password, $user['password_hash'])) {
            echo "<p style='color:green;font-size:18px;'>✅ رمز درست بود!</p>";
            
            // ست کردن سشن
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['credits'] = $user['credits'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            
            echo "<p>✅ سشن ست شد!</p>";
            echo "<p>User ID in session: " . $_SESSION['user_id'] . "</p>";
            
            echo "<p><a href='/user/dashboard/v2/' style='font-size:18px;color:green;'>🚀 برو به داشبورد</a></p>";
            echo "<p><a href='/user/dashboard/v2/chat.php' style='font-size:18px;color:green;'>💬 برو به چت</a></p>";
        } else {
            echo "<p style='color:red;font-size:18px;'>❌ رمز اشتباه است!</p>";
            
            // فرم تغییر رمز
            echo "<h4>تغییر رمز مستقیم:</h4>";
            echo "<form method='POST'>";
            echo "<input type='hidden' name='phone' value='09177418286'>";
            echo "<input type='text' name='new_pass' value='admin123' placeholder='رمز جدید'>";
            echo "<button type='submit' name='change_pass'>تغییر رمز به admin123</button>";
            echo "</form>";
        }
    } else {
        echo "<p style='color:red;'>❌ کاربر پیدا نشد یا غیرفعال است!</p>";
    }
}

// تغییر رمز
if (isset($_POST['change_pass'])) {
    $new_hash = password_hash($_POST['new_pass'], PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE phone = ?");
    $stmt->execute([$new_hash, '09177418286']);
    echo "<p style='color:green;font-size:18px;'>✅ رمز به '{$_POST['new_pass']}' تغییر کرد!</p>";
    echo "<p>حالا میتونی با این رمز لاگین کنی</p>";
}
?>