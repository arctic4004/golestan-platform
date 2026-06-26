<?php
// user/dashboard/v2/settings.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isLoggedIn()) {
    redirect('/login.php?redirect=/user/dashboard/v2/settings.php');
}

$user = getUserData($_SESSION['user_id']);
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'خطای امنیتی.';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        if (isset($_POST['update_theme'])) {
            $theme = in_array($_POST['theme'], ['light', 'dark']) ? $_POST['theme'] : 'light';
            $stmt = $db->prepare("UPDATE users SET theme = ? WHERE id = ?");
            $stmt->execute([$theme, $_SESSION['user_id']]);
            $_SESSION['theme'] = $theme;
            $success = 'تم با موفقیت تغییر کرد.';
        }

        if (isset($_POST['delete_account'])) {
            $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            logActivity($_SESSION['user_id'], 'account_deleted', 'حذف حساب کاربری');
            session_destroy();
            redirect('/login.php?message=account_deleted');
        }
    }
}

$page_title = 'تنظیمات | ' . SITE_NAME;
$extra_css = ['user/dashboard/v2/assets/css/dashboard.css'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="user-profile-summary">
            <div class="avatar"><?php echo mb_substr($user['full_name'] ?? 'U', 0, 1); ?></div>
            <h3><?php echo sanitize($user['full_name']); ?></h3>
        </div>
        <nav class="dashboard-nav">
            <a href="/user/dashboard/v2/" class="nav-item"><i class="fas fa-home"></i> داشبورد</a>
            <a href="/user/dashboard/v2/chat.php" class="nav-item"><i class="fas fa-comments"></i> چت AI</a>
            <a href="/projects/" class="nav-item"><i class="fab fa-github"></i> پروژه‌ها</a>
            <a href="/user/dashboard/v2/image.php" class="nav-item"><i class="fas fa-image"></i> ساخت عکس</a>
            <a href="/user/dashboard/v2/tasks.php" class="nav-item"><i class="fas fa-tasks"></i> تسک‌ها</a>
            <a href="/user/dashboard/v2/history.php" class="nav-item"><i class="fas fa-history"></i> تاریخچه</a>
            <a href="/user/dashboard/v2/profile.php" class="nav-item"><i class="fas fa-user"></i> پروفایل</a>
            <a href="/user/dashboard/v2/settings.php" class="nav-item active"><i class="fas fa-cog"></i> تنظیمات</a>
            <a href="/logout.php" class="nav-item nav-item-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <h1>⚙️ تنظیمات</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- تغییر تم -->
        <div class="card mb-4">
            <h3>🎨 تم سایت</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="theme-options" style="display: flex; gap: 15px;">
                    <label>
                        <input type="radio" name="theme" value="light" <?php echo (getTheme() == 'light') ? 'checked' : ''; ?>>
                        <div class="theme-preview" style="background:white; color:black; padding:10px 20px; border-radius:8px;">☀️ روشن</div>
                    </label>
                    <label>
                        <input type="radio" name="theme" value="dark" <?php echo (getTheme() == 'dark') ? 'checked' : ''; ?>>
                        <div class="theme-preview" style="background:#1e293b; color:white; padding:10px 20px; border-radius:8px;">🌙 تاریک</div>
                    </label>
                </div>
                <button type="submit" name="update_theme" class="btn btn-primary mt-3">💾 ذخیره</button>
            </form>
        </div>

        <!-- حذف حساب -->
        <div class="card border-danger">
            <h3 class="text-danger">🗑️ حذف حساب کاربری</h3>
            <p>با حذف حساب، تمام اطلاعات شما برای همیشه از بین می‌رود.</p>
            <form method="POST" onsubmit="return confirm('آیا از حذف حساب خود اطمینان دارید؟')">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <button type="submit" name="delete_account" class="btn btn-danger">حذف حساب</button>
            </form>
        </div>
    </main>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>