<?php
// test_gemini.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 تست مستقیم Gemini API</h2>";

// گرفتن API Key از دیتابیس
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
$stmt->execute(['deepseek_api_key']);
$api_key = $stmt->fetch()['setting_value'] ?? '';

echo "<p>API Key: " . substr($api_key, 0, 15) . "...</p>";

// تست با CURL مستقیم
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key;

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'سلام! به فارسی بگو حالت چطوره؟']
            ]
        ]
    ]
];

echo "<h3>📤 ارسال به Gemini...</h3>";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<p>HTTP Code: " . $http_code . "</p>";

if ($curl_error) {
    echo "<p style='color:red;'>❌ CURL Error: " . $curl_error . "</p>";
} elseif ($http_code === 200) {
    $result = json_decode($response, true);
    echo "<p style='color:green;font-size:18px;'>✅ Gemini کار میکنه!</p>";
    echo "<div style='background:#e8f5e9;padding:15px;border-radius:8px;'>";
    echo "<strong>پاسخ:</strong><br>";
    echo nl2br(htmlspecialchars($result['candidates'][0]['content']['parts'][0]['text']));
    echo "</div>";
} else {
    echo "<p style='color:red;'>❌ خطا (HTTP $http_code)</p>";
    echo "<pre style='background:#ffe6e6;padding:10px;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    
    // پیشنهادات
    $error = json_decode($response, true);
    $error_msg = $error['error']['message'] ?? '';
    
    if (strpos($error_msg, 'API_KEY_INVALID') !== false) {
        echo "<p style='color:red;'>🔑 کلید API نامعتبر است!</p>";
    } elseif (strpos($error_msg, 'PERMISSION_DENIED') !== false) {
        echo "<p style='color:red;'>🚫 دسترسی غیرمجاز! مطمئن شو Gemini API فعال باشه</p>";
    } elseif (strpos($error_msg, 'QUOTA_EXCEEDED') !== false) {
        echo "<p style='color:orange;'>⏳ محدودیت استفاده! کمی صبر کن</p>";
    }
}

// راهنما
echo "<h3>📝 راهنما:</h3>";
echo "<ol>";
echo "<li>برو به <a href='https://aistudio.google.com/apikey' target='_blank'>Google AI Studio</a></li>";
echo "<li>چک کن API Key فعال باشه</li>";
echo "<li>مطمئن شو 'Gemini API' در پروژه فعال باشه</li>";
echo "<li>اگر نیازه، یه کلید جدید بساز</li>";
echo "</ol>";
?>