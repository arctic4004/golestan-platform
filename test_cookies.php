<?php
session_start();
echo "<h2>🍪 تست کوکی‌ها</h2>";

echo "<h3>۱. کوکی‌های موجود در مرورگر:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h3>۲. سشن فعلی:</h3>";
echo "<pre>";
echo 'user_id: ' . ($_SESSION['user_id'] ?? 'ندارد') . "\n";
echo 'full_name: ' . ($_SESSION['full_name'] ?? 'ندارد') . "\n";
echo 'is_admin: ' . ($_SESSION['is_admin'] ?? 'ندارد') . "\n";
echo "</pre>";

echo "<h3>۳. تست تنظیم کوکی:</h3>";

// تست کوکی ساده
setcookie('test_simple', 'hello', time() + 3600, '/');
echo "✅ test_simple set<br>";

// تست کوکی با تنظیمات کامل
setcookie('test_secure', 'world', [
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);
echo "✅ test_secure set<br>";

echo "<h3>۴. اطلاعات سرور:</h3>";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? '✅ بله' : '❌ خیر (HTTP)') . "<br>";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "SERVER_PORT: " . $_SERVER['SERVER_PORT'] . "<br>";
echo "REQUEST_SCHEME: " . ($_SERVER['REQUEST_SCHEME'] ?? 'ندارد') . "<br>";

echo "<h3>۵. چک فایل‌هایی که کوکی ست می‌کنن:</h3>";

$files_to_check = [
    'login.php' => ['golestan_user', 'golestan_token'],
    'signup.php' => ['golestan_user', 'golestan_token'],
    'oauth/google-callback.php' => ['golestan_user', 'golestan_token'],
    'oauth/github-callback.php' => ['golestan_user', 'golestan_token'],
    'logout.php' => ['golestan_user', 'golestan_token'],
];

foreach ($files_to_check as $file => $cookies) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $found = 0;
        foreach ($cookies as $c) {
            if (strpos($content, $c) !== false) $found++;
        }
        echo ($found === count($cookies) ? "✅" : "⚠️") . " $file: " . $found . "/" . count($cookies) . " کوکی<br>";
    } else {
        echo "❌ $file: فایل نیست<br>";
    }
}

echo "<h3>۶. تست لاگین:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ کاربر لاگین شده (user_id: {$_SESSION['user_id']})<br>";
    
    // چک کوکی
    if (isset($_COOKIE['golestan_user'])) {
        echo "✅ کوکی golestan_user: {$_COOKIE['golestan_user']}<br>";
    } else {
        echo "❌ کوکی golestan_user تنظیم نشده!<br>";
    }
} else {
    echo "❌ کاربر لاگین نیست<br>";
    echo "<a href='/login.php'>رفتن به صفحه لاگین</a>";
}
?>