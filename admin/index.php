<?php
// admin/index.php - پنل مدیریت کاملاً حرفه‌ای
if (session_status() === PHP_SESSION_NONE) session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('/login.php');

$db = (new Database())->getConnection();

// =============================================
// پردازش عملیات POST
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tab = $_POST['tab'] ?? 'dashboard';
    
    if ($action === 'toggle_user') {
        $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?")->execute([$_POST['user_id']]);
    } elseif ($action === 'edit_user') {
        $db->prepare("UPDATE users SET full_name=?, email=?, credits=?, wallet_balance=?, is_admin=? WHERE id=?")
           ->execute([$_POST['full_name'], $_POST['email'], (int)$_POST['credits'], (int)$_POST['wallet_balance'], isset($_POST['is_admin'])?1:0, $_POST['user_id']]);
    } elseif ($action === 'add_product') {
        $db->prepare("INSERT INTO products (name, description, price, type, `condition`, stock, category) VALUES (?,?,?,?,?,?,?)")
           ->execute([$_POST['name'], $_POST['description'], (int)$_POST['price'], $_POST['type'], $_POST['condition']??'new', (int)$_POST['stock'], $_POST['category']]);
    } elseif ($action === 'update_order') {
        $db->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$_POST['status'], $_POST['order_id']]);
    } elseif ($action === 'update_settings') {
        foreach (['deepseek_api_key','max_free_credits','rate_limit_per_hour'] as $k) {
            if (isset($_POST['settings'][$k])) {
                $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")
                   ->execute([$k, $_POST['settings'][$k], $_POST['settings'][$k]]);
            }
        }
    } elseif ($action === 'add_todo') {
        $db->prepare("INSERT INTO todos (user_id, title, priority) VALUES (?,?,?)")->execute([$_SESSION['user_id'], $_POST['title'], $_POST['priority']??'medium']);
    } elseif ($action === 'toggle_todo') {
        $db->query("UPDATE todos SET status = IF(status='completed','pending','completed'), completed_at = IF(status='completed',NULL,NOW()) WHERE id = {$_POST['todo_id']}");
    }
    
    redirect('/admin/?tab=' . $tab);
}

// =============================================
// پارامترها
// =============================================
$tab = $_GET['tab'] ?? 'dashboard';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$user_id = $_GET['user_id'] ?? 0;

$allowed_sorts = ['id', 'created_at', 'full_name', 'phone', 'credits', 'wallet_balance', 'total', 'updated_at', 'price', 'name'];
if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// =============================================
// آمار کلی
// =============================================
$stats = [
    'users'    => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active'   => $db->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn(),
    'messages' => $db->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
    'chats'    => $db->query("SELECT COUNT(*) FROM conversations")->fetchColumn(),
    'images'   => count(glob($_SERVER['DOCUMENT_ROOT'].'/uploads/gen_*') ?: []) + count(glob($_SERVER['DOCUMENT_ROOT'].'/uploads/ai_*') ?: []),
    'products' => $db->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn(),
    'orders'   => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'revenue'  => $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('paid','shipped','delivered')")->fetchColumn(),
    'pending'  => $db->query("SELECT COUNT(*) FROM service_requests WHERE status='pending'")->fetchColumn(),
];

// =============================================
// داده‌ها بر اساس تب
// =============================================
$data = [];
$total = 0;
$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page_num - 1) * $per_page;

switch ($tab) {
    case 'users':
        $q = "SELECT * FROM users WHERE 1=1";
        $p = [];
        if ($search) { $q .= " AND (full_name LIKE ? OR phone LIKE ? OR email LIKE ?)"; $p = ["%$search%","%$search%","%$search%"]; }
        $count_q = str_replace('SELECT *', 'SELECT COUNT(*)', $q);
        $stmt = $db->prepare($count_q);
        $stmt->execute($p);
        $total = $stmt->fetchColumn();
        $q .= " ORDER BY `$sort` $order LIMIT $per_page OFFSET $offset";
        $stmt = $db->prepare($q);
        $stmt->execute($p);
        $data = $stmt->fetchAll();
        break;
        
    case 'user_detail':
        $stmt = $db->prepare("SELECT * FROM users WHERE id=?");
        $stmt->execute([$user_id]);
        $data = $stmt->fetch();
        if (!$data) { redirect('/admin/?tab=users'); }
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM conversations WHERE user_id=?");
        $stmt->execute([$user_id]);
        $user_stats['chats'] = $stmt->fetchColumn();
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE user_id=?");
        $stmt->execute([$user_id]);
        $user_stats['messages'] = $stmt->fetchColumn();
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
        $stmt->execute([$user_id]);
        $user_stats['orders'] = $stmt->fetchColumn();
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE user_id=?");
        $stmt->execute([$user_id]);
        $user_stats['tasks'] = $stmt->fetchColumn();
        
        $stmt = $db->prepare("SELECT * FROM conversations WHERE user_id=? ORDER BY updated_at DESC LIMIT 20");
        $stmt->execute([$user_id]);
        $user_chats = $stmt->fetchAll();
        
        $stmt = $db->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$user_id]);
        $user_orders = $stmt->fetchAll();
        
        $stmt = $db->prepare("SELECT * FROM activity_logs WHERE user_id=? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$user_id]);
        $user_logs = $stmt->fetchAll();
        break;
        
    case 'products':
        $total = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $data = $db->query("SELECT * FROM products ORDER BY `$sort` $order LIMIT $per_page OFFSET $offset")->fetchAll();
        break;
        
    case 'orders_list':
        $total = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $data = $db->query("SELECT o.*, u.full_name, u.phone FROM orders o JOIN users u ON o.user_id=u.id ORDER BY `$sort` $order LIMIT $per_page OFFSET $offset")->fetchAll();
        break;
        
    case 'chats':
        $total = $db->query("SELECT COUNT(*) FROM conversations")->fetchColumn();
        $data = $db->query("SELECT c.*, u.full_name, (SELECT COUNT(*) FROM messages WHERE conversation_id=c.id) as msg_count FROM conversations c LEFT JOIN users u ON c.user_id=u.id ORDER BY `$sort` $order LIMIT $per_page OFFSET $offset")->fetchAll();
        break;
        
    case 'logs':
        $total = $db->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
        $data = $db->query("SELECT al.*, u.full_name FROM activity_logs al LEFT JOIN users u ON al.user_id=u.id ORDER BY `$sort` $order LIMIT $per_page OFFSET $offset")->fetchAll();
        break;
        
    case 'settings':
        $data = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        break;
}

$todos = $db->query("SELECT * FROM todos WHERE user_id={$_SESSION['user_id']} ORDER BY FIELD(status,'pending','in_progress','completed'), FIELD(priority,'high','medium','low')")->fetchAll();
$recent_logs = $db->query("SELECT al.*, u.full_name FROM activity_logs al LEFT JOIN users u ON al.user_id=u.id ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

$page_title = 'پنل مدیریت | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
.admin-wrapper { display: flex; min-height: calc(100vh - 60px); margin-top: 60px; background: var(--bg-secondary); }
.admin-sidebar { width: 250px; min-width: 250px; background: var(--bg-card); border-left: 1px solid var(--border); padding: 24px 0; position: sticky; top: 60px; height: calc(100vh - 60px); overflow-y: auto; }
.admin-sidebar-header { padding: 0 20px 16px; border-bottom: 1px solid var(--border); margin-bottom: 12px; display: flex; align-items: center; gap: 8px; font-weight: 700; color: var(--primary); font-size: 1rem; }
.admin-sidebar a { display: flex; align-items: center; gap: 10px; padding: 10px 20px; color: var(--text-secondary); font-size: 0.9rem; transition: all 0.15s; border-right: 3px solid transparent; }
.admin-sidebar a:hover { background: var(--bg-hover); color: var(--text-primary); }
.admin-sidebar a.active { background: var(--primary-light); color: var(--primary); border-right-color: var(--primary); font-weight: 600; }
.admin-main { flex: 1; padding: 24px 28px; overflow-y: auto; }

.admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.admin-header h1 { font-size: 1.6rem; font-weight: 800; }
.breadcrumb { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px; }
.breadcrumb a { color: var(--primary); }

.stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; margin-bottom: 28px; }
.stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 14px; padding: 20px; text-align: center; transition: all 0.2s; }
.stat-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); border-color: var(--primary); }
.stat-card .icon { font-size: 1.8rem; margin-bottom: 8px; }
.stat-card .num { font-size: 1.8rem; font-weight: 800; color: var(--text-primary); }
.stat-card .lbl { font-size: 0.8rem; color: var(--text-muted); margin-top: 2px; }

.table-wrapper { background: var(--bg-card); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
.table-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border); }
.table-header h3 { font-size: 1rem; font-weight: 700; }
.table-header .search-box { display: flex; gap: 8px; }
.table-header input { padding: 8px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-input); color: var(--text-primary); font-family: var(--font); font-size: 0.85rem; width: 220px; }
.table-responsive { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
table th { background: var(--bg-tertiary); padding: 12px 16px; font-size: 0.8rem; font-weight: 700; text-align: right; white-space: nowrap; }
table td { padding: 10px 16px; border-bottom: 1px solid var(--border-light); font-size: 0.85rem; }
table tr:hover td { background: rgba(99,102,241,0.02); }
.badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-info { background: #dbeafe; color: #1e40af; }

.info-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 14px; padding: 20px; margin-bottom: 16px; }
.info-card h3 { font-size: 1rem; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }

.pagination { display: flex; gap: 4px; justify-content: center; padding: 16px; }
.pagination a, .pagination span { padding: 8px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.85rem; color: var(--text-secondary); text-decoration: none; transition: all 0.15s; }
.pagination a:hover { background: var(--bg-hover); color: var(--primary); }
.pagination .active { background: var(--primary); color: white; border-color: var(--primary); }

.mobile-menu-btn { display: none; }
@media (max-width: 768px) {
    .admin-sidebar { position: fixed; right: -280px; z-index: 200; transition: right 0.3s; box-shadow: var(--shadow-xl); }
    .admin-sidebar.open { right: 0; }
    .mobile-menu-btn { display: block; margin-bottom: 16px; }
    .admin-main { padding: 16px; }
    .grid-2, .grid-3 { grid-template-columns: 1fr; }
    .table-header { flex-direction: column; gap: 8px; }
    .table-header input { width: 100%; }
}

.todo-item { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--border-light); }
.todo-item:last-child { border-bottom: none; }
.todo-check { background: none; border: none; cursor: pointer; font-size: 1.1rem; }
.todo-title { flex: 1; }
.todo-title.done { text-decoration: line-through; opacity: 0.6; }
</style>

<div class="admin-wrapper">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-header">🛡️ پنل مدیریت</div>
        <a href="?tab=dashboard" class="<?=$tab=='dashboard'?'active':''?>"><span>📊</span> داشبورد</a>
        <a href="?tab=users" class="<?=$tab=='users'?'active':''?>"><span>👥</span> کاربران</a>
        <a href="?tab=products" class="<?=$tab=='products'?'active':''?>"><span>📦</span> محصولات</a>
        <a href="?tab=orders_list" class="<?=$tab=='orders_list'?'active':''?>"><span>🛒</span> سفارشات</a>
        <a href="?tab=chats" class="<?=$tab=='chats'?'active':''?>"><span>💬</span> چت‌ها</a>
        <a href="?tab=logs" class="<?=$tab=='logs'?'active':''?>"><span>📜</span> لاگ فعالیت</a>
        <a href="?tab=settings" class="<?=$tab=='settings'?'active':''?>"><span>⚙️</span> تنظیمات</a>
        <a href="/" target="_blank"><span>🌐</span> مشاهده سایت</a>
    </aside>

    <main class="admin-main">
        <button class="mobile-menu-btn btn btn-outline btn-sm" onclick="document.getElementById('adminSidebar').classList.toggle('open')">☰ منو</button>
        
        <!-- ==================== داشبورد ==================== -->
        <?php if ($tab === 'dashboard'): ?>
        <div class="admin-header">
            <div>
                <div class="breadcrumb"><a href="?tab=dashboard">پنل مدیریت</a> / داشبورد</div>
                <h1>📊 نمای کلی</h1>
            </div>
            <span style="color:var(--text-muted);"><?=date('l d F Y')?></span>
        </div>

        <div class="stats-grid">
            <?php foreach ([
                ['👥','کل کاربران',$stats['users'],'users'],
                ['✅','کاربران فعال',$stats['active'],'users'],
                ['💬','کل پیام‌ها',number_format($stats['messages']),'chats'],
                ['💭','چت‌ها',$stats['chats'],'chats'],
                ['🖼️','تصاویر',$stats['images'],'images'],
                ['📦','محصولات',$stats['products'],'products'],
                ['🛒','سفارشات',$stats['orders'],'orders_list'],
                ['💰','درآمد',number_format($stats['revenue']).' تومان','orders_list'],
                ['📋','در انتظار',$stats['pending'],'requests'],
            ] as $s): ?>
            <a href="?tab=<?=$s[3]?>" style="text-decoration:none;color:inherit;">
                <div class="stat-card"><div class="icon"><?=$s[0]?></div><div class="num"><?=$s[2]?></div><div class="lbl"><?=$s[1]?></div></div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="grid-2">
            <div class="info-card">
                <h3>📝 وظایف امروز</h3>
                <form method="POST" style="display:flex;gap:6px;margin-bottom:12px;">
                    <input type="hidden" name="action" value="add_todo"><input type="hidden" name="tab" value="dashboard">
                    <input type="text" name="title" placeholder="وظیفه جدید..." required style="flex:1;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg-input);color:var(--text-primary);font-family:var(--font);">
                    <button type="submit" class="btn btn-primary btn-sm">+</button>
                </form>
                <?php foreach ($todos as $t): ?>
                <div class="todo-item">
                    <form method="POST" style="display:contents;">
                        <input type="hidden" name="action" value="toggle_todo"><input type="hidden" name="tab" value="dashboard">
                        <input type="hidden" name="todo_id" value="<?=$t['id']?>">
                        <button type="submit" class="todo-check" style="color:<?=$t['status']=='completed'?'var(--primary)':'var(--text-muted)'?>;">
                            <i class="fas fa-<?=$t['status']=='completed'?'check-circle':'circle'?>"></i>
                        </button>
                    </form>
                    <span class="todo-title <?=$t['status']=='completed'?'done':''?>"><?=sanitize($t['title'])?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="info-card">
                <h3>🕒 آخرین فعالیت‌ها</h3>
                <?php foreach ($recent_logs as $l): ?>
                <div style="padding:6px 0;border-bottom:1px solid var(--border-light);font-size:0.85rem;">
                    <strong><?=sanitize($l['full_name']??'سیستم')?></strong>
                    <span style="color:var(--text-secondary);"> • <?=$l['description']?></span>
                    <span style="float:left;color:var(--text-muted);font-size:0.75rem;"><?=timeAgo($l['created_at'])?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ==================== کاربران ==================== -->
        <?php elseif ($tab === 'users'): ?>
        <div class="admin-header">
            <div>
                <div class="breadcrumb"><a href="?tab=dashboard">پنل مدیریت</a> / کاربران</div>
                <h1>👥 مدیریت کاربران</h1>
            </div>
        </div>

        <div class="table-wrapper">
            <div class="table-header">
                <h3>لیست کاربران (<?=$total?>)</h3>
                <form method="GET" class="search-box">
                    <input type="hidden" name="tab" value="users">
                    <input type="text" name="search" value="<?=htmlspecialchars($search)?>" placeholder="🔍 جستجوی نام، موبایل یا ایمیل...">
                    <button type="submit" class="btn btn-primary btn-sm">جستجو</button>
                </form>
            </div>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>#</th><th>نام</th><th>موبایل</th><th>ایمیل</th><th>اعتبار</th><th>کیف پول</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                    <tbody>
                        <?php if (empty($data)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:40px;">😔 هیچ کاربری یافت نشد</td></tr>
                        <?php else: foreach ($data as $u): ?>
                        <tr>
                            <td><?=$u['id']?></td>
                            <td><a href="?tab=user_detail&user_id=<?=$u['id']?>" style="color:var(--primary);font-weight:600;"><?=sanitize($u['full_name'])?></a></td>
                            <td dir="ltr" style="text-align:left;"><?=$u['phone']?></td>
                            <td><?=$u['email'] ?: '<span style="color:var(--text-muted);">---</span>'?></td>
                            <td><?=number_format($u['credits'])?></td>
                            <td><?=number_format($u['wallet_balance']??0)?> تومان</td>
                            <td><span class="badge <?=$u['is_active']?'badge-success':'badge-danger'?>"><?=$u['is_active']?'فعال':'غیرفعال'?></span></td>
                            <td>
                                <a href="?tab=user_detail&user_id=<?=$u['id']?>" class="btn btn-sm btn-outline">✏️</a>
                                <form method="POST" style="display:contents;">
                                    <input type="hidden" name="action" value="toggle_user"><input type="hidden" name="tab" value="users">
                                    <input type="hidden" name="user_id" value="<?=$u['id']?>">
                                    <button type="submit" class="btn btn-sm <?=$u['is_active']?'btn-outline':'btn-primary'?>"><?=$u['is_active']?'غیرفعال':'فعال'?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total > $per_page): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= ceil($total / $per_page); $i++): ?>
                <a href="?tab=users&page=<?=$i?>&search=<?=urlencode($search)?>" class="<?=$i==$page_num?'active':''?>"><?=$i?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ==================== جزئیات کاربر ==================== -->
        <?php elseif ($tab === 'user_detail'): ?>
        <div class="admin-header">
            <div>
                <div class="breadcrumb"><a href="?tab=dashboard">پنل مدیریت</a> / <a href="?tab=users">کاربران</a> / <?=sanitize($data['full_name'])?></div>
                <h1>👤 <?=sanitize($data['full_name'])?></h1>
            </div>
        </div>

        <div class="grid-2">
            <div class="info-card">
                <h3>📝 ویرایش اطلاعات</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="edit_user"><input type="hidden" name="tab" value="user_detail">
                    <input type="hidden" name="user_id" value="<?=$data['id']?>">
                    <div class="form-group"><label>نام کامل</label><input type="text" name="full_name" value="<?=sanitize($data['full_name'])?>"></div>
                    <div class="form-group"><label>ایمیل</label><input type="email" name="email" value="<?=$data['email']??''?>"></div>
                    <div class="grid-2">
                        <div class="form-group"><label>اعتبار</label><input type="number" name="credits" value="<?=$data['credits']?>"></div>
                        <div class="form-group"><label>کیف پول (تومان)</label><input type="number" name="wallet_balance" value="<?=$data['wallet_balance']??0?>"></div>
                    </div>
                    <label style="display:flex;align-items:center;gap:6px;margin-bottom:12px;">
                        <input type="checkbox" name="is_admin" <?=$data['is_admin']?'checked':''?>> دسترسی ادمین
                    </label>
                    <button type="submit" class="btn btn-primary">💾 ذخیره تغییرات</button>
                </form>
            </div>

            <div class="info-card">
                <h3>📊 آمار کاربر</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <?php foreach ([['💬','چت‌ها',$user_stats['chats']],['📨','پیام‌ها',$user_stats['messages']],['🛒','سفارشات',$user_stats['orders']],['📋','تسک‌ها',$user_stats['tasks']]] as $s): ?>
                    <div style="text-align:center;background:var(--bg-secondary);border-radius:10px;padding:12px;">
                        <div style="font-size:1.4rem;font-weight:700;color:var(--primary);"><?=$s[2]?></div>
                        <div style="font-size:0.8rem;color:var(--text-muted);"><?=$s[0]?> <?=$s[1]?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p style="margin-top:12px;">📅 عضویت: <strong><?=date('Y/m/d', strtotime($data['created_at']))?></strong></p>
                <p>🕐 آخرین ورود: <strong><?=$data['last_login'] ? date('Y/m/d H:i', strtotime($data['last_login'])) : '---'?></strong></p>
            </div>
        </div>

        <!-- ==================== محصولات ==================== -->
        <?php elseif ($tab === 'products'): ?>
        <div class="admin-header"><div><div class="breadcrumb"><a href="?tab=dashboard">پنل مدیریت</a> / محصولات</div><h1>📦 مدیریت محصولات</h1></div></div>
        <div class="info-card">
            <h3>➕ افزودن محصول</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_product"><input type="hidden" name="tab" value="products">
                <div class="grid-3">
                    <input type="text" name="name" placeholder="نام محصول *" required>
                    <input type="number" name="price" placeholder="قیمت (تومان) *" required>
                    <select name="type"><option value="service">خدمات</option><option value="goods">کالا</option></select>
                </div>
                <textarea name="description" rows="2" placeholder="توضیحات..." style="width:100%;margin:8px 0;"></textarea>
                <button type="submit" class="btn btn-primary btn-sm">افزودن</button>
            </form>
        </div>
        <div class="table-wrapper"><div class="table-responsive"><table>
            <tr><th>#</th><th>نام</th><th>قیمت</th><th>نوع</th><th>موجودی</th></tr>
            <?php foreach ($data as $p): ?>
            <tr><td><?=$p['id']?></td><td><?=sanitize($p['name'])?></td><td><?=number_format($p['price'])?></td><td><?=$p['type']=='service'?'خدمات':'کالا'?></td><td><?=$p['stock']?></td></tr>
            <?php endforeach; ?>
        </table></div></div>

        <!-- ==================== سفارشات ==================== -->
        <?php elseif ($tab === 'orders_list'): ?>
        <div class="admin-header"><div><h1>🛒 سفارشات</h1></div></div>
        <div class="table-wrapper"><div class="table-responsive"><table>
            <tr><th>#</th><th>مشتری</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th></tr>
            <?php foreach ($data as $o): ?>
            <tr>
                <td><?=$o['id']?></td><td><?=sanitize($o['full_name'])?></td><td><?=number_format($o['total'])?></td>
                <td><span class="badge <?=$o['status']=='paid'?'badge-success':($o['status']=='pending'?'badge-warning':'badge-info')?>"><?=$o['status']?></span></td>
                <td><?=date('Y/m/d', strtotime($o['created_at']))?></td>
                <td>
                    <form method="POST" style="display:contents;">
                        <input type="hidden" name="action" value="update_order"><input type="hidden" name="tab" value="orders_list">
                        <input type="hidden" name="order_id" value="<?=$o['id']?>">
                        <select name="status" onchange="this.form.submit()">
                            <?php foreach (['pending'=>'در انتظار','paid'=>'پرداخت','shipped'=>'ارسال','delivered'=>'تحویل','cancelled'=>'لغو'] as $k=>$v): ?>
                            <option value="<?=$k?>" <?=$o['status']==$k?'selected':''?>><?=$v?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table></div></div>

        <!-- ==================== تنظیمات ==================== -->
        <?php elseif ($tab === 'settings'): ?>
        <div class="admin-header"><div><h1>⚙️ تنظیمات سیستم</h1></div></div>
        <div class="info-card" style="max-width:500px;">
            <form method="POST">
                <input type="hidden" name="action" value="update_settings"><input type="hidden" name="tab" value="settings">
                <div class="form-group"><label>🔑 API Key</label><input type="text" name="settings[deepseek_api_key]" value="<?=$data['deepseek_api_key']??''?>" style="direction:ltr;"></div>
                <div class="form-group"><label>🎁 اعتبار هدیه</label><input type="number" name="settings[max_free_credits]" value="<?=$data['max_free_credits']??'1000'?>"></div>
                <div class="form-group"><label>⏱️ محدودیت/ساعت</label><input type="number" name="settings[rate_limit_per_hour]" value="<?=$data['rate_limit_per_hour']??'20'?>"></div>
                <button type="submit" class="btn btn-primary">💾 ذخیره</button>
            </form>
        </div>

        <!-- ==================== بقیه ==================== -->
        <?php else: ?>
        <div class="admin-header"><div><h1><?=$tab?></h1></div></div>
        <div class="table-wrapper"><div class="table-responsive"><table>
            <?php if (!empty($data)): ?>
            <tr><?php foreach (array_keys($data[0]) as $k): ?><th><?=$k?></th><?php endforeach; ?></tr>
            <?php foreach ($data as $row): ?>
            <tr><?php foreach ($row as $v): ?><td><?=sanitize((string)$v)?></td><?php endforeach; ?></tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </table></div></div>
        <?php endif; ?>
    </main>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>