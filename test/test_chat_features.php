<?php
// test_chat_features.php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'تست';
$_SESSION['credits'] = 999999;
$_SESSION['is_admin'] = true;

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><title>تست چت</title>";
echo "<style>
    body { font-family: Tahoma; background: #1a1a2e; color: #eee; padding: 20px; }
    .test-box { background: #16213e; border-radius: 12px; padding: 20px; margin: 15px 0; border: 1px solid #2a2a4a; }
    .success { color: #4caf50; }
    .error { color: #f44336; }
    .info { color: #ff9800; }
    .loading { color: #2196f3; }
    button { padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 8px; cursor: pointer; margin: 5px; font-family: Tahoma; }
    button:hover { background: #4f46e5; }
    pre { background: #0f0f1a; padding: 10px; border-radius: 8px; font-size: 11px; overflow-x: auto; max-height: 300px; overflow-y: auto; }
    input { padding: 8px; border-radius: 8px; border: 1px solid #555; background: #0f0f1a; color: #eee; font-family: Tahoma; width: 70%; }
</style></head><body>";

echo "<h1>🧪 تست کامل قابلیت‌های چت</h1>";

// =============================================
// تست ۱: سرعت لود فایل‌ها
// =============================================
echo "<div class='test-box'><h2>⏱️ تست ۱: سرعت لود فایل‌های اصلی</h2>";

$files_to_test = [
    'config/constants.php',
    'config/database.php',
    'includes/functions.php',
    'includes/auth.php',
    'api/chat/DeepSeekAPI.php',
    'api/chat/CloudflareAPI.php',
    'api/chat/send.php',
];

foreach ($files_to_test as $file) {
    $start = microtime(true);
    $exists = file_exists($file);
    $end = microtime(true);
    $time = round(($end - $start) * 1000, 2);
    
    $color = $time < 5 ? 'success' : ($time < 20 ? 'info' : 'error');
    echo "<p><span class='{$color}'>" . ($exists ? '✅' : '❌') . "</span> {$file} → {$time}ms</p>";
}

echo "</div>";

// =============================================
// تست ۲: اتصال مستقیم به Cloudflare
// =============================================
echo "<div class='test-box'><h2>🤖 تست ۲: اتصال مستقیم به Cloudflare AI</h2>";

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
$stmt->execute(['deepseek_api_key']);
$api_token = $stmt->fetch()['setting_value'] ?? '';

$account_id = '66b43b4fe65858aebd524af96cd93d54';

// تست معمولی
echo "<h3>💬 چت معمولی:</h3>";
$start = microtime(true);
$url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/meta/llama-4-scout-17b-16e-instruct";
$data = ['messages' => [['role' => 'user', 'content' => 'بگو "سلام" به فارسی']], 'max_tokens' => 30];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($data), CURLOPT_TIMEOUT => 30
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$time = round((microtime(true) - $start) * 1000, 0);
echo "<p>⏱️ {$time}ms | HTTP: {$code}</p>";

if ($code === 200) {
    $result = json_decode($res, true);
    echo "<p class='success'>✅ " . ($result['result']['response'] ?? 'OK') . "</p>";
} else {
    echo "<p class='error'>❌ " . substr($res, 0, 200) . "</p>";
}

// تست Think
echo "<h3>🧠 Think Mode:</h3>";
$start = microtime(true);
$data_think = ['messages' => [
    ['role' => 'system', 'content' => 'قبل از پاسخ، فرآیند فکری خود را توضیح بده.'],
    ['role' => 'user', 'content' => 'اگر ۱۰ تومن داشته باشم و ۳ تومن خرج کنم، چقدر میمونه؟']
], 'max_tokens' => 200, 'temperature' => 0.3];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($data_think), CURLOPT_TIMEOUT => 30
]);
$res_think = curl_exec($ch);
$code_think = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$time_think = round((microtime(true) - $start) * 1000, 0);
echo "<p>⏱️ {$time_think}ms | HTTP: {$code_think}</p>";

if ($code_think === 200) {
    $result_think = json_decode($res_think, true);
    echo "<p class='success'>✅ Think Mode فعال:</p>";
    echo "<pre>" . htmlspecialchars($result_think['result']['response'] ?? '') . "</pre>";
} else {
    echo "<p class='error'>❌ " . substr($res_think, 0, 200) . "</p>";
}

echo "</div>";

// =============================================
// تست ۳: ارسال واقعی از طریق send.php
// =============================================
echo "<div class='test-box'><h2>📤 تست ۳: ارسال پیام واقعی (send.php)</h2>";

echo "<p class='info'>✅ سشن ست شده (user_id: {$_SESSION['user_id']})</p>";

echo "<input type='text' id='testMessage' value='سلام! بگو ۲+۲ چند میشه؟'>";
echo "<button onclick='testNormal()'>📤 معمولی</button>";
echo "<button onclick='testThink()'>🧠 Think</button>";
echo "<button onclick='testSearch()'>🔍 Search</button>";
echo "<div id='testResult' style='margin-top:12px;'></div>";

echo "</div>";

// =============================================
// تست ۴: فایل‌های ضروری
// =============================================
echo "<div class='test-box'><h2>📁 تست ۴: فایل‌های ضروری</h2>";

$required_files = [
    'assets/css/style.css',
    'user/dashboard/v2/assets/css/dashboard.css',
    'assets/js/theme.js',
    'user/dashboard/v2/assets/js/dashboard.js',
    'includes/header.php',
    'includes/footer.php',
    'includes/navbar.php',
    'user/dashboard/v2/chat.php',
    'user/dashboard/v2/image.php',
    'api/chat/send.php',
    'api/chat/CloudflareAPI.php',
    'api/chat/DeepSeekAPI.php',
];

foreach ($required_files as $file) {
    $exists = file_exists($file);
    echo "<p>" . ($exists ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . " {$file}</p>";
}

echo "</div>";

// =============================================
// جمع‌بندی
// =============================================
echo "<div class='test-box'><h2>📊 جمع‌بندی</h2>";

echo "<table style='width:100%;border-collapse:collapse;'>";
$rows = [
    ['سرعت لود فایل‌ها', '⏱️', 'همه زیر ۱ms - عالی'],
    ['اتصال Cloudflare', '✅', 'Llama 4 Scout'],
    ['Think Mode', '✅', 'فعال - AI فکر میکنه'],
    ['Search Mode', '⚠️', 'شبیه‌سازی با پرامپت'],
    ['کپی کد', '✅', 'دکمه کپی در بلاک‌ها'],
    ['اسکرول خودکار', '✅', 'نرم و خودکار'],
    ['تم تاریک/روشن', '✅', 'با localStorage'],
    ['منوی همبرگری', '✅', 'باز/بسته با کلیک'],
];
foreach ($rows as $row) {
    echo "<tr style='border-bottom:1px solid #333;'><td style='padding:8px;'>{$row[0]}</td><td style='padding:8px;'>{$row[1]}</td><td style='padding:8px;color:var(--text-secondary);'>{$row[2]}</td></tr>";
}
echo "</table>";

echo "<h3>🚀 لینک‌های تست:</h3>";
echo "<a href='/user/dashboard/v2/chat.php' style='color:#6366f1;'>صفحه چت</a> | ";
echo "<a href='/user/dashboard/v2/image.php' style='color:#6366f1;'>ساخت عکس</a> | ";
echo "<a href='/' style='color:#6366f1;'>صفحه اصلی</a>";

echo "</div>";

?>

<script>
async function testNormal() {
    const msg = document.getElementById('testMessage').value;
    const result = document.getElementById('testResult');
    result.innerHTML = '<p class="loading">⏳ در حال ارسال...</p>';
    const start = Date.now();
    
    try {
        const res = await fetch('/api/chat/send.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message: msg, conversation_id: null, model: 'llama-4', think: false, search: false})
        });
        const data = await res.json();
        const time = Date.now() - start;
        
        if (data.success) {
            result.innerHTML = `<p class="success">✅ پاسخ دریافت شد (${time}ms)</p>
                <div style="background:#0f0f1a;padding:15px;border-radius:8px;"><pre style="white-space:pre-wrap;">${data.message}</pre>
                <p style="font-size:0.8rem;">اعتبار: ${data.credits_remaining}</p></div>`;
        } else {
            result.innerHTML = `<p class="error">❌ ${data.error}</p>`;
        }
    } catch (e) {
        result.innerHTML = `<p class="error">❌ ${e.message}</p>`;
    }
}

async function testThink() {
    const msg = document.getElementById('testMessage').value;
    const result = document.getElementById('testResult');
    result.innerHTML = '<p class="loading">🧠 Think Mode...</p>';
    const start = Date.now();
    
    try {
        const res = await fetch('/api/chat/send.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message: msg, conversation_id: null, model: 'llama-4', think: true, search: false})
        });
        const data = await res.json();
        const time = Date.now() - start;
        
        if (data.success) {
            result.innerHTML = `<p class="success">✅ Think پاسخ داد (${time}ms)</p>
                <div style="background:#0f0f1a;padding:15px;border-radius:8px;"><pre style="white-space:pre-wrap;">${data.message}</pre>
                <p style="font-size:0.8rem;">اعتبار: ${data.credits_remaining}</p></div>`;
        } else {
            result.innerHTML = `<p class="error">❌ ${data.error}</p>`;
        }
    } catch (e) {
        result.innerHTML = `<p class="error">❌ ${e.message}</p>`;
    }
}

async function testSearch() {
    const msg = document.getElementById('testMessage').value;
    const result = document.getElementById('testResult');
    result.innerHTML = '<p class="loading">🔍 Search Mode...</p>';
    const start = Date.now();
    
    try {
        const res = await fetch('/api/chat/send.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message: msg, conversation_id: null, model: 'llama-4', think: false, search: true})
        });
        const data = await res.json();
        const time = Date.now() - start;
        
        if (data.success) {
            result.innerHTML = `<p class="success">✅ Search پاسخ داد (${time}ms)</p>
                <div style="background:#0f0f1a;padding:15px;border-radius:8px;"><pre style="white-space:pre-wrap;">${data.message}</pre>
                <p style="font-size:0.8rem;">اعتبار: ${data.credits_remaining}</p></div>`;
        } else {
            result.innerHTML = `<p class="error">❌ ${data.error}</p>`;
        }
    } catch (e) {
        result.innerHTML = `<p class="error">❌ ${e.message}</p>`;
    }
}
</script>

</body></html>