<?php
// session_check.php - برای تست سشن
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h2>🔍 وضعیت سشن</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Save Path: " . session_save_path() . "</p>";
echo "<p>Session Status: " . session_status() . " (2=active)</p>";

echo "<h3>متغیرهای سشن:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    echo "<p style='color:green;font-size:18px;'>✅ کاربر لاگین هست! (User ID: {$_SESSION['user_id']})</p>";
    echo "<p><a href='/user/dashboard/v2/'>رفتن به داشبورد</a></p>";
    echo "<p><a href='/user/dashboard/v2/chat.php'>رفتن به چت</a></p>";
} else {
    echo "<p style='color:red;font-size:18px;'>❌ کاربر لاگین نیست!</p>";
    echo "<p><a href='/login.php'>رفتن به صفحه ورود</a></p>";
}

// تست تنظیم سشن
echo "<h3>تست ست کردن سشن:</h3>";
echo "<form method='POST'>";
echo "<button type='submit' name='set_session'>ست کردن سشن تست</button>";
echo "<button type='submit' name='clear_session'>پاک کردن سشن</button>";
echo "</form>";

if (isset($_POST['set_session'])) {
    $_SESSION['test'] = 'This is a test at ' . date('H:i:s');
    $_SESSION['user_id'] = 1;
    $_SESSION['full_name'] = 'تست';
    echo "<p style='color:green'>✅ سشن ست شد! صفحه رو رفرش کن.</p>";
}

if (isset($_POST['clear_session'])) {
    session_destroy();
    echo "<p style='color:orange'>🗑️ سشن پاک شد!</p>";
}
?>