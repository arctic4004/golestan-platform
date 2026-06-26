<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
$db = (new Database())->getConnection();

$category = $_GET['category'] ?? '';
$query = "SELECT * FROM products WHERE type='service' AND is_active=1";
$params = [];
if ($category) { $query .= " AND category=?"; $params[] = $category; }
$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$services = $stmt->fetchAll();

$page_title = 'خدمات | فروشگاه | ' . SITE_NAME;
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
.product-card h3 { font-size: 1rem; margin-bottom: 8px; flex: 1; }
.product-card .price { font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 12px; }
</style>

<div class="shop-hero">
    <h1>🔧 خدمات کامپیوتری</h1>
    <p>نصب ویندوز، لینوکس، طراحی سایت، امنیت شبکه و ...</p>
</div>

<div class="container">
    <div class="filter-bar">
        <a href="/shop/services.php" class="filter-chip <?php echo !$category?'active':''; ?>">همه</a>
        <a href="?category=computer" class="filter-chip <?php echo $category=='computer'?'active':''; ?>">💻 کامپیوتر</a>
        <a href="?category=web" class="filter-chip <?php echo $category=='web'?'active':''; ?>">🌐 طراحی سایت</a>
        <a href="?category=security" class="filter-chip <?php echo $category=='security'?'active':''; ?>">🔒 امنیت</a>
        <a href="?category=other" class="filter-chip <?php echo $category=='other'?'active':''; ?>">📦 سایر</a>
    </div>

    <?php if ($services): ?>
    <div class="product-grid">
        <?php foreach ($services as $s): ?>
        <div class="product-card">
            <h3><?php echo sanitize($s['name']); ?></h3>
            <?php if ($s['description']): ?><p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:8px;"><?php echo mb_substr(sanitize($s['description']), 0, 80); ?></p><?php endif; ?>
            <div class="price"><?php echo number_format($s['price']); ?> تومان</div>
            <a href="/shop/product.php?id=<?php echo $s['id']; ?>" class="btn btn-primary btn-sm">مشاهده و سفارش</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:60px;">
        <p style="font-size:1.2rem;color:var(--text-muted);">😔 هنوز خدماتی در این دسته ثبت نشده است.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>