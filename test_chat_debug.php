<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// شبیه‌سازی لاگین
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['credits'] = 1000;

echo "<h2>📨 تست ارسال پیام به چت</h2>";

echo "<h3>۱. لود فایل‌ها:</h3>";
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
echo "✅ فایل‌ها لود شدن<br>";

echo "<h3>۲. تست دیتابیس:</h3>";
$db = (new Database())->getConnection();
$user = $db->query("SELECT * FROM users WHERE id = 1")->fetch();
echo "✅ کاربر: " . $user['full_name'] . " | اعتبار: " . $user['credits'] . "<br>";

echo "<h3>۳. تست DeepSeekAPI:</h3>";
try {
    require_once 'api/chat/DeepSeekAPI.php';
    $ai = new DeepSeekAPI();
    $response = $ai->sendMessage("سلام");
    echo "✅ پاسخ: " . substr($response['content'], 0, 200) . "<br>";
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage() . "<br>";
}

echo "<h3>۴. تست Knowledge Base:</h3>";
$kb = json_decode(file_get_contents('knowledge/cafenet_knowledge.json'), true);
echo "✅ تعداد خدمات: " . count($kb['cafenet_knowledge']['services']) . "<br>";

echo "<h3>۵. تست send.php با POST:</h3>";
$_SERVER['REQUEST_METHOD'] = 'POST';
$GLOBALS['HTTP_RAW_POST_DATA'] = json_encode(['message' => 'قیمت پرینت چنده؟', 'model' => 'llama-4']);
echo "✅ آماده تست<br>";
?>