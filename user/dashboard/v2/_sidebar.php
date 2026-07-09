<?php
$current = basename($_SERVER['PHP_SELF']);
$user = $user ?? getUserData($_SESSION['user_id'] ?? 0);
$wallet = $user['wallet_balance'] ?? 0;
?>
<aside class="dashboard-sidebar">
    <div class="user-profile-summary">
        <div class="avatar"><?= mb_substr($user['full_name'] ?? 'U', 0, 1) ?></div>
        <h3><?= sanitize($user['full_name'] ?? 'کاربر') ?></h3>
        <span class="user-phone"><?= $user['phone'] ?? '' ?></span>
        <div style="margin-top:6px;font-size:0.85rem">
            <i class="fas fa-wallet"></i> <?= number_format($wallet) ?> تومان
        </div>
    </div>
    <nav class="dashboard-nav">
        <?php
        $items = [
            ['url'=>'/user/dashboard/v2/','icon'=>'fa-home','label'=>'داشبورد','file'=>'index.php'],
            ['url'=>'/user/dashboard/v2/chat.php','icon'=>'fa-comments','label'=>'چت AI','file'=>'chat.php'],
            ['url'=>'/user/dashboard/v2/image.php','icon'=>'fa-image','label'=>'ساخت عکس','file'=>'image.php'],
            ['url'=>'/user/dashboard/v2/tools.php','icon'=>'fa-tools','label'=>'ابزارها','file'=>'tools.php'],
            ['url'=>'/projects/','icon'=>'fa-github','label'=>'پروژه‌ها','file'=>''],
            ['url'=>'/user/dashboard/v2/tasks.php','icon'=>'fa-tasks','label'=>'تسک‌ها','file'=>'tasks.php'],
            ['url'=>'/shop/','icon'=>'fa-store','label'=>'فروشگاه','file'=>''],
            ['url'=>'/shop/orders.php','icon'=>'fa-shopping-bag','label'=>'سفارشات','file'=>'orders.php'],
            ['url'=>'/user/dashboard/v2/history.php','icon'=>'fa-history','label'=>'تاریخچه','file'=>'history.php'],
            ['url'=>'/user/dashboard/v2/profile.php','icon'=>'fa-user','label'=>'پروفایل','file'=>'profile.php'],
            ['url'=>'/user/dashboard/v2/settings.php','icon'=>'fa-cog','label'=>'تنظیمات','file'=>'settings.php'],
        ];
        if (isAdmin()) $items[] = ['url'=>'/admin/','icon'=>'fa-shield-alt','label'=>'مدیریت','file'=>''];
        
        foreach ($items as $item):
            $active = ($item['file'] && $current === $item['file']) ? ' active' : '';
        ?>
            <a href="<?= $item['url'] ?>" class="nav-item<?= $active ?>"><i class="fas <?= $item['icon'] ?>"></i> <?= $item['label'] ?></a>
        <?php endforeach; ?>
        <a href="/logout.php" class="nav-item nav-item-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
    </nav>
</aside>