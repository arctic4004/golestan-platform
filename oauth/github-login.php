<?php
// oauth/github-login.php
session_start();
require_once __DIR__ . '/../config/oauth_config.php';

$params = [
    'client_id' => GITHUB_CLIENT_ID,
    'redirect_uri' => GITHUB_REDIRECT_URI,
    'scope' => 'read:user user:email', // دسترسی به ایمیل و پروفایل
    'state' => bin2hex(random_bytes(16)),
];

$_SESSION['github_state'] = $params['state'];
header('Location: https://github.com/login/oauth/authorize?' . http_build_query($params));
exit;