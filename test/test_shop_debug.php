<?php
// test_shop_debug.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 عیب‌یابی فروشگاه</h2>";

// ۱. چک فایل‌ها
echo "<h3>۱. فایل‌های فروشگاه:</h3>";
$files = ['shop/index.php', 'shop/services.php', 'shop/goods.php', 'shop/product.php', 'shop/cart.php'];
foreach ($files as $f) {
    echo file_exists($f) ? "✅ $f<br>" : "❌ $f وجود ندارد<br>";
}

// ۲. تست دیتابیس
echo "<h3>۲. تست دیتابیس:</h3>";
require_once 'config/database.php';
try {
    $db = (new Database())->getConnection();
    echo "✅ اتصال دیتابیس برقرار شد<br>";
    
    // چک جدول products
    $count = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    echo "✅ تعداد محصولات: $count<br>";
    
    // تست کوئری
    $services = $db->query("SELECT * FROM products WHERE type='service' AND is_active=1 LIMIT 1")->fetchAll();
    echo "✅ کوئری services: " . count($services) . " نتیجه<br>";
    
    $goods = $db->query("SELECT * FROM products WHERE type='goods' AND is_active=1 LIMIT 1")->fetchAll();
    echo "✅ کوئری goods: " . count($goods) . " نتیجه<br>";
    
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage() . "<br>";
}

// ۳. تست مستقیم فایل‌ها
echo "<h3>۳. تست اجرای مستقیم:</h3>";

foreach (['shop/services.php', 'shop/goods.php'] as $file) {
    echo "<h4>$file:</h4>";
    try {
        ob_start();
        include $file;
        $output = ob_get_clean();
        $len = strlen($output);
        echo $len > 100 ? "✅ خروجی: $len کاراکتر<br>" : "❌ خروجی کم: $len کاراکتر<br>";
        if ($len < 100) {
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
        }
    } catch (Throwable $e) {
        ob_end_clean();
        echo "❌ خطا: " . $e->getMessage() . "<br>";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    }
}

// ۴. چک error_log
echo "<h3>۴. آخرین خطاهای PHP:</h3>";
$error_log = ini_get('error_log') ?: '/home/golestanyasujir/public_html/error_log';
if (file_exists($error_log)) {
    $lines = file($error_log);
    $lines = array_slice($lines, -15);
    echo "<pre>";
    foreach ($lines as $line) {
        if (strpos($line, 'shop') !== false || strpos($line, 'Fatal') !== false || strpos($line, 'Error') !== false) {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p>error_log پیدا نشد در مسیر: $error_log</p>";
}

// ۵. لینک تست
echo "<h3>۵. لینک‌های تست:</h3>";
echo "<a href='/shop/services.php'>services.php</a><br>";
echo "<a href='/shop/goods.php'>goods.php</a><br>";
?>