<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

$cart = $_SESSION['cart'] ?? [];

if (isset($_GET['remove'])) {
    $index = (int)$_GET['remove'];
    if (isset($cart[$index])) {
        unset($cart[$index]);
        $_SESSION['cart'] = array_values($cart);
    }
    header("Location: /shop/cart.php");
    exit();
}

if (isset($_POST['update'])) {
    $new_cart = [];
    foreach ($_POST['qty'] as $i => $q) {
        $qty = (int)$q;
        if ($qty > 0 && isset($cart[$i])) {
            $cart[$i]['qty'] = $qty;
            $new_cart[] = $cart[$i];
        }
    }
    $_SESSION['cart'] = $new_cart;
    header("Location: /shop/cart.php");
    exit();
}

$db = (new Database())->getConnection();
$items = [];
$total = 0;

foreach ($cart as $i => $item) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$item['id']]);
    $product = $stmt->fetch();
    
    if ($product) {
        $qty = max(1, (int)$item['qty']);
        $subtotal = $product['price'] * $qty;
        $total += $subtotal;
        $items[] = ['index' => $i, 'product' => $product, 'qty' => $qty, 'subtotal' => $subtotal];
    }
}

$page_title = 'سبد خرید | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
.cart-page { max-width: 900px; margin: 100px auto 40px; }
.cart-empty { text-align: center; padding: 80px 20px; }
.cart-empty i { font-size: 4rem; color: var(--text-muted); margin-bottom: 20px; display: block; }
.cart-table { width: 100%; border-collapse: collapse; background: var(--bg-card); border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-md); }
.cart-table th { background: var(--bg-secondary); padding: 14px 16px; text-align: right; font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); }
.cart-table td { padding: 14px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; color: var(--text-primary); }
.cart-table .product-name { font-weight: 600; }
.cart-table .product-type { font-size: 0.75rem; color: var(--text-muted); display: block; }
.cart-table input[type="number"] { width: 65px; padding: 8px; border: 1px solid var(--border); border-radius: 8px; text-align: center; font-family: var(--font); font-size: 0.9rem; background: var(--bg-input); color: var(--text-primary); }
.cart-table .remove-btn { color: var(--danger); font-size: 1.2rem; }
.cart-summary { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; margin-top: 20px; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
.cart-summary .total-amount { font-size: 1.5rem; font-weight: 800; color: var(--primary); }
.alert-success { background: var(--success-light); border: 1px solid #bbf7d0; color: #16a34a; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
@media (max-width: 768px) { .cart-table { font-size: 0.85rem; } .cart-table th, .cart-table td { padding: 10px; } }
</style>

<div class="container cart-page">
    <h1 style="margin-bottom: 24px;"><i class="ph ph-shopping-cart"></i> سبد خرید</h1>
    
    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success"><i class="ph ph-check"></i> محصول با موفقیت به سبد خرید اضافه شد</div>
    <?php endif; ?>
    
    <?php if (empty($items)): ?>
        <div class="cart-empty">
            <i class="ph ph-shopping-cart"></i>
            <h2>سبد خرید شما خالی است</h2>
            <p style="color: var(--text-secondary); margin: 10px 0 20px;">هنوز هیچ محصولی به سبد خرید اضافه نکرده‌اید</p>
            <a href="/shop/" class="btn btn-primary btn-lg"><i class="ph ph-storefront"></i> رفتن به فروشگاه</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <div style="overflow-x: auto;">
                <table class="cart-table">
                    <thead>
                        <tr><th>محصول</th><th>قیمت واحد</th><th>تعداد</th><th>جمع</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <span class="product-name"><?= sanitize($item['product']['name']) ?></span>
                                <span class="product-type"><?= $item['product']['type'] == 'service' ? 'خدمات' : 'کالا' ?></span>
                            </td>
                            <td><?= number_format($item['product']['price']) ?> تومان</td>
                            <td><input type="number" name="qty[<?= $item['index'] ?>]" value="<?= $item['qty'] ?>" min="1" max="99"></td>
                            <td><strong><?= number_format($item['subtotal']) ?></strong> تومان</td>
                            <td><a href="?remove=<?= $item['index'] ?>" class="remove-btn" title="حذف"><i class="ph ph-trash"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 16px; display: flex; gap: 10px;">
                <button type="submit" name="update" class="btn btn-outline"><i class="ph ph-arrows-clockwise"></i> بروزرسانی سبد</button>
                <a href="/shop/" class="btn btn-outline"><i class="ph ph-storefront"></i> ادامه خرید</a>
            </div>
        </form>
        <div class="cart-summary">
            <div><span style="color: var(--text-secondary);">جمع کل:</span> <span class="total-amount"><?= number_format($total) ?> تومان</span></div>
            <a href="/shop/checkout.php" class="btn btn-primary btn-lg"><i class="ph ph-credit-card"></i> نهایی کردن خرید</a>
        </div>
    <?php endif; ?>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>