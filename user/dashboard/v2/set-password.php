<?php
// user/dashboard/v2/set-password.php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// فقط کاربران لاگین‌شده میتونن بیان
if (!isLoggedIn()) {
    redirect('/login.php');
}

$error = '';
$success = '';

// چک کن این کاربر واقعاً نیاز به رمز داره (با OAuth اومده)
$user = getUserData($_SESSION['user_id']);
$needs_password = (strpos($user['phone'], 'GO') === 0 || strpos($user['phone'], 'GH') === 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $error = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
    } elseif ($password !== $confirm) {
        $error = 'رمز عبور و تکرار آن مطابقت ندارند.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $db = (new Database())->getConnection();
        $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $_SESSION['user_id']]);
        
        $success = '✅ رمز عبور با موفقیت تنظیم شد!';
        logActivity($_SESSION['user_id'], 'password_set', 'تنظیم رمز عبور جدید (OAuth)');
        
        // ریدایرکت بعد از ۲ ثانیه
        header("Refresh: 2; URL=/user/dashboard/v2/");
    }
}

$page_title = 'تنظیم رمز عبور | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
.set-pass-page { max-width: 450px; margin: 100px auto 40px; }
</style>

<div class="container set-pass-page">
    <div class="auth-box">
        <div class="auth-header">
            <div class="auth-icon"><i class="fas fa-key"></i></div>
            <h1>🔐 تنظیم رمز عبور</h1>
            <p style="color:var(--text-secondary);">
                <?php if ($needs_password): ?>
                    شما از طریق گوگل/گیت‌هاب وارد شده‌اید. برای ورود با موبایل، یک رمز عبور تنظیم کنید.
                <?php else: ?>
                    می‌توانید رمز عبور حساب خود را تغییر دهید.
                <?php endif; ?>
            </p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>🔒 رمز عبور جدید</label>
                    <input type="password" name="password" placeholder="حداقل ۶ کاراکتر" minlength="6" required>
                </div>
                <div class="form-group">
                    <label>🔒 تکرار رمز عبور</label>
                    <input type="password" name="confirm_password" placeholder="تکرار رمز عبور" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">💾 ذخیره رمز عبور</button>
            </form>
        <?php endif; ?>
        
        <?php if (!$needs_password && !$success): ?>
            <p style="margin-top:12px;font-size:0.85rem;color:var(--text-muted);">
                ⚠️ شما قبلاً رمز عبور دارید. برای تغییر رمز به <a href="/user/dashboard/v2/profile.php">پروفایل</a> بروید.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>