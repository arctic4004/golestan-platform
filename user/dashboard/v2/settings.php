<?php
// user/dashboard/v2/settings.php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isLoggedIn()) {
    redirect('/login.php?redirect=/user/dashboard/v2/settings.php');
}

$user = getUserData($_SESSION['user_id']);
$db   = (new Database())->getConnection();
$success = '';
$errors  = [];

// ========== ویرایش پروفایل ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = sanitize($_POST['email'] ?? '');
    $bio   = sanitize($_POST['bio'] ?? '');

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'ایمیل نامعتبر است.';
    } else {
        $stmt = $db->prepare("UPDATE users SET email = ?, bio = ? WHERE id = ?");
        $stmt->execute([$email ?: null, $bio, $_SESSION['user_id']]);
        $success = 'پروفایل با موفقیت به‌روزرسانی شد.';
        $user = getUserData($_SESSION['user_id']);
    }
}

// ========== تغییر رمز عبور ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old     = $_POST['old_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
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
            logActivity($_SESSION['user_id'], 'password_change', 'تغییر رمز عبور از تنظیمات');
        }
    }
}

// ========== ذخیره تم (AJAX هم پشتیبانی می‌شود) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_theme'])) {
    $color = $_POST['theme_color'] ?? 'amethyst';
    $mode  = $_POST['theme_mode'] ?? 'dark';

    $allowed_colors = ['sapphire','emerald','ruby','amber','amethyst','teal','rose','indigo','cyan'];
    $allowed_modes  = ['light','dark'];

    if (in_array($color, $allowed_colors)) $_SESSION['theme_color'] = $color;
    if (in_array($mode, $allowed_modes))   $_SESSION['theme_mode']  = $mode;

    // ذخیره در localStorage + اعمال فوری (حتی بدون رفرش)
    echo '<script>
        document.documentElement.setAttribute("data-theme", "'.$color.'");
        document.documentElement.setAttribute("data-mode", "'.$mode.'");
        localStorage.setItem("theme_color", "'.$color.'");
        localStorage.setItem("theme_mode", "'.$mode.'");
    </script>';
    $success = 'تم با موفقیت تغییر کرد.';
}

// ========== حذف حساب ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    logActivity($_SESSION['user_id'], 'account_deleted', 'حذف حساب کاربری');
    session_destroy();
    redirect('/login.php?message=account_deleted');
}

$current_color = $_SESSION['theme_color'] ?? 'amethyst';
$current_mode  = $_SESSION['theme_mode'] ?? 'dark';

$page_title = 'تنظیمات | ' . SITE_NAME;
$extra_css = ['user/dashboard/v2/assets/css/dashboard.css'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

$theme_list = [
    'sapphire' => '#4f46e5', 'emerald' => '#059669', 'ruby' => '#dc2626',
    'amber' => '#d97706', 'amethyst' => '#9333ea', 'teal' => '#0d9488',
    'rose' => '#e11d48', 'indigo' => '#6366f1', 'cyan' => '#06b6d4',
];
?>

<style>
.settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 768px) { .settings-grid { grid-template-columns: 1fr; } }
.color-option {
    width: 48px; height: 48px; border-radius: 14px; cursor: pointer;
    transition: all 0.2s ease; position: relative; margin: 0 auto;
    border: 3px solid transparent;
}
.color-option:hover { transform: scale(1.1); }
.color-option.active {
    border-color: var(--text-primary);
    box-shadow: 0 0 0 3px var(--primary), 0 0 20px currentColor;
}
.color-option .check-icon {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    color: #fff; font-size: 1.2rem; display: none;
    text-shadow: 0 0 4px rgba(0,0,0,0.5);
}
.color-option.active .check-icon { display: block; }
.mode-option {
    display: flex; align-items: center; gap: 8px; padding: 12px 20px;
    border: 2px solid var(--border); border-radius: 12px; cursor: pointer;
    transition: all 0.2s; flex: 1; justify-content: center; font-weight: 500;
}
.mode-option.active { border-color: var(--primary); background: var(--primary-light); color: var(--primary); }
</style>

<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="user-profile-summary">
            <div class="avatar"><?= mb_substr($user['full_name'] ?? 'U', 0, 1) ?></div>
            <h3><?= sanitize($user['full_name']) ?></h3>
            <span style="font-size:0.8rem;color:var(--text-muted)"><?= $user['phone'] ?></span>
        </div>
        <nav class="dashboard-nav">
            <a href="/user/dashboard/v2/" class="nav-item"><i class="ph ph-house"></i> داشبورد</a>
            <a href="/user/dashboard/v2/chat.php" class="nav-item"><i class="ph ph-chats-circle"></i> چت AI</a>
            <a href="/projects/" class="nav-item"><i class="ph ph-github-logo"></i> پروژه‌ها</a>
            <a href="/user/dashboard/v2/image.php" class="nav-item"><i class="ph ph-image"></i> ساخت عکس</a>
            <a href="/user/dashboard/v2/tasks.php" class="nav-item"><i class="ph ph-kanban"></i> تسک‌ها</a>
            <a href="/user/dashboard/v2/history.php" class="nav-item"><i class="ph ph-clock-counter-clockwise"></i> تاریخچه</a>
            <a href="/user/dashboard/v2/profile.php" class="nav-item"><i class="ph ph-user"></i> پروفایل</a>
            <a href="/user/dashboard/v2/settings.php" class="nav-item active"><i class="ph ph-gear"></i> تنظیمات</a>
            <a href="/logout.php" class="nav-item nav-item-danger"><i class="ph ph-sign-out"></i> خروج</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <button class="sidebar-toggle" onclick="toggleDashboardSidebar()"><i class="ph ph-list"></i></button>
        <h1><i class="ph ph-gear"></i> تنظیمات</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="ph ph-check"></i> <?= $success ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><?php foreach ($errors as $e) echo "<p><i class='ph ph-x-circle'></i> $e</p>"; ?></div>
        <?php endif; ?>

        <!-- کارت اطلاعات کاربر -->
        <div class="card mb-4">
            <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                <div style="width:60px;height:60px;font-size:1.5rem;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;"><?= mb_substr($user['full_name'],0,1) ?></div>
                <div>
                    <h3 style="margin:0"><?= sanitize($user['full_name']) ?></h3>
                    <p style="color:var(--text-secondary);margin:4px 0 0"><?= $user['phone'] ?> | اعتبار: <?= number_format($user['credits']) ?> | کیف پول: <?= number_format($user['wallet_balance'] ?? 0) ?> تومان</p>
                </div>
            </div>
        </div>

        <div class="settings-grid">
            <!-- ویرایش سریع پروفایل -->
            <div class="card">
                <h3><i class="ph ph-user-circle"></i> ویرایش پروفایل</h3>
                <form method="POST">
                    <div class="form-group"><label>ایمیل</label><input type="email" name="email" value="<?= sanitize($user['email'] ?? '') ?>" placeholder="example@gmail.com"></div>
                    <div class="form-group"><label>درباره من</label><textarea name="bio" rows="2"><?= sanitize($user['bio'] ?? '') ?></textarea></div>
                    <button type="submit" name="update_profile" class="btn btn-primary btn-sm"><i class="ph ph-floppy-disk"></i> ذخیره</button>
                </form>
            </div>

            <!-- تغییر رمز عبور -->
            <div class="card">
                <h3><i class="ph ph-key"></i> تغییر رمز عبور</h3>
                <form method="POST">
                    <div class="form-group"><label>رمز عبور فعلی</label><input type="password" name="old_password" placeholder="••••••" required></div>
                    <div class="form-group"><label>رمز عبور جدید (حداقل ۶ کاراکتر)</label><input type="password" name="new_password" placeholder="حداقل ۶ کاراکتر" minlength="6" required></div>
                    <div class="form-group"><label>تکرار رمز جدید</label><input type="password" name="confirm_password" placeholder="تکرار رمز جدید" required></div>
                    <button type="submit" name="change_password" class="btn btn-primary btn-sm"><i class="ph ph-check"></i> تغییر رمز</button>
                </form>
            </div>
        </div>

        <!-- انتخاب تم -->
        <div class="card mb-4">
            <h3><i class="ph ph-palette"></i> شخصی‌سازی ظاهر</h3>
            <form method="POST" id="themeForm">
                <p style="margin-bottom:12px;color:var(--text-secondary)">رنگ اصلی:</p>
                <div style="display:flex; flex-wrap:wrap; gap:12px; margin-bottom:20px;" id="colorOptions">
                    <?php foreach ($theme_list as $key => $color): ?>
                    <div style="text-align:center; cursor:pointer;" onclick="selectColor('<?= $key ?>', this)" data-color="<?= $key ?>">
                        <div class="color-option <?= $current_color==$key ? 'active' : '' ?>" style="background:<?= $color ?>">
                            <i class="ph ph-check check-icon"></i>
                        </div>
                        <small style="display:block;margin-top:4px;font-size:0.7rem;"><?= $key ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="theme_color" id="themeColorInput" value="<?= $current_color ?>">

                <p style="margin-bottom:12px;color:var(--text-secondary)">حالت نمایش:</p>
                <div style="display:flex; gap:12px; margin-bottom:20px;">
                    <div class="mode-option <?= $current_mode=='light' ? 'active' : '' ?>" onclick="selectMode('light', this)">☀️ روشن</div>
                    <div class="mode-option <?= $current_mode=='dark' ? 'active' : '' ?>" onclick="selectMode('dark', this)">🌙 تاریک</div>
                </div>
                <input type="hidden" name="theme_mode" id="themeModeInput" value="<?= $current_mode ?>">

                <button type="submit" name="update_theme" class="btn btn-primary"><i class="ph ph-check"></i> ذخیره تم</button>
            </form>
        </div>

        <!-- منطقه خطر -->
        <div class="card border-danger" style="border-color:#fecaca; background:#fef2f2;">
            <h3 style="color:#dc2626"><i class="ph ph-trash"></i> حذف حساب کاربری</h3>
            <p style="color:var(--text-secondary);margin-bottom:16px">این عملیات غیرقابل بازگشت است و تمام اطلاعات شما برای همیشه حذف خواهد شد.</p>
            <form method="POST" onsubmit="return confirm('آیا از حذف حساب خود اطمینان دارید؟')">
                <button type="submit" name="delete_account" class="btn btn-danger"><i class="ph ph-x-circle"></i> حذف حساب</button>
            </form>
        </div>
    </main>
</div>

<script>
function selectColor(color, element) {
    // حذف active از همه
    document.querySelectorAll('.color-option').forEach(el => el.classList.remove('active'));
    // اضافه کردن به انتخاب شده
    element.querySelector('.color-option').classList.add('active');
    // آپدیت input مخفی
    document.getElementById('themeColorInput').value = color;
    
    // پیش‌نمایش فوری (بدون ذخیره در سرور)
    document.documentElement.setAttribute('data-theme', color);
}

function selectMode(mode, element) {
    document.querySelectorAll('.mode-option').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
    document.getElementById('themeModeInput').value = mode;
    
    document.documentElement.setAttribute('data-mode', mode);
}
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>