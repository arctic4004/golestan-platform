<?php
require_once __DIR__ . '/config/constants.php';
$page_title = SITE_NAME . ' | چت هوشمند، ساخت عکس، پروژه‌های گیت‌هاب، فروشگاه و ابزارهای حرفه‌ای';
$page_description = 'کافی‌نت گلستان یاسوج؛ چت با Llama 4، ساخت عکس با AI، اتصال و تحلیل پروژه‌های گیت‌هاب، فروشگاه خدمات و کالا، ابزارهای ویرایش تصویر، تقویم و مدیریت تسک‌ها — همه رایگان و بدون محدودیت.';
require_once 'includes/header.php';
?>

<!-- Schema.org SEO -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "کافی‌نت گلستان",
  "applicationCategory": "AIApplication",
  "description": "پلتفرم جامع هوش مصنوعی با چت Llama 4، ساخت عکس، تحلیل پروژه‌های گیت‌هاب، فروشگاه، ابزارهای ویرایش تصویر و مدیریت تسک",
  "url": "https://golestanyasuj.ir",
  "offers": { "@type": "Offer", "price": "0", "priceCurrency": "IRR" }
}
</script>

<section class="hero-new">
    <div class="container">
        <div class="hero-new-content">
            <span class="hero-new-badge">✨ پلتفرم جامع هوش مصنوعی و خدمات کامپیوتری</span>
            <h1 class="hero-new-title">
                <span>هوش مصنوعی</span> در خدمت <span>کسب‌وکار</span>، <span>کدنویسی</span> و <span>خلاقیت</span> شما
            </h1>
            <p class="hero-new-desc">
                چت هوشمند با Llama 4، ساخت تصاویر شگفت‌انگیز،
                <strong>اتصال و تحلیل پروژه‌های گیت‌هاب با AI</strong>،
                فروشگاه خدمات و کالا، ابزارهای ویرایش عکس، تقویم و مدیریت تسک‌ها —
                همه در یک پلتفرم یکپارچه و <strong>کاملاً رایگان</strong>.
            </p>
            <div class="hero-new-actions">
                <?php if (isLoggedIn()): ?>
                    <a href="/user/dashboard/v2/chat.php" class="btn btn-light btn-lg"><i class="fas fa-comments"></i> چت با AI</a>
                    <a href="/projects/" class="btn btn-light btn-lg"><i class="fab fa-github"></i> پروژه‌های گیت‌هاب</a>
                <?php else: ?>
                    <a href="/signup.php" class="btn btn-light btn-lg"><i class="fas fa-rocket"></i> شروع رایگان</a>
                    <a href="/login.php" class="btn btn-light btn-lg"><i class="fas fa-sign-in-alt"></i> ورود</a>
                <?php endif; ?>
            </div>
            
            <!-- لینک‌های سریع -->
            <div class="hero-quick-links">
                <a href="/user/dashboard/v2/chat.php" class="hero-quick-link"><i class="fas fa-brain"></i> چت AI</a>
                <a href="/projects/" class="hero-quick-link"><i class="fab fa-github"></i> پروژه‌های گیت‌هاب</a>
                <a href="/user/dashboard/v2/image.php" class="hero-quick-link"><i class="fas fa-image"></i> ساخت عکس</a>
                <a href="/shop/" class="hero-quick-link"><i class="fas fa-store"></i> فروشگاه</a>
                <a href="/user/dashboard/v2/tools.php" class="hero-quick-link"><i class="fas fa-tools"></i> ابزارها</a>
                <a href="/user/dashboard/v2/tasks.php" class="hero-quick-link"><i class="fas fa-tasks"></i> تسک‌ها</a>
                <a href="/shop/agent.php" class="hero-quick-link"><i class="fas fa-robot"></i> مشاور AI</a>
            </div>
            
            <div class="hero-new-stats">
                <div class="stat-card"><span class="stat-icon">🦙</span><span class="stat-title">Llama 4</span><span class="stat-desc">مدل زبانی</span></div>
                <div class="stat-card"><span class="stat-icon">🎨</span><span class="stat-title">۳ مدل</span><span class="stat-desc">ساخت عکس</span></div>
                <div class="stat-card"><span class="stat-icon">📂</span><span class="stat-title">گیت‌هاب</span><span class="stat-desc">تحلیل کد با AI</span></div>
                <div class="stat-card"><span class="stat-icon">🛒</span><span class="stat-title">فروشگاه</span><span class="stat-desc">خدمات و کالا</span></div>
                <div class="stat-card"><span class="stat-icon">🆓</span><span class="stat-title">رایگان</span><span class="stat-desc">برای همیشه</span></div>
            </div>
        </div>
    </div>
</section>

<!-- خدمات -->
<section id="services" class="services-section">
    <div class="container">
        <h2 class="section-title">🚀 هر آنچه نیاز دارید، یکجا اینجاست</h2>
        <p class="section-subtitle">از هوش مصنوعی و تحلیل پروژه‌های گیت‌هاب تا فروشگاه و ابزارهای کاربردی</p>
        <div class="services-grid">
            <a href="/user/dashboard/v2/chat.php" class="service-card">
                <div class="service-icon"><i class="fas fa-brain"></i></div>
                <h3>💬 چت هوشمند</h3>
                <p>پرسش و پاسخ، برنامه‌نویسی، ترجمه و یادگیری با Llama 4</p>
            </a>
            <a href="/projects/" class="service-card">
                <div class="service-icon"><i class="fab fa-github"></i></div>
                <h3>📂 پروژه‌های گیت‌هاب</h3>
                <p>اتصال ریپازیتوری، تحلیل کد، رفع باگ و ویرایش با هوش مصنوعی</p>
            </a>
            <a href="/user/dashboard/v2/image.php" class="service-card">
                <div class="service-icon"><i class="fas fa-wand-magic-sparkles"></i></div>
                <h3>🎨 ساخت عکس با AI</h3>
                <p>خلق تصاویر واقع‌گرا، هنری و فانتزی با سه مدل مختلف</p>
            </a>
            <a href="/shop/" class="service-card">
                <div class="service-icon"><i class="fas fa-store"></i></div>
                <h3>🛒 فروشگاه</h3>
                <p>خدمات کامپیوتری، قطعات نو و استوک با بهترین قیمت</p>
            </a>
            <a href="/user/dashboard/v2/tools.php" class="service-card">
                <div class="service-icon"><i class="fas fa-tools"></i></div>
                <h3>🛠️ ابزارهای تصویر</h3>
                <p>ویرایش، برش، چرخش، حذف پس‌زمینه و تبدیل فرمت</p>
            </a>
            <a href="/user/dashboard/v2/tasks.php" class="service-card">
                <div class="service-icon"><i class="fas fa-tasks"></i></div>
                <h3>📋 تقویم و تسک‌ها</h3>
                <p>مدیریت وظایف با Kanban، تقویم و یادآوری</p>
            </a>
            <a href="/shop/agent.php" class="service-card">
                <div class="service-icon"><i class="fas fa-robot"></i></div>
                <h3>🤖 مشاور هوشمند</h3>
                <p>راهنمای خرید با هوش مصنوعی — بهترین انتخاب</p>
            </a>
            <a href="/user/dashboard/v2/settings.php" class="service-card">
                <div class="service-icon"><i class="fas fa-cog"></i></div>
                <h3>⚙️ تنظیمات پیشرفته</h3>
                <p>شخصی‌سازی تم، مدیریت حساب و امنیت</p>
            </a>
        </div>
    </div>
</section>

<!-- بخش پروژه‌های گیت‌هاب -->
<section class="models-section" style="background:var(--bg-secondary);">
    <div class="container">
        <h2 class="section-title">📂 تحلیل پروژه‌های گیت‌هاب با هوش مصنوعی</h2>
        <p class="section-subtitle">ریپازیتوری‌های خود را متصل کنید و از قدرت AI برای تحلیل، دیباگ و توسعه استفاده کنید</p>
        
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:16px;">
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:24px;text-align:center;">
                <div style="font-size:2.5rem;margin-bottom:8px;">🔍</div>
                <h4>تحلیل خودکار کد</h4>
                <p style="color:var(--text-secondary);font-size:0.9rem;">AI ساختار پروژه را بررسی و تحلیل جامع ارائه می‌دهد</p>
            </div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:24px;text-align:center;">
                <div style="font-size:2.5rem;margin-bottom:8px;">🐛</div>
                <h4>رفع باگ هوشمند</h4>
                <p style="color:var(--text-secondary);font-size:0.9rem;">باگ‌ها را پیدا کنید و با یک کلیک رفع کنید</p>
            </div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:24px;text-align:center;">
                <div style="font-size:2.5rem;margin-bottom:8px;">✏️</div>
                <h4>ویرایش کد با AI</h4>
                <p style="color:var(--text-secondary);font-size:0.9rem;">دستور بدهید — AI کد را بهینه و ویرایش کند</p>
            </div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:24px;text-align:center;">
                <div style="font-size:2.5rem;margin-bottom:8px;">💬</div>
                <h4>چت با کد پروژه</h4>
                <p style="color:var(--text-secondary);font-size:0.9rem;">مستقیم با AI درباره کد پروژه گفتگو کنید</p>
            </div>
        </div>
        
        <div style="text-align:center;margin-top:24px;">
            <a href="/projects/" class="btn btn-primary btn-lg"><i class="fab fa-github"></i> مشاهده پروژه‌های گیت‌هاب</a>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container" style="text-align:center;">
        <h2>🎯 همین حالا شروع کنید — کاملاً رایگان</h2>
        <p style="color:var(--text-secondary);margin-bottom:24px;">
            ۱۰۰۰ اعتبار هدیه برای چت و ساخت عکس | اتصال پروژه‌های گیت‌هاب | فروشگاه خدمات و کالا
        </p>
        <?php if (!isLoggedIn()): ?>
            <a href="/signup.php" class="btn btn-primary btn-lg"><i class="fas fa-user-plus"></i> ثبت‌نام رایگان</a>
        <?php else: ?>
            <a href="/user/dashboard/v2/chat.php" class="btn btn-primary btn-lg"><i class="fas fa-comments"></i> چت با AI</a>
            <a href="/projects/" class="btn btn-outline btn-lg" style="margin-right:12px;"><i class="fab fa-github"></i> پروژه‌های گیت‌هاب</a>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>