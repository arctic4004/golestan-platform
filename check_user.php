<?php
// check_user.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h2>🔍 بررسی کاربر ادمین</h2>";

$database = new Database();
$db = $database->getConnection();

// پیدا کردن کاربر
$stmt = $db->prepare("SELECT * FROM users WHERE phone = ?");
$stmt->execute(['09177418286']);
$user = $stmt->fetch();

if ($user) {
    echo "<p>✅ کاربر پیدا شد:</p>";
    echo "<ul>";
    echo "<li>ID: " . $user['id'] . "</li>";
    echo "<li>نام: " . $user['full_name'] . "</li>";
    echo "<li>موبایل: " . $user['phone'] . "</li>";
    echo "<li>ادمین: " . ($user['is_admin'] ? 'بله' : 'خیر') . "</li>";
    echo "<li>فعال: " . ($user['is_active'] ? 'بله' : 'خیر') . "</li>";
    echo "<li>اعتبار: " . $user['credits'] . "</li>";
    echo "<li>Password Hash: " . substr($user['password_hash'], 0, 20) . "...</li>";
    echo "</ul>";
    
    // تست رمز
    $test_passwords = ['admin123', 'password', '09177418286'];
    echo "<h3>تست رمزها:</h3>";
    foreach ($test_passwords as $pass) {
        if (password_verify($pass, $user['password_hash'])) {
            echo "<p style='color:green'>✅ رمز '$pass' کار میکنه!</p>";
        } else {
            echo "<p style='color:red'>❌ رمز '$pass' کار نمیکنه</p>";
        }
    }
    
    // فرم تغییر رمز
    echo "<h3>تغییر رمز عبور:</h3>";
    echo "<form method='POST'>";
    echo "<input type='text' name='new_password' placeholder='رمز جدید' required>";
    echo "<button type='submit' name='reset_password'>تغییر رمز</button>";
    echo "</form>";
    
} else {
    echo "<p style='color:red'>❌ کاربر با شماره 09177418286 پیدا نشد!</p>";
    
    // ساخت ادمین جدید
    echo "<h3>ساخت کاربر ادمین جدید:</h3>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='create_admin'>ساخت ادمین با رمز admin123</button>";
    echo "</form>";
}

// پردازش فرم‌ها
if (isset($_POST['reset_password']) && !empty($_POST['new_password'])) {
    $new_password = $_POST['new_password'];
    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
    
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE phone = ?");
    $stmt->execute([$new_hash, '09177418286']);
    
    echo "<p style='color:green;font-size:18px;'>✅ رمز عبور با موفقیت به '$new_password' تغییر کرد!</p>";
    echo "<p><a href='/login.php'>حالا میتونی وارد بشی →</a></p>";
}

if (isset($_POST['create_admin'])) {
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    
    $stmt = $db->prepare("INSERT INTO users (phone, full_name, password_hash, is_admin, is_active, credits) VALUES (?, ?, ?, 1, 1, 999999)");
    $stmt->execute(['09177418286', 'مدیر سیستم', $hash]);
    
    echo "<p style='color:green;font-size:18px;'>✅ کاربر ادمین ساخته شد!</p>";
    echo "<p>موبایل: 09177418286</p>";
    echo "<p>رمز: admin123</p>";
    echo "<p><a href='/login.php'>برو به صفحه ورود →</a></p>";
}

// لیست همه کاربران
echo "<h3>همه کاربران:</h3>";
$stmt = $db->query("SELECT id, phone, full_name, is_admin, is_active FROM users");
$users = $stmt->fetchAll();

if (count($users) > 0) {
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>موبایل</th><th>نام</th><th>ادمین</th><th>فعال</th></tr>";
    foreach ($users as $u) {
        echo "<tr>";
        echo "<td>{$u['id']}</td>";
        echo "<td>{$u['phone']}</td>";
        echo "<td>{$u['full_name']}</td>";
        echo "<td>" . ($u['is_admin'] ? '✅' : '❌') . "</td>";
        echo "<td>" . ($u['is_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>هیچ کاربری وجود ندارد.</p>";
}
?>