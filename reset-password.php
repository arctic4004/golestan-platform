<?php
require_once 'config/constants.php';
$token = $_GET['token'] ?? '';
$db = (new Database())->getConnection();
$user = $db->prepare("SELECT id FROM users WHERE reset_token=? AND reset_expires > NOW()")->execute([$token])->fetch();
if(!$user) die('لینک نامعتبر یا منقضی شده است');

if($_SERVER['REQUEST_METHOD']==='POST'){
    $pass = $_POST['password'];
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $db->prepare("UPDATE users SET password_hash=?, reset_token=NULL, reset_expires=NULL WHERE id=?")->execute([$hash, $user['id']]);
    flashMessage('success','رمز عبور با موفقیت تغییر کرد. وارد شوید.');
    redirect('/login.php');
}
$page_title = 'تنظیم رمز جدید';
require_once 'includes/header.php';
?>
<div class="auth-container"><div class="auth-box">
<h1>🔐 رمز عبور جدید</h1>
<form method="POST">
  <input type="password" name="password" placeholder="رمز جدید" required minlength="6">
  <button type="submit" class="btn btn-primary btn-block">ذخیره</button>
</form>
</div></div>
<?php require_once 'includes/footer.php'; ?>