<?php
// test_session.php
session_start();

// فقط یک بار هنگام کلیک روی دکمه‌ها تنظیم کن، نه هنگام رفرش خودکار
if (isset($_GET['set'])) {
    if ($_GET['set'] === 'clear') {
        unset($_SESSION['theme_color'], $_SESSION['theme_mode']);
    } else {
        $_SESSION['theme_color'] = $_GET['set'];
        $_SESSION['theme_mode'] = 'dark';
    }
    // رفرش خودکار انجام نشود
}

echo "<h2>🧪 تست واقعی سشن</h2>";

echo "<h3>۱. وضعیت فعلی:</h3>";
echo "theme_color: <strong>" . ($_SESSION['theme_color'] ?? '❌ خالی') . "</strong><br>";
echo "theme_mode: <strong>" . ($_SESSION['theme_mode'] ?? '❌ خالی') . "</strong><br>";

echo "<h3>۲. تنظیم دستی:</h3>";
echo "<a href='?set=ruby' style='padding:8px 16px;background:#dc2626;color:#fff;border-radius:8px;text-decoration:none;margin:4px'>روبی</a> ";
echo "<a href='?set=emerald' style='padding:8px 16px;background:#059669;color:#fff;border-radius:8px;text-decoration:none;margin:4px'>زمرد</a> ";
echo "<a href='?set=cyan' style='padding:8px 16px;background:#06b6d4;color:#fff;border-radius:8px;text-decoration:none;margin:4px'>فیروزه‌ای</a> ";
echo "<a href='?set=clear' style='padding:8px 16px;background:#64748b;color:#fff;border-radius:8px;text-decoration:none;margin:4px'>پاک کردن</a>";

echo "<h3>۳. تست localStorage:</h3>";
echo "<p>مقادیر ذخیره شده در مرورگر: <span id='ls'></span></p>";

echo "<h3>۴. لینک‌ها:</h3>";
echo "<a href='/'>🏠 برو به صفحه اصلی</a> | ";
echo "<a href='/shop/'>🛒 برو به فروشگاه</a> | ";
echo "<a href='test_session.php'>🔄 رفرش همین صفحه</a>";

echo "<script>
document.getElementById('ls').textContent = 
    'color: ' + (localStorage.getItem('theme_color') || 'ندارد') + 
    ' | mode: ' + (localStorage.getItem('theme_mode') || 'ندارد');
</script>";
?>