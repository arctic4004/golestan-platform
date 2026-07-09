<?php
session_start();
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$_SESSION['is_admin']) {
    header('Location: /login.php');
    exit;
}

$db = (new Database())->getConnection();
$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE customers SET full_name=?, phone=?, email=?, telegram_id=?, tags=?, interests=?, notes=? WHERE id=?");
    $stmt->execute([
        $_POST['full_name'], $_POST['phone'], $_POST['email'],
        $_POST['telegram_id'], $_POST['tags'], $_POST['interests'],
        $_POST['notes'], $id
    ]);
    header("Location: /admin/crm/view.php?id=$id");
    exit;
}

$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) die('مشتری یافت نشد.');

$page_title = 'ویرایش ' . $customer['full_name'] . ' | CRM';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.crm-form { max-width: 600px; margin: 30px auto; background: var(--card-bg, #fff); border-radius: 16px; padding: 30px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 10px; font-family: inherit; }
.btn { padding: 12px 25px; border: none; border-radius: 10px; cursor: pointer; }
.btn-primary { background: #667eea; color: white; }
</style>

<div class="crm-form">
    <h2>✏️ ویرایش: <?= htmlspecialchars($customer['full_name']) ?></h2>
    <form method="POST">
        <div class="form-group"><label>نام</label><input type="text" name="full_name" value="<?= htmlspecialchars($customer['full_name']) ?>" required></div>
        <div class="form-group"><label>موبایل</label><input type="tel" name="phone" value="<?= $customer['phone'] ?>"></div>
        <div class="form-group"><label>ایمیل</label><input type="email" name="email" value="<?= $customer['email'] ?>"></div>
        <div class="form-group"><label>تلگرام</label><input type="text" name="telegram_id" value="<?= $customer['telegram_id'] ?>"></div>
        <div class="form-group"><label>تگ‌ها</label><input type="text" name="tags" value="<?= htmlspecialchars($customer['tags']) ?>"></div>
        <div class="form-group"><label>علایق</label><textarea name="interests"><?= htmlspecialchars($customer['interests']) ?></textarea></div>
        <div class="form-group"><label>یادداشت کلی</label><textarea name="notes"><?= htmlspecialchars($customer['notes']) ?></textarea></div>
        <button type="submit" class="btn btn-primary">💾 ذخیره</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>