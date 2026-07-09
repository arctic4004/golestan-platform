<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

$db = (new Database())->getConnection();
$cat_id = $_GET['id'] ?? 0;

$cat = $db->prepare("SELECT * FROM categories WHERE id = ?");
$cat->execute([$cat_id]);
$category = $cat->fetch();

if (!$category) { header("Location: /shop/"); exit(); }

$all_cats = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order")->fetchAll();
$page = max(1, $_GET['page'] ?? 1);
$per_page = 12;
$offset = ($page - 1) * $per_page;
$cat_name = $category['name'];

$total = $db->prepare("SELECT COUNT(*) FROM products WHERE is_active = 1 AND category = ?")->fetchColumn();
$total = $db->prepare("SELECT COUNT(*) FROM products WHERE is_active = 1 AND category = ?");
$total->execute([$cat_name]);
$total = $total->fetchColumn();

$products = $db->prepare("SELECT * FROM products WHERE is_active = 1 AND category = ? ORDER BY id DESC LIMIT $per_page OFFSET $offset");
$products->execute([$cat_name]);
$products = $products->fetchAll();
$total_pages = ceil($total / $per_page);

$page_title = $category['name'] . ' | فروشگاه | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

$catIcons = [
    'چاپ و تکثیر' => 'ph-printer', 'تایپ و ترجمه' => 'ph-keyboard', 'تبدیل فایل' => 'ph-file-arrow-up',
    'اینترنت و ایمیل' => 'ph-globe', 'انتقال اطلاعات' => 'ph-devices', 'طراحی گرافیک' => 'ph-paint-brush',
    'خدمات بانکی' => 'ph-bank', 'خودرو و راهور' => 'ph-car', 'پلیس +10' => 'ph-shield',
    'ثبت احوال' => 'ph-identification-card', 'قوه قضاییه' => 'ph-scales', 'تامین اجتماعی' => 'ph-heartbeat',
    'بیمه سلامت' => 'ph-first-aid', 'بیمه' => 'ph-umbrella', 'قطعات کامپیوتر' => 'ph-cpu',
];
$currentIcon = $catIcons[$category['name']] ?? 'ph-folder';
?>

<style>
.category-page { max-width: 1200px; margin: 80px auto 40px; padding: 0 20px; }
.cat-header { text-align: center; margin-bottom: 30px; }
.cat-header i { font-size: 3rem; color: var(--primary); margin-bottom: 10px; }
.cat-header h1 { font-size: 1.8rem; font-weight: 800; color: var(--text-primary); }
.cat-header p { color: var(--text-secondary); margin-top: 6px; }
.cat-filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; justify-content: center; }
.filter-chip { padding: 8px 18px; border-radius: 25px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-secondary); text-decoration: none; font-size: 0.85rem; font-weight: 500; transition: all 0.2s; display: flex; align-items: center; gap: 6px; }
.filter-chip:hover, .filter-chip.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
.product-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; text-decoration: none; color: inherit; transition: all 0.2s; position: relative; overflow: hidden; }
.product-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--primary), var(--secondary)); transform: scaleX(0); transition: 0.3s; }
.product-card:hover::before { transform: scaleX(1); }
.product-card:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); border-color: var(--primary); }
.product-card h3 { font-size: 1rem; margin-bottom: 6px; color: var(--text-primary); }
.product-card .p-cat { font-size: 0.7rem; color: var(--primary); margin-bottom: 8px; }
.product-card .p-price { font-weight: 700; color: var(--primary); font-size: 1.1rem; }
.product-card .p-desc { font-size: 0.8rem; color: var(--text-secondary); margin: 8px 0; }
.pagination { display: flex; justify-content: center; gap: 8px; margin-top: 30px; }
.page-link { padding: 8px 16px; border-radius: 10px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-primary); text-decoration: none; font-size: 0.9rem; transition: all 0.2s; }
.page-link:hover, .page-link.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.empty-state { text-align: center; padding: 80px 20px; color: var(--text-muted); }
.empty-state i { font-size: 4rem; display: block; margin-bottom: 16px; }
</style>

<div class="category-page">
    <div class="cat-header">
        <i class="ph <?= $currentIcon ?>"></i>
        <h1><?= htmlspecialchars($category['name']) ?></h1>
        <p><?= $total ?> محصول در این دسته</p>
    </div>
    <div class="cat-filters">
        <a href="/shop/" class="filter-chip"><i class="ph ph-grid-four"></i> همه</a>
        <?php foreach ($all_cats as $c): ?>
        <a href="/shop/category.php?id=<?= $c['id'] ?>" class="filter-chip <?= $c['id'] == $cat_id ? 'active' : '' ?>">
            <i class="ph ph-<?= $c['icon'] ?>"></i> <?= $c['name'] ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php if (empty($products)): ?>
    <div class="empty-state"><i class="ph ph-smiley-sad"></i><h3>محصولی در این دسته یافت نشد</h3><a href="/shop/" class="btn btn-primary" style="margin-top:16px">بازگشت به فروشگاه</a></div>
    <?php else: ?>
    <div class="products-grid">
        <?php foreach ($products as $p): ?>
        <a href="/shop/product.php?id=<?= $p['id'] ?>" class="product-card">
            <div class="p-cat"><?= htmlspecialchars($p['category']) ?></div>
            <h3><?= htmlspecialchars($p['name']) ?></h3>
            <div class="p-desc"><?= mb_substr($p['description'] ?? '', 0, 80) ?>...</div>
            <div class="p-price"><?= number_format($p['price']) ?> تومان</div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?id=<?= $cat_id ?>&page=<?= $i ?>" class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>