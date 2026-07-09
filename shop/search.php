<?php
// shop/search.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

$db = (new Database())->getConnection();
$q = trim($_GET['q'] ?? '');
$results = [];
$total = 0;

if (!empty($q)) {
    $stmt = $db->prepare("SELECT * FROM products WHERE is_active = 1 AND (name LIKE ? OR description LIKE ? OR category LIKE ?) ORDER BY id DESC LIMIT 40");
    $like = "%$q%";
    $stmt->execute([$like, $like, $like]);
    $results = $stmt->fetchAll();
    $total = count($results);
}

$page_title = 'جستجو: ' . htmlspecialchars($q) . ' | فروشگاه | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
.search-page { max-width: 1200px; margin: 80px auto 40px; padding: 0 20px; }
.search-header { text-align: center; margin-bottom: 30px; }
.search-header h1 { font-size: 1.6rem; font-weight: 800; color: var(--text-primary); }
.search-header p { color: var(--text-secondary); margin-top: 6px; }
.search-box { max-width: 500px; margin: 0 auto 30px; display: flex; gap: 8px; }
.search-box input { flex: 1; padding: 14px 18px; border: 2px solid var(--border); border-radius: 30px; font-family: inherit; font-size: 0.95rem; background: var(--bg-input); color: var(--text-primary); }
.search-box input:focus { border-color: var(--primary); outline: none; }
.search-box button { padding: 14px 24px; border-radius: 30px; background: var(--primary); color: #fff; border: none; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 0.95rem; }
.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.product-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; text-decoration: none; color: inherit; transition: all 0.2s; position: relative; overflow: hidden; }
.product-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--primary), var(--secondary)); transform: scaleX(0); transition: 0.3s; }
.product-card:hover::before { transform: scaleX(1); }
.product-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); border-color: var(--primary); }
.product-card .p-cat { font-size: 0.7rem; color: var(--primary); font-weight: 600; margin-bottom: 8px; }
.product-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 6px; color: var(--text-primary); }
.product-card .p-desc { font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 12px; }
.product-card .p-price { font-weight: 800; color: var(--primary); font-size: 1.1rem; }
.empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.empty-state i { font-size: 3rem; display: block; margin-bottom: 16px; }
</style>

<div class="search-page">
    <div class="search-header">
        <h1><i class="ph ph-magnifying-glass"></i> جستجو در فروشگاه</h1>
        <?php if (!empty($q)): ?>
        <p><?= $total ?> نتیجه برای "<strong><?= htmlspecialchars($q) ?></strong>"</p>
        <?php endif; ?>
    </div>
    
    <form class="search-box" method="GET">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="جستجوی خدمات یا کالا...">
        <button type="submit"><i class="ph ph-magnifying-glass"></i> جستجو</button>
    </form>

    <?php if (empty($q)): ?>
        <div class="empty-state">
            <i class="ph ph-magnifying-glass"></i>
            <p>برای جستجو، عبارت مورد نظر را وارد کنید</p>
        </div>
    <?php elseif (empty($results)): ?>
        <div class="empty-state">
            <i class="ph ph-smiley-sad"></i>
            <p>نتیجه‌ای برای "<strong><?= htmlspecialchars($q) ?></strong>" یافت نشد</p>
            <a href="/shop/" class="btn btn-primary" style="margin-top:16px"><i class="ph ph-storefront"></i> بازگشت به فروشگاه</a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($results as $p): ?>
            <a href="/shop/product.php?id=<?= $p['id'] ?>" class="product-card">
                <div class="p-cat"><?= htmlspecialchars($p['category']) ?></div>
                <h3><?= htmlspecialchars($p['name']) ?></h3>
                <div class="p-desc"><?= mb_substr($p['description'] ?? '', 0, 80) ?>...</div>
                <div class="p-price"><?= number_format($p['price']) ?> تومان</div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>