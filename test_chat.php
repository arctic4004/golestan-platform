<?php
echo "<h1>🔍 تست سیستم چت</h1>";

echo "<h3>۱. چک دیتابیس:</h3>";
try {
    require_once 'config/database.php';
    $db = (new Database())->getConnection();
    echo "✅ اتصال دیتابیس: OK<br>";
    
    $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'deepseek_api_key'");
    $token = $stmt->fetchColumn();
    echo "🔑 Cloudflare Token: " . (empty($token) ? "❌ خالی" : "✅ موجود (" . substr($token, 0, 10) . "...)") . "<br>";
} catch (Exception $e) {
    echo "❌ خطا دیتابیس: " . $e->getMessage() . "<br>";
}

echo "<h3>۲. تست Cloudflare:</h3>";
try {
    require_once 'api/chat/CloudflareAPI.php';
    $cf = new CloudflareAPI();
    $result = $cf->sendMessage("سلام");
    echo "✅ پاسخ: " . substr($result['content'], 0, 300) . "<br>";
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage() . "<br>";
}

echo "<h3>۳. تست DeepSeekAPI:</h3>";
try {
    require_once 'api/chat/DeepSeekAPI.php';
    $ai = new DeepSeekAPI();
    $result = $ai->sendMessage("یه جمله به فارسی بگو");
    echo "✅ پاسخ: " . $result['content'] . "<br>";
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage() . "<br>";
}

echo "<h3>۴. فایل‌های موجود:</h3>";
echo "CloudflareAPI.php: " . (file_exists('api/chat/CloudflareAPI.php') ? "✅" : "❌") . "<br>";
echo "DeepSeekAPI.php: " . (file_exists('api/chat/DeepSeekAPI.php') ? "✅" : "❌") . "<br>";
echo "GeminiAPI.php: " . (file_exists('api/chat/GeminiAPI.php') ? "✅" : "❌") . "<br>";
echo "HuggingFaceAPI.php: " . (file_exists('api/chat/HuggingFaceAPI.php') ? "✅" : "❌") . "<br>";
?>