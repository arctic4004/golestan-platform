<?php
session_start();
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$_SESSION['is_admin']) {
    header('Location: /login.php');
    exit;
}

$db = (new Database())->getConnection();

// حذف
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM customers WHERE id = ?")->execute([$_GET['delete']]);
    header('Location: /admin/crm/');
    exit;
}

// آپدیت رنک
if (isset($_GET['update_ranks'])) {
    $db->query("UPDATE customers SET rank = 
        CASE 
            WHEN rank_score >= 1000 THEN 'diamond'
            WHEN rank_score >= 500 THEN 'platinum'
            WHEN rank_score >= 150 THEN 'gold'
            WHEN rank_score >= 50 THEN 'silver'
            ELSE 'bronze'
        END");
    header('Location: /admin/crm/');
    exit;
}

$search = $_GET['search'] ?? '';
$rank_filter = $_GET['rank'] ?? '';
$tag = $_GET['tag'] ?? '';

$query = "SELECT c.*, u.full_name as user_name,
          (SELECT COUNT(*) FROM customer_notes WHERE customer_id = c.id) as note_count
          FROM customers c 
          LEFT JOIN users u ON c.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (c.full_name LIKE ? OR c.phone LIKE ? OR c.tags LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($rank_filter) {
    $query .= " AND c.rank = ?";
    $params[] = $rank_filter;
}
if ($tag) {
    $query .= " AND c.tags LIKE ?";
    $params[] = "%$tag%";
}

$query .= " ORDER BY c.rank_score DESC, c.last_visit DESC LIMIT 100";

$stmt = $db->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// آمار
$total = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$active = $db->query("SELECT COUNT(*) FROM customers WHERE last_visit > DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
$ranks = $db->query("SELECT rank, COUNT(*) as cnt FROM customers GROUP BY rank")->fetchAll();

$page_title = 'مشتریان | CRM';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
:root {
    --bronze: #cd7f32;
    --silver: #c0c0c0;
    --gold: #ffd700;
    --platinum: #e5e4e2;
    --diamond: #b9f2ff;
}
.crm-container { padding: 20px; max-width: 1400px; margin: 0 auto; }
.crm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
.crm-header h1 { font-size: 24px; margin: 0; }
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px; }
.stat-card { background: var(--card-bg, #fff); border-radius: 16px; padding: 18px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center; border-top: 3px solid #ddd; }
.stat-card.bronze { border-color: var(--bronze); }
.stat-card.silver { border-color: var(--silver); }
.stat-card.gold { border-color: var(--gold); }
.stat-card.platinum { border-color: var(--platinum); }
.stat-card.diamond { border-color: var(--diamond); }
.stat-card .num { font-size: 28px; font-weight: bold; }
.stat-card .label { color: #666; font-size: 12px; margin-top: 5px; }
.toolbar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
.toolbar input, .toolbar select { padding: 10px 15px; border: 1px solid #ddd; border-radius: 10px; font-size: 14px; }
.toolbar input { flex: 1; min-width: 200px; }
.btn { padding: 10px 18px; border: none; border-radius: 10px; cursor: pointer; font-size: 13px; transition: all 0.2s; text-decoration: none; display: inline-block; }
.btn-primary { background: #667eea; color: white; }
.btn-success { background: #27ae60; color: white; }
.btn-warning { background: #f39c12; color: white; }
.btn-sm { padding: 5px 10px; font-size: 11px; }
.customer-card { background: var(--card-bg, #fff); border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 15px; display: grid; grid-template-columns: auto 1fr auto auto; gap: 15px; align-items: center; transition: transform 0.2s; }
.customer-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.rank-badge { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; }
.rank-badge.bronze { background: #fdf0e0; }
.rank-badge.silver { background: #f0f0f0; }
.rank-badge.gold { background: #fffbe0; }
.rank-badge.platinum { background: #f5f5f5; }
.rank-badge.diamond { background: #e0f7fa; }
.customer-info h3 { margin: 0 0 5px 0; font-size: 16px; }
.customer-info h3 a { color: inherit; text-decoration: none; }
.customer-info h3 a:hover { color: #667eea; }
.customer-info .phone { color: #666; font-size: 13px; }
.customer-info .tags-row { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 5px; }
.tag { background: #e8f0fe; color: #667eea; padding: 2px 8px; border-radius: 12px; font-size: 11px; }
.customer-stats { text-align: center; }
.customer-stats .visits { font-size: 20px; font-weight: bold; color: #667eea; }
.customer-stats .visits-label { font-size: 11px; color: #999; }
.customer-actions { display: flex; gap: 5px; }
.empty-state { text-align: center; padding: 60px 20px; color: #999; }
.empty-state .icon { font-size: 60px; margin-bottom: 15px; }
.rank-score { font-size: 11px; color: #666; }
</style>

<div class="crm-container">
    <div class="crm-header">
        <h1>👥 مشتریان</h1>
        <div>
            <a href="/admin/crm/?update_ranks=1" class="btn btn-warning btn-sm">🔄 بروزرسانی رنک‌ها</a>
            <a href="/admin/crm/add.php" class="btn btn-primary">➕ مشتری جدید</a>
        </div>
    </div>
    
    <div class="stats-row">
        <div class="stat-card"><div class="num"><?= $total ?></div><div class="label">کل مشتریان</div></div>
        <?php foreach ($ranks as $r): ?>
        <div class="stat-card <?= $r['rank'] ?>">
            <div class="num"><?= $r['cnt'] ?></div>
            <div class="label"><?= $r['rank'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <form class="toolbar">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 جستجو...">
        <select name="rank">
            <option value="">همه رنک‌ها</option>
            <option value="diamond" <?= $rank_filter=='diamond'?'selected':'' ?>>👑 الماس</option>
            <option value="platinum" <?= $rank_filter=='platinum'?'selected':'' ?>>💎 پلاتینیوم</option>
            <option value="gold" <?= $rank_filter=='gold'?'selected':'' ?>>🥇 طلایی</option>
            <option value="silver" <?= $rank_filter=='silver'?'selected':'' ?>>🥈 نقره‌ای</option>
            <option value="bronze" <?= $rank_filter=='bronze'?'selected':'' ?>>🥉 برنزی</option>
        </select>
        <button type="submit" class="btn btn-primary">جستجو</button>
    </form>
    
    <?php if (empty($customers)): ?>
    <div class="empty-state">
        <div class="icon">👻</div>
        <h3>هیچ مشتری‌ای یافت نشد</h3>
        <a href="/admin/crm/add.php" class="btn btn-primary">➕ افزودن اولین مشتری</a>
    </div>
    <?php endif; ?>
    
    <?php foreach ($customers as $c): ?>
    <div class="customer-card">
        <div class="rank-badge <?= $c['rank'] ?>"><?= ['bronze'=>'🥉','silver'=>'🥈','gold'=>'🥇','platinum'=>'💎','diamond'=>'👑'][$c['rank']] ?></div>
        <div class="customer-info">
            <h3><a href="/admin/crm/view.php?id=<?= $c['id'] ?>"><?= htmlspecialchars($c['full_name']) ?></a></h3>
            <div class="phone">📱 <?= $c['phone'] ?: '---' ?></div>
            <div class="tags-row">
                <span class="rank-score">امتیاز: <?= $c['rank_score'] ?></span>
                <?php foreach (explode(',', $c['tags'] ?? '') as $t): if(empty(trim($t))) continue; ?>
                    <span class="tag"><?= trim($t) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="customer-stats">
            <div class="visits"><?= $c['visit_count'] ?></div>
            <div class="visits-label">بازدید</div>
            <div style="font-size:11px;color:#999"><?= $c['note_count'] ?> یادداشت</div>
        </div>
        <div class="customer-actions">
            <a href="/admin/crm/view.php?id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">👁️</a>
            <a href="/admin/crm/edit.php?id=<?= $c['id'] ?>" class="btn btn-success btn-sm">✏️</a>
            <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm" style="background:#e74c3c;color:white" onclick="return confirm('حذف شود؟')">🗑️</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>