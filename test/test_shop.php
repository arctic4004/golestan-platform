<?php
// test_shop.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// اطمینان از وجود محصولات تستی
$db = (new Database())->getConnection();
$has_products = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
if ($has_products == 0) {
    $db->exec("INSERT INTO products (name, description, price, type, `condition`, stock, category) VALUES 
        ('نصب ویندوز ۱۱', 'نصب و راه‌اندازی کامل', 300000, 'service', 'new', 999, 'computer'),
        ('طراحی سایت فروشگاهی', 'PHP + ووکامرس', 8000000, 'service', 'new', 999, 'web'),
        ('ماوس گیمینگ ریزر (نو)', 'مدل DeathAdder', 2500000, 'goods', 'new', 5, 'hardware'),
        ('کیبورد مکانیکی استوک', 'Redragon K552', 1200000, 'goods', 'used', 2, 'hardware')");
}

// تست دسترسی به صفحات
$pages = [
    '/shop/index.php' => 'صفحه اصلی فروشگاه',
    '/shop/services.php' => 'خدمات',
    '/shop/goods.php' => 'کالاها',
    '/shop/product.php?id=1' => 'جزئیات محصول',
    '/shop/cart.php' => 'سبد خرید',
    '/login.php' => 'ورود',
    '/signup.php' => 'ثبت‌نام',
    '/forgot-password.php' => 'فراموشی رمز',
];

echo "<!DOCTYPE html><html dir='rtl'><head><meta charset='UTF-8'><title>تست فروشگاه</title>
<style>
body{font-family:Tahoma;background:#f9fafb;padding:30px}
.box{background:white;border-radius:12px;padding:20px;margin:15px 0;box-shadow:0 1px 3px rgba(0,0,0,0.1)}
.ok{color:#16a34a}.err{color:#dc2626}
a{display:block;padding:8px;margin:5px 0}
</style></head><body>
<h1>🧪 تست فروشگاه</h1>";

echo "<div class='box'><h2>📁 فایل‌های فروشگاه</h2>";
$shop_files = ['shop/index.php','shop/product.php','shop/cart.php','shop/checkout.php','shop/invoice.php','shop/orders.php','shop/wallet.php'];
foreach($shop_files as $f) {
    echo file_exists($f) ? "<span class='ok'>✅ $f</span><br>" : "<span class='err'>❌ $f</span><br>";
}
echo "</div>";

echo "<div class='box'><h2>🗄️ جداول دیتابیس</h2>";
$tables = ['products', 'orders', 'order_items', 'wallet_transactions'];
foreach($tables as $t) {
    try {
        $db->query("SELECT 1 FROM $t LIMIT 1");
        echo "<span class='ok'>✅ $t</span><br>";
    } catch(Exception $e) {
        echo "<span class='err'>❌ $t - {$e->getMessage()}</span><br>";
    }
}
echo "</div>";

echo "<div class='box'><h2>📄 وضعیت صفحات</h2>";
foreach($pages as $url => $title) {
    $full_url = SITE_URL . $url;
    $ch = curl_init($full_url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5, CURLOPT_NOBODY=>true]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo $code===200 ? "<span class='ok'>✅ $title</span><br>" : "<span class='err'>❌ $title (HTTP $code)</span><br>";
}
echo "</div>";

echo "<div class='box'><h2>🛒 تست سبد خرید</h2>";
$_SESSION['cart'] = [['id'=>1,'qty'=>1,'price'=>300000]];
echo "<p>سبد خرید ست شد: " . count($_SESSION['cart']) . " آیتم</p>";
echo "</div>";

echo "<div class='box'><h2>🔗 لینک‌های مستقیم</h2>";
foreach($pages as $url => $title) {
    echo "<a href='$url'>$title</a>";
}
echo "</div>";

echo "</body></html>";
?>