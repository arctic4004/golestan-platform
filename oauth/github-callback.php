<?php
session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/oauth_config.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_GET['state'] !== ($_SESSION['github_state'] ?? '')) {
    die('خطای امنیتی');
}

// دریافت توکن
$ch = curl_init('https://github.com/login/oauth/access_token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_POSTFIELDS => http_build_query([
        'client_id' => GITHUB_CLIENT_ID,
        'client_secret' => GITHUB_CLIENT_SECRET,
        'code' => $_GET['code'],
        'redirect_uri' => GITHUB_REDIRECT_URI,
    ])
]);
$token_data = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($token_data['access_token'])) {
    die('خطا در دریافت توکن');
}

// دریافت اطلاعات کاربر
$ch = curl_init('https://api.github.com/user');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token_data['access_token'],
        'User-Agent: GolestanNet'
    ]
]);
$github_user = json_decode(curl_exec($ch), true);
curl_close($ch);

// دریافت ایمیل
$ch = curl_init('https://api.github.com/user/emails');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token_data['access_token'],
        'User-Agent: GolestanNet'
    ]
]);
$emails = json_decode(curl_exec($ch), true);
curl_close($ch);

$primary_email = '';
if (is_array($emails)) {
    foreach ($emails as $email) {
        if ($email['primary']) {
            $primary_email = $email['email'];
            break;
        }
    }
}

$db = (new Database())->getConnection();

if ($primary_email) {
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$primary_email]);
    $user = $stmt->fetch();
}

if (isset($user) && $user) {
    $user_id = $user['id'];
} else {
    $phone = 'GH' . substr(bin2hex(random_bytes(4)), 0, 9);
    $stmt = $db->prepare("INSERT INTO users (phone, email, full_name, password_hash, credits, wallet_balance) VALUES (?, ?, ?, ?, 1000, 0)");
    $stmt->execute([$phone, $primary_email, $github_user['name'] ?? $github_user['login'], '']);
    $user_id = $db->lastInsertId();
}

// ذخیره OAuth
$stmt = $db->prepare("INSERT IGNORE INTO oauth_users (user_id, provider, provider_id, email, name, avatar) VALUES (?, 'github', ?, ?, ?, ?)");
$stmt->execute([$user_id, $github_user['id'], $primary_email, $github_user['name'] ?? $github_user['login'], $github_user['avatar_url']]);

$user = getUserData($user_id);

// ست سشن
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

logActivity($user_id, 'login', 'ورود با GitHub');

if (empty($user['password_hash']) || password_verify('', $user['password_hash'])) {
    header("Location: /user/dashboard/v2/set-password.php?welcome=1");
} else {
    header("Location: /user/dashboard/v2/");
}
exit;
