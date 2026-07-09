<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
$db = (new Database())->getConnection();

$category = $_GET['category'] ?? '';
$query = "SELECT * FROM products WHERE type='service' AND is_active=1";
$params = [];
if ($category) { $query .= " AND category=?"; $params[] = $category; }
$query .= " ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$services = $stmt->fetchAll();

// دسته‌بندی‌های موجود
$all_cats = $db->query("SELECT DISTINCT category FROM products WHERE type='service' AND is_active=1 ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'خدمات | فروشگاه | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

$catIcons = [
    'چاپ و تکثیر' => 'ph-printer', 'تایپ و ترجمه' => 'ph-keyboard', 'تبدیل فایل' => 'ph-file-arrow-up',
    'اینترنت و ایمیل' => 'ph-globe', 'انتقال اطلاعات' => 'ph-devices', 'طراحی گرافیک' => 'ph-paint-brush',
    'خدمات بانکی' => 'ph-bank', 'خودرو و راهور' => 'ph-car', 'پلیس +10' => 'ph-shield',
    'ثبت احوال' => 'ph-identification-card', 'قوه قضاییه' => 'ph-scales', 'تامین اجتماعی' => 'ph-heartbeat',
    'بیمه سلامت' => 'ph-first-aid', 'بیمه' => 'ph-umbrella', 'قطعات کامپیوتر' => 'ph-cpu',
];
?>

<style>
.services-hero { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; text-align: center; padding: 50px 20px 30px; border-radius: 0 0 30px 30px; margin-top: 62px; margin-bottom: 24px; }
.services-hero h1 { font-size: 2rem; font-weight: 800; }
.services-hero p { opacity: 0.9; }
.filter-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; justify-content: center; }
.filter-chip { padding: 8px 18px; border-radius: 25px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-secondary); text-decoration: none; font-size: 0.85rem; transition: all 0.2s; display: flex; align-items: center; gap: 6px; }
.filter-chip.active, .filter-chip:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
.product-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; transition: all 0.2s; position: relative; overflow: hidden; }
.product-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--primary), var(--secondary)); transform: scaleX(0); transition: 0.3s; }
.product-card:hover::before { transform: scaleX(1); }
.product-card:hover { box-shadow: var(--shadow-md); border-color: var(--primary); transform: translateY(-2px); }
.product-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 8px; color: var(--text-primary); }
.product-card .price { font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 12px; }
.product-card .p-desc { font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 12px; }
</style>

<div class="services-hero">
    <h1><i class="ph ph-wrench"></i> خدمات کافی‌نت</h1>
    <p>تمامی خدمات کامپیوتری، اداری و بانکی</p>
</div>

<div class="container">
    <div class="filter-bar">
        <a href="/shop/services.php" class="filter-chip <?= !$category?'active':'' ?>"><i class="ph ph-grid-four"></i> همه</a>
        <?php foreach ($all_cats as $cat): ?>
        <a href="?category=<?= urlencode($cat) ?>" class="filter-chip <?= $category==$cat?'active':'' ?>">
            <i class="ph <?= $catIcons[$cat] ?? 'ph-folder' ?>"></i> <?= $cat ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($services): ?>
    <div class="product-grid">
        <?php foreach ($services as $s): ?>
        <div class="product-card">
            <h3><?= sanitize($s['name']) ?></h3>
            <?php if ($s['description']): ?><p class="p-desc"><?= mb_substr(sanitize($s['description']), 0, 80) ?></p><?php endif; ?>
            <div class="price"><?= number_format($s['price']) ?> تومان</div>
            <a href="/shop/product.php?id=<?= $s['id'] ?>" class="btn btn-primary btn-sm"><i class="ph ph-arrow-right"></i> مشاهده و سفارش</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:60px;"><i class="ph ph-smiley-sad" style="font-size:3rem;color:var(--text-muted)"></i><p style="color:var(--text-muted);margin-top:16px;">هنوز خدماتی در این دسته ثبت نشده است.</p></div>
    <?php endif; ?>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>