<?php
// includes/header.php - نسخه کامل با Phosphor Icons + PWA + 9 تم رنگی

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

// تنظیمات تم از session خوانده می‌شود (پیش‌فرض: amethyst + dark)
$theme_color = $_SESSION['theme_color'] ?? 'amethyst';
$theme_mode  = $_SESSION['theme_mode'] ?? 'dark';
$page_description = $page_description ?? 'پلتفرم هوش مصنوعی کافی‌نت گلستان؛ چت هوشمند، ساخت عکس، تحلیل پروژه‌های گیت‌هاب، فروشگاه و ابزارهای حرفه‌ای';
$page_image = $page_image ?? '/assets/icons/icon-512x512.png';
?><!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="<?php echo $theme_color; ?>" data-mode="<?php echo $theme_mode; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $page_title ?? SITE_NAME; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="هوش مصنوعی, چت AI, ساخت عکس, تحلیل گیت‌هاب, کافی نت یاسوج, خدمات کامپیوتری, طراحی سایت, امنیت شبکه, CRM, مدیریت مشتریان">
    <meta name="author" content="<?php echo SITE_NAME; ?>">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#9333ea">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

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
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="گلستان AI">
    <link rel="apple-touch-icon" href="/assets/icons/icon-192x192.png">

    <!-- Canonical -->
    <link rel="canonical" href="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">

    <!-- Phosphor Icons + فونت انگلیسی -->
    <script src="https://unpkg.com/@phosphor-icons/web@2.1.1"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
    
    <style>
    .navbar { z-index: 50 !important; position: sticky !important; top: 0 !important; }
    .dropdown-menu, .user-dropdown { z-index: 100 !important; }
    .mobile-overlay { z-index: 45 !important; }
    #navMenu.active { z-index: 55 !important; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>
    <main>