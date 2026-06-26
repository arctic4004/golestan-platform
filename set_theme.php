<?php
// set_theme.php
session_start();

$allowed = ['gold', 'emerald', 'sapphire', 'light', 'dark'];
$theme = $_GET['theme'] ?? 'light';

if (!in_array($theme, $allowed)) {
    $theme = 'light';
}

// ذخیره در localStorage (با AJAX)
if (isset($_GET['ajax'])) {
    // ذخیره در دیتابیس اگر کاربر لاگین هست
    if (isset($_SESSION['user_id'])) {
        require_once 'config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare("UPDATE users SET theme = ? WHERE id = ?");
        $stmt->execute([$theme, $_SESSION['user_id']]);
        $_SESSION['theme'] = $theme;
    }
    exit('ok');
}

// ذخیره در سشن (روش قبلی)
$_SESSION['theme'] = $theme;

if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("UPDATE users SET theme = ? WHERE id = ?");
    $stmt->execute([$theme, $_SESSION['user_id']]);
}

// برگشت به صفحه قبلی
$referer = $_SERVER['HTTP_REFERER'] ?? '/';
header('Location: ' . $referer);
exit;