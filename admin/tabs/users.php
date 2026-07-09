<?php
$users = $db->query("SELECT * FROM users ORDER BY id DESC LIMIT 100")->fetchAll();
?>
<div class="admin-header"><h1>👥 کاربران سایت</h1><p>مدیریت کاربران ثبت‌نامی</p></div>
<table class="admin-table">
    <thead><tr><th>نام</th><th>موبایل</th><th>اعتبار</th><th>رنک</th><th>امتیاز</th><th>آخرین ورود</th></tr></thead>
    <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['full_name']) ?></td>
            <td><?= $u['phone'] ?></td>
            <td><?= $u['credits'] ?></td>
            <td><span class="rank-badge rank-<?= $u['rank'] ?>"><?= $u['rank'] ?></span></td>
            <td><?= $u['rank_score'] ?></td>
            <td><?= $u['last_login'] ? substr($u['last_login'],0,16) : '-' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>