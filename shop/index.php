<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
$db = (new Database())->getConnection();

$services = $db->query("SELECT * FROM products WHERE type='service' AND is_active=1 ORDER BY created_at DESC LIMIT 6")->fetchAll();
$goods_new = $db->query("SELECT * FROM products WHERE type='goods' AND `condition`='new' AND is_active=1 ORDER BY created_at DESC LIMIT 4")->fetchAll();
$goods_used = $db->query("SELECT * FROM products WHERE type='goods' AND `condition`='used' AND is_active=1 ORDER BY created_at DESC LIMIT 4")->fetchAll();

$page_title = 'فروشگاه | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
.shop-hero {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white; text-align: center; padding: 60px 20px 40px; border-radius: 0 0 30px 30px;
    margin-top: 68px; margin-bottom: 30px;
}
.shop-hero h1 { font-size: 2.2rem; font-weight: 800; margin-bottom: 8px; }
.shop-hero p { opacity: 0.9; font-size: 1rem; }

.shop-categories { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 40px; }
.cat-card {
    background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
    padding: 24px; text-align: center; transition: all 0.2s; text-decoration: none; color: var(--text-primary);
}
.cat-card:hover { border-color: var(--primary); box-shadow: var(--shadow-lg); transform: translateY(-3px); }
.cat-card .icon { font-size: 2.5rem; margin-bottom: 10px; }
.cat-card h3 { font-size: 1.2rem; margin-bottom: 6px; }

.section-header { display: flex; justify-content: space-between; align-items: center; margin: 30px 0 15px; }
.section-header h2 { font-size: 1.4rem; }

.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.product-card {
    background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
    padding: 20px; transition: all 0.2s; display: flex; flex-direction: column;
}
.product-card:hover { box-shadow: var(--shadow-md); border-color: var(--primary); }
.product-card .badge { display: inline-block; background: var(--primary-light); color: var(--primary); padding: 3px 12px; border-radius: 20px; font-size: 0.75rem; margin-bottom: 8px; width: fit-content; }
.product-card h3 { font-size: 1rem; margin-bottom: 8px; flex: 1; }
.product-card .price { font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 12px; }
.product-card .btn { align-self: flex-start; }
</style>

<div class="shop-hero">
    <h1>🏪 فروشگاه کافی‌نت گلستان</h1>
    <p>خدمات کامپیوتری، طراحی سایت، قطعات نو و استوک — همه در یک جا</p>
</div>

<div class="container">
    <!-- دسته‌بندی‌ها -->
    <div class="shop-categories">
        <a href="/shop/services.php" class="cat-card">
            <div class="icon">🔧</div>
            <h3>خدمات کامپیوتری</h3>
            <p style="color:var(--text-secondary);">نصب ویندوز، لینوکس، طراحی سایت، امنیت و ...</p>
        </a>
        <a href="/shop/goods.php?condition=new" class="cat-card">
            <div class="icon">🆕</div>
            <h3>کالاهای نو</h3>
            <p style="color:var(--text-secondary);">قطعات کامپیوتر، لوازم جانبی نو و آکبند</p>
        </a>
        <a href="/shop/goods.php?condition=used" class="cat-card">
            <div class="icon">♻️</div>
            <h3>کالاهای استوک</h3>
            <p style="color:var(--text-secondary);">قطعات و لوازم دست دوم با ضمانت سلامت</p>
        </a>
        <a href="/shop/agent.php" class="cat-card">
            <div class="icon">🤖</div>
            <h3>مشاور هوشمند</h3>
            <p style="color:var(--text-secondary);">از AI بپرسید تا بهترین پیشنهاد را بدهد</p>
        </a>
    </div>

    <!-- خدمات -->
    <?php if ($services): ?>
    <div class="section-header">
        <h2>💼 خدمات ویژه</h2>
        <a href="/shop/services.php" style="color:var(--primary);">مشاهده همه ←</a>
    </div>
    <div class="product-grid">
        <?php foreach ($services as $s): ?>
        <div class="product-card">
            <span class="badge">خدمات</span>
            <h3><?php echo sanitize($s['name']); ?></h3>
            <div class="price"><?php echo number_format($s['price']); ?> تومان</div>
            <a href="/shop/product.php?id=<?php echo $s['id']; ?>" class="btn btn-primary btn-sm">مشاهده و سفارش</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- کالاهای نو -->
    <?php if ($goods_new): ?>
    <div class="section-header">
        <h2>🆕 کالاهای نو</h2>
        <a href="/shop/goods.php?condition=new" style="color:var(--primary);">مشاهده همه ←</a>
    </div>
    <div class="product-grid">
        <?php foreach ($goods_new as $g): ?>
        <div class="product-card">
            <span class="badge">نو</span>
            <h3><?php echo sanitize($g['name']); ?></h3>
            <div class="price"><?php echo number_format($g['price']); ?> تومان</div>
            <a href="/shop/product.php?id=<?php echo $g['id']; ?>" class="btn btn-primary btn-sm">مشاهده</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- کالاهای استوک -->
    <?php if ($goods_used): ?>
    <div class="section-header">
        <h2>♻️ کالاهای استوک</h2>
        <a href="/shop/goods.php?condition=used" style="color:var(--primary);">مشاهده همه ←</a>
    </div>
    <div class="product-grid">
        <?php foreach ($goods_used as $g): ?>
        <div class="product-card">
            <span class="badge" style="background:#fef3c7;color:#92400e;">استوک</span>
            <h3><?php echo sanitize($g['name']); ?></h3>
            <div class="price"><?php echo number_format($g['price']); ?> تومان</div>
            <a href="/shop/product.php?id=<?php echo $g['id']; ?>" class="btn btn-primary btn-sm">مشاهده</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>