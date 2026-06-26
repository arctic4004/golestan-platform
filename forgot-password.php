<?php
// forgot-password.php - نسخه اصلاح‌شده
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'لطفاً ایمیل خود را وارد کنید.';
    } else {
        $db = (new Database())->getConnection();
        
        $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            
            $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            $stmt->execute([$token, $user['id']]);
            
            $link = SITE_URL . "/reset-password.php?token=" . $token;
            $subject = "بازیابی رمز عبور - " . SITE_NAME;
            $message = "سلام {$user['full_name']}،\n\nبرای بازیابی رمز عبور خود روی لینک زیر کلیک کنید:\n{$link}\n\nاین لینک تا ۱ ساعت معتبر است.";
            $headers = "From: noreply@golestanyasuj.ir\r\nContent-Type: text/plain; charset=UTF-8";
            
            mail($email, $subject, $message, $headers);
            $success = '✅ لینک بازیابی به ایمیل شما ارسال شد. لطفاً ایمیل خود را چک کنید.';
        } else {
            $error = '❌ هیچ کاربری با این ایمیل یافت نشد.';
        }
    }
}

require_once __DIR__ . '/includes/functions.php';
$page_title = 'فراموشی رمز عبور | ' . SITE_NAME;
require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <div class="auth-icon"><i class="fas fa-key"></i></div>
            <h1>🔑 فراموشی رمز عبور</h1>
            <p>ایمیل خود را وارد کنید تا لینک بازیابی ارسال شود</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>📧 ایمیل</label>
                <input type="email" name="email" placeholder="example@gmail.com" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">ارسال لینک بازیابی</button>
        </form>
        
        <div class="auth-footer">
            <p><a href="/login.php">← بازگشت به صفحه ورود</a></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>