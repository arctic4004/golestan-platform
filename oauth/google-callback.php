<?php
// oauth/google-callback.php
session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/oauth_config.php';
require_once __DIR__ . '/../includes/functions.php';

// ۱. چک وجود code
if (!isset($_GET['code'])) {
    die('خطا: کد احراز هویت دریافت نشد.');
}

// ۲. دریافت توکن از گوگل
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
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    die('خطا در دریافت توکن: ' . ($token_data['error_description'] ?? 'نامشخص'));
}

// ۳. دریافت اطلاعات کاربر از گوگل
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

// ۴. لاگین یا ثبت‌نام کاربر
$db = (new Database())->getConnection();

// چک وجود کاربر با email
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$google_user['email']]);
$user = $stmt->fetch();

if ($user) {
    // کاربر قبلاً ثبت‌نام کرده - لاگین
    $user_id = $user['id'];
} else {
    // ثبت‌نام کاربر جدید
    $phone = 'GO' . substr(md5($google_user['email']), 0, 9);
    $random_password = bin2hex(random_bytes(16));
    
    $stmt = $db->prepare("INSERT INTO users (phone, email, full_name, password_hash, credits, wallet_balance) VALUES (?, ?, ?, ?, 1000, 0)");
    $stmt->execute([
        $phone,
        $google_user['email'],
        $google_user['name'],
        password_hash($random_password, PASSWORD_BCRYPT)
    ]);
    $user_id = $db->lastInsertId();
}

// ۵. ذخیره اطلاعات OAuth
$stmt = $db->prepare("INSERT IGNORE INTO oauth_users (user_id, provider, provider_id, email, name, avatar) VALUES (?, 'google', ?, ?, ?, ?)");
$stmt->execute([$user_id, $google_user['sub'], $google_user['email'], $google_user['name'], $google_user['picture']]);

// ۶. ست کردن سشن
$user = getUserData($user_id);
$_SESSION['user_id'] = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['phone'] = $user['phone'];
$_SESSION['credits'] = $user['credits'];
$_SESSION['wallet_balance'] = $user['wallet_balance'] ?? 0;
$_SESSION['is_admin'] = (bool)($user['is_admin'] ?? false);
$_SESSION['theme'] = $user['theme'] ?? 'light';

// ۷. ست کردن کوکی (ماندگاری)
$token = md5($user_id . 'golestan_salt_2024');
setcookie('golestan_user', $user_id, time() + (86400 * 30), '/', '', false, true);
setcookie('golestan_token', $token, time() + (86400 * 30), '/', '', false, true);

logActivity($user_id, 'login', 'ورود با Google OAuth');

// ۸. ریدایرکت به داشبورد
header("Location: /user/dashboard/v2/");
exit;