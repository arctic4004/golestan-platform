<?php
// includes/footer.php
?>
    </main>
    
    <?php if (!isset($hide_footer) || !$hide_footer): ?>
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <span class="footer-logo-icon"><i class="ph ph-cpu" style="font-size:24px"></i></span>
                        <span><?php echo defined('SITE_NAME') ? SITE_NAME : 'کافی‌نت گلستان'; ?></span>
                    </div>
                    <p class="footer-desc">
                        پلتفرم جامع هوش مصنوعی، چت با Llama 4، ساخت عکس، تحلیل پروژه‌های گیت‌هاب، فروشگاه و ابزارهای حرفه‌ای
                    </p>
                </div>
                
                <div class="footer-col">
                    <h4>دسترسی سریع</h4>
                    <ul>
                        <li><a href="/"><i class="ph ph-house"></i> خانه</a></li>
                        <li><a href="/user/dashboard/v2/chat.php"><i class="ph ph-chats-circle"></i> چت هوشمند</a></li>
                        <li><a href="/user/dashboard/v2/image.php"><i class="ph ph-image"></i> ساخت عکس</a></li>
                        <li><a href="/projects/"><i class="ph ph-github-logo"></i> پروژه‌های گیت‌هاب</a></li>
                        <li><a href="/shop/"><i class="ph ph-storefront"></i> فروشگاه</a></li>
                        <?php if (isLoggedIn() && isAdmin()): ?>
                        <li><a href="/admin/"><i class="ph ph-gear"></i> پنل مدیریت</a></li>
                        <li><a href="/admin/?tab=customers"><i class="ph ph-users-three"></i> مشتریان CRM</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>خدمات</h4>
                    <ul>
                        <li><a href="/user/dashboard/v2/chat.php"><i class="ph ph-brain"></i> دستیار AI</a></li>
                        <li><a href="/user/dashboard/v2/image.php"><i class="ph ph-image"></i> ساخت عکس</a></li>
                        <li><a href="/user/dashboard/v2/tools.php"><i class="ph ph-wrench"></i> ابزارهای تصویر</a></li>
                        <li><a href="/#services"><i class="ph ph-desktop"></i> خدمات کامپیوتری</a></li>
                        <li><a href="/#security"><i class="ph ph-shield-check"></i> امنیت شبکه</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>تماس با ما</h4>
                    <ul class="footer-contact">
                        <li><i class="ph ph-map-pin"></i> یاسوج، پاسداران، بین گلستان ۳ و ۴</li>
                        <li><a href="tel:09177418286"><i class="ph ph-phone"></i> ۰۹۱۷۷۴۱۸۲۸۶</a></li>
                        <li><a href="mailto:arctic4004@gmail.com"><i class="ph ph-envelope"></i> arctic4004@gmail.com</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo jalali_date('Y'); ?> <?php echo defined('SITE_NAME') ? SITE_NAME : 'کافی‌نت گلستان'; ?> | تمامی حقوق محفوظ است</p>
            </div>
        </div>
    </footer>
    <?php endif; ?>
    
    <!-- Theme Floating Button -->
    <?php if (!isset($hide_footer) || !$hide_footer): ?>
    <div class="theme-floating-btn" id="themeFAB">
        <button class="fab-toggle" onclick="toggleThemePanel()" title="تغییر تم">
            <i class="ph ph-palette"></i>
        </button>
        
        <div class="theme-panel" id="themePanel">
            <div class="panel-header">
                <span><i class="ph ph-paint-brush"></i> انتخاب تم</span>
                <button onclick="toggleThemePanel()" class="close-panel"><i class="ph ph-x"></i></button>
            </div>
            
            <div class="color-options">
                <?php
                $theme_list = [
                    'sapphire' => '#4f46e5',
                    'emerald'  => '#059669',
                    'ruby'     => '#dc2626',
                    'amber'    => '#d97706',
                    'amethyst' => '#9333ea',
                    'teal'     => '#0d9488',
                    'rose'     => '#e11d48',
                    'indigo'   => '#6366f1',
                    'cyan'     => '#06b6d4',
                ];
                foreach ($theme_list as $key => $color):
                ?>
                <button class="color-swatch" style="background:<?= $color ?>" onclick="changeTheme('<?= $key ?>')" title="<?= $key ?>"></button>
                <?php endforeach; ?>
            </div>
            
            <div class="mode-options">
                <button class="mode-btn light" onclick="changeMode('light')">
                    <i class="ph ph-sun"></i> روشن
                </button>
                <button class="mode-btn dark" onclick="changeMode('dark')">
                    <i class="ph ph-moon"></i> تاریک
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/main.js"></script>
    <?php if(isset($extra_js)): ?>
        <?php foreach($extra_js as $js): ?>
            <script src="/<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <script>
        window.SITE_URL = '<?php echo defined('SITE_URL') ? SITE_URL : 'https://golestanyasuj.ir'; ?>';
        window.CSRF_TOKEN = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
    </script>

    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js')
                .then(function(reg) { console.log('✅ PWA registered'); })
                .catch(function(err) { console.log('❌ SW failed', err); });
        });
    }
    
    // ========== توابع تغییر تم (با localStorage) ==========
    function toggleThemePanel() {
        var panel = document.getElementById('themePanel');
        if (panel) panel.classList.toggle('active');
    }
    
    function changeTheme(color) {
        document.documentElement.setAttribute('data-theme', color);
        localStorage.setItem('theme_color', color);
        saveTheme(color, null);
    }
    
    function changeMode(mode) {
        document.documentElement.setAttribute('data-mode', mode);
        localStorage.setItem('theme_mode', mode);
        saveTheme(null, mode);
    }
    
    function saveTheme(color, mode) {
        var formData = new FormData();
        if (color) formData.append('theme_color', color);
        if (mode) formData.append('theme_mode', mode);
        
        fetch('/set_theme.php', {
            method: 'POST',
            body: formData
        }).then(function(r) { return r.json(); }).then(function(d) {
            if (!d.success) console.warn('Theme save failed on server');
        }).catch(function(e) {
            console.warn('Theme save network error', e);
        });
    }
    
    // هنگام بارگذاری: localStorage همیشه اولویت دارد (اگر وجود داشته باشد)
    (function() {
        var html = document.documentElement;
        var savedColor = localStorage.getItem('theme_color');
        var savedMode = localStorage.getItem('theme_mode');
        
        if (savedColor) {
            html.setAttribute('data-theme', savedColor);
        }
        if (savedMode) {
            html.setAttribute('data-mode', savedMode);
        }
    })();
    </script>
</body>
</html>