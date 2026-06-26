<?php
// login.php
session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

function isLoggedInLogin() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function sanitizeLogin($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

$errors = [];
$phone = '';

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitizeLogin($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($phone) || empty($password)) {
        $errors[] = 'لطفاً تمام فیلدها را پر کنید.';
    } elseif (!preg_match('/^09[0-9]{9}$/', $phone)) {
        $errors[] = 'شماره موبایل نامعتبر است.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE phone = ? AND is_active = 1");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['credits'] = $user['credits'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            $_SESSION['theme'] = $user['theme'] ?? 'light';
            
            $token = md5($user['id'] . 'golestan_salt_2024');
            setcookie('golestan_user', $user['id'], time() + (86400 * 30), '/', '', false, true);
            setcookie('golestan_token', $token, time() + (86400 * 30), '/', '', false, true);
            
            $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            
            $redirect = $_GET['redirect'] ?? '/user/dashboard/v2/';
            header("Location: " . $redirect);
            exit();
        } else {
            $errors[] = 'شماره موبایل یا رمز عبور اشتباه است.';
        }
    }
}

// اگر لاگین هست
if (isLoggedInLogin() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $redirect = $_GET['redirect'] ?? '/user/dashboard/v2/';
    header("Location: " . $redirect);
    exit();
}

require_once __DIR__ . '/includes/functions.php';
$page_title = 'ورود | ' . SITE_NAME;
require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <div class="auth-icon"><i class="fas fa-sign-in-alt"></i></div>
            <h1>ورود به <?php echo SITE_NAME; ?></h1>
            <p>به دنیای هوش مصنوعی خوش آمدید</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- ورود با گوگل -->
        <a href="/oauth/google-login.php" class="oauth-btn google-btn">
            <img src="https://www.google.com/favicon.ico" alt="Google" style="width:20px;height:20px;">
            ورود با حساب گوگل
        </a>
        
        <div class="divider">
            <span>یا با شماره موبایل</span>
        </div>
        
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label><i class="fas fa-mobile-alt"></i> شماره موبایل</label>
                <input type="tel" name="phone" value="<?php echo $phone; ?>" placeholder="09xxxxxxxxx" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> رمز عبور</label>
                <input type="password" name="password" placeholder="رمز عبور" required>
            </div>
            
            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember"> مرا به خاطر بسپار
                </label>
                <a href="/forgot-password.php" class="forgot-link">رمز عبور را فراموش کرده‌اید؟</a>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> ورود به حساب
            </button>
        </form>
        
        <div class="auth-footer">
            <p>حساب ندارید؟ <a href="/signup.php">ثبت‌نام کنید و ۱۰۰۰ اعتبار رایگان بگیرید</a></p>
            <p><a href="/"><i class="fas fa-arrow-right"></i> بازگشت به صفحه اصلی</a></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>