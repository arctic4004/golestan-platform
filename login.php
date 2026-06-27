<?php
session_start();
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

// تولید CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function isLoggedInLogin()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}
function sanitizeLogin($data)
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

$errors = [];
$phone = '';

// Rate limiting برای brute-force
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$attempts_file = sys_get_temp_dir() . '/login_attempts_' . md5($ip);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // چک CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'خطای امنیتی. لطفاً صفحه را refresh کنید.';
    } else {
        // چک rate limit
        $attempts = @json_decode(file_get_contents($attempts_file), true) ?: ['count' => 0, 'time' => time()];
        if ($attempts['count'] >= 5 && (time() - $attempts['time']) < 300) {
            $errors[] = 'تعداد تلاش‌های ناموفق زیاد. لطفاً ۵ دقیقه صبر کنید.';
        } else {
            $phone = sanitizeLogin($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($phone) || empty($password)) {
                $errors[] = 'لطفاً تمام فیلدها را پر کنید.';
            } elseif (!preg_match('/^09[0-9]{9}$/', $phone)) {
                $errors[] = 'شماره موبایل نامعتبر است.';
            } else {
                $db = (new Database())->getConnection();
                $stmt = $db->prepare("SELECT * FROM users WHERE phone = ? AND is_active = 1");
                $stmt->execute([$phone]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    // ریست rate limit
                    @unlink($attempts_file);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['phone'] = $user['phone'];
                    $_SESSION['credits'] = $user['credits'];
                    $_SESSION['wallet_balance'] = $user['wallet_balance'] ?? 0;
                    $_SESSION['is_admin'] = (bool)$user['is_admin'];
                    $_SESSION['theme'] = $user['theme'] ?? 'light';

                    // کوکی امن
                    $token = bin2hex(random_bytes(32));
                    setcookie('golestan_user', $user['id'], [
                        'expires' => time() + (86400 * 30),
                        'path' => '/',
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                    setcookie('golestan_token', $token, [
                        'expires' => time() + (86400 * 30),
                        'path' => '/',
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);

                    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);

                    // چک کن رمز داره یا نه (برای کاربرای OAuth)
                    if (empty($user['password_hash']) || password_verify('', $user['password_hash'])) {
                        $redirect = '/user/dashboard/v2/set-password.php?welcome=1';
                    } else {
                        $redirect = $_GET['redirect'] ?? '/user/dashboard/v2/';
                    }

                    header("Location: " . $redirect);
                    exit();
                } else {
                    // ثبت تلاش ناموفق
                    if ((time() - $attempts['time']) > 300) {
                        $attempts = ['count' => 1, 'time' => time()];
                    } else {
                        $attempts['count']++;
                    }
                    file_put_contents($attempts_file, json_encode($attempts));

                    $errors[] = 'شماره موبایل یا رمز عبور اشتباه است.';
                }
            }
        }
    }

    // تولید token جدید بعد از POST
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isLoggedInLogin() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . ($_GET['redirect'] ?? '/user/dashboard/v2/'));
    exit();
}

require_once __DIR__ . '/includes/functions.php';
$page_title = 'ورود | ' . SITE_NAME;
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
            <div class="auth-icon"><i class="fas fa-sign-in-alt"></i></div>
            <h1>ورود به <?php echo SITE_NAME; ?></h1>
            <p>از طریق گوگل، گیت‌هاب یا شماره موبایل وارد شوید</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
            </div>
        <?php endif; ?>

        <a href="/oauth/google-login.php" class="oauth-btn">
            <img src="https://www.google.com/favicon.ico" alt="Google" width="20" height="20">
            ورود با حساب گوگل
        </a>
        <a href="/oauth/github-login.php" class="oauth-btn github-btn">
            <i class="fab fa-github"></i> ورود با گیت‌هاب
        </a>

        <div class="divider"><span>یا با شماره موبایل</span></div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-group">
                <label>📱 شماره موبایل</label>
                <input type="tel" name="phone" value="<?php echo $phone; ?>" placeholder="09xxxxxxxxx" required>
            </div>
            <div class="form-group">
                <label>🔒 رمز عبور</label>
                <input type="password" name="password" placeholder="رمز عبور" required>
            </div>
            <div class="form-options">
                <label class="remember-me"><input type="checkbox"> مرا به خاطر بسپار</label>
                <a href="/forgot-password.php" class="forgot-link">فراموشی رمز؟</a>
            </div>
            <button type="submit" class="btn btn-primary btn-block">ورود به حساب</button>
        </form>

        <div class="auth-footer">
            <p>حساب ندارید؟ <a href="/signup.php">ثبت‌نام کنید و ۱۰۰۰ اعتبار بگیرید</a></p>
            <p><a href="/">← بازگشت به صفحه اصلی</a></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>