<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
if(!isLoggedIn()) redirect('/login.php');

$order_id = $_GET['order_id'] ?? 0;
$db = (new Database())->getConnection();
$order = $db->prepare("SELECT * FROM orders WHERE id=? AND user_id=?")->execute([$order_id, $_SESSION['user_id']])->fetch();
if(!$order) die('فاکتور یافت نشد');

$items = $db->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?")->execute([$order_id])->fetchAll();

$page_title = 'فاکتور شماره ' . $order['invoice_number'];
?>
<!DOCTYPE html>
<html dir="rtl"><head><meta charset="UTF-8"><title><?=$page_title?></title>
<style>
body{font-family:Tahoma;padding:30px;direction:rtl}
table{width:100%;border-collapse:collapse;margin:20px 0}
th,td{border:1px solid #ddd;padding:10px;text-align:center}
</style></head><body>
<h1>🧾 فاکتور رسمی</h1>
<p>شماره فاکتور: <?=$order['invoice_number']?></p>
<p>تاریخ: <?=date('Y/m/d H:i', strtotime($order['created_at']))?></p>
<table>
<tr><th>محصول</th><th>تعداد</th><th>قیمت واحد</th><th>جمع</th></tr>
<?php $total=0; foreach($items as $i): $sub=$i['price']*$i['quantity']; $total+=$sub;?>
<tr><td><?=sanitize($i['name'])?></td><td><?=$i['quantity']?></td><td><?=number_format($i['price'])?></td><td><?=number_format($sub)?></td></tr>
<?php endforeach; ?>
<tr><td colspan="3"><strong>جمع کل</strong></td><td><strong><?=number_format($total)?> تومان</strong></td></tr>
</table>
<button onclick="window.print()">🖨️ چاپ فاکتور</button>
</body></html>