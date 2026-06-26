<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
requireAuth();

$user = getUserData($_SESSION['user_id']);
$transactions = (new Database())->getConnection()
    ->prepare("SELECT * FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 20")
    ->execute([$_SESSION['user_id']])->fetchAll();

$page_title = 'کیف پول من';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>
<div class="container" style="margin-top:100px;">
  <h1>💰 کیف پول</h1>
  <a href="/shop/payment-manual.php" class="btn btn-primary">💳 شارژ کیف پول</a>
  <p>موجودی: <strong><?= number_format($user['wallet_balance']) ?> تومان</strong></p>
  <h3>تراکنش‌ها</h3>
  <table>
    <tr><th>تاریخ</th><th>مبلغ</th><th>نوع</th><th>توضیح</th></tr>
    <?php foreach($transactions as $t): ?>
    <tr>
      <td><?= date('Y/m/d H:i', strtotime($t['created_at'])) ?></td>
      <td><?= number_format($t['amount']) ?></td>
      <td><?= $t['type'] ?></td>
      <td><?= sanitize($t['description']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>