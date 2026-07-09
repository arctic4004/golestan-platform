<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
requireAuth();

$user = getUserData($_SESSION['user_id']);
$db = (new Database())->getConnection();

$stats = [
    'convs' => $db->query("SELECT COUNT(*) FROM conversations WHERE user_id = {$_SESSION['user_id']}")->fetchColumn(),
    'msgs' => $db->query("SELECT COUNT(*) FROM messages WHERE user_id = {$_SESSION['user_id']}")->fetchColumn(),
    'tasks' => $db->query("SELECT COUNT(*) FROM tasks WHERE user_id = {$_SESSION['user_id']} AND status IN ('todo','in_progress')")->fetchColumn(),
    'orders' => $db->query("SELECT COUNT(*) FROM orders WHERE user_id = {$_SESSION['user_id']}")->fetchColumn(),
    'projects' => $db->query("SELECT COUNT(*) FROM github_projects WHERE user_id = {$_SESSION['user_id']}")->fetchColumn(),
];
$wallet = $user['wallet_balance'] ?? 0;
$conversations = getConversations($_SESSION['user_id'], 3);

$page_title = 'داشبورد | ' . SITE_NAME;
$extra_css = ['user/dashboard/v2/assets/css/dashboard.css'];
$extra_js = ['user/dashboard/v2/assets/js/dashboard.js'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<div class="dashboard-container">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    
    <main class="dashboard-main">
        <div class="dashboard-header">
            <button class="sidebar-toggle" onclick="toggleDashboardSidebar()"><i class="ph ph-list"></i></button>
            <div>
                <h1><i class="ph ph-hand-waving"></i> خوش آمدید، <?= sanitize($user['full_name']) ?></h1>
                <p><?= jalali_date('l d F Y') ?> | امروز چه کاری برات انجام بدم؟</p>
            </div>
            <a href="/user/dashboard/v2/chat.php" class="btn btn-primary"><i class="ph ph-plus"></i> چت جدید</a>
        </div>

        <div class="stats-grid">
            <?php foreach ([
                ['icon'=>'ph-coin','num'=>number_format($user['credits']),'label'=>'اعتبار','bg'=>'var(--primary-light)'],
                ['icon'=>'ph-wallet','num'=>number_format($wallet),'label'=>'کیف پول','sub'=>'تومان','bg'=>'var(--primary-light)'],
                ['icon'=>'ph-chats-circle','num'=>$stats['convs'],'label'=>'چت‌های فعال','bg'=>'var(--accent-light)'],
                ['icon'=>'ph-shopping-bag','num'=>$stats['orders'],'label'=>'سفارشات','bg'=>'rgba(16,185,129,0.1)'],
                ['icon'=>'ph-kanban','num'=>$stats['tasks'],'label'=>'تسک‌های باز','bg'=>'rgba(239,68,68,0.1)'],
                ['icon'=>'ph-github-logo','num'=>$stats['projects'],'label'=>'پروژه‌ها','bg'=>'rgba(245,158,11,0.1)'],
            ] as $s): ?>
            <div class="stat-card" style="background:<?= $s['bg'] ?>; border: none; backdrop-filter: blur(10px);">
                <div class="stat-icon" style="background: transparent;"><i class="ph <?= $s['icon'] ?>"></i></div>
                <div>
                    <span class="stat-value"><?= $s['num'] ?></span>
                    <span class="stat-label"><?= $s['label'] ?></span>
                    <?php if(isset($s['sub'])): ?><span class="stat-sub"><?= $s['sub'] ?></span><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <h2 style="margin:20px 0 12px; display: flex; align-items: center; gap: 8px;"><i class="ph ph-rocket"></i> دسترسی سریع</h2>
        <div class="actions-grid">
            <?php foreach ([
                ['url'=>'/user/dashboard/v2/chat.php','icon'=>'ph-brain','title'=>'چت هوشمند','desc'=>'Llama 4'],
                ['url'=>'/user/dashboard/v2/image.php','icon'=>'ph-image','title'=>'ساخت عکس','desc'=>'۳ مدل'],
                ['url'=>'/projects/','icon'=>'ph-github-logo','title'=>'پروژه‌ها','desc'=>'تحلیل با AI'],
                ['url'=>'/shop/','icon'=>'ph-storefront','title'=>'فروشگاه','desc'=>'خدمات و کالا'],
                ['url'=>'/user/dashboard/v2/tasks.php','icon'=>'ph-kanban','title'=>'تسک‌ها','desc'=>'مدیریت وظایف'],
                ['url'=>'/shop/agent.php','icon'=>'ph-robot','title'=>'مشاور','desc'=>'راهنمای خرید'],
            ] as $a): ?>
            <a href="<?= $a['url'] ?>" class="action-card">
                <i class="ph <?= $a['icon'] ?>"></i>
                <h3><?= $a['title'] ?></h3>
                <p><?= $a['desc'] ?></p>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($conversations): ?>
        <h2 style="margin:24px 0 12px; display: flex; align-items: center; gap: 8px;"><i class="ph ph-chats-circle"></i> آخرین گفتگوها</h2>
        <div class="conversation-list">
            <?php foreach ($conversations as $c): ?>
            <a href="/user/dashboard/v2/chat.php?conversation=<?= $c['id'] ?>" class="conversation-item">
                <div class="conv-icon"><i class="ph ph-chat-circle"></i></div>
                <div class="conv-info">
                    <h4><?= sanitize($c['title']) ?></h4>
                    <p><?= mb_substr($c['last_message'] ?? 'بدون پیام', 0, 50) ?></p>
                </div>
                <span class="conv-meta"><?= $c['message_count'] ?> پیام</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>