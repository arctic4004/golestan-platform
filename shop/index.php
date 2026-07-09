<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

$db = (new Database())->getConnection();

$categories = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order LIMIT 8")->fetchAll();
$featured = $db->query("SELECT * FROM products WHERE is_active = 1 AND type = 'service' ORDER BY id DESC LIMIT 8")->fetchAll();
$popular = $db->query("SELECT * FROM products WHERE is_active = 1 ORDER BY id DESC LIMIT 8")->fetchAll();
$total_products = $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$total_categories = $db->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();

$page_title = 'فروشگاه | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
.shop-hero {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff; text-align: center; padding: 60px 20px 40px; border-radius: 0 0 30px 30px; margin-top: 62px; margin-bottom: 30px;
}
.shop-hero h1 { font-size: 2.2rem; font-weight: 900; }
.shop-hero p { opacity: 0.9; margin: 10px 0 20px; }
.shop-search { max-width: 450px; margin: 0 auto; display: flex; background: #fff; border-radius: 30px; overflow: hidden; }
.shop-search input { flex: 1; padding: 14px 20px; border: none; font-family: inherit; font-size: 0.95rem; outline: none; }
.shop-search button { padding: 14px 24px; background: var(--accent); color: #fff; border: none; font-weight: 700; cursor: pointer; }
.categories-row { display: flex; gap: 8px; overflow-x: auto; padding: 10px 0; margin-bottom: 30px; }
.cat-chip { flex-shrink: 0; padding: 8px 18px; border-radius: 20px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-secondary); text-decoration: none; font-size: 0.85rem; transition: 0.2s; }
.cat-chip:hover, .cat-chip.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.section-head { display: flex; justify-content: space-between; align-items: center; margin: 30px 0 16px; }
.section-head h2 { font-size: 1.3rem; font-weight: 800; display: flex; align-items: center; gap: 8px; }
.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.product-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; transition: 0.2s; text-decoration: none; color: inherit; position: relative; overflow: hidden; }
.product-card:hover { border-color: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow-md); }
.product-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--primary), var(--secondary)); transform: scaleX(0); transition: 0.3s; }
.product-card:hover::before { transform: scaleX(1); }
.product-card .p-cat { font-size: 0.7rem; color: var(--primary); font-weight: 600; margin-bottom: 8px; }
.product-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 6px; }
.product-card .p-desc { font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 12px; flex: 1; }
.product-card .p-price { font-weight: 800; color: var(--primary); font-size: 1.1rem; }
.btn-cart { padding: 8px 16px; border-radius: 20px; border: 2px solid var(--primary); background: transparent; color: var(--primary); cursor: pointer; font-weight: 700; font-size: 0.82rem; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
.btn-cart:hover { background: var(--primary); color: #fff; }
.shop-cta { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; text-align: center; padding: 40px 20px; border-radius: 20px; margin-top: 40px; }
.shop-cta a { color: var(--primary); background: #fff; padding: 12px 24px; border-radius: 25px; font-weight: 700; text-decoration: none; display: inline-block; margin-top: 16px; }
@media (max-width: 768px) { .products-grid { grid-template-columns: 1fr; } }
</style>

<div class="shop-hero">
    <h1><i class="ph ph-storefront"></i> فروشگاه کافی‌نت گلستان</h1>
    <p><?= number_format($total_products) ?>+ خدمت و کالا — همه در یک پلتفرم</p>
    <form class="shop-search" action="/shop/search.php" method="GET">
        <input type="text" name="q" placeholder="جستجوی خدمات یا کالا...">
        <button type="submit"><i class="ph ph-magnifying-glass"></i></button>
    </form>
</div>

<div class="container">
    <div class="categories-row">
        <a href="/shop/" class="cat-chip active"><i class="ph ph-grid-four"></i> همه</a>
        <?php foreach ($categories as $cat): ?>
        <a href="/shop/category.php?id=<?= $cat['id'] ?>" class="cat-chip"><i class="ph ph-<?= $cat['icon'] ?>"></i> <?= $cat['name'] ?></a>
        <?php endforeach; ?>
    </div>
    
    <?php if (!empty($featured)): ?>
    <div class="section-head">
        <h2><i class="ph ph-star"></i> خدمات ویژه</h2>
        <a href="/shop/services.php">مشاهده همه <i class="ph ph-arrow-left"></i></a>
    </div>
    <div class="products-grid">
        <?php foreach ($featured as $p): ?>
        <a href="/shop/product.php?id=<?= $p['id'] ?>" class="product-card">
            <div class="p-cat"><?= htmlspecialchars($p['category']) ?></div>
            <h3><?= htmlspecialchars($p['name']) ?></h3>
            <div class="p-desc"><?= mb_substr($p['description'] ?? '', 0, 80) ?>...</div>
            <div class="p-price"><?= number_format($p['price']) ?> تومان</div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="section-head">
        <h2><i class="ph ph-fire"></i> پرفروش‌ترین‌ها</h2>
        <a href="/shop/services.php">مشاهده همه <i class="ph ph-arrow-left"></i></a>
    </div>
    <div class="products-grid">
        <?php foreach ($popular as $p): ?>
        <div class="product-card">
            <div class="p-cat"><?= htmlspecialchars($p['category']) ?></div>
            <h3><?= htmlspecialchars($p['name']) ?></h3>
            <div class="p-desc"><?= mb_substr($p['description'] ?? '', 0, 80) ?>...</div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div class="p-price"><?= number_format($p['price']) ?> تومان</div>
                <button class="btn-cart" onclick="event.preventDefault();addToCart(this, <?= $p['id'] ?>)"><i class="ph ph-shopping-cart"></i> افزودن</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="shop-cta">
        <h2><i class="ph ph-robot"></i> نیاز به راهنمایی داری؟</h2>
        <p>از مشاور هوشمند ما بپرس — با صدات هم می‌تونی سوال کنی!</p>
        <a href="/shop/agent.php"><i class="ph ph-microphone"></i> مشاوره با AI</a>
    </div>
</div>

<script>
function addToCart(btn, productId) {
    btn.classList.add('added');
    btn.innerHTML = '<i class="ph ph-check"></i> اضافه شد';
    fetch('/shop/cart.php?action=add&id=' + productId + '&ajax=1')
        .then(r => r.json())
        .then(d => { if (d.success) setTimeout(() => { btn.classList.remove('added'); btn.innerHTML = '<i class="ph ph-shopping-cart"></i> افزودن'; }, 2000); });
}
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>