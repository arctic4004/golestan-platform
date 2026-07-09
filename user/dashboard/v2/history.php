<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "/login.php?redirect=/user/dashboard/v2/history.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
$page_title = 'تاریخچه چت‌ها | ' . SITE_NAME;
$extra_css = ['user/dashboard/v2/assets/css/dashboard.css'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

$user_data = getUserData($_SESSION['user_id']);
$db = (new Database())->getConnection();

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM conversations WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $_SESSION['user_id']]);
    header("Location: /user/dashboard/v2/history.php?deleted=1");
    exit();
}

$tab = $_GET['tab'] ?? 'active';
$status_filter = $tab === 'archived' ? 'archived' : 'active';

$stmt = $db->prepare("SELECT c.*, (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message, (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time, (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as total_messages FROM conversations c WHERE c.user_id = ? AND c.status = ? ORDER BY c.updated_at DESC");
$stmt->execute([$_SESSION['user_id'], $status_filter]);
$conversations = $stmt->fetchAll();
?>

<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="user-profile-summary">
            <div class="avatar"><?= mb_substr($user_data['full_name'] ?? 'U', 0, 1) ?></div>
            <h3><?= sanitize($user_data['full_name'] ?? 'کاربر') ?></h3>
        </div>
        <nav class="dashboard-nav">
            <a href="/user/dashboard/v2/" class="nav-item"><i class="ph ph-house"></i> داشبورد</a>
            <a href="/user/dashboard/v2/chat.php" class="nav-item"><i class="ph ph-chats-circle"></i> چت AI</a>
            <a href="/projects/" class="nav-item"><i class="ph ph-github-logo"></i> پروژه‌ها</a>
            <a href="/user/dashboard/v2/image.php" class="nav-item"><i class="ph ph-image"></i> ساخت عکس</a>
            <a href="/user/dashboard/v2/tasks.php" class="nav-item"><i class="ph ph-kanban"></i> تسک‌ها</a>
            <a href="/user/dashboard/v2/history.php" class="nav-item active"><i class="ph ph-clock-counter-clockwise"></i> تاریخچه</a>
            <a href="/user/dashboard/v2/profile.php" class="nav-item"><i class="ph ph-user"></i> پروفایل</a>
            <a href="/user/dashboard/v2/settings.php" class="nav-item"><i class="ph ph-gear"></i> تنظیمات</a>
            <a href="/logout.php" class="nav-item nav-item-danger"><i class="ph ph-sign-out"></i> خروج</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <button class="sidebar-toggle" onclick="toggleDashboardSidebar()"><i class="ph ph-list"></i></button>
        <div class="dashboard-header">
            <div><h1><i class="ph ph-clock-counter-clockwise"></i> تاریخچه چت‌ها</h1></div>
            <a href="/user/dashboard/v2/chat.php" class="btn btn-primary"><i class="ph ph-plus"></i> چت جدید</a>
        </div>

        <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success"><i class="ph ph-check"></i> چت حذف شد</div><?php endif; ?>

        <div class="tabs" style="margin-bottom:16px;">
            <a href="?tab=active" class="tab <?= $tab!=='archived'?'active':''; ?>"><i class="ph ph-chat-circle"></i> فعال</a>
            <a href="?tab=archived" class="tab <?= $tab==='archived'?'active':''; ?>"><i class="ph ph-archive"></i> بایگانی</a>
        </div>

        <?php if (empty($conversations)): ?>
            <div class="empty-state"><i class="ph ph-chats-circle" style="font-size:3rem;"></i><h3>چتی یافت نشد</h3></div>
        <?php else: ?>
            <?php foreach ($conversations as $conv): ?>
                <div class="conversation-item" style="display:flex;align-items:center;gap:12px;padding:14px;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;margin-bottom:8px;">
                    <div class="conv-icon"><i class="ph ph-chat-circle" style="font-size:1.5rem;color:var(--primary)"></i></div>
                    <div class="conv-info" style="flex:1;">
                        <h4><?= sanitize($conv['title']) ?></h4>
                        <p><?= mb_substr($conv['last_message'] ?? 'بدون پیام', 0, 80) ?></p>
                        <span><?= $conv['total_messages'] ?> پیام | <?= date('Y/m/d H:i', strtotime($conv['last_message_time'] ?? $conv['created_at'])) ?></span>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <a href="/user/dashboard/v2/chat.php?conversation=<?= $conv['id'] ?>" class="btn btn-sm btn-primary"><i class="ph ph-arrow-right"></i></a>
                        <a href="?delete=<?= $conv['id'] ?>" class="btn btn-sm" style="background:#fef2f2;color:#ef4444" onclick="return confirm('حذف شود؟')"><i class="ph ph-trash"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>