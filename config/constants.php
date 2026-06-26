<?php
// config/constants.php
define('SITE_NAME', 'کافی‌نت گلستان');
define('SITE_URL', 'https://golestanyasuj.ir');  // ← URL اصلی سایت
define('ADMIN_EMAIL', 'arctic4004@gmail.com');
define('TIMEZONE', 'Asia/Tehran');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900);
define('SESSION_LIFETIME', 86400);
define('UPLOAD_MAX_SIZE', 5242880);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

date_default_timezone_set(TIMEZONE);

// آدرس‌های اصلی
define('BASE_URL', 'https://golestanyasuj.ir');
define('DASHBOARD_URL', BASE_URL . '/user/dashboard/v2');
define('ADMIN_URL', BASE_URL . '/admin');
define('API_URL', BASE_URL . '/api');