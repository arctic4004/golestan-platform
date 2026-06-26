<?php
// signup.php - نسخه نهایی با Google و GitHub OAuth
session_start();
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

function isLoggedInSignup() { return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']); }
function sanitizeSignup($data) { return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8'); }

if (isLoggedInSignup()) { header("Location: /user/dashboard/v2/"); exit(); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitizeSignup($_POST['full_name'] ?? '');
    $phone = sanitizeSignup($_POST['phone'] ?? '');
    $email = sanitizeSignup($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($full_name)) $errors[] = 'نام و نام خانوادگی الزامی است.';
    if (!preg_match('/^09[0-9]{9}$/', $phone)) $errors[] = 'شماره موبایل نامعتبر است.';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'ایمیل نامعتبر است.';
    if (strlen($password) < 6) $errors[] = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
    if ($password !== $confirm) $errors[] = 'رمز عبور و تکرار آن مطابقت ندارند.';
    
    if (empty($errors)) {
        $db = (new Database())->getConnection();
        
        // چک تکراری بودن
        if ($db->prepare("SELECT id FROM users WHERE phone = ?")->execute([$phone])->fetch()) {
            $errors[] = 'این شماره موبایل قبلاً ثبت شده است.';
        } elseif (!empty($email) && $db->prepare("SELECT id FROM users WHERE email = ?")->execute([$email])->fetch()) {
            $errors[] = 'این ایمیل قبلاً ثبت شده است.';
        }
        
        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare("INSERT INTO users (phone, email, full_name, password_hash, credits, wallet_balance) VALUES (?, ?, ?, ?, 1000, 0)")
               ->execute([$phone, $email ?: null, $full_name, $hash]);
            
            $user_id = $db->lastInsertId();
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['phone'] = $phone;
            $_SESSION['credits'] = 1000;
            $_SESSION['wallet_balance'] = 0;
            $_SESSION['is_admin'] = false;
            $_SESSION['theme'] = 'light';
            
            // کوکی برای ماندگاری
            $token = md5($user_id . 'golestan_salt_2024');
            setcookie('golestan_user', $user_id, time() + (86400 * 30), '/', '', false, true);
            setcookie('golestan_token', $token, time() + (86400 * 30), '/', '', false, true);
            
            logActivity($user_id, 'register', 'ثبت‌نام کاربر جدید');
            header("Location: /user/dashboard/v2/");
            exit();
        }
    }
}

require_once __DIR__ . '/includes/functions.php';
$page_title = 'ثبت‌نام | ' . SITE_NAME;
require_once 'includes/header.php';
?>

<style>
.github-btn {
    background: #24292e !important;
    color: white !important;
    border-color: #24292e !important;
    margin-top: 8px;
}
.github-btn:hover {
    background: #1b1f23 !important;
    border-color: #1b1f23 !important;
    color: white !important;
}
.github-btn i {
    font-size: 1.2rem;
}
</style>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <div class="auth-icon"><i class="fas fa-user-plus"></i></div>
            <h1>ثبت‌نام در <?php echo SITE_NAME; ?></h1>
            <p>از طریق گوگل، گیت‌هاب یا شماره موبایل ثبت‌نام کنید</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><?php foreach ($errors as $e) echo "<p>$e</p>"; ?></div>
        <?php endif; ?>
        
        <!-- OAuth Buttons -->
        <a href="/oauth/google-login.php" class="oauth-btn">
            <img src="https://www.google.com/favicon.ico" alt="Google" width="20" height="20">
            ثبت‌نام با حساب گوگل
        </a>
        <a href="/oauth/github-login.php" class="oauth-btn github-btn">
            <i class="fab fa-github"></i> ثبت‌نام با گیت‌هاب
        </a>
        
        <div class="divider"><span>یا با شماره موبایل</span></div>
        
        <form method="POST">
            <div class="form-group">
                <label>👤 نام و نام خانوادگی *</label>
                <input type="text" name="full_name" value="<?php echo $_POST['full_name'] ?? ''; ?>" placeholder="علی محمدی" required>
            </div>
            <div class="form-group">
                <label>📱 شماره موبایل *</label>
                <input type="tel" name="phone" value="<?php echo $_POST['phone'] ?? ''; ?>" placeholder="09xxxxxxxxx" required>
                <small>شماره موبایل به عنوان نام کاربری استفاده می‌شود</small>
            </div>
            <div class="form-group">
                <label>📧 ایمیل (اختیاری)</label>
                <input type="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" placeholder="example@gmail.com">
                <small>برای بازیابی رمز عبور و دریافت فاکتور</small>
            </div>
            <div class="form-group">
                <label>🔒 رمز عبور *</label>
                <input type="password" name="password" placeholder="حداقل ۶ کاراکتر" minlength="6" required>
            </div>
            <div class="form-group">
                <label>🔒 تکرار رمز عبور *</label>
                <input type="password" name="confirm_password" placeholder="تکرار رمز عبور" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">ثبت‌نام و دریافت ۱۰۰۰ اعتبار</button>
        </form>
        
        <div class="auth-footer">
            <p>قبلاً ثبت‌نام کرده‌اید؟ <a href="/login.php">وارد شوید</a></p>
            <p><a href="/">← بازگشت به صفحه اصلی</a></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>