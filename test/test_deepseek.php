<?php
// test_deepseek.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 تست DeepSeek API</h2>";

// تست دیتابیس
echo "<h3>مرحله ۱: تست دیتابیس</h3>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color:green'>✅ اتصال به دیتابیس برقرار شد</p>";
    
    // چک API Key در دیتابیس
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'deepseek_api_key'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result && !empty($result['setting_value'])) {
        $api_key_preview = substr($result['setting_value'], 0, 10) . '...';
        echo "<p style='color:green'>✅ API Key در دیتابیس یافت شد: {$api_key_preview}</p>";
    } else {
        echo "<p style='color:red'>❌ API Key در دیتابیس یافت نشد!</p>";
        echo "<p>لطفاً این کوئری رو در phpMyAdmin اجرا کن:</p>";
        echo "<pre>INSERT INTO settings (setting_key, setting_value) VALUES ('deepseek_api_key', 'sk-کلید-تو');</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ خطای دیتابیس: " . $e->getMessage() . "</p>";
}

// تست API
echo "<h3>مرحله ۲: تست ارتباط با DeepSeek</h3>";
try {
    require_once 'api/chat/DeepSeekAPI.php';
    $api = new DeepSeekAPI();
    
    echo "<p style='color:blue'>📤 ارسال پیام به DeepSeek...</p>";
    
    $response = $api->sendMessage('سلام! به فارسی بگو حالت چطوره؟');
    
    echo "<p style='color:green'>✅ پاسخ دریافت شد:</p>";
    echo "<div style='background:#f5f5f5; padding:15px; border-radius:8px;'>";
    echo "<p><strong>پاسخ:</strong> " . $response['content'] . "</p>";
    echo "<p><strong>توکن مصرفی:</strong> " . $response['tokens_used'] . "</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ خطا در ارتباط با DeepSeek:</p>";
    echo "<p>" . $e->getMessage() . "</p>";
}

// راهنما
echo "<h3>📝 راهنمای رفع مشکل</h3>";
echo "<ol>";
echo "<li>مطمئن شو API Key معتبر از <a href='https://platform.deepseek.com/' target='_blank'>DeepSeek Platform</a> گرفتی</li>";
echo "<li>API Key باید با 'sk-' شروع بشه</li>";
echo "<li>در phpMyAdmin جدول settings رو چک کن</li>";
echo "<li>اگر API Key نداری، باید ثبت‌نام کنی و اعتبار بخری</li>";
echo "</ol>";
?>