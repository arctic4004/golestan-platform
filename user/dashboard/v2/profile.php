<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isLoggedIn()) {
    redirect('/login.php?redirect=/user/dashboard/v2/profile.php');
}

$user = getUserData($_SESSION['user_id']);
$db = (new Database())->getConnection();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = sanitize($_POST['email'] ?? '');
    $bio = sanitize($_POST['bio'] ?? '');
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'ایمیل نامعتبر است.';
    } else {
        $stmt = $db->prepare("UPDATE users SET email = ?, bio = ? WHERE id = ?");
        $stmt->execute([$email ?: null, $bio, $_SESSION['user_id']]);
        $success = 'پروفایل با موفقیت به‌روزرسانی شد.';
        logActivity($_SESSION['user_id'], 'profile_update', 'به‌روزرسانی پروفایل');
        $user = getUserData($_SESSION['user_id']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($old) || empty($new)) {
        $errors[] = 'لطفاً تمام فیلدهای رمز عبور را پر کنید.';
    } elseif (strlen($new) < 6) {
        $errors[] = 'رمز عبور جدید باید حداقل ۶ کاراکتر باشد.';
    } elseif ($new !== $confirm) {
        $errors[] = 'رمز عبور جدید و تکرار آن مطابقت ندارند.';
    } else {
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $current = $stmt->fetch();
        
        if (!password_verify($old, $current['password_hash'])) {
            $errors[] = 'رمز عبور فعلی اشتباه است.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $_SESSION['user_id']]);
            $success = 'رمز عبور با موفقیت تغییر کرد.';
            logActivity($_SESSION['user_id'], 'password_change', 'تغییر رمز عبور');
        }
    }
}

$total_chats = $db->query("SELECT COUNT(*) FROM conversations WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();
$total_messages = $db->query("SELECT COUNT(*) FROM messages WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();
$total_orders = $db->query("SELECT COUNT(*) FROM orders WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();
$total_tasks = $db->query("SELECT COUNT(*) FROM tasks WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();
$wallet = $user['wallet_balance'] ?? 0;

$page_title = 'پروفایل من | ' . SITE_NAME;
$extra_css = ['user/dashboard/v2/assets/css/dashboard.css'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
.profile-page { max-width: 900px; margin: 0 auto; }
.profile-header {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 20px; padding: 30px; color: white; text-align: center; margin-bottom: 24px;
}
.profile-avatar {
    width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.2);
    border: 3px solid white; display: flex; align-items: center; justify-content: center;
    font-size: 2rem; font-weight: 700; margin: 0 auto 12px;
}
.profile-header h2 { font-size: 1.5rem; margin-bottom: 4px; }
.profile-header p { opacity: 0.85; font-size: 0.9rem; }

.profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.profile-card {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: 16px; padding: 24px; transition: all 0.2s;
}
.profile-card h3 { font-size: 1.1rem; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; color: var(--primary); }

.stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px; }
.stat-mini {
    background: var(--bg-secondary); border-radius: 12px; padding: 14px;
    text-align: center; border: 1px solid var(--border);
}
.stat-mini .num { font-size: 1.4rem; font-weight: 700; color: var(--primary); }
.stat-mini .lbl { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; display: flex; align-items: center; justify-content: center; gap: 4px; }

.full-width { grid-column: 1 / -1; }
.danger-zone { border: 1px solid #fecaca; background: #fef2f2; }
.danger-zone h3 { color: #dc2626; }

@media (max-width: 768px) { .profile-grid { grid-template-columns: 1fr; } }
</style>

<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="user-profile-summary">
            <div class="avatar"><?= mb_substr($user['full_name'], 0, 1) ?></div>
            <h3><?= sanitize($user['full_name']) ?></h3>
            <span class="user-phone"><?= $user['phone'] ?></span>
            <div style="margin-top:6px;font-size:0.85rem;"><i class="ph ph-wallet"></i> <?= number_format($wallet) ?> تومان</div>
        </div>
        <nav class="dashboard-nav">
            <a href="/user/dashboard/v2/" class="nav-item"><i class="ph ph-house"></i> داشبورد</a>
            <a href="/user/dashboard/v2/chat.php" class="nav-item"><i class="ph ph-chats-circle"></i> چت AI</a>
            <a href="/projects/" class="nav-item"><i class="ph ph-github-logo"></i> پروژه‌ها</a>
            <a href="/user/dashboard/v2/image.php" class="nav-item"><i class="ph ph-image"></i> ساخت عکس</a>
            <a href="/user/dashboard/v2/tools.php" class="nav-item"><i class="ph ph-wrench"></i> ابزارها</a>
            <a href="/user/dashboard/v2/tasks.php" class="nav-item"><i class="ph ph-kanban"></i> تسک‌ها</a>
            <a href="/shop/" class="nav-item"><i class="ph ph-storefront"></i> فروشگاه</a>
            <a href="/shop/orders.php" class="nav-item"><i class="ph ph-shopping-bag"></i> سفارشات</a>
            <a href="/user/dashboard/v2/history.php" class="nav-item"><i class="ph ph-clock-counter-clockwise"></i> تاریخچه</a>
            <a href="/user/dashboard/v2/profile.php" class="nav-item active"><i class="ph ph-user"></i> پروفایل</a>
            <a href="/user/dashboard/v2/settings.php" class="nav-item"><i class="ph ph-gear"></i> تنظیمات</a>
            <a href="/logout.php" class="nav-item nav-item-danger"><i class="ph ph-sign-out"></i> خروج</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <button class="sidebar-toggle" onclick="toggleDashboardSidebar()"><i class="ph ph-list"></i></button>

        <?php if ($success): ?><div class="alert alert-success"><i class="ph ph-check"></i> <?= $success ?></div><?php endif; ?>
        <?php if (!empty($errors)): ?><div class="alert alert-error"><?php foreach ($errors as $e) echo "<p><i class='ph ph-x-circle'></i> $e</p>"; ?></div><?php endif; ?>

        <div class="profile-page">
            <div class="profile-header">
                <div class="profile-avatar"><?= mb_substr($user['full_name'], 0, 1) ?></div>
                <h2><?= sanitize($user['full_name']) ?></h2>
                <p><?= $user['phone'] ?> | عضو از <?= jalali_date('Y/m/d', strtotime($user['created_at'])) ?></p>
            </div>

            <div class="profile-grid">
                <div class="profile-card">
                    <h3><i class="ph ph-user-circle"></i> اطلاعات حساب</h3>
                    <form method="POST">
                        <div class="form-group"><label>نام کامل</label><input type="text" value="<?= sanitize($user['full_name']) ?>" disabled></div>
                        <div class="form-group"><label>شماره موبایل</label><input type="text" value="<?= $user['phone'] ?>" disabled></div>
                        <div class="form-group"><label>ایمیل</label><input type="email" name="email" value="<?= sanitize($user['email'] ?? '') ?>" placeholder="example@gmail.com"><small>برای بازیابی رمز و دریافت فاکتور</small></div>
                        <div class="form-group"><label>درباره من</label><textarea name="bio" rows="3"><?= sanitize($user['bio'] ?? '') ?></textarea></div>
                        <button type="submit" name="update_profile" class="btn btn-primary btn-sm"><i class="ph ph-floppy-disk"></i> ذخیره</button>
                    </form>
                </div>

                <div class="profile-card">
                    <h3><i class="ph ph-key"></i> تغییر رمز عبور</h3>
                    <form method="POST">
                        <div class="form-group"><label>رمز عبور فعلی</label><input type="password" name="old_password" placeholder="••••••" required></div>
                        <div class="form-group"><label>رمز عبور جدید</label><input type="password" name="new_password" placeholder="حداقل ۶ کاراکتر" minlength="6" required></div>
                        <div class="form-group"><label>تکرار رمز جدید</label><input type="password" name="confirm_password" placeholder="تکرار رمز جدید" required></div>
                        <button type="submit" name="change_password" class="btn btn-primary btn-sm"><i class="ph ph-check"></i> تغییر رمز</button>
                    </form>
                </div>

                <div class="profile-card full-width">
                    <h3><i class="ph ph-chart-bar"></i> آمار حساب</h3>
                    <div class="stats-grid">
                        <div class="stat-mini"><div class="num"><?= number_format($user['credits']) ?></div><div class="lbl"><i class="ph ph-coin"></i> اعتبار</div></div>
                        <div class="stat-mini"><div class="num"><?= number_format($wallet) ?></div><div class="lbl"><i class="ph ph-wallet"></i> کیف پول</div></div>
                        <div class="stat-mini"><div class="num"><?= $total_chats ?></div><div class="lbl"><i class="ph ph-chats-circle"></i> چت‌ها</div></div>
                        <div class="stat-mini"><div class="num"><?= number_format($total_messages) ?></div><div class="lbl"><i class="ph ph-chat-text"></i> پیام‌ها</div></div>
                        <div class="stat-mini"><div class="num"><?= $total_orders ?></div><div class="lbl"><i class="ph ph-shopping-bag"></i> سفارشات</div></div>
                        <div class="stat-mini"><div class="num"><?= $total_tasks ?></div><div class="lbl"><i class="ph ph-kanban"></i> تسک‌ها</div></div>
                    </div>
                </div>

                <div class="profile-card full-width danger-zone">
                    <h3><i class="ph ph-trash"></i> حذف حساب کاربری</h3>
                    <p style="color:var(--text-secondary);margin-bottom:12px;">این عملیات غیرقابل بازگشت است.</p>
                    <form method="POST" onsubmit="return confirm('آیا مطمئن هستید؟')">
                        <input type="hidden" name="delete_account" value="1">
                        <button type="submit" class="btn btn-sm" style="background:#dc2626;color:white;"><i class="ph ph-x-circle"></i> حذف حساب</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>