<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
if(!isLoggedIn()) redirect('/login.php?redirect=/shop/checkout.php');

$cart = $_SESSION['cart'] ?? [];
if(empty($cart)) redirect('/shop/cart.php');

$db = (new Database())->getConnection();
$total = 0;
$items = [];
foreach($cart as $item) {
    $p = $db->prepare("SELECT * FROM products WHERE id=?")->execute([$item['id']])->fetch();
    if(!$p) continue;
    $sub = $p['price'] * $item['qty'];
    $total += $sub;
    $items[] = ['product'=>$p, 'qty'=>$item['qty'], 'subtotal'=>$sub];
}

$user = getUserData($_SESSION['user_id']);
if($user['wallet_balance'] < $total) {
    flashMessage('error', 'موجودی کیف پول شما کافی نیست. لطفاً حساب خود را شارژ کنید.');
    redirect('/shop/cart.php');
}

// ایجاد سفارش
$db->beginTransaction();
try {
    $inv = 'INV-'.date('Ymd').'-'.rand(1000,9999);
    $stmt = $db->prepare("INSERT INTO orders (user_id,total,status,invoice_number) VALUES (?,?,'paid',?)");
    $stmt->execute([$_SESSION['user_id'], $total, $inv]);
    $order_id = $db->lastInsertId();

    foreach($items as $item) {
        $stmt = $db->prepare("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)");
        $stmt->execute([$order_id, $item['product']['id'], $item['qty'], $item['product']['price']]);
        // اگر کالا بود، موجودی کم شود
        if($item['product']['type']=='goods') {
            $db->prepare("UPDATE products SET stock=stock-? WHERE id=?")->execute([$item['qty'], $item['product']['id']]);
        }
    }

    // کسر از کیف پول
    $new_balance = $user['wallet_balance'] - $total;
    $db->prepare("UPDATE users SET wallet_balance=? WHERE id=?")->execute([$new_balance, $_SESSION['user_id']]);
    $db->prepare("INSERT INTO wallet_transactions (user_id,amount,type,description,balance_after) VALUES (?,-?,'purchase',?,?)")
       ->execute([$_SESSION['user_id'], $total, "پرداخت سفارش $inv", $new_balance]);
    
    $_SESSION['cart'] = [];
    $db->commit();
    flashMessage('success', "سفارش شما با موفقیت ثبت شد. شماره فاکتور: $inv");
    redirect("/shop/invoice.php?order_id=$order_id");
} catch(Exception $e) {
    $db->rollBack();
    flashMessage('error', 'خطا در ثبت سفارش');
    redirect('/shop/cart.php');
}