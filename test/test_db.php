<?php
// test_db.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 تست اتصال دیتابیس</h1>";

// ۱. نمایش تنظیمات فعلی
echo "<h3>📋 تنظیمات فعلی:</h3>";
require_once 'config/database.php';
$db = new Database();
// نمی‌تونیم مستقیم private ها رو ببینیم، پس تست می‌کنیم

// ۲. تست اتصال
echo "<h3>🔌 تست اتصال:</h3>";
try {
    $conn = $db->getConnection();
    echo "<p style='color:green;font-size:18px;'>✅ اتصال موفقیت‌آمیز!</p>";
    
    // ۳. تست جداول
    echo "<h3>📊 جداول موجود:</h3>";
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>✅ {$table}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:orange;'>⚠️ هیچ جدولی وجود ندارد. باید SQL را اجرا کنی.</p>";
    }
    
    // ۴. تست کاربر ادمین
    echo "<h3>👤 کاربران:</h3>";
    try {
        $stmt = $conn->query("SELECT id, full_name, phone, is_admin FROM users LIMIT 5");
        $users = $stmt->fetchAll();
        if (count($users) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>نام</th><th>موبایل</th><th>ادمین</th></tr>";
            foreach ($users as $user) {
                $admin = $user['is_admin'] ? '✅' : '❌';
                echo "<tr><td>{$user['id']}</td><td>{$user['full_name']}</td><td>{$user['phone']}</td><td>{$admin}</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:orange;'>⚠️ هیچ کاربری وجود ندارد.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ جدول users وجود ندارد: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;font-size:18px;'>❌ خطا در اتصال</p>";
    echo "<pre style='background:#ffe6e6;padding:15px;'>" . $e->getMessage() . "</pre>";
    
    echo "<h3>💡 راه‌حل:</h3>";
    echo "<ol>";
    echo "<li>وارد cPanel شو</li>";
    echo "<li>برو به <b>MySQL® Databases</b></li>";
    echo "<li>چک کن این موارد وجود دارن:";
    echo "<ul>";
    echo "<li>Database: <b>golestanyasujir_chat</b></li>";
    echo "<li>User: <b>golestanyasujir_golestan</b></li>";
    echo "</ul></li>";
    echo "<li>از بخش <b>Add User To Database</b> کاربر رو به دیتابیس اضافه کن</li>";
    echo "<li>مطمئن شو ALL PRIVILEGES تیک خورده</li>";
    echo "<li>اگر پسورد رو فراموش کردی، از <b>Change Password</b> عوضش کن</li>";
    echo "</ol>";
}
?>