<?php
// shop/product.php - نسخه نهایی با سبد خرید کاملاً اصلاح‌شده
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

$db = (new Database())->getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: /shop/");
    exit();
}

// افزودن به سبد - بخش اصلاح‌شده
if (isset($_POST['add_to_cart'])) {
    // اطمینان از وجود آرایه cart در سشن
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $cart = $_SESSION['cart'];
    $found = false;
    
    // جستجوی محصول در سبد
    foreach ($cart as $key => $item) {
        if ($item['id'] == $id) {
            $cart[$key]['qty'] += 1;
            $found = true;
            break;
        }
    }
    
    // اگر محصول جدید است، اضافه کن
    if (!$found) {
        $cart[] = [
            'id' => $id,
            'qty' => 1,
            'price' => $product['price']
        ];
    }
    
    // ذخیره در سشن
    $_SESSION['cart'] = $cart;
    
    // ریدایرکت به سبد خرید
    header("Location: /shop/cart.php?added=1");
    exit();
}

$page_title = $product['name'] . ' | فروشگاه | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
.product-page { max-width: 700px; margin: 100px auto 40px; }
.product-card-detail {
    background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-xl);
    padding: 30px; box-shadow: var(--shadow-md);
}
.product-card-detail h1 { font-size: 1.8rem; margin-bottom: 12px; }
.product-card-detail .badge { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 0.8rem; margin-bottom: 16px; }
.product-card-detail .price { font-size: 1.5rem; font-weight: 800; color: var(--primary); margin: 16px 0; }
.product-card-detail .meta { color: var(--text-secondary); margin: 8px 0; }
.product-card-detail .description { line-height: 1.8; margin: 16px 0; color: var(--text-secondary); }
</style>

<div class="container product-page">
    <a href="/shop/" style="color:var(--text-muted);margin-bottom:16px;display:inline-block;">← بازگشت به فروشگاه</a>
    
    <div class="product-card-detail">
        <span class="badge" style="background:<?php echo $product['type']=='service'?'var(--primary-light)':'#fef3c7';?>;color:<?php echo $product['type']=='service'?'var(--primary)':'#92400e';?>;">
            <?php echo $product['type']=='service' ? 'خدمات' : ($product['condition']=='new' ? 'نو' : 'استوک'); ?>
        </span>
        <h1><?php echo sanitize($product['name']); ?></h1>
        
        <div class="price">💰 <?php echo number_format($product['price']); ?> تومان</div>
        
        <div class="meta">
            <?php if ($product['type'] == 'goods'): ?>
                📦 موجودی: <strong><?php echo $product['stock']; ?> عدد</strong> | 
                وضعیت: <strong><?php echo $product['condition']=='new'?'نو':'استوک'; ?></strong>
            <?php endif; ?>
            | دسته: <strong><?php echo $product['category']; ?></strong>
        </div>
        
        <?php if ($product['description']): ?>
        <div class="description">
            <h4>📝 توضیحات:</h4>
            <p><?php echo nl2br(sanitize($product['description'])); ?></p>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="/shop/product.php?id=<?php echo $id; ?>" style="margin-top:20px;">
            <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">
                🛒 افزودن به سبد خرید
            </button>
        </form>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>