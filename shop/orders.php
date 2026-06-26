<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
requireAuth();

$orders = (new Database())->getConnection()
    ->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC")
    ->execute([$_SESSION['user_id']])->fetchAll();

$page_title = 'سفارش‌های من';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>
<div class="container" style="margin-top:100px;">
  <h1>📦 سفارش‌های من</h1>
  <?php if($orders): ?>
    <table>
      <tr><th>شماره فاکتور</th><th>تاریخ</th><th>مبلغ</th><th>وضعیت</th><th>فاکتور</th></tr>
      <?php foreach($orders as $o): ?>
      <tr>
        <td><?= $o['invoice_number'] ?></td>
        <td><?= date('Y/m/d', strtotime($o['created_at'])) ?></td>
        <td><?= number_format($o['total']) ?></td>
        <td><?= $o['status'] ?></td>
        <td><a href="/shop/invoice.php?order_id=<?= $o['id'] ?>">مشاهده</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p>هنوز سفارشی ثبت نکرده‌اید.</p>
  <?php endif; ?>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>