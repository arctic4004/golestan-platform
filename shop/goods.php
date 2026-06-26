<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
$db = (new Database())->getConnection();

$condition = $_GET['condition'] ?? '';
$category = $_GET['category'] ?? '';

$query = "SELECT * FROM products WHERE type='goods' AND is_active=1";
$params = [];
if ($condition) { $query .= " AND `condition`=?"; $params[] = $condition; }
if ($category) { $query .= " AND category=?"; $params[] = $category; }
$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$goods = $stmt->fetchAll();

$page_title = 'کالاها | فروشگاه | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
.shop-hero {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white; text-align: center; padding: 50px 20px 30px; border-radius: 0 0 30px 30px;
    margin-top: 68px; margin-bottom: 24px;
}
.shop-hero h1 { font-size: 2rem; }
.filter-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
.filter-chip {
    padding: 8px 16px; border-radius: 20px; border: 1px solid var(--border);
    background: var(--bg-card); color: var(--text-secondary); text-decoration: none;
    font-size: 0.85rem; transition: all 0.2s;
}
.filter-chip.active, .filter-chip:hover { background: var(--primary); color: white; border-color: var(--primary); }
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.product-card {
    background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
    padding: 20px; transition: all 0.2s; display: flex; flex-direction: column;
}
.product-card:hover { box-shadow: var(--shadow-md); border-color: var(--primary); }
.product-card .badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 0.75rem; margin-bottom: 8px; width: fit-content; }
.badge-new { background: var(--primary-light); color: var(--primary); }
.badge-used { background: #fef3c7; color: #92400e; }
.product-card h3 { font-size: 1rem; margin-bottom: 8px; flex: 1; }
.product-card .price { font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 12px; }
.product-card .stock { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px; }
</style>

<div class="shop-hero">
    <h1>🛍️ کالاهای کامپیوتری</h1>
    <p>قطعات نو و استوک با بهترین قیمت</p>
</div>

<div class="container">
    <div class="filter-bar">
        <a href="/shop/goods.php" class="filter-chip <?php echo !$condition?'active':''; ?>">همه</a>
        <a href="?condition=new" class="filter-chip <?php echo $condition=='new'?'active':''; ?>">🆕 نو</a>
        <a href="?condition=used" class="filter-chip <?php echo $condition=='used'?'active':''; ?>">♻️ استوک</a>
        <a href="?category=hardware" class="filter-chip <?php echo $category=='hardware'?'active':''; ?>">🖥️ سخت‌افزار</a>
        <a href="?category=accessories" class="filter-chip <?php echo $category=='accessories'?'active':''; ?>">⌨️ لوازم جانبی</a>
    </div>

    <?php if ($goods): ?>
    <div class="product-grid">
        <?php foreach ($goods as $g): ?>
        <div class="product-card">
            <span class="badge <?php echo $g['condition']=='new'?'badge-new':'badge-used'; ?>"><?php echo $g['condition']=='new'?'نو':'استوک'; ?></span>
            <h3><?php echo sanitize($g['name']); ?></h3>
            <?php if ($g['description']): ?><p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:8px;"><?php echo mb_substr(sanitize($g['description']), 0, 80); ?></p><?php endif; ?>
            <div class="price"><?php echo number_format($g['price']); ?> تومان</div>
            <div class="stock">📦 موجودی: <?php echo $g['stock']; ?> عدد</div>
            <a href="/shop/product.php?id=<?php echo $g['id']; ?>" class="btn btn-primary btn-sm">مشاهده</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:60px;">
        <p style="font-size:1.2rem;color:var(--text-muted);">😔 هنوز کالایی در این دسته ثبت نشده است.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>