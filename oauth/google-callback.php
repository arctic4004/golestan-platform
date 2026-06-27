<?php
session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/oauth_config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_GET['code'])) {
    die('خطا: کد احراز هویت دریافت نشد.');
}

// دریافت توکن
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_POSTFIELDS => http_build_query([
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ])
]);

$response = curl_exec($ch);
curl_close($ch);
$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    die('خطا در دریافت توکن.');
}

// دریافت اطلاعات کاربر
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token_data['access_token']]
]);
$user_response = curl_exec($ch);
curl_close($ch);
$google_user = json_decode($user_response, true);

if (!isset($google_user['email'])) {
    die('خطا در دریافت اطلاعات کاربر.');
}

$db = (new Database())->getConnection();

// چک وجود کاربر
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$google_user['email']]);
$user = $stmt->fetch();

if ($user) {
    $user_id = $user['id'];
    $is_new_user = false;
} else {
    // ثبت‌نام با رمز خالی (بعداً باید تنظیم کنه)
    $phone = 'GO' . substr(bin2hex(random_bytes(4)), 0, 9);

    $stmt = $db->prepare("INSERT INTO users (phone, email, full_name, password_hash, credits, wallet_balance) VALUES (?, ?, ?, ?, 1000, 0)");
    $stmt->execute([
        $phone,
        $google_user['email'],
        $google_user['name'],
        '' // رمز خالی - کاربر باید تنظیم کنه
    ]);
    $user_id = $db->lastInsertId();
    $is_new_user = true;
}

// ذخیره OAuth
$stmt = $db->prepare("INSERT IGNORE INTO oauth_users (user_id, provider, provider_id, email, name, avatar) VALUES (?, 'google', ?, ?, ?, ?)");
$stmt->execute([$user_id, $google_user['sub'], $google_user['email'], $google_user['name'], $google_user['picture']]);

// گرفتن اطلاعات کامل کاربر
$user = getUserData($user_id);

// ست کردن سشن
$_SESSION['user_id'] = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['phone'] = $user['phone'];
$_SESSION['credits'] = $user['credits'];
$_SESSION['wallet_balance'] = $user['wallet_balance'] ?? 0;
$_SESSION['is_admin'] = (bool)($user['is_admin'] ?? false);
$_SESSION['theme'] = $user['theme'] ?? 'light';

// کوکی امن
$token = bin2hex(random_bytes(32));
setcookie('golestan_user', $user_id, [
    'expires' => time() + (86400 * 30),
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
setcookie('golestan_token', $token, [
    'expires' => time() + (86400 * 30),
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

logActivity($user_id, 'login', 'ورود با Google OAuth');

// ریدایرکت: کاربر جدید → تنظیم رمز
if (empty($user['password_hash'])) {
    header("Location: /user/dashboard/v2/set-password.php?welcome=1");
} else {
    header("Location: /user/dashboard/v2/");
}
exit;
