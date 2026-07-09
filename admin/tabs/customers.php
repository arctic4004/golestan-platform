<?php
// admin/tabs/customers.php
$search = $_GET['search'] ?? '';
$rank = $_GET['rank'] ?? '';

$query = "SELECT * FROM customers WHERE 1=1";
$params = [];
if ($search) { $query .= " AND (full_name LIKE ? OR phone LIKE ?)"; $params = ["%$search%", "%$search%"]; }
if ($rank) { $query .= " AND rank = ?"; $params[] = $rank; }
$query .= " ORDER BY id DESC LIMIT 100";

$stmt = $db->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<div class="admin-header" style="display:flex;justify-content:space-between;align-items:center">
    <div>
        <h1>👤 مشتریان CRM</h1>
        <p>مدیریت مشتریان حضوری</p>
    </div>
    <a href="/admin/crm/add.php" class="btn-xs" style="background:#6366f1;color:#fff;padding:10px 20px;font-size:14px;text-decoration:none;border-radius:10px">➕ مشتری جدید</a>
</div>

<form style="display:flex;gap:10px;margin-bottom:20px">
    <input type="hidden" name="tab" value="customers">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 جستجو..." style="flex:1;padding:10px;border:1px solid #ddd;border-radius:10px">
    <select name="rank" style="padding:10px;border:1px solid #ddd;border-radius:10px">
        <option value="">همه رنک‌ها</option>
        <option value="diamond" <?= $rank=='diamond'?'selected':'' ?>>👑 الماس</option>
        <option value="platinum" <?= $rank=='platinum'?'selected':'' ?>>💎 پلاتینیوم</option>
        <option value="gold" <?= $rank=='gold'?'selected':'' ?>>🥇 طلایی</option>
        <option value="silver" <?= $rank=='silver'?'selected':'' ?>>🥈 نقره‌ای</option>
        <option value="bronze" <?= $rank=='bronze'?'selected':'' ?>>🥉 برنزی</option>
    </select>
    <button type="submit" style="background:#6366f1;color:#fff;border:none;padding:10px 20px;border-radius:10px;cursor:pointer">جستجو</button>
</form>

<table class="admin-table">
    <thead><tr><th>نام</th><th>موبایل</th><th>تگ‌ها</th><th>رنک</th><th>امتیاز</th><th>بازدید</th><th>عملیات</th></tr></thead>
    <tbody>
        <?php foreach ($customers as $c): ?>
        <tr>
            <td><strong><?= htmlspecialchars($c['full_name']) ?></strong></td>
            <td><?= $c['phone'] ? '<a href="tel:'.$c['phone'].'">'.$c['phone'].'</a>' : '-' ?></td>
            <td><?= $c['tags'] ?: '-' ?></td>
            <td><span class="rank-badge rank-<?= $c['rank'] ?>"><?= $c['rank'] ?></span></td>
            <td><?= $c['rank_score'] ?></td>
            <td><?= $c['visit_count'] ?></td>
            <td class="actions">
                <a href="/admin/crm/view.php?id=<?= $c['id'] ?>" class="btn-xs btn-view">👁️</a>
                <a href="/admin/crm/edit.php?id=<?= $c['id'] ?>" class="btn-xs btn-edit">✏️</a>
                <a href="?tab=customers&delete=<?= $c['id'] ?>" class="btn-xs btn-danger" onclick="return confirm('حذف شود؟')">🗑️</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>