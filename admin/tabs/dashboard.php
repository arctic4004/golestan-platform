<?php
// admin/tabs/dashboard.php
$recent_users = $db->query("SELECT * FROM users ORDER BY id DESC LIMIT 5")->fetchAll();
$recent_customers = $db->query("SELECT * FROM customers ORDER BY id DESC LIMIT 5")->fetchAll();
?>

<div class="admin-header">
    <h1>📊 داشبورد مدیریت</h1>
    <p>خلاصه وضعیت امروز سایت</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">👥</div>
        <div class="stat-info">
            <div class="num"><?= number_format($stats['users']) ?></div>
            <div class="label">کل کاربران</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">🟢</div>
        <div class="stat-info">
            <div class="num"><?= number_format($stats['active_users']) ?></div>
            <div class="label">کاربران فعال</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">👤</div>
        <div class="stat-info">
            <div class="num"><?= number_format($stats['customers']) ?></div>
            <div class="label">مشتریان CRM</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">💬</div>
        <div class="stat-info">
            <div class="num"><?= number_format($stats['messages']) ?></div>
            <div class="label">پیام‌های امروز</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">🛒</div>
        <div class="stat-info">
            <div class="num"><?= number_format($stats['orders']) ?></div>
            <div class="label">سفارشات هفته</div>
        </div>
    </div>
</div>

<h3 class="section-title">👥 کاربران جدید</h3>
<table class="admin-table">
    <thead><tr><th>نام</th><th>موبایل</th><th>اعتبار</th><th>رنک</th><th>تاریخ</th></tr></thead>
    <tbody>
        <?php foreach ($recent_users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['full_name']) ?></td>
            <td><?= $u['phone'] ?></td>
            <td><?= $u['credits'] ?></td>
            <td><span class="rank-badge rank-<?= $u['rank'] ?>"><?= $u['rank'] ?></span></td>
            <td><?= substr($u['created_at'] ?? '', 0, 10) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3 class="section-title">👤 مشتریان جدید CRM</h3>
<table class="admin-table">
    <thead><tr><th>نام</th><th>موبایل</th><th>تگ‌ها</th><th>رنک</th></tr></thead>
    <tbody>
        <?php foreach ($recent_customers as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['full_name']) ?></td>
            <td><?= $c['phone'] ?: '-' ?></td>
            <td><?= $c['tags'] ?: '-' ?></td>
            <td><span class="rank-badge rank-<?= $c['rank'] ?>"><?= $c['rank'] ?></span></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>