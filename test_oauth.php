<?php
// test_oauth.php
require_once 'config/oauth_config.php';

echo "<h2>🧪 تست تنظیمات OAuth</h2>";
echo "<p>Client ID: " . substr(GOOGLE_CLIENT_ID, 0, 30) . "...</p>";
echo "<p>Client Secret: " . substr(GOOGLE_CLIENT_SECRET, 0, 10) . "...</p>";
echo "<p>Redirect URI: " . GOOGLE_REDIRECT_URI . "</p>";

// چک فایل‌ها
$files = ['oauth/google-login.php', 'oauth/google-callback.php', 'config/oauth_config.php'];
echo "<h3>فایل‌ها:</h3>";
foreach ($files as $f) {
    echo file_exists($f) ? "✅ $f<br>" : "❌ $f<br>";
}

echo "<h3>لینک تست:</h3>";
echo "<a href='/oauth/google-login.php' style='display:inline-block;padding:12px 24px;background:#6366f1;color:white;border-radius:8px;text-decoration:none;'>🚀 تست ورود با گوگل</a>";
echo "<p style='color:#666;margin-top:10px;'>بعد از کلیک، باید به صفحه لاگین گوگل بروید. اگر خطا دادید، redirect URI را چک کنید.</p>";
?>