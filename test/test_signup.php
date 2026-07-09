<?php
// test_auth.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// اول session رو شروع کن
session_start();

echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><title>تست سیستم احراز هویت</title>";
echo "<style>
    body { font-family: Tahoma; background: #1a1a2e; color: #eee; padding: 20px; }
    .box { background: #16213e; border-radius: 12px; padding: 20px; margin: 15px 0; border: 1px solid #2a2a4a; }
    .ok { color: #4caf50; } .err { color: #f44336; } .warn { color: #ff9800; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 8px 12px; border-bottom: 1px solid #333; text-align: right; }
    th { background: #2a2a4a; }
    button { padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 8px; cursor: pointer; margin: 5px; font-family: Tahoma; }
    input { padding: 8px; border-radius: 8px; border: 1px solid #555; background: #0f0f1a; color: #eee; margin: 5px; font-family: Tahoma; }
    pre { background: #0f0f1a; padding: 10px; border-radius: 8px; font-size: 11px; overflow-x: auto; }
    .success-box { background: #1b5e20; padding: 15px; border-radius: 8px; }
    .error-box { background: #4a0000; padding: 15px; border-radius: 8px; }
</style></head><body>";

echo "<h1>🧪 تست سیستم احراز هویت</h1>";

// =============================================
// ۱. چک فایل‌ها
// =============================================
echo "<div class='box'><h2>📁 فایل‌های ضروری</h2><table>";
$files = [
    'config/constants.php', 'config/database.php', 'includes/functions.php',
    'includes/header.php', 'includes/navbar.php', 'login.php', 'signup.php',
    'logout.php', 'forgot-password.php', 'reset-password.php',
    'oauth/google-login.php', 'oauth/google-callback.php', 'config/oauth_config.php'
];
foreach ($files as $f) {
    $exists = file_exists($f);
    echo "<tr><td>$f</td><td class='" . ($exists ? 'ok' : 'err') . "'>" . ($exists ? '✅' : '❌') . "</td></tr>";
}
echo "</table></div>";

// =============================================
// ۲. تست دیتابیس
// =============================================
echo "<div class='box'><h2>🗄️ تست دیتابیس</h2>";
require_once 'config/database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "<p class='ok'>✅ اتصال به دیتابیس برقرار شد</p>";
    
    // چک جدول users
    $stmt = $conn->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "<p class='ok'>✅ تعداد کاربران: {$count}</p>";
    
    // چک ستون‌های ضروری
    $cols = ['phone', 'email', 'password_hash', 'is_active', 'reset_token', 'reset_expires'];
    foreach ($cols as $col) {
        $has = $conn->query("SHOW COLUMNS FROM users LIKE '$col'")->fetch();
        echo "<p class='" . ($has ? 'ok' : 'err') . "'>" . ($has ? '✅' : '❌') . " ستون $col</p>";
    }
    
    // چک جدول oauth_users
    $has_oauth = $conn->query("SHOW TABLES LIKE 'oauth_users'")->fetch();
    echo "<p class='" . ($has_oauth ? 'ok' : 'warn') . "'>" . ($has_oauth ? '✅' : '⚠️') . " جدول oauth_users</p>";
    
} catch (Exception $e) {
    echo "<p class='err'>❌ خطا: " . $e->getMessage() . "</p>";
}
echo "</div>";

// =============================================
// ۳. تست OAuth Config
// =============================================
echo "<div class='box'><h2>🔑 تست OAuth Config</h2>";
if (file_exists('config/oauth_config.php')) {
    require_once 'config/oauth_config.php';
    $client_id = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';
    $secret = defined('GOOGLE_CLIENT_SECRET') ? GOOGLE_CLIENT_SECRET : '';
    $redirect = defined('GOOGLE_REDIRECT_URI') ? GOOGLE_REDIRECT_URI : '';
    
    echo "<p>Client ID: " . (strlen($client_id) > 10 ? "<span class='ok'>✅ تنظیم شده (" . substr($client_id, 0, 20) . "...)</span>" : "<span class='err'>❌ خالی یا نامعتبر</span>") . "</p>";
    echo "<p>Client Secret: " . (strlen($secret) > 10 ? "<span class='ok'>✅ تنظیم شده</span>" : "<span class='err'>❌ خالی یا نامعتبر</span>") . "</p>";
    echo "<p>Redirect URI: <code>" . htmlspecialchars($redirect) . "</code></p>";
} else {
    echo "<p class='err'>❌ فایل config/oauth_config.php وجود ندارد!</p>";
}
echo "</div>";

// =============================================
// ۴. تست لاگین با POST
// =============================================
echo "<div class='box'><h2>🔐 تست لاگین</h2>";

echo "<form method='POST'>";
echo "<input type='text' name='phone' value='09177418286' placeholder='موبایل' style='direction:ltr;'>";
echo "<input type='password' name='password' value='admin123' placeholder='رمز عبور'>";
echo "<button type='submit' name='test_login'>ورود تستی</button>";
echo "</form>";

if (isset($_POST['test_login'])) {
    require_once 'includes/functions.php';
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ? AND is_active = 1");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p class='ok'>✅ کاربر پیدا شد: {$user['full_name']}</p>";
        echo "<p>ID: {$user['id']} | Admin: " . ($user['is_admin'] ? 'بله' : 'خیر') . "</p>";
        
        if (password_verify($password, $user['password_hash'])) {
            echo "<div class='success-box'>";
            echo "<p class='ok'>✅ رمز عبور صحیح است!</p>";
            
            // تست سشن
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['credits'] = $user['credits'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            
            echo "<p>Session user_id: " . $_SESSION['user_id'] . "</p>";
            echo "<p>isLoggedIn(): " . (isLoggedIn() ? "<span class='ok'>✅ true</span>" : "<span class='err'>❌ false</span>") . "</p>";
            
            // تست کوکی
            $token = md5($user['id'] . 'golestan_salt_2024');
            setcookie('golestan_user', $user['id'], time() + 60, '/');
            setcookie('golestan_token', $token, time() + 60, '/');
            echo "<p class='ok'>✅ کوکی‌ها ست شدند</p>";
            
            echo "<p><a href='/user/dashboard/v2/'>🚀 رفتن به داشبورد</a></p>";
            echo "</div>";
        } else {
            echo "<div class='error-box'><p class='err'>❌ رمز عبور اشتباه است!</p></div>";
        }
    } else {
        echo "<div class='error-box'><p class='err'>❌ کاربر پیدا نشد!</p></div>";
    }
}
echo "</div>";

// =============================================
// ۵. تست ثبت‌نام
// =============================================
echo "<div class='box'><h2>📝 تست ثبت‌نام</h2>";

echo "<form method='POST'>";
echo "<input type='text' name='signup_name' placeholder='نام کامل'>";
echo "<input type='text' name='signup_phone' placeholder='موبایل (09xxxxxxxxx)'>";
echo "<input type='email' name='signup_email' placeholder='ایمیل (اختیاری)'>";
echo "<input type='password' name='signup_password' placeholder='رمز عبور'>";
echo "<button type='submit' name='test_signup'>ثبت‌نام تستی</button>";
echo "</form>";

if (isset($_POST['test_signup'])) {
    $name = $_POST['signup_name'] ?? '';
    $phone = $_POST['signup_phone'] ?? '';
    $email = $_POST['signup_email'] ?? '';
    $pass = $_POST['signup_password'] ?? '';
    
    $errors = [];
    if (empty($name)) $errors[] = 'نام الزامی است';
    if (!preg_match('/^09[0-9]{9}$/', $phone)) $errors[] = 'موبایل نامعتبر';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'ایمیل نامعتبر';
    if (strlen($pass) < 6) $errors[] = 'رمز عبور حداقل ۶ کاراکتر';
    
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            echo "<p class='err'>❌ این شماره قبلاً ثبت شده است</p>";
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (phone, email, full_name, password_hash, credits) VALUES (?, ?, ?, ?, 1000)");
            if ($stmt->execute([$phone, $email ?: null, $name, $hash])) {
                echo "<p class='ok'>✅ کاربر با موفقیت ثبت شد!</p>";
                echo "<p>موبایل: $phone | رمز: $pass</p>";
            } else {
                echo "<p class='err'>❌ خطا در ثبت‌نام</p>";
            }
        }
    } else {
        foreach ($errors as $e) echo "<p class='err'>❌ $e</p>";
    }
}
echo "</div>";

// =============================================
// ۶. تست بازیابی رمز
// =============================================
echo "<div class='box'><h2>🔑 تست بازیابی رمز</h2>";

echo "<form method='POST'>";
echo "<input type='email' name='reset_email' placeholder='ایمیل کاربر'>";
echo "<button type='submit' name='test_reset'>ارسال لینک بازیابی</button>";
echo "</form>";

if (isset($_POST['test_reset'])) {
    $email = $_POST['reset_email'] ?? '';
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
        $stmt->execute([$token, $user['id']]);
        
        $link = "https://golestanyasuj.ir/reset-password.php?token={$token}";
        echo "<p class='ok'>✅ لینک بازیابی برای {$user['full_name']} ساخته شد:</p>";
        echo "<p><a href='{$link}' style='color:#6366f1;word-break:break-all;'>{$link}</a></p>";
        echo "<p>روی لینک بالا کلیک کنید تا رمز جدید تنظیم کنید.</p>";
    } else {
        echo "<p class='err'>❌ کاربری با این ایمیل پیدا نشد</p>";
    }
}
echo "</div>";

// =============================================
// ۷. وضعیت سشن و کوکی فعلی
// =============================================
echo "<div class='box'><h2>🍪 وضعیت فعلی</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session user_id: " . ($_SESSION['user_id'] ?? 'خالی') . "</p>";
echo "<p>Cookie golestan_user: " . ($_COOKIE['golestan_user'] ?? 'خالی') . "</p>";
echo "<p>Cookie golestan_token: " . (isset($_COOKIE['golestan_token']) ? substr($_COOKIE['golestan_token'], 0, 10) . '...' : 'خالی') . "</p>";

echo "<h3>پاک کردن:</h3>";
echo "<a href='?clear=1' style='color:#f44336;'>🗑️ پاک کردن سشن و کوکی</a>";

if (isset($_GET['clear'])) {
    session_destroy();
    setcookie('golestan_user', '', time() - 3600, '/');
    setcookie('golestan_token', '', time() - 3600, '/');
    header("Location: /test_auth.php");
    exit();
}

echo "</div>";

// =============================================
// جمع‌بندی
// =============================================
echo "<div class='box'><h2>📊 لینک‌های سریع</h2>";
echo "<a href='/login.php' style='color:#6366f1;display:block;margin:5px;'>🔑 صفحه ورود</a>";
echo "<a href='/signup.php' style='color:#6366f1;display:block;margin:5px;'>📝 صفحه ثبت‌نام</a>";
echo "<a href='/forgot-password.php' style='color:#6366f1;display:block;margin:5px;'>🔑 فراموشی رمز</a>";
echo "<a href='/user/dashboard/v2/' style='color:#6366f1;display:block;margin:5px;'>🚀 داشبورد</a>";
echo "</div>";

echo "</body></html>";
?>