<?php
// offline.php
require_once 'config/constants.php';
?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>آفلاین | <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Vazirmatn', Tahoma, sans-serif;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; background: #f9fafb; direction: rtl;
        }
        .offline-box { text-align: center; padding: 40px 20px; }
        .offline-box .icon { font-size: 5rem; margin-bottom: 20px; }
        .offline-box h1 { color: #1e293b; margin-bottom: 10px; font-size: 1.5rem; }
        .offline-box p { color: #64748b; margin-bottom: 20px; }
        .offline-box button {
            padding: 12px 30px; background: #6366f1; color: white;
            border: none; border-radius: 12px; font-size: 1rem; cursor: pointer;
            font-family: inherit;
        }
        [data-theme="dark"] body { background: #0f172a; }
        [data-theme="dark"] h1 { color: #f1f5f9; }
        [data-theme="dark"] p { color: #94a3b8; }
    </style>
</head>
<body>
    <div class="offline-box">
        <div class="icon">📡</div>
        <h1>شما آفلاین هستید</h1>
        <p>لطفاً اتصال اینترنت خود را بررسی کنید و دوباره تلاش کنید.</p>
        <button onclick="location.reload()">🔄 تلاش مجدد</button>
    </div>
</body>
</html>