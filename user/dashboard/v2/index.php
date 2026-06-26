<?php
// user/dashboard/v2/index.php - نسخه کامل و نهایی
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
requireAuth();

$user = getUserData($_SESSION['user_id']);
$db = (new Database())->getConnection();

// آمار
$total_convs = $db->query("SELECT COUNT(*) FROM conversations WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();
$total_msgs = $db->query("SELECT COUNT(*) FROM messages WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();
$total_tasks = $db->query("SELECT COUNT(*) FROM tasks WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();
$pending_tasks = $db->query("SELECT COUNT(*) FROM tasks WHERE user_id = {$_SESSION['user_id']} AND status IN ('todo','in_progress')")->fetchColumn();
$total_orders = $db->query("SELECT COUNT(*) FROM orders WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();
$total_projects = $db->query("SELECT COUNT(*) FROM github_projects WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();
$wallet = $user['wallet_balance'] ?? 0;

$conversations = getConversations($_SESSION['user_id'], 3);

$page_title = 'داشبورد | ' . SITE_NAME;
$extra_css = ['user/dashboard/v2/assets/css/dashboard.css'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="user-profile-summary">
            <div class="avatar"><?php echo mb_substr($user['full_name'], 0, 1); ?></div>
            <h3><?php echo sanitize($user['full_name']); ?></h3>
            <span class="user-phone"><?php echo $user['phone']; ?></span>
            <div style="margin-top:8px;font-size:0.85rem;">
                <i class="fas fa-wallet"></i> <?php echo number_format($wallet); ?> تومان
            </div>
        </div>
        <nav class="dashboard-nav">
            <a href="/user/dashboard/v2/" class="nav-item active"><i class="fas fa-home"></i> داشبورد</a>
            <a href="/user/dashboard/v2/chat.php" class="nav-item"><i class="fas fa-comments"></i> چت AI</a>
            <a href="/user/dashboard/v2/image.php" class="nav-item"><i class="fas fa-image"></i> ساخت عکس</a>
            <a href="/user/dashboard/v2/tools.php" class="nav-item"><i class="fas fa-tools"></i> ابزارها</a>
            <a href="/projects/" class="nav-item"><i class="fab fa-github"></i> پروژه‌ها</a>
            <a href="/user/dashboard/v2/tasks.php" class="nav-item"><i class="fas fa-tasks"></i> تسک‌ها</a>
            <a href="/shop/" class="nav-item"><i class="fas fa-store"></i> فروشگاه</a>
            <a href="/shop/orders.php" class="nav-item"><i class="fas fa-shopping-bag"></i> سفارشات</a>
            <a href="/user/dashboard/v2/history.php" class="nav-item"><i class="fas fa-history"></i> تاریخچه</a>
            <a href="/user/dashboard/v2/profile.php" class="nav-item"><i class="fas fa-user"></i> پروفایل</a>
            <a href="/user/dashboard/v2/settings.php" class="nav-item"><i class="fas fa-cog"></i> تنظیمات</a>
            <?php if(isAdmin()): ?><a href="/admin/" class="nav-item"><i class="fas fa-shield-alt"></i> مدیریت</a><?php endif; ?>
            <a href="/logout.php" class="nav-item nav-item-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>👋 خوش آمدید، <?php echo sanitize($user['full_name']); ?></h1>
                <p><?php echo date('l d F Y'); ?> | امروز چه کاری برات انجام بدم؟</p>
            </div>
            <a href="/user/dashboard/v2/chat.php" class="btn btn-primary"><i class="fas fa-plus"></i> چت جدید</a>
        </div>

        <!-- کارت‌های آمار -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-coins"></i></div>
                <span class="stat-value"><?php echo number_format($user['credits']); ?></span>
                <span class="stat-label">اعتبار</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                <span class="stat-value"><?php echo number_format($wallet); ?></span>
                <span class="stat-label">کیف پول (تومان)</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-comments"></i></div>
                <span class="stat-value"><?php echo $total_convs; ?></span>
                <span class="stat-label">چت‌های فعال</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                <span class="stat-value"><?php echo $total_orders; ?></span>
                <span class="stat-label">سفارشات</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                <span class="stat-value"><?php echo $pending_tasks; ?></span>
                <span class="stat-label">تسک‌های باز</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fab fa-github"></i></div>
                <span class="stat-value"><?php echo $total_projects; ?></span>
                <span class="stat-label">پروژه‌ها</span>
            </div>
        </div>

        <!-- دسترسی سریع -->
        <h2 style="margin:20px 0 10px;">🚀 دسترسی سریع</h2>
        <div class="actions-grid">
            <a href="/user/dashboard/v2/chat.php" class="action-card">
                <i class="fas fa-brain"></i><h3>چت هوشمند</h3><p>Llama 4</p>
            </a>
            <a href="/user/dashboard/v2/image.php" class="action-card">
                <i class="fas fa-image"></i><h3>ساخت عکس</h3><p>۳ مدل مختلف</p>
            </a>
            <a href="/projects/" class="action-card">
                <i class="fab fa-github"></i><h3>پروژه‌های گیت‌هاب</h3><p>تحلیل با AI</p>
            </a>
            <a href="/shop/" class="action-card">
                <i class="fas fa-store"></i><h3>فروشگاه</h3><p>خدمات و کالا</p>
            </a>
            <a href="/user/dashboard/v2/tasks.php" class="action-card">
                <i class="fas fa-tasks"></i><h3>تسک‌ها</h3><p>مدیریت وظایف</p>
            </a>
            <a href="/shop/agent.php" class="action-card">
                <i class="fas fa-robot"></i><h3>مشاور فروش</h3><p>راهنمای خرید</p>
            </a>
        </div>

        <!-- چت‌های اخیر -->
        <?php if ($conversations): ?>
        <h2 style="margin:20px 0 10px;">💬 آخرین گفتگوها</h2>
        <div class="conversation-list">
            <?php foreach ($conversations as $c): ?>
            <a href="/user/dashboard/v2/chat.php?conversation=<?php echo $c['id']; ?>" class="conversation-item">
                <div class="conv-icon"><i class="fas fa-comment-dots"></i></div>
                <div class="conv-info">
                    <h4><?php echo sanitize($c['title']); ?></h4>
                    <p><?php echo mb_substr($c['last_message'] ?? 'بدون پیام', 0, 50); ?></p>
                </div>
                <span><?php echo $c['message_count']; ?> پیام</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>