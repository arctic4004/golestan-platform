<?php
session_start();
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$has_password = !empty($user['password_hash']) && !password_verify('', $user['password_hash']);
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($password) < 8) {
        $error = 'رمز عبور باید حداقل ۸ کاراکتر باشد.';
    } elseif ($password !== $confirm) {
        $error = 'رمز عبور و تکرار آن مطابقت ندارند.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $_SESSION['user_id']]);
        $success = true;
    }
}

if ($has_password && !$success) {
    header('Location: /user/dashboard/v2/');
    exit;
}

$page_title = 'تنظیم رمز عبور | ' . SITE_NAME;
require_once __DIR__ . '/../../../includes/header.php';
?>

<style>
    .set-password-container {
        max-width: 450px;
        margin: 50px auto;
        padding: 30px;
        background: var(--card-bg, #fff);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .set-password-container h2 {
        text-align: center;
        margin-bottom: 10px;
    }

    .set-password-container .subtitle {
        text-align: center;
        color: #666;
        margin-bottom: 25px;
        font-size: 14px;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 16px;
    }

    .btn {
        display: block;
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
    }

    .btn-primary {
        background: #007bff;
        color: white;
    }

    .btn-primary:hover {
        background: #0056b3;
    }
</style>

<div class="set-password-container">
    <h2>🔐 تنظیم رمز عبور</h2>
    <p class="subtitle">
        شما از طریق گوگل یا گیت‌هاب وارد شده‌اید.<br>
        برای ورود با شماره موبایل در آینده، لطفاً یک رمز عبور تعیین کنید.
    </p>

    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ رمز عبور با موفقیت تنظیم شد!<br><br>
            <a href="/user/dashboard/v2/" class="btn btn-primary">رفتن به داشبورد</a>
        </div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>🔒 رمز عبور جدید (حداقل ۸ کاراکتر)</label>
                <input type="password" name="password" required minlength="8" placeholder="حداقل ۸ کاراکتر">
            </div>
            <div class="form-group">
                <label>🔒 تکرار رمز عبور</label>
                <input type="password" name="confirm" required minlength="8" placeholder="تکرار رمز عبور">
            </div>
            <button type="submit" class="btn btn-primary">ذخیره رمز عبور</button>
        </form>

        <p style="text-align:center;margin-top:15px;">
            <a href="/user/dashboard/v2/" style="color:#999;font-size:14px;">بعداً انجام می‌دم</a>
        </p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>