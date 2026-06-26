<?php
// test_mobile.php
session_start();

// شبیه‌سازی یک کاربر لاگین‌شده (ادمین)
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'مدیر';
$_SESSION['phone'] = '09177418286';
$_SESSION['credits'] = 999999;
$_SESSION['is_admin'] = true;
$_SESSION['theme'] = 'light';

require_once 'config/constants.php';
require_once 'includes/header.php';
?>

<div style="padding: 100px 20px 20px; text-align: center;">
    <h2>📱 تست منوی همبرگری و صفحه ابزارها</h2>
    <p>برای آزمایش، <strong>عرض مرورگر را به کمتر از ۷۶۸ پیکسل کاهش دهید</strong> (یا از حالت شبیه‌ساز موبایل در DevTools استفاده کنید).</p>
    <p>سپس روی آیکون <strong>☰</strong> در بالای صفحه کلیک کنید.</p>
    
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin: 20px auto; max-width: 400px; text-align: right;">
        <h4>✅ مواردی که باید در منوی موبایل ببینید:</h4>
        <ul style="list-style: none; padding: 0;">
            <li>🏠 خانه</li>
            <li>🛠️ خدمات</li>
            <li>💬 چت AI</li>
            <li>🎨 ساخت عکس</li>
            <li>🧰 ابزارها</li>
            <li>📊 داشبورد</li>
            <li>📋 تسک‌ها</li>
            <li>🛡️ مدیریت (فقط برای ادمین)</li>
        </ul>
        <p style="font-size:0.9rem;">👤 همچنین باید نام کاربر (مدیر) و اعتبار در منو دیده شود.</p>
        <p style="font-size:0.9rem;">با کلیک روی <strong>پس‌زمینه تیره</strong> یا <strong>دکمه همبرگر</strong> منو باید بسته شود.</p>
    </div>

    <p>
        <a href="/user/dashboard/v2/tools.php" class="btn btn-primary">🛠️ رفتن به صفحه ابزارها</a>
    </p>
</div>

<?php require_once 'includes/footer.php'; ?>