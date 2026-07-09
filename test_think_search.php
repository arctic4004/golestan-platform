<?php
// test_think_search.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧠 تست Think و Search</h2>";

require_once 'config/database.php';
$db = (new Database())->getConnection();
$token = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'deepseek_api_key'")->fetchColumn();

if (empty($token)) die("❌ Token نیست");

$account_id = '66b43b4fe65858aebd524af96cd93d54';
$model = '@cf/meta/llama-4-scout-17b-16e-instruct';
$question = "۳ تا زبان برنامه‌نویسی وب معرفی کن";

// ========== تست ۱: Think خاموش ==========
echo "<div style='background:#f0fdf4;padding:16px;border-radius:12px;margin-bottom:16px'>";
echo "<h3>🟢 تست ۱: Think = OFF | Search = OFF</h3>";
echo "<p><strong>سوال:</strong> $question</p>";

$system = "شما دستیار کافی‌نت هستید. به فارسی کوتاه پاسخ بده.";
$messages = [
    ['role' => 'system', 'content' => $system],
    ['role' => 'user', 'content' => $question]
];

$ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$token, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['messages' => $messages, 'max_tokens' => 200, 'temperature' => 0.7]),
    CURLOPT_TIMEOUT => 30
]);
$res = json_decode(curl_exec($ch), true);
echo "<pre style='background:#fff;padding:10px;border-radius:8px'>" . ($res['result']['response'] ?? 'خطا') . "</pre>";
echo "</div>";

// ========== تست ۲: Think روشن ==========
echo "<div style='background:#eff6ff;padding:16px;border-radius:12px;margin-bottom:16px'>";
echo "<h3>🧠 تست ۲: Think = ON | Search = OFF</h3>";
echo "<p><strong>سوال:</strong> $question</p>";

$system = "شما دستیار کافی‌نت هستید. به فارسی کوتاه پاسخ بده.
⚠️ حالت تفکر عمیق فعال است. قبل از پاسخ، فرآیند فکری را گام به گام توضیح بده:
💭 تحلیل: سوال را تحلیل کن
🧠 استدلال: گام‌های منطقی را بگو
✅ پاسخ نهایی: جواب اصلی را بده";

$messages = [
    ['role' => 'system', 'content' => $system],
    ['role' => 'user', 'content' => $question]
];

$ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$token, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['messages' => $messages, 'max_tokens' => 300, 'temperature' => 0.3]),
    CURLOPT_TIMEOUT => 30
]);
$res = json_decode(curl_exec($ch), true);
echo "<pre style='background:#fff;padding:10px;border-radius:8px'>" . ($res['result']['response'] ?? 'خطا') . "</pre>";
echo "</div>";

// ========== تست ۳: Search روشن ==========
echo "<div style='background:#fef3c7;padding:16px;border-radius:12px;margin-bottom:16px'>";
echo "<h3>🌐 تست ۳: Think = OFF | Search = ON</h3>";
echo "<p><strong>سوال:</strong> $question</p>";

$system = "شما دستیار کافی‌نت هستید. به فارسی کوتاه پاسخ بده.
🌐 حالت جستجو فعال است. طوری پاسخ بده که انگار به اینترنت دسترسی داری و اطلاعاتت کاملاً به‌روز است.";

$messages = [
    ['role' => 'system', 'content' => $system],
    ['role' => 'user', 'content' => $question]
];

$ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$token, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['messages' => $messages, 'max_tokens' => 200, 'temperature' => 0.7]),
    CURLOPT_TIMEOUT => 30
]);
$res = json_decode(curl_exec($ch), true);
echo "<pre style='background:#fff;padding:10px;border-radius:8px'>" . ($res['result']['response'] ?? 'خطا') . "</pre>";
echo "</div>";

// ========== تست ۴: Think + Search ==========
echo "<div style='background:#f5f3ff;padding:16px;border-radius:12px;margin-bottom:16px'>";
echo "<h3>🧠🌐 تست ۴: Think = ON | Search = ON</h3>";
echo "<p><strong>سوال:</strong> $question</p>";

$system = "شما دستیار کافی‌نت هستید. به فارسی کوتاه پاسخ بده.
⚠️ حالت تفکر عمیق فعال است. گام به گام فکر کن.
🌐 حالت جستجو فعال است. اطلاعات به‌روز بده.";

$messages = [
    ['role' => 'system', 'content' => $system],
    ['role' => 'user', 'content' => $question]
];

$ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$token, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['messages' => $messages, 'max_tokens' => 300, 'temperature' => 0.3]),
    CURLOPT_TIMEOUT => 30
]);
$res = json_decode(curl_exec($ch), true);
echo "<pre style='background:#fff;padding:10px;border-radius:8px'>" . ($res['result']['response'] ?? 'خطا') . "</pre>";
echo "</div>";

echo "<h3>📊 نتیجه‌گیری:</h3>";
echo "<ul>
    <li>🟢 <strong>Think OFF:</strong> پاسخ مستقیم و کوتاه</li>
    <li>🧠 <strong>Think ON:</strong> تحلیل → استدلال → پاسخ</li>
    <li>🌐 <strong>Search ON:</strong> شبیه‌سازی اطلاعات به‌روز</li>
    <li>🧠🌐 <strong>هر دو:</strong> تفکر عمیق + اطلاعات به‌روز</li>
</ul>";
?>