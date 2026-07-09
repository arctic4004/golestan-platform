<?php
session_start();
ob_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: /login.php');
    exit;
}

$db = (new Database())->getConnection();

$stats = [
    'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active_users' => $db->query("SELECT COUNT(*) FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
    'new_today' => $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
    'customers' => $db->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
    'messages' => $db->query("SELECT COUNT(*) FROM messages WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn(),
    'orders' => $db->query("SELECT COUNT(*) FROM orders WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
    'total_credits' => $db->query("SELECT SUM(credits) FROM users")->fetchColumn() ?? 0,
];

$rank_dist = $db->query("SELECT COALESCE(rank,'bronze') as r, COUNT(*) as cnt FROM users GROUP BY rank")->fetchAll();
$rank_colors = ['diamond'=>'#0e7490','platinum'=>'#6d28d9','gold'=>'#a16207','silver'=>'#475569','bronze'=>'#92400e'];
$rank_icons = ['diamond'=>'👑','platinum'=>'💎','gold'=>'🥇','silver'=>'🥈','bronze'=>'🥉'];

$tab = $_GET['tab'] ?? 'dashboard';
$allowed = ['dashboard', 'users', 'user_detail', 'user_edit', 'customers'];

if (in_array($tab, ['user_detail', 'user_edit']) && empty($_GET['id'])) {
    $tab = 'users';
}

if ($tab === 'customers' && isset($_GET['delete'])) {
    $db->prepare("DELETE FROM customers WHERE id = ?")->execute([$_GET['delete']]);
    header('Location: /admin/?tab=customers'); exit;
}
if ($tab === 'users' && isset($_GET['delete'])) {
    $db->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0")->execute([$_GET['delete']]);
    header('Location: /admin/?tab=users'); exit;
}
if ($tab === 'users' && isset($_GET['toggle'])) {
    $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND is_admin = 0")->execute([$_GET['toggle']]);
    header('Location: /admin/?tab=users'); exit;
}
if ($tab === 'user_edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = $_GET['id'] ?? 0;
    $db->prepare("UPDATE users SET full_name=?, phone=?, email=?, city=?, birth_date=?, bio=?, credits=?, wallet_balance=?, rank=?, rank_score=?, is_active=?, is_admin=? WHERE id=?")
       ->execute([$_POST['full_name'], $_POST['phone'], $_POST['email'], $_POST['city'], $_POST['birth_date']?:null, $_POST['bio'], $_POST['credits'], $_POST['wallet_balance']??0, $_POST['rank'], $_POST['rank_score']??0, isset($_POST['is_active'])?1:0, isset($_POST['is_admin'])?1:0, $uid]);
    if (!empty($_POST['phone'])) {
        $db->prepare("UPDATE customers SET full_name=?, phone=?, email=? WHERE phone=? OR user_id=?")->execute([$_POST['full_name'], $_POST['phone'], $_POST['email'], $_POST['phone'], $uid]);
    }
    header('Location: /admin/?tab=user_detail&id='.$uid.'&saved=1'); exit;
}

$page_title = 'پنل مدیریت | ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.admin-wrapper { display: flex; min-height: calc(100vh - 60px); }
.admin-sidebar { width: 260px; background: #0f172a; color: #e2e8f0; padding: 0; position: sticky; top: 60px; height: calc(100vh - 60px); overflow-y: auto; z-index: 100; }
.sidebar-header { padding: 24px 20px; border-bottom: 1px solid #1e293b; }
.sidebar-header h3 { font-size: 16px; margin: 0; }
.sidebar-header small { color: #64748b; font-size: 11px; display: block; margin-top: 4px; }
.sidebar-section { padding: 8px 20px 4px; font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700; }
.sidebar-menu { list-style: none; padding: 0; margin: 0; }
.sidebar-menu li { margin: 1px 10px; }
.sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 10px 14px; color: #94a3b8; text-decoration: none; border-radius: 10px; font-size: 13px; transition: all 0.15s; font-weight: 500; }
.sidebar-menu a:hover, .sidebar-menu a.active { background: #1e293b; color: #fff; }
.sidebar-menu a .icon { font-size: 16px; width: 22px; text-align: center; }
.sidebar-badge { background: #ef4444; color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 10px; margin-right: auto; }
.sidebar-footer { padding: 16px 20px; border-top: 1px solid #1e293b; }
.sidebar-footer a { color: #94a3b8; font-size: 12px; text-decoration: none; }
.admin-content { flex: 1; padding: 0; background: #f1f5f9; }
.admin-topbar { background: #fff; padding: 16px 28px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.admin-topbar h1 { font-size: 20px; color: #0f172a; margin: 0; }
.admin-topbar .breadcrumb { font-size: 12px; color: #64748b; }
.admin-topbar .breadcrumb a { color: #0ea5e9; }
.admin-inner { padding: 28px; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px; }
.stat-card { background: #fff; border-radius: 16px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; align-items: center; gap: 16px; transition: all 0.2s; border: 1px solid #f1f5f9; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
.stat-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
.stat-info .num { font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1; }
.stat-info .label { font-size: 12px; color: #64748b; margin-top: 4px; }
.stat-info .sublabel { font-size: 10px; color: #94a3b8; }
.card { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; overflow: hidden; }
.card-header { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.card-header h3 { font-size: 16px; margin: 0; }
.card-body { padding: 0; overflow-x: auto; }
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table th { background: #f8fafc; padding: 12px 16px; text-align: right; font-size: 11px; color: #64748b; font-weight: 700; white-space: nowrap; }
.admin-table td { padding: 12px 16px; border-top: 1px solid #f1f5f9; font-size: 13px; }
.admin-table tbody tr:hover { background: #f8fafc; }
.rank-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.rank-bronze { background: #fef3c7; color: #92400e; } .rank-silver { background: #f1f5f9; color: #475569; } .rank-gold { background: #fef9c3; color: #a16207; } .rank-platinum { background: #f5f3ff; color: #6d28d9; } .rank-diamond { background: #ecfeff; color: #0e7490; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; text-decoration: none; font-family: inherit; }
.btn-primary { background: #0ea5e9; color: #fff; } .btn-primary:hover { background: #0284c7; }
.btn-ghost { background: transparent; color: #64748b; border: 1px solid #e2e8f0; } .btn-ghost:hover { background: #f8fafc; }
.btn-xs { padding: 5px 10px; font-size: 11px; border-radius: 8px; }
.btn-xs.btn-view { background: #eff6ff; color: #3b82f6; } .btn-xs.btn-edit { background: #fefce8; color: #ca8a04; } .btn-xs.btn-danger { background: #fef2f2; color: #ef4444; }
.toolbar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.toolbar input, .toolbar select { padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; font-family: inherit; background: #fff; }
.toolbar input { flex: 1; min-width: 200px; }
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.detail-grid .card { padding: 20px; }
.detail-table { width: 100%; font-size: 13px; } .detail-table td { padding: 6px 0; } .detail-table td:first-child { color: #64748b; width: 100px; } .detail-table td:last-child { font-weight: 600; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px; color: #475569; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; font-family: inherit; background: #fff; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #0ea5e9; outline: none; }
.form-group.full { grid-column: 1 / -1; }
.checkbox-label { display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500; } .checkbox-label input { width: auto; }
.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; } .empty-state .icon { font-size: 60px; margin-bottom: 16px; }
.badge { padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; }
@media (max-width: 768px) { .admin-sidebar { display: none; } .detail-grid, .form-grid { grid-template-columns: 1fr; } .admin-inner { padding: 16px; } }
</style>

<div class="admin-wrapper">
    <div class="admin-sidebar">
        <div class="sidebar-header"><h3>⚙️ مدیریت</h3><small><?= SITE_NAME ?> | <?= jalali_date('Y/m/d') ?></small></div>
        <div class="sidebar-section">اصلی</div>
        <ul class="sidebar-menu">
            <li><a href="/admin/" class="<?= $tab=='dashboard'?'active':'' ?>"><span class="icon">📊</span> داشبورد</a></li>
        </ul>
        <div class="sidebar-section">مدیریت</div>
        <ul class="sidebar-menu">
            <li><a href="/admin/?tab=users" class="<?= in_array($tab,['users','user_detail','user_edit'])?'active':'' ?>"><span class="icon">👥</span> کاربران <span class="sidebar-badge"><?= $stats['users'] ?></span></a></li>
            <li><a href="/admin/?tab=customers" class="<?= $tab=='customers'?'active':'' ?>"><span class="icon">👤</span> مشتریان CRM</a></li>
        </ul>
        <div class="sidebar-footer"><a href="/">🏠 بازگشت به سایت</a></div>
    </div>
    
    <div class="admin-content">
        <?php if ($tab === 'dashboard'): ?>
        <div class="admin-topbar"><h1>📊 داشبورد</h1><div class="breadcrumb">خانه / داشبورد</div></div>
        <div class="admin-inner">
            <div class="stats-grid">
                <?php
                $cards = [
                    ['bg'=>'#eff6ff','icon'=>'👥','num'=>number_format($stats['users']),'label'=>'کل کاربران','sub'=>$stats['new_today'].' جدید امروز'],
                    ['bg'=>'#f0fdf4','icon'=>'🟢','num'=>number_format($stats['active_users']),'label'=>'کاربران فعال','sub'=>'۳۰ روز گذشته'],
                    ['bg'=>'#faf5ff','icon'=>'👤','num'=>number_format($stats['customers']),'label'=>'مشتریان CRM','sub'=>''],
                    ['bg'=>'#fff7ed','icon'=>'💬','num'=>number_format($stats['messages']),'label'=>'پیام امروز','sub'=>''],
                    ['bg'=>'#fef2f2','icon'=>'🛒','num'=>number_format($stats['orders']),'label'=>'سفارشات هفته','sub'=>''],
                    ['bg'=>'#fefce8','icon'=>'💰','num'=>number_format($stats['total_credits']),'label'=>'کل اعتبار','sub'=>''],
                ];
                foreach ($cards as $c):
                ?>
                <div class="stat-card">
                    <div class="stat-icon" style="background:<?= $c['bg'] ?>"><?= $c['icon'] ?></div>
                    <div class="stat-info"><div class="num"><?= $c['num'] ?></div><div class="label"><?= $c['label'] ?></div><?php if($c['sub']): ?><div class="sublabel"><?= $c['sub'] ?></div><?php endif; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!empty($rank_dist)): ?>
            <div class="card" style="margin-bottom:24px">
                <div class="card-header"><h3>🏆 توزیع رنک</h3></div>
                <div class="card-body" style="padding:20px;display:flex;gap:12px;flex-wrap:wrap;">
                    <?php foreach ($rank_dist as $r): if(empty($r['r'])) continue; ?>
                    <div style="flex:1;min-width:100px;text-align:center;padding:16px;border-radius:12px;background:#f8fafc;">
                        <div style="font-size:28px;"><?= $rank_icons[$r['r']] ?? '🥉' ?></div>
                        <div style="font-weight:800;font-size:18px;color:<?= $rank_colors[$r['r']] ?? '#92400e' ?>"><?= $r['cnt'] ?></div>
                        <div style="font-size:12px;color:#64748b;"><?= $r['r'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php $recent = $db->query("SELECT * FROM users ORDER BY id DESC LIMIT 5")->fetchAll(); ?>
            <div class="card">
                <div class="card-header"><h3>👥 کاربران جدید</h3><a href="/admin/?tab=users" class="btn btn-ghost btn-xs">همه</a></div>
                <div class="card-body">
                    <table class="admin-table">
                        <thead><tr><th>نام</th><th>موبایل</th><th>اعتبار</th><th>رنک</th><th>تاریخ</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent as $u): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                                <td><?= $u['phone'] ?></td>
                                <td><?= number_format($u['credits']) ?></td>
                                <td><span class="rank-badge rank-<?= $u['rank']??'bronze' ?>"><?= $u['rank']??'bronze' ?></span></td>
                                <td><?= jalali_date('Y/m/d', strtotime($u['created_at']??'now')) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php elseif ($tab === 'users'): ?>
        <?php
        $search = $_GET['search'] ?? ''; $rank_f = $_GET['rank'] ?? ''; $status_f = $_GET['status'] ?? '';
        $q = "SELECT u.*, (SELECT COUNT(*) FROM messages WHERE user_id=u.id) as msg_c, c.id as cid FROM users u LEFT JOIN customers c ON c.phone=u.phone OR c.user_id=u.id WHERE 1=1";
        $p = [];
        if ($search) { $q .= " AND (u.full_name LIKE ? OR u.phone LIKE ?)"; $p = ["%$search%", "%$search%"]; }
        if ($rank_f) { $q .= " AND u.rank = ?"; $p[] = $rank_f; }
        if ($status_f === 'active') { $q .= " AND u.is_active = 1"; }
        elseif ($status_f === 'inactive') { $q .= " AND u.is_active = 0"; }
        $q .= " ORDER BY u.id DESC LIMIT 50";
        $stmt = $db->prepare($q); $stmt->execute($p); $users = $stmt->fetchAll();
        ?>
        <div class="admin-topbar"><h1>👥 کاربران (<?= $stats['users'] ?>)</h1><div class="breadcrumb"><a href="/admin/">خانه</a> / کاربران</div></div>
        <div class="admin-inner">
            <form class="toolbar">
                <input type="hidden" name="tab" value="users">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 نام یا موبایل...">
                <select name="rank"><option value="">همه رنک‌ها</option>
                    <?php foreach (['diamond'=>'👑 الماس','platinum'=>'💎 پلاتین','gold'=>'🥇 طلایی','silver'=>'🥈 نقره','bronze'=>'🥉 برنز'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= $rank_f==$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
                </select>
                <select name="status"><option value="">همه</option><option value="active" <?= $status_f=='active'?'selected':'' ?>>🟢 فعال</option><option value="inactive" <?= $status_f=='inactive'?'selected':'' ?>>🔴 غیرفعال</option></select>
                <button type="submit" class="btn btn-primary btn-xs">🔍</button>
            </form>
            <div class="card"><div class="card-body">
                <table class="admin-table">
                    <thead><tr><th>#</th><th>نام</th><th>موبایل</th><th>اعتبار</th><th>رنک</th><th>پیام</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><strong><?= htmlspecialchars($u['full_name']) ?></strong><?= $u['is_admin']?' <span class="badge" style="background:#f59e0b;color:#fff">ادمین</span>':'' ?><?= $u['cid']?' <span class="badge" style="background:#e0f2fe;color:#0369a1">CRM</span>':'' ?></td>
                            <td><?= $u['phone']?:'-' ?></td>
                            <td><?= number_format($u['credits']) ?></td>
                            <td><span class="rank-badge rank-<?= $u['rank']??'bronze' ?>"><?= $u['rank']??'bronze' ?></span></td>
                            <td><?= $u['msg_c'] ?></td>
                            <td><a href="?tab=users&toggle=<?= $u['id'] ?>"><?= $u['is_active']?'🟢':'🔴' ?></a></td>
                            <td>
                                <a href="/admin/?tab=user_detail&id=<?= $u['id'] ?>" class="btn-xs btn-view">👁️</a>
                                <a href="/admin/?tab=user_edit&id=<?= $u['id'] ?>" class="btn-xs btn-edit">✏️</a>
                                <?php if (!$u['is_admin']): ?><a href="?tab=users&delete=<?= $u['id'] ?>" class="btn-xs btn-danger" onclick="return confirm('حذف؟')">🗑️</a><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>
        </div>
        
        <?php elseif ($tab === 'user_detail'): ?>
        <?php
        $uid = $_GET['id']??0;
        $u = $db->prepare("SELECT u.*, (SELECT COUNT(*) FROM messages WHERE user_id=u.id) as msg_c, c.id as cid, c.tags as ctags, c.visit_count FROM users u LEFT JOIN customers c ON c.phone=u.phone OR c.user_id=u.id WHERE u.id=?");
        $u->execute([$uid]); $user = $u->fetch();
        if (!$user): ?><div class="admin-inner"><p>کاربر یافت نشد.</p></div>
        <?php else:
        $msgs = $db->prepare("SELECT * FROM messages WHERE user_id=? ORDER BY created_at DESC LIMIT 5"); $msgs->execute([$uid]);
        ?>
        <div class="admin-topbar"><h1>👤 <?= htmlspecialchars($user['full_name']) ?></h1><div class="breadcrumb"><a href="/admin/">خانه</a> / <a href="/admin/?tab=users">کاربران</a> / <?= htmlspecialchars($user['full_name']) ?><?= isset($_GET['saved'])?' <span style="color:#10b981">✅ ذخیره شد</span>':'' ?></div></div>
        <div class="admin-inner">
            <div style="margin-bottom:16px"><a href="/admin/?tab=user_edit&id=<?= $user['id'] ?>" class="btn btn-primary">✏️ ویرایش</a> <a href="/admin/?tab=users" class="btn btn-ghost">← بازگشت</a></div>
            <div class="detail-grid">
                <div class="card"><h3>📋 اطلاعات</h3>
                    <table class="detail-table">
                        <tr><td>نام</td><td><?= htmlspecialchars($user['full_name']) ?></td></tr>
                        <tr><td>موبایل</td><td><?= $user['phone']?:'-' ?></td></tr>
                        <tr><td>ایمیل</td><td><?= $user['email']?:'-' ?></td></tr>
                        <tr><td>شهر</td><td><?= $user['city']?:'-' ?></td></tr>
                        <tr><td>تولد</td><td><?= $user['birth_date']?:'-' ?></td></tr>
                        <tr><td>عضویت</td><td><?= jalali_date('Y/m/d', strtotime($user['created_at']??'now')) ?></td></tr>
                    </table>
                </div>
                <div class="card"><h3>📊 آمار</h3>
                    <table class="detail-table">
                        <tr><td>اعتبار</td><td><strong><?= number_format($user['credits']) ?></strong></td></tr>
                        <tr><td>کیف پول</td><td><?= number_format($user['wallet_balance']??0) ?> ت</td></tr>
                        <tr><td>رنک</td><td><span class="rank-badge rank-<?= $user['rank']??'bronze' ?>"><?= $user['rank']??'bronze' ?></span></td></tr>
                        <tr><td>امتیاز</td><td><?= $user['rank_score']??0 ?></td></tr>
                        <tr><td>پیام‌ها</td><td><?= $user['msg_c'] ?></td></tr>
                        <tr><td>لاگین</td><td><?= $user['login_count']??0 ?></td></tr>
                        <tr><td>آخرین</td><td><?= $user['last_login'] ? jalali_date('Y/m/d H:i', strtotime($user['last_login'])) : '-' ?></td></tr>
                        <tr><td>وضعیت</td><td><?= $user['is_active']?'🟢 فعال':'🔴 غیرفعال' ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php elseif ($tab === 'user_edit'): ?>
        <?php
        $uid = $_GET['id']??0;
        $u = $db->prepare("SELECT * FROM users WHERE id=?"); $u->execute([$uid]); $user = $u->fetch();
        if (!$user): ?><div class="admin-inner"><p>کاربر یافت نشد.</p></div>
        <?php else: ?>
        <div class="admin-topbar"><h1>✏️ <?= htmlspecialchars($user['full_name']) ?></h1><div class="breadcrumb"><a href="/admin/">خانه</a> / <a href="/admin/?tab=users">کاربران</a> / ویرایش</div></div>
        <div class="admin-inner">
            <div class="card" style="max-width:700px;padding:24px">
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group"><label>👤 نام</label><input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required></div>
                        <div class="form-group"><label>📱 موبایل</label><input type="text" name="phone" value="<?= $user['phone'] ?>"></div>
                        <div class="form-group"><label>📧 ایمیل</label><input type="email" name="email" value="<?= $user['email'] ?>"></div>
                        <div class="form-group"><label>🏙️ شهر</label><input type="text" name="city" value="<?= $user['city'] ?>"></div>
                        <div class="form-group"><label>🎂 تولد</label><input type="date" name="birth_date" value="<?= $user['birth_date'] ?>"></div>
                        <div class="form-group"><label>⭐ رنک</label><select name="rank"><?php foreach (['diamond'=>'👑 الماس','platinum'=>'💎 پلاتین','gold'=>'🥇 طلایی','silver'=>'🥈 نقره','bronze'=>'🥉 برنز'] as $k=>$v): ?><option value="<?= $k ?>" <?= ($user['rank']??'bronze')==$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
                        <div class="form-group"><label>💰 اعتبار</label><input type="number" name="credits" value="<?= $user['credits'] ?>"></div>
                        <div class="form-group"><label>🏆 امتیاز</label><input type="number" name="rank_score" value="<?= $user['rank_score']??0 ?>"></div>
                        <div class="form-group"><label>💳 کیف پول</label><input type="number" name="wallet_balance" value="<?= $user['wallet_balance']??0 ?>"></div>
                        <div class="form-group full"><label>📝 بیو</label><textarea name="bio" rows="2"><?= htmlspecialchars($user['bio']??'') ?></textarea></div>
                        <div class="form-group"><label class="checkbox-label"><input type="checkbox" name="is_active" <?= $user['is_active']?'checked':'' ?>> 🟢 فعال</label></div>
                        <div class="form-group"><label class="checkbox-label"><input type="checkbox" name="is_admin" <?= $user['is_admin']?'checked':'' ?>> 👑 ادمین</label></div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:20px">💾 ذخیره</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <?php elseif ($tab === 'customers'): ?>
        <?php
        $search = $_GET['search'] ?? ''; $rank = $_GET['rank'] ?? '';
        $q = "SELECT * FROM customers WHERE 1=1"; $p = [];
        if ($search) { $q .= " AND (full_name LIKE ? OR phone LIKE ?)"; $p = ["%$search%", "%$search%"]; }
        if ($rank) { $q .= " AND rank = ?"; $p[] = $rank; }
        $q .= " ORDER BY id DESC LIMIT 50";
        $stmt = $db->prepare($q); $stmt->execute($p); $customers = $stmt->fetchAll();
        ?>
        <div class="admin-topbar"><h1>👤 مشتریان CRM</h1><div class="breadcrumb"><a href="/admin/">خانه</a> / مشتریان</div></div>
        <div class="admin-inner">
            <div style="display:flex;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
                <form class="toolbar" style="margin:0"><input type="hidden" name="tab" value="customers"><input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 جستجو..."><select name="rank"><option value="">همه</option><?php foreach (['diamond'=>'👑','platinum'=>'💎','gold'=>'🥇','silver'=>'🥈','bronze'=>'🥉'] as $k=>$v): ?><option value="<?= $k ?>" <?= $rank==$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select><button type="submit" class="btn btn-primary btn-xs">🔍</button></form>
                <a href="/admin/crm/add.php" class="btn btn-primary">➕ جدید</a>
            </div>
            <div class="card"><div class="card-body">
                <table class="admin-table">
                    <thead><tr><th>نام</th><th>موبایل</th><th>تگ‌ها</th><th>رنک</th><th>بازدید</th><th>عملیات</th></tr></thead>
                    <tbody>
                        <?php foreach ($customers as $c): ?>
                        <tr><td><strong><?= htmlspecialchars($c['full_name']) ?></strong></td><td><?= $c['phone']?:'-' ?></td><td><?= $c['tags']?:'-' ?></td><td><span class="rank-badge rank-<?= $c['rank'] ?>"><?= $c['rank'] ?></span></td><td><?= $c['visit_count'] ?></td>
                        <td><a href="/admin/crm/view.php?id=<?= $c['id'] ?>" class="btn-xs btn-view">👁️</a><a href="/admin/crm/edit.php?id=<?= $c['id'] ?>" class="btn-xs btn-edit">✏️</a><a href="?tab=customers&delete=<?= $c['id'] ?>" class="btn-xs btn-danger" onclick="return confirm('حذف؟')">🗑️</a></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>
        </div>
        
        <?php else: ?>
        <div class="admin-topbar"><h1>📄 <?= $tab ?></h1></div>
        <div class="admin-inner"><div class="empty-state"><div class="icon">🚧</div><h3>به زودی...</h3></div></div>
        <?php endif; ?>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
ob_end_flush();