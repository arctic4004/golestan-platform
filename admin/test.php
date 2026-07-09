<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) die("لاگین نیستی");

$db = (new Database())->getConnection();
$tab = 'dashboard';

// تست include header
echo "=== قبل header ===<br>";
ob_start();
require_once '../includes/header.php';
echo "=== بعد header ===<br>";

// تست محتوای ساده
echo "<h1>تست داشبورد</h1>";

// تست include footer
echo "=== قبل footer ===<br>";
require_once '../includes/footer.php';
echo "=== بعد footer ===<br>";

ob_end_flush();
?>