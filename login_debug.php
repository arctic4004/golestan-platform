<?php
// login_debug.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 عیب‌یابی ریدایرکت</h2>";

// چک فایل‌ها
echo "<h4>includes/header.php:</h4>";
$header_content = file_get_contents('includes/header.php');
if (strpos($header_content, 'redirect') !== false || strpos($header_content, 'Location') !== false) {
    echo "<p style='color:red'>⚠️ header.php شامل ریدایرکت است!</p>";
} else {
    echo "<p style='color:green'>✅ header.php ریدایرکت ندارد</p>";
}

echo "<h4>includes/navbar.php:</h4>";
$navbar_content = file_get_contents('includes/navbar.php');
if (strpos($navbar_content, 'redirect') !== false || strpos($navbar_content, 'Location') !== false) {
    echo "<p style='color:red'>⚠️ navbar.php شامل ریدایرکت است!</p>";
} else {
    echo "<p style='color:green'>✅ navbar.php ریدایرکت ندارد</p>";
}

echo "<h4>includes/functions.php:</h4>";
$func_content = file_get_contents('includes/functions.php');
if (strpos($func_content, 'isLoggedIn()') !== false) {
    echo "<p style='color:orange'>⚠️ functions.php تابع isLoggedIn دارد</p>";
}

// لینک تست
echo "<h3>لینک تست:</h3>";
echo "<a href='/login_debug.php?test=1' style='display:block;padding:10px;background:blue;color:white;margin:5px;'>تست ریدایرکت</a>";
if (isset($_GET['test'])) {
    echo "<p style='color:green'>✅ صفحه بدون ریدایرکت لود شد!</p>";
}

// فرم لاگین ساده
echo "<h3>فرم لاگین تستی (با دیتابیس):</h3>";
echo "<form method='POST' action='/login_debug.php'>";
echo "<input type='text' name='phone' value='09177418286' placeholder='موبایل' style='padding:8px;margin:5px;'>";
echo "<input type='password' name='password' value='admin123' placeholder='رمز' style='padding:8px;margin:5px;'>";
echo "<button type='submit' name='login_test' style='padding:10px 20px;background:blue;color:white;border:none;cursor:pointer;'>ورود</button>";
echo "</form>";

// تست لاگین
if (isset($_POST['login_test'])) {
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h4>بررسی لاگین:</h4>";
    echo "<p>موبایل: $phone</p>";
    echo "<p>رمز: $password</p>";
    
    require_once 'config/database.php';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        echo "<p style='color:green'>✅ اتصال به دیتابیس برقرار شد</p>";
        
        // چک وجود کاربر
        $stmt = $db->prepare("SELECT id, full_name, phone, password_hash, credits, is_admin, is_active FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p style='color:green'>✅ کاربر پیدا شد: {$user['full_name']}</p>";
            echo "<p>Active: " . ($user['is_active'] ? 'بله' : 'خیر') . "</p>";
            echo "<p>Hash: " . substr($user['password_hash'], 0, 20) . "...</p>";
            
            // تست رمز
            if (password_verify($password, $user['password_hash'])) {
                echo "<p style='color:green;font-size:18px;'>✅ رمز درست است!</p>";
                
                // ست کردن سشن
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['phone'] = $user['phone'];
                $_SESSION['credits'] = $user['credits'];
                $_SESSION['is_admin'] = (bool)$user['is_admin'];
                $_SESSION['theme'] = $user['theme'] ?? 'gold';
                
                // ست کردن کوکی
                $token = md5($user['id'] . 'golestan_salt_2024');
                setcookie('golestan_user', $user['id'], time() + (86400 * 30), '/', '', false, true);
                setcookie('golestan_token', $token, time() + (86400 * 30), '/', '', false, true);
                
                echo "<p style='color:green;font-size:18px;'>✅ سشن و کوکی ست شد!</p>";
                echo "<p>User ID in session: {$_SESSION['user_id']}</p>";
                
                echo "<p><a href='/user/dashboard/v2/' style='font-size:18px;color:green;padding:10px;background:#e8f5e9;border-radius:5px;text-decoration:none;'>🚀 برو به داشبورد</a></p>";
                echo "<p><a href='/user/dashboard/v2/chat.php' style='font-size:18px;color:green;padding:10px;background:#e8f5e9;border-radius:5px;text-decoration:none;'>💬 برو به چت</a></p>";
                echo "<p><a href='/' style='font-size:18px;color:blue;padding:10px;background:#e3f2fd;border-radius:5px;text-decoration:none;'>🏠 صفحه اصلی</a></p>";
                
            } else {
                echo "<p style='color:red;font-size:18px;'>❌ رمز اشتباه است!</p>";
                
                // فرم تغییر رمز مستقیم
                echo "<h4>🔧 تغییر رمز:</h4>";
                echo "<form method='POST' action='/login_debug.php'>";
                echo "<input type='hidden' name='phone' value='$phone'>";
                echo "<input type='text' name='new_password' value='admin123' placeholder='رمز جدید' style='padding:8px;margin:5px;'>";
                echo "<button type='submit' name='reset_password' style='padding:10px 20px;background:red;color:white;border:none;cursor:pointer;'>تغییر رمز به admin123</button>";
                echo "</form>";
            }
        } else {
            echo "<p style='color:red;'>❌ کاربر با شماره $phone پیدا نشد!</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ خطا: " . $e->getMessage() . "</p>";
    }
}

// تغییر رمز
if (isset($_POST['reset_password'])) {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $new_hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE phone = ?");
    $stmt->execute([$new_hash, $_POST['phone']]);
    
    echo "<p style='color:green;font-size:18px;'>✅ رمز به '{$_POST['new_password']}' تغییر کرد!</p>";
    echo "<p>حالا با این رمز لاگین کن</p>";
}

// تست ریدایرکت - فایل login.php رو مستقیم چک کن
echo "<h3>📁 محتوای login.php (خلاصه):</h3>";
echo "<pre style='background:#f5f5f5;padding:10px;max-height:200px;overflow:auto;'>";
$login_content = file_get_contents('login.php');
echo htmlspecialchars(substr($login_content, 0, 1000));
echo "</pre>";
?>