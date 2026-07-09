<?php
// test_theme.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🎨 تست سیستم تغییر تم</h1>";
echo "<style>
    body { font-family: Tahoma; padding: 20px; }
    .box { padding: 15px; margin: 10px 0; border-radius: 8px; }
    .success { background: #e8f5e9; color: #2e7d32; }
    .error { background: #fce4ec; color: #c62828; }
    .info { background: #e3f2fd; color: #1565c0; }
    button { padding: 10px 20px; margin: 5px; font-size: 16px; cursor: pointer; }
</style>";

// ۱. چک فایل‌ها
echo "<div class='box info'><h3>📁 چک فایل‌ها:</h3>";
$files = [
    'assets/js/theme.js',
    'assets/css/style.css',
    'includes/footer.php',
    'set_theme.php'
];
foreach ($files as $f) {
    echo file_exists($f) ? "✅ $f<br>" : "❌ $f وجود ندارد!<br>";
}
echo "</div>";

// ۲. چک محتوای footer.php
echo "<div class='box info'><h3>🔍 چک footer.php:</h3>";
$footer = file_get_contents('includes/footer.php');
if (strpos($footer, 'theme-toggle') !== false) {
    echo "✅ دکمه theme-toggle پیدا شد<br>";
} else {
    echo "❌ دکمه theme-toggle در footer.php نیست!<br>";
}
if (strpos($footer, 'theme.js') !== false) {
    echo "✅ theme.js لود میشه<br>";
} else {
    echo "❌ theme.js در footer.php لود نشده!<br>";
}
echo "</div>";

// ۳. چک style.css
echo "<div class='box info'><h3>🎨 چک style.css:</h3>";
$css = file_get_contents('assets/css/style.css');
if (strpos($css, '[data-theme="dark"]') !== false) {
    echo "✅ تم تاریک در CSS تعریف شده<br>";
} else {
    echo "❌ تم تاریک در CSS نیست!<br>";
}
if (strpos($css, 'data-theme') !== false) {
    echo "✅ متغیرهای data-theme وجود دارن<br>";
} else {
    echo "❌ متغیرهای data-theme نیستن!<br>";
}
echo "</div>";

// ۴. تست localStorage
echo "<div class='box'><h3>💾 تست localStorage:</h3>";
echo "<p>تم فعلی: <strong id='currentTheme'>---</strong></p>";
echo "<button onclick='testLight()'>☀️ روشن</button>";
echo "<button onclick='testDark()'>🌙 تاریک</button>";
echo "<button onclick='checkLocalStorage()'>🔍 چک localStorage</button>";
echo "<div id='result' style='margin-top:10px;'></div>";
echo "</div>";

// ۵. لینک‌ها
echo "<div class='box info'><h3>🔗 لینک‌های تست:</h3>";
echo "<a href='/' target='_blank'>🏠 صفحه اصلی (پنجره جدید)</a><br>";
echo "<a href='/user/dashboard/v2/chat.php' target='_blank'>💬 صفحه چت (پنجره جدید)</a>";
echo "</div>";
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    checkLocalStorage();
});

function testLight() {
    document.documentElement.setAttribute('data-theme', 'light');
    localStorage.setItem('golestan_theme', 'light');
    document.getElementById('result').innerHTML = '<span style="color:green;">✅ تم روشن فعال شد</span>';
    document.getElementById('currentTheme').textContent = 'روشن';
}

function testDark() {
    document.documentElement.setAttribute('data-theme', 'dark');
    localStorage.setItem('golestan_theme', 'dark');
    document.getElementById('result').innerHTML = '<span style="color:green;">✅ تم تاریک فعال شد</span>';
    document.getElementById('currentTheme').textContent = 'تاریک';
}

function checkLocalStorage() {
    const theme = localStorage.getItem('golestan_theme') || 'light';
    document.getElementById('currentTheme').textContent = theme === 'dark' ? 'تاریک' : 'روشن';
    document.getElementById('result').innerHTML = 'localStorage: golestan_theme = <strong>' + theme + '</strong>';
}

// تابع toggleTheme (همونی که توی دکمه صدا زده میشه)
window.toggleTheme = function() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('golestan_theme', next);
    document.getElementById('currentTheme').textContent = next === 'dark' ? 'تاریک' : 'روشن';
    document.getElementById('result').innerHTML = '<span style="color:green;">✅ تغییر تم به: ' + next + '</span>';
};
</script>