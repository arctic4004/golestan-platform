<?php
require_once __DIR__ . '/config/constants.php';
$page_title = SITE_NAME . ' | چت هوشمند، ساخت عکس، مشاوره و فروشگاه';
$page_description = 'کافی‌نت گلستان یاسوج؛ چت هوشمند با Llama 4، ساخت عکس با AI، مشاوره هوشمند، فروشگاه خدمات و کالا — همه رایگان و حرفه‌ای.';
require_once 'includes/header.php';
?>

<!-- Schema.org SEO -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "کافی‌نت گلستان",
  "applicationCategory": "AIApplication",
  "description": "پلتفرم جامع هوش مصنوعی با چت Llama 4، ساخت عکس، مشاور هوشمند، فروشگاه و ابزارهای حرفه‌ای",
  "url": "https://golestanyasuj.ir",
  "offers": { "@type": "Offer", "price": "0", "priceCurrency": "IRR" }
}
</script>

<!-- ===== HERO ===== -->
<section class="hero-new">
    <div class="container">
        <div class="hero-new-content">
            <div class="hero-new-badge">
                <span class="pulse"></span> هوش مصنوعی در خدمت کسب‌وکار شما
            </div>
            <h1 class="hero-new-title">
                هر سوالی داری، <span>از AI بپرس</span>
            </h1>
            <p class="hero-new-desc">
                <strong>چت هوشمند</strong> برای پاسخ به هر سوال، <strong>ساخت تصویر</strong> با ۳ مدل خلاقانه،
                <strong>مشاوره خرید</strong> با ورودی صوتی، و <strong>فروشگاه</strong> ۱۰۰+ خدمات —
                همه در یک پلتفرم <strong>کاملاً رایگان</strong>.
            </p>
            
            <div id="installBanner" style="display:none; margin-bottom:20px;">
                <button id="btnInstall" class="btn btn-light btn-lg" style="background:#f59e0b; color:#fff; border:none; box-shadow:0 4px 15px rgba(245,158,11,0.4);">
                    <i class="ph ph-download-simple"></i> نصب برنامه
                </button>
                <p style="color:white; margin-top:8px; font-size:0.85rem; opacity:0.8;">با نصب برنامه، سریع‌تر و راحت‌تر از خدمات استفاده کنید</p>
            </div>
            
            <div class="hero-new-actions">
                <?php if (isLoggedIn()): ?>
                    <a href="/user/dashboard/v2/chat.php" class="btn btn-light btn-lg">
                        <i class="ph ph-brain"></i> چت با AI
                    </a>
                    <a href="/user/dashboard/v2/image.php" class="btn btn-light btn-lg">
                        <i class="ph ph-image"></i> ساخت عکس
                    </a>
                <?php else: ?>
                    <a href="/signup.php" class="btn btn-light btn-lg">
                        <i class="ph ph-rocket"></i> شروع رایگان — ۱۰۰۰ اعتبار هدیه
                    </a>
                    <a href="/login.php" class="btn btn-outline btn-lg" style="border-color:rgba(255,255,255,0.5);color:white;">
                        <i class="ph ph-sign-in"></i> ورود
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="hero-quick-links">
                <a href="/user/dashboard/v2/chat.php" class="hero-quick-link"><i class="ph ph-brain"></i> چت AI</a>
                <a href="/user/dashboard/v2/image.php" class="hero-quick-link"><i class="ph ph-image"></i> ساخت عکس</a>
                <a href="/shop/agent.php" class="hero-quick-link"><i class="ph ph-robot"></i> مشاور هوشمند</a>
                <a href="/shop/" class="hero-quick-link"><i class="ph ph-storefront"></i> فروشگاه</a>
                <a href="/user/dashboard/v2/tools.php" class="hero-quick-link"><i class="ph ph-wrench"></i> ابزارها</a>
                <a href="/projects/" class="hero-quick-link"><i class="ph ph-github-logo"></i> پروژه‌ها</a>
            </div>
            
            <!-- Stats با استایل جدید -->
            <div class="hero-new-stats">
                <div class="stat-card-hero">
                    <i class="ph ph-brain"></i>
                    <span class="stat-title">Llama 4</span>
                    <span class="stat-desc">مدل زبانی</span>
                </div>
                <div class="stat-card-hero">
                    <i class="ph ph-image"></i>
                    <span class="stat-title">۳ مدل</span>
                    <span class="stat-desc">ساخت عکس</span>
                </div>
                <div class="stat-card-hero">
                    <i class="ph ph-robot"></i>
                    <span class="stat-title">مشاور AI</span>
                    <span class="stat-desc">ورودی صوتی</span>
                </div>
                <div class="stat-card-hero">
                    <i class="ph ph-storefront"></i>
                    <span class="stat-title">فروشگاه</span>
                    <span class="stat-desc">۱۰۰+ خدمت</span>
                </div>
                <div class="stat-card-hero">
                    <i class="ph ph-gift"></i>
                    <span class="stat-title">رایگان</span>
                    <span class="stat-desc">برای همیشه</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== خدمات ===== -->
<section id="services">
    <div class="container">
        <div class="section-header">
            <span class="section-badge"><i class="ph ph-rocket"></i> خدمات ویژه</span>
            <h2 class="section-title">همه چی تو یه جا</h2>
            <p class="section-subtitle">از چت هوشمند تا فروشگاه — هر چیزی که برای کسب‌وکار و خلاقیت نیاز داری</p>
        </div>
        
        <div class="services-grid">
            <a href="/user/dashboard/v2/chat.php" class="service-card">
                <div class="service-icon"><i class="ph ph-brain"></i></div>
                <h3>چت هوشمند AI</h3>
                <p>پرسش و پاسخ، برنامه‌نویسی، ترجمه و یادگیری با قدرت Llama 4</p>
            </a>
            <a href="/user/dashboard/v2/image.php" class="service-card">
                <div class="service-icon"><i class="ph ph-image"></i></div>
                <h3>ساخت عکس با AI</h3>
                <p>خلق تصاویر واقع‌گرا، هنری و فانتزی با ۳ مدل مختلف</p>
            </a>
            <a href="/shop/agent.php" class="service-card">
                <div class="service-icon"><i class="ph ph-robot"></i></div>
                <h3>مشاور هوشمند</h3>
                <p>سوالات خود را بپرسید یا با صدای خود جستجو کنید</p>
            </a>
            <a href="/shop/" class="service-card">
                <div class="service-icon"><i class="ph ph-storefront"></i></div>
                <h3>فروشگاه</h3>
                <p>۱۰۰+ خدمت کامپیوتری، بانکی، اداری و قطعات</p>
            </a>
            <a href="/projects/" class="service-card">
                <div class="service-icon"><i class="ph ph-github-logo"></i></div>
                <h3>پروژه‌های گیت‌هاب</h3>
                <p>اتصال ریپازیتوری، تحلیل کد، رفع باگ و ویرایش با AI</p>
            </a>
            <a href="/user/dashboard/v2/tasks.php" class="service-card">
                <div class="service-icon"><i class="ph ph-kanban"></i></div>
                <h3>تقویم و تسک‌ها</h3>
                <p>مدیریت وظایف با Kanban، تقویم شمسی و یادآوری هوشمند</p>
            </a>
        </div>
    </div>
</section>

<!-- ===== چرا گلستان ===== -->
<section style="background: var(--bg-secondary);">
    <div class="container">
        <div class="section-header">
            <span class="section-badge"><i class="ph ph-chart-bar"></i> چرا گلستان؟</span>
            <h2 class="section-title">یه پلتفرم، بی‌نهایت امکان</h2>
        </div>
        
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; text-align: center;">
            <?php
            $stats = [
                ['icon' => 'ph-brain', 'title' => 'Llama 4', 'desc' => 'مدل زبانی', 'color' => 'var(--primary)'],
                ['icon' => 'ph-image', 'title' => '۳ مدل تصویر', 'desc' => 'SDXL · Lightning', 'color' => 'var(--secondary)'],
                ['icon' => 'ph-robot', 'title' => 'مشاور AI', 'desc' => 'ورودی صوتی', 'color' => 'var(--accent)'],
                ['icon' => 'ph-storefront', 'title' => 'فروشگاه', 'desc' => '۱۰۰+ خدمت', 'color' => 'var(--success-fixed)'],
                ['icon' => 'ph-wrench', 'title' => 'ابزارها', 'desc' => 'ویرایش تصویر', 'color' => 'var(--accent-fixed)'],
                ['icon' => 'ph-gift', 'title' => 'رایگان', 'desc' => 'برای همیشه', 'color' => 'var(--danger-fixed)'],
            ];
            foreach ($stats as $s):
            ?>
            <div class="service-card" style="text-align:center; cursor:default;">
                <div style="font-size:2.5rem; margin-bottom:8px; color:<?= $s['color'] ?>"><i class="ph <?= $s['icon'] ?>"></i></div>
                <div style="font-weight:800; font-size:1.1rem; color:<?= $s['color'] ?>;"><?= $s['title'] ?></div>
                <div style="color:var(--text-secondary); font-size:0.85rem; margin-top:4px;"><?= $s['desc'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== CTA ===== -->
<section style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
    <div class="container" style="text-align:center; padding: 40px 0;">
        <h2 style="font-size:2rem; font-weight:800; margin-bottom:12px;"><i class="ph ph-rocket"></i> آماده‌ای شروع کنی؟</h2>
        <p style="font-size:1.1rem; opacity:0.9; margin-bottom:28px; max-width:500px; margin-left:auto; margin-right:auto;">
            ثبت‌نام کن، ۱۰۰۰ اعتبار هدیه بگیر و همه ابزارها رو رایگان امتحان کن
        </p>
        <?php if (!isLoggedIn()): ?>
            <a href="/signup.php" class="btn btn-light btn-lg"><i class="ph ph-user-plus"></i> ثبت‌نام رایگان</a>
        <?php else: ?>
            <a href="/user/dashboard/v2/chat.php" class="btn btn-light btn-lg"><i class="ph ph-chats-circle"></i> چت با AI</a>
            <a href="/user/dashboard/v2/image.php" class="btn btn-light btn-lg" style="margin-right:10px;"><i class="ph ph-image"></i> ساخت عکس</a>
        <?php endif; ?>
    </div>
</section>

<script>
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    document.getElementById('installBanner').style.display = 'block';
});
document.getElementById('btnInstall').addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    console.log(`User response: ${outcome}`);
    document.getElementById('installBanner').style.display = 'none';
    deferredPrompt = null;
});
window.addEventListener('appinstalled', () => {
    document.getElementById('installBanner').style.display = 'none';
});
</script>

<?php require_once 'includes/footer.php'; ?>