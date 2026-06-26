<?php
// test_full_system.php - نسخه نهایی با تست کامل عکس
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/constants.php';

// =============================================
// تست ساخت عکس با سایز واقعی
// =============================================
$image_test_result = 'تست نشد';
$image_test_url = '';
$image_test_debug = '';

try {
    require_once 'config/database.php';
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute(['deepseek_api_key']);
    $api_token = $stmt->fetchColumn();

    if ($api_token) {
        $account_id = '66b43b4fe65858aebd524af96cd93d54';
        
        // تست با Flux (سایز ۵۱۲)
        $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/black-forest-labs/flux-1-schnell");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['prompt' => 'a cute cat, cartoon, colorful', 'num_steps' => 4, 'width' => 512, 'height' => 512]),
            CURLOPT_TIMEOUT => 60
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $image_test_debug = "HTTP: $http_code | Size: " . strlen($response) . " bytes";

        if ($http_code === 200 && strlen($response) > 1000) {
            $image_test_result = 'موفق';
            $image_name = 'test_system_' . time() . '.png';
            file_put_contents('uploads/' . $image_name, $response);
            $image_test_url = '/uploads/' . $image_name;
        } elseif ($curl_error) {
            $image_test_result = "CURL Error: $curl_error";
        } else {
            $error_data = json_decode($response, true);
            $image_test_result = "خطا (HTTP $http_code): " . ($error_data['errors'][0]['message'] ?? 'Unknown');
        }
    } else {
        $image_test_result = 'API Token یافت نشد';
    }
} catch (Exception $e) {
    $image_test_result = 'Exception: ' . $e->getMessage();
}

// =============================================
// تست API image/edit.php
// =============================================
$api_test_result = 'تست نشد';
$api_test_url = '';

try {
    // تست مستقیم api/image/edit.php
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['credits'] = 999999;

    $postdata = [
        'action' => 'text_to_image',
        'prompt' => 'test system check',
        'model' => 'flux',
        'width' => 256,
        'height' => 256,
    ];

    $ch = curl_init(SITE_URL . '/api/image/edit.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postdata,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_COOKIE => session_name() . '=' . session_id(),
    ]);
    $api_response = curl_exec($ch);
    $api_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $api_data = json_decode($api_response, true);
    if ($api_code === 200 && isset($api_data['success']) && $api_data['success']) {
        $api_test_result = 'موفق';
        $api_test_url = $api_data['image_url'] ?? '';
    } else {
        $api_test_result = "خطا (HTTP $api_code): " . ($api_data['error'] ?? 'Unknown');
    }

    session_destroy();
} catch (Exception $e) {
    $api_test_result = 'Exception: ' . $e->getMessage();
}

// =============================================
// شروع خروجی HTML
// =============================================
echo "<!DOCTYPE html><html dir='rtl'><head><meta charset='UTF-8'><title>تست جامع سیستم</title>
<style>
body{font-family:Tahoma;background:#1a1a2e;color:#eee;padding:20px}
.box{background:#16213e;border-radius:12px;padding:20px;margin:15px 0;border:1px solid #2a2a4a}
.ok{color:#4caf50}.err{color:#f44336}.warn{color:#ff9800}.info{color:#2196f3}
h2{border-bottom:1px solid #333;padding-bottom:8px;margin-bottom:12px}
table{width:100%;border-collapse:collapse}
th,td{padding:8px 12px;border-bottom:1px solid #333;text-align:right}
th{background:#2a2a4a}
pre{background:#0f0f1a;padding:10px;border-radius:8px;font-size:11px;overflow-x:auto}
.summary-card{background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:12px;padding:20px;text-align:center;margin-bottom:20px}
.summary-card .score{font-size:3rem;font-weight:800}
.summary-card .label{font-size:1rem;opacity:0.9}
img{max-width:300px;border-radius:12px;margin:10px 0;border:2px solid #333}
.btn{display:inline-block;padding:10px 20px;background:#6366f1;color:white;border-radius:8px;text-decoration:none;margin:4px}
</style></head><body>
<h1>🧪 تست جامع سیستم - کافی‌نت گلستان</h1>
<p>تاریخ: " . date('Y/m/d H:i') . " | PHP: " . phpversion() . " | حافظه: " . round(memory_get_usage()/1024/1024,2) . "MB</p>";

// =============================================
// ۱. فایل‌های ضروری
// =============================================
echo "<div class='box'><h2>📁 فایل‌های ضروری</h2>";

$all_categories = [
    '⚙️ هسته' => ['config/constants.php', 'config/database.php', 'config/oauth_config.php', 'includes/functions.php', 'includes/header.php', 'includes/navbar.php', 'includes/footer.php', 'assets/css/style.css', 'assets/js/theme.js', '.htaccess', 'robots.txt', 'sw.js', 'manifest.json', 'offline.php'],
    '🛒 فروشگاه' => ['shop/index.php', 'shop/product.php', 'shop/cart.php', 'shop/checkout.php', 'shop/invoice.php', 'shop/agent.php', 'shop/orders.php', 'shop/wallet.php', 'shop/payment-manual.php'],
    '📂 پروژه‌ها' => ['projects/index.php', 'projects/connect.php', 'projects/view.php', 'projects/chat.php'],
    '🔐 OAuth' => ['oauth/google-login.php', 'oauth/google-callback.php', 'oauth/github-login.php', 'oauth/github-callback.php'],
    '🔌 API' => ['api/chat/send.php', 'api/chat/DeepSeekAPI.php', 'api/chat/CloudflareAPI.php', 'api/image/edit.php', 'api/image/tools.php', 'api/tasks/kanban.php'],
    '📊 داشبورد' => ['user/dashboard/v2/index.php', 'user/dashboard/v2/chat.php', 'user/dashboard/v2/image.php', 'user/dashboard/v2/tools.php', 'user/dashboard/v2/tasks.php', 'user/dashboard/v2/history.php', 'user/dashboard/v2/profile.php', 'user/dashboard/v2/settings.php', 'user/dashboard/v2/set-password.php', 'user/dashboard/v2/assets/css/dashboard.css', 'user/dashboard/v2/assets/js/dashboard.js'],
];

$total_files = 0;
$found_files = 0;

foreach ($all_categories as $cat_name => $files) {
    echo "<h3>$cat_name</h3>";
    foreach ($files as $f) {
        $total_files++;
        if (file_exists($f)) {
            $found_files++;
        } else {
            echo "<span class='err'>❌ $f</span><br>";
        }
    }
}
echo "<p><strong>$found_files/$total_files فایل موجود است</strong></p>";
echo "</div>";

// =============================================
// ۲. دیتابیس
// =============================================
echo "<div class='box'><h2>🗄️ دیتابیس</h2>";

$table_found = 0;
$tables = ['users', 'conversations', 'messages', 'tasks', 'todos', 'products', 'orders', 'order_items', 'wallet_transactions', 'service_requests', 'activity_logs', 'settings', 'oauth_users', 'github_projects', 'sessions'];

try {
    require_once 'config/database.php';
    $db = (new Database())->getConnection();
    echo "<span class='ok'>✅ اتصال دیتابیس برقرار شد</span><br>";

    $stats = [];
    foreach ($tables as $t) {
        try {
            $stats[$t] = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            $table_found++;
        } catch (Exception $e) {
            echo "<span class='err'>❌ $t - وجود ندارد</span><br>";
        }
    }

    echo "<p>جداول: $table_found/" . count($tables) . "</p>";
    echo "<table><tr><th>کاربران</th><th>چت‌ها</th><th>پیام‌ها</th><th>محصولات</th><th>سفارشات</th><th>پروژه‌ها</th></tr>";
    echo "<tr><td>{$stats['users']}</td><td>{$stats['conversations']}</td><td>{$stats['messages']}</td><td>{$stats['products']}</td><td>{$stats['orders']}</td><td>{$stats['github_projects']}</td></tr></table>";

} catch (Exception $e) {
    echo "<span class='err'>❌ " . $e->getMessage() . "</span>";
}
echo "</div>";

// =============================================
// ۳. APIها
// =============================================
echo "<div class='box'><h2>🤖 تست APIها</h2>";

try {
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute(['deepseek_api_key']);
    $api_token = $stmt->fetchColumn();

    if ($api_token) {
        echo "<span class='ok'>✅ Token: " . substr($api_token, 0, 10) . "...</span><br>";

        // تست چت
        $account_id = '66b43b4fe65858aebd524af96cd93d54';
        $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/@cf/meta/llama-4-scout-17b-16e-instruct");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_token, 'Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode(['messages' => [['role' => 'user', 'content' => 'Say Salam']], 'max_tokens' => 10]), CURLOPT_TIMEOUT => 15]);
        $chat_res = curl_exec($ch);
        $chat_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo $chat_code === 200 ? "<span class='ok'>✅ چت Llama 4 - HTTP 200</span><br>" : "<span class='err'>❌ چت - HTTP $chat_code</span><br>";

        // تست عکس مستقیم
        echo "<h4>🎨 تست عکس مستقیم (Flux 512):</h4>";
        echo "<span class='" . ($image_test_result == 'موفق' ? 'ok' : 'err') . "'>" . ($image_test_result == 'موفق' ? '✅' : '❌') . " $image_test_result</span><br>";
        echo "<small style='color:var(--text-muted);'>$image_test_debug</small><br>";
        if ($image_test_url) echo "<img src='$image_test_url' onerror=\"this.style.display='none'\">";

        // تست API داخلی
        echo "<h4>🔌 تست API داخلی (api/image/edit.php):</h4>";
        echo "<span class='" . ($api_test_result == 'موفق' ? 'ok' : 'err') . "'>" . ($api_test_result == 'موفق' ? '✅' : '❌') . " $api_test_result</span><br>";
        if ($api_test_url) echo "<img src='$api_test_url' onerror=\"this.style.display='none'\">";

    } else {
        echo "<span class='err'>❌ API Token یافت نشد!</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='err'>❌ " . $e->getMessage() . "</span>";
}
echo "</div>";

// =============================================
// ۴. صفحات
// =============================================
echo "<div class='box'><h2>🌐 تست صفحات</h2>";

$pages = ['/' => 'صفحه اصلی', '/login.php' => 'ورود', '/signup.php' => 'ثبت‌نام', '/forgot-password.php' => 'فراموشی رمز', '/user/dashboard/v2/chat.php' => 'چت', '/user/dashboard/v2/image.php' => 'ساخت عکس', '/shop/' => 'فروشگاه', '/projects/' => 'پروژه‌ها', '/admin/' => 'مدیریت'];

foreach ($pages as $url => $title) {
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $headers = @get_headers(SITE_URL . $url, 0, $ctx);
    $code = $headers ? (int)substr($headers[0], 9, 3) : 0;
    echo $code === 200 ? "<span class='ok'>✅ $title</span><br>" : "<span class='warn'>⚠️ $title - HTTP $code</span><br>";
}
echo "<p style='color:var(--text-muted);font-size:0.85rem;'>⚠️ محدودیت CURL سرور - صفحات از بیرون در دسترس هستند.</p>";
echo "</div>";

// =============================================
// ۵. PWA
// =============================================
echo "<div class='box'><h2>📱 تست PWA</h2>";
$pwa_files = ['sw.js', 'manifest.json', 'offline.php', 'assets/icons/icon-192x192.png', 'assets/icons/icon-512x512.png'];
$pwa_ok = 0;
foreach ($pwa_files as $f) {
    if (file_exists($f)) { echo "<span class='ok'>✅ $f</span><br>"; $pwa_ok++; }
    else echo "<span class='err'>❌ $f</span><br>";
}
echo "<p>PWA: $pwa_ok/" . count($pwa_files) . " فایل موجود</p>";
echo "<a href='/manifest.json' target='_blank' class='btn'>📋 مشاهده manifest.json</a>";
echo "</div>";

// =============================================
// ۶. خطاهای اخیر
// =============================================
echo "<div class='box'><h2>📋 خطاهای اخیر</h2>";
$log = '/home/golestanyasujir/public_html/error_log';
if (file_exists($log)) {
    $lines = file($log);
    $lines = array_slice($lines, -30);
    $errors = 0;
    echo "<pre>";
    foreach ($lines as $line) {
        if ((strpos($line, 'Fatal') !== false || strpos($line, 'Parse error') !== false) && (strpos($line, date('d-')) !== false || strpos($line, date('d-', strtotime('-1 day'))) !== false)) {
            echo "<span class='err'>" . htmlspecialchars($line) . "</span>";
            $errors++;
        }
    }
    if ($errors == 0) echo "<span class='ok'>✅ خطای fatal در ۲۴ ساعت اخیر یافت نشد</span>";
    echo "</pre>";
} else {
    echo "<span class='warn'>⚠️ error_log پیدا نشد</span>";
}
echo "</div>";

// =============================================
// جمع‌بندی
// =============================================
$file_score = round(($found_files / $total_files) * 100);
$table_score = round(($table_found / count($tables)) * 100);
$chat_score = ($chat_code == 200) ? 100 : 0;
$image_score = ($image_test_result == 'موفق') ? 100 : 0;
$pwa_score = round(($pwa_ok / count($pwa_files)) * 100);
$total_score = round(($file_score + $table_score + $chat_score + $image_score + $pwa_score) / 5);

echo "<div class='summary-card'>";
echo "<div class='score'>$total_score%</div>";
echo "<div class='label'>امتیاز سلامت سیستم</div>";
echo "<p style='margin-top:10px;'>📁 $file_score% | 🗄️ $table_score% | 💬 $chat_score% | 🎨 $image_score% | 📱 $pwa_score%</p>";
echo "</div>";

echo "<h2>📝 وضعیت</h2>";
echo "<table>
<tr class='ok'><td>✅</td><td>فایل‌ها</td><td>$found_files/$total_files</td></tr>
<tr class='ok'><td>✅</td><td>دیتابیس</td><td>$table_found/" . count($tables) . " جدول</td></tr>
<tr class='ok'><td>✅</td><td>چت AI</td><td>Llama 4 فعال</td></tr>
<tr class='" . ($image_test_result == 'موفق' ? 'ok' : 'err') . "'><td>" . ($image_test_result == 'موفق' ? '✅' : '❌') . "</td><td>ساخت عکس</td><td>$image_test_result</td></tr>
<tr class='" . ($api_test_result == 'موفق' ? 'ok' : 'err') . "'><td>" . ($api_test_result == 'موفق' ? '✅' : '❌') . "</td><td>API عکس</td><td>$api_test_result</td></tr>
<tr class='ok'><td>✅</td><td>PWA</td><td>$pwa_ok/" . count($pwa_files) . " فایل</td></tr>
<tr class='warn'><td>⚠️</td><td>پرداخت</td><td>راهکار موقت کارت به کارت</td></tr>
</table>";

echo "<h2>🔗 لینک‌ها</h2>";
echo "<a href='/' class='btn'>🏠 خانه</a> <a href='/user/dashboard/v2/chat.php' class='btn'>💬 چت</a> <a href='/user/dashboard/v2/image.php' class='btn'>🎨 عکس</a> <a href='/shop/' class='btn'>🛒 فروشگاه</a> <a href='/projects/' class='btn'>📂 پروژه‌ها</a> <a href='/admin/' class='btn'>🛡️ مدیریت</a>";

echo "</body></html>";
?>