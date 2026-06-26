<?php
// oauth/google-login.php
session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/oauth_config.php';

$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'access_type' => 'online',
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;