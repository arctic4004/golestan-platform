<?php
// user/dashboard/v2/history.php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

// لاگین اجباری
if (!isset($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "/login.php?redirect=/user/dashboard/v2/history.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
$page_title = 'تاریخچه چت‌ها | ' . SITE_NAME;
$extra_css = ['user/dashboard/v2/assets/css/dashboard.css'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

$user_data = getUserData($_SESSION['user_id']);
$database = new Database();
$db = $database->getConnection();

// عملیات حذف/آرشیو
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM conversations WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $_SESSION['user_id']]);
    header("Location: /user/dashboard/v2/history.php?deleted=1");
    exit();
}

$tab = $_GET['tab'] ?? 'active';
$status_filter = $tab === 'archived' ? 'archived' : 'active';

$stmt = $db->prepare("
    SELECT c.*, 
           (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
           (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
           (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as total_messages
    FROM conversations c 
    WHERE c.user_id = ? AND c.status = ? 
    ORDER BY c.updated_at DESC
");
$stmt->execute([$_SESSION['user_id'], $status_filter]);
$conversations = $stmt->fetchAll();

$total_count = $db->query("SELECT COUNT(*) FROM conversations WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();
$total_messages_count = $db->query("SELECT COUNT(*) FROM messages WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();
$archived_total = $db->query("SELECT COUNT(*) FROM conversations WHERE user_id = {$_SESSION['user_id']} AND status = 'archived'")->fetchColumn();
?>

<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="user-profile-summary">
            <div class="avatar"><?php echo mb_substr($user_data['full_name'] ?? 'U', 0, 1); ?></div>
            <h3><?php echo sanitize($user_data['full_name'] ?? 'کاربر'); ?></h3>
        </div>
        <nav class="dashboard-nav">
            <a href="/user/dashboard/v2/" class="nav-item"><i class="fas fa-home"></i> داشبورد</a>
            <a href="/user/dashboard/v2/chat.php" class="nav-item"><i class="fas fa-comments"></i> چت AI</a>
            <a href="/projects/" class="nav-item"><i class="fab fa-github"></i> پروژه‌ها</a>
            <a href="/user/dashboard/v2/image.php" class="nav-item"><i class="fas fa-image"></i> ساخت عکس</a>
            <a href="/user/dashboard/v2/tasks.php" class="nav-item"><i class="fas fa-tasks"></i> تسک‌ها</a>
            <a href="/user/dashboard/v2/history.php" class="nav-item active"><i class="fas fa-history"></i> تاریخچه</a>
            <a href="/user/dashboard/v2/profile.php" class="nav-item"><i class="fas fa-user"></i> پروفایل</a>
            <a href="/user/dashboard/v2/settings.php" class="nav-item"><i class="fas fa-cog"></i> تنظیمات</a>
            <a href="/logout.php" class="nav-item nav-item-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>📜 تاریخچه چت‌ها</h1>
                <p><?php echo $total_count; ?> چت | <?php echo number_format($total_messages_count); ?> پیام</p>
            </div>
            <a href="/user/dashboard/v2/chat.php" class="btn btn-primary"><i class="fas fa-plus"></i> چت جدید</a>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">✅ چت حذف شد</div>
        <?php endif; ?>

        <div class="tabs">
            <a href="?tab=active" class="tab <?php echo $tab!=='archived'?'active':''; ?>">🟢 فعال</a>
            <a href="?tab=archived" class="tab <?php echo $tab==='archived'?'active':''; ?>">📦 بایگانی (<?php echo $archived_total; ?>)</a>
        </div>

        <?php if (empty($conversations)): ?>
            <div class="empty-state">
                <i class="fas fa-comments" style="font-size:3rem;"></i>
                <h3>چتی یافت نشد</h3>
                <a href="/user/dashboard/v2/chat.php" class="btn btn-primary">شروع چت جدید</a>
            </div>
        <?php else: ?>
            <div class="conversation-list">
                <?php foreach ($conversations as $conv): ?>
                    <div class="conversation-item">
                        <div class="conv-icon"><i class="fas fa-comment-dots"></i></div>
                        <div class="conv-info">
                            <h4><?php echo sanitize($conv['title']); ?></h4>
                            <p><?php echo mb_substr($conv['last_message'] ?? 'بدون پیام', 0, 80); ?></p>
                            <span><?php echo $conv['total_messages']; ?> پیام | <?php echo date('Y/m/d H:i', strtotime($conv['last_message_time'] ?? $conv['created_at'])); ?></span>
                        </div>
                        <div class="conv-actions">
                            <a href="/user/dashboard/v2/chat.php?conversation=<?php echo $conv['id']; ?>" class="btn btn-sm btn-primary">باز کردن</a>
                            <?php if ($conv['status'] === 'active'): ?>
                                <a href="?archive=<?php echo $conv['id']; ?>" class="btn btn-sm btn-outline">📦</a>
                            <?php else: ?>
                                <a href="?restore=<?php echo $conv['id']; ?>" class="btn btn-sm btn-outline">🔄</a>
                            <?php endif; ?>
                            <a href="?delete=<?php echo $conv['id']; ?>" class="btn btn-sm btn-outline text-danger" onclick="return confirm('حذف شود؟')">🗑️</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>