<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

$db = (new Database())->getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) { header("Location: /shop/"); exit(); }

$related = $db->prepare("SELECT * FROM products WHERE category = ? AND id != ? AND is_active = 1 ORDER BY RAND() LIMIT 4");
$related->execute([$product['category'], $id]);
$related = $related->fetchAll();

if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $cart = $_SESSION['cart'];
    $found = false;
    foreach ($cart as $key => $item) {
        if ($item['id'] == $id) { $cart[$key]['qty'] += 1; $found = true; break; }
    }
    if (!$found) $cart[] = ['id' => $id, 'qty' => 1, 'price' => $product['price']];
    $_SESSION['cart'] = $cart;
    header("Location: /shop/cart.php?added=1"); exit();
}

$page_title = $product['name'] . ' | فروشگاه | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

// نگاشت آیکون دسته‌بندی
$catIcons = [
    'چاپ و تکثیر' => 'ph-printer',
    'تایپ و ترجمه' => 'ph-keyboard',
    'تبدیل فایل' => 'ph-file-arrow-up',
    'اینترنت و ایمیل' => 'ph-globe',
    'انتقال اطلاعات' => 'ph-devices',
    'طراحی گرافیک' => 'ph-paint-brush',
    'خدمات بانکی' => 'ph-bank',
    'خودرو و راهور' => 'ph-car',
    'پلیس +10' => 'ph-shield',
    'ثبت احوال' => 'ph-identification-card',
    'قوه قضاییه' => 'ph-scales',
    'تامین اجتماعی' => 'ph-heartbeat',
    'بیمه سلامت' => 'ph-first-aid',
    'بیمه' => 'ph-umbrella',
    'قطعات کامپیوتر' => 'ph-cpu',
];
$categoryIcon = $catIcons[$product['category']] ?? 'ph-package';
?>

<style>
.product-page { max-width: 1000px; margin: 80px auto 40px; padding: 0 20px; }
.breadcrumb { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 24px; display: flex; align-items: center; gap: 6px; }
.breadcrumb a { color: var(--primary); }
.product-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
.product-image { background: var(--bg-secondary); border-radius: 20px; display: flex; align-items: center; justify-content: center; padding: 40px; min-height: 300px; }
.product-image i { font-size: 6rem; color: var(--primary); }
.product-category { display: inline-flex; align-items: center; gap: 6px; background: var(--primary-light); color: var(--primary); padding: 6px 16px; border-radius: 25px; font-size: 0.8rem; font-weight: 600; margin-bottom: 16px; }
.product-info h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 8px; color: var(--text-primary); }
.product-price { font-size: 2rem; font-weight: 900; color: var(--primary); margin: 16px 0; }
.product-meta { display: flex; flex-wrap: wrap; gap: 10px; margin: 20px 0; }
.meta-item { display: flex; align-items: center; gap: 6px; padding: 8px 14px; background: var(--bg-secondary); border-radius: 10px; font-size: 0.85rem; color: var(--text-secondary); border: 1px solid var(--border); }
.meta-item i { color: var(--primary); }
.product-description { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; margin: 24px 0; line-height: 2; color: var(--text-secondary); }
.btn-add { width: 100%; padding: 16px; border-radius: 14px; border: none; background: var(--primary); color: #fff; font-size: 1rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; }
.btn-add:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 8px 20px var(--primary-glow); }
.related-section { margin-top: 50px; }
.related-section h2 { font-size: 1.3rem; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.related-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; }
.related-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; text-decoration: none; color: inherit; transition: all 0.2s; }
.related-card:hover { border-color: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow-md); }
.related-card h4 { font-size: 0.95rem; margin-bottom: 6px; color: var(--text-primary); }
.related-card .r-price { font-weight: 700; color: var(--primary); }
@media (max-width: 768px) { .product-layout { grid-template-columns: 1fr; } }
</style>

<div class="product-page">
    <div class="breadcrumb">
        <a href="/"><i class="ph ph-house"></i> خانه</a> <span>/</span>
        <a href="/shop/"><i class="ph ph-storefront"></i> فروشگاه</a> <span>/</span>
        <span><?= htmlspecialchars($product['name']) ?></span>
    </div>
    
    <div class="product-layout">
        <div class="product-image">
            <i class="ph <?= $categoryIcon ?>"></i>
        </div>
        <div class="product-info">
            <div class="product-category"><i class="ph ph-tag"></i> <?= htmlspecialchars($product['category']) ?></div>
            <h1><?= htmlspecialchars($product['name']) ?></h1>
            <div class="product-price"><?= number_format($product['price']) ?> تومان</div>
            
            <div class="product-meta">
                <?php if ($product['type'] == 'service'): ?>
                <div class="meta-item"><i class="ph ph-clock"></i> <?= $product['estimated_time'] ?? 'متغیر' ?></div>
                <?php else: ?>
                <div class="meta-item"><i class="ph ph-package"></i> موجودی: <?= $product['stock'] ?> عدد</div>
                <?php endif; ?>
                <div class="meta-item"><i class="ph ph-tag"></i> <?= $product['type'] == 'service' ? 'خدمات' : 'کالا' ?></div>
            </div>
            
            <?php if ($product['description']): ?>
            <div class="product-description">
                <strong>توضیحات:</strong><br>
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <button type="submit" name="add_to_cart" class="btn-add">
                    <i class="ph ph-shopping-cart"></i> افزودن به سبد خرید
                </button>
            </form>
        </div>
    </div>
    
    <?php if (!empty($related)): ?>
    <div class="related-section">
        <h2><i class="ph ph-stack"></i> محصولات مرتبط</h2>
        <div class="related-grid">
            <?php foreach ($related as $r): ?>
            <a href="/shop/product.php?id=<?= $r['id'] ?>" class="related-card">
                <h4><?= htmlspecialchars($r['name']) ?></h4>
                <div class="r-price"><?= number_format($r['price']) ?> تومان</div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>