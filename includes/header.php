<?php
// includes/header.php - نسخه کامل با PWA
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

$theme = function_exists('getTheme') ? getTheme() : 'light';
$page_description = $page_description ?? 'پلتفرم هوش مصنوعی کافی‌نت گلستان؛ چت هوشمند، ساخت عکس، تحلیل پروژه‌های گیت‌هاب، فروشگاه و ابزارهای حرفه‌ای';
$page_image = $page_image ?? '/assets/icons/icon-512x512.png';
?><!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $page_title ?? SITE_NAME; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="هوش مصنوعی, چت AI, ساخت عکس, تحلیل گیت‌هاب, کافی نت یاسوج, خدمات کامپیوتری, طراحی سایت, امنیت شبکه">
    <meta name="author" content="<?php echo SITE_NAME; ?>">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#6366f1">
    <meta name="csrf-token" content="<?php echo function_exists('generateCSRFToken') ? generateCSRFToken() : ''; ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo $page_title ?? SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo $page_description; ?>">
    <meta property="og:image" content="<?php echo SITE_URL . $page_image; ?>">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $page_title ?? SITE_NAME; ?>">
    <meta name="twitter:description" content="<?php echo $page_description; ?>">
    <meta name="twitter:image" content="<?php echo SITE_URL . $page_image; ?>">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="گلستان AI">
    <link rel="apple-touch-icon" href="/assets/icons/icon-192x192.png">

    <!-- Canonical -->
    <link rel="canonical" href="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Preconnect Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if (!empty($extra_css)): foreach ($extra_css as $css): ?>
        <link rel="stylesheet" href="/<?php echo $css; ?>">
    <?php endforeach; endif; ?>

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "<?php echo SITE_NAME; ?>",
        "description": "<?php echo $page_description; ?>",
        "url": "<?php echo SITE_URL; ?>",
        "telephone": "09177418286",
        "address": { "@type": "PostalAddress", "addressLocality": "یاسوج", "streetAddress": "پاسداران، بین گلستان ۳ و ۴" },
        "openingHours": "Sa-Th 09:00-21:00"
    }
    </script>
</head>
<body class="theme-<?php echo $theme; ?>">
    <?php include __DIR__ . '/navbar.php'; ?>
    <main>