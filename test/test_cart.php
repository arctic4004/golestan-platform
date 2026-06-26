<?php
// test_cart.php
session_start();
echo "<h2>محتوای سبد خرید (SESSION)</h2>";
echo "<pre>";
print_r($_SESSION['cart'] ?? 'سبد خالی است');
echo "</pre>";

echo "<h3>Session ID: " . session_id() . "</h3>";
echo "<h3>Session Save Path: " . session_save_path() . "</h3>";

// تست ست کردن سبد
echo "<h3>تست ست کردن:</h3>";
$_SESSION['cart'] = [['id' => 1, 'qty' => 2, 'price' => 300000]];
echo "<p>سبد ست شد. <a href='/shop/cart.php'>برو به سبد خرید</a></p>";
echo "<pre>"; print_r($_SESSION['cart']); echo "</pre>";
?>