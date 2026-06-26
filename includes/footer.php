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
                    <div class="logo" style="margin-bottom:12px;">
                        <span class="logo-icon"><i class="fas fa-robot"></i></span>
                        <span><?php echo defined('SITE_NAME') ? SITE_NAME : 'کافی‌نت گلستان'; ?></span>
                    </div>
                    <p style="color:var(--text-secondary);font-size:0.85rem;line-height:1.8;">
                        پلتفرم جامع هوش مصنوعی، چت با Llama 4، ساخت عکس، تحلیل پروژه‌های گیت‌هاب، فروشگاه و ابزارهای حرفه‌ای
                    </p>
                </div>
                
                <div class="footer-col">
                    <h4>دسترسی سریع</h4>
                    <ul>
                        <li><a href="/">🏠 خانه</a></li>
                        <li><a href="/user/dashboard/v2/chat.php">💬 چت هوشمند</a></li>
                        <li><a href="/user/dashboard/v2/image.php">🎨 ساخت عکس</a></li>
                        <li><a href="/projects/">📂 پروژه‌های گیت‌هاب</a></li>
                        <li><a href="/shop/">🛒 فروشگاه</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>خدمات</h4>
                    <ul>
                        <li><a href="/user/dashboard/v2/chat.php">💬 دستیار AI</a></li>
                        <li><a href="/user/dashboard/v2/image.php">🎨 ساخت عکس</a></li>
                        <li><a href="/user/dashboard/v2/tools.php">🛠️ ابزارهای تصویر</a></li>
                        <li><a href="/#services">💻 خدمات کامپیوتری</a></li>
                        <li><a href="/#security">🔒 امنیت شبکه</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>تماس با ما</h4>
                    <ul>
                        <li style="display:flex;align-items:center;gap:6px;color:var(--text-muted);font-size:0.85rem;">
                            <i class="fas fa-map-marker-alt"></i> یاسوج، پاسداران، بین گلستان ۳ و ۴
                        </li>
                        <li>
                            <a href="tel:09177418286"><i class="fas fa-phone"></i> ۰۹۱۷۷۴۱۸۲۸۶</a>
                        </li>
                        <li>
                            <a href="mailto:arctic4004@gmail.com"><i class="fas fa-envelope"></i> arctic4004@gmail.com</a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo defined('SITE_NAME') ? SITE_NAME : 'کافی‌نت گلستان'; ?> | تمامی حقوق محفوظ است</p>
            </div>
        </div>
    </footer>
    <?php endif; ?>
    
    <!-- Theme Toggle -->
    <?php if (!isset($hide_footer) || !$hide_footer): ?>
    <button class="theme-toggle" onclick="toggleTheme()" title="تغییر تم روشن/تاریک">
        <i class="fas fa-moon"></i>
    </button>
    <?php endif; ?>
    
    <!-- Scripts -->
    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/main.js"></script>
    <?php if(isset($extra_js)): ?>
        <?php foreach($extra_js as $js): ?>
            <script src="/<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- CSRF & Site URL -->
    <script>
        window.SITE_URL = '<?php echo defined('SITE_URL') ? SITE_URL : 'https://golestanyasuj.ir'; ?>';
        window.CSRF_TOKEN = '<?php echo function_exists('generateCSRFToken') ? generateCSRFToken() : ''; ?>';
    </script>

    <!-- Register Service Worker (PWA) -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js')
                .then(function(reg) {
                    console.log('✅ PWA Service Worker registered');
                })
                .catch(function(err) {
                    console.log('❌ Service Worker failed', err);
                });
        });
    }
    </script>
</body>
</html>