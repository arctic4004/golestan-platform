<?php
// includes/navbar.php - نوبار نهایی با منوی موبایل و لینک پروژه‌ها
$current_page = basename($_SERVER['PHP_SELF']);
$isLoggedIn = function_exists('isLoggedIn') ? isLoggedIn() : false;
$isAdmin = function_exists('isAdmin') ? isAdmin() : false;
?>
<nav class="navbar" id="mainNav">
    <div class="container nav-container">
        <!-- لوگو -->
        <a href="/" class="logo" aria-label="<?php echo SITE_NAME; ?>">
            <div class="logo-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
                </svg>
            </div>
            <span class="logo-text"><span class="logo-title"><?php echo SITE_NAME; ?></span></span>
        </a>
        
        <!-- منوی اصلی -->
        <ul class="nav-menu" id="navMenu">
            <li><a href="/" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">خانه</a></li>
            <li class="nav-dropdown">
                <a href="#" class="dropdown-toggle" data-dropdown="servicesDropdown">خدمات <i class="fas fa-chevron-down"></i></a>
                <div class="dropdown-menu" id="servicesDropdown">
                    <a href="/user/dashboard/v2/chat.php"><i class="fas fa-brain"></i> چت هوشمند AI</a>
                    <a href="/user/dashboard/v2/image.php"><i class="fas fa-image"></i> ساخت عکس</a>
                    <a href="/user/dashboard/v2/tools.php"><i class="fas fa-tools"></i> ابزارهای تصویر</a>
                    <a href="/shop/"><i class="fas fa-store"></i> فروشگاه</a>
                    <a href="/projects/"><i class="fab fa-github"></i> پروژه‌های گیت‌هاب</a>
                    <a href="/shop/agent.php"><i class="fas fa-robot"></i> مشاور خرید</a>
                    <a href="/user/dashboard/v2/tasks.php"><i class="fas fa-tasks"></i> تقویم و تسک‌ها</a>
                </div>
            </li>
            <li><a href="/shop/">فروشگاه</a></li>
            <li><a href="/projects/">پروژه‌ها</a></li>
            <?php if($isLoggedIn): ?>
                <li><a href="/user/dashboard/v2/">داشبورد</a></li>
                <?php if($isAdmin): ?><li><a href="/admin/">مدیریت</a></li><?php endif; ?>
                <li class="user-menu">
                    <button class="user-trigger" data-user-dropdown>
                        <div class="user-avatar"><?php echo mb_substr($_SESSION['full_name'] ?? 'U', 0, 1); ?></div>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="dropdown-header">
                            <strong><?php echo sanitize($_SESSION['full_name'] ?? 'کاربر'); ?></strong>
                            <span><?php echo $_SESSION['phone'] ?? ''; ?></span>
                        </div>
                        <div class="dropdown-credits"><i class="fas fa-coins"></i> <?php echo number_format($_SESSION['credits'] ?? 0); ?></div>
                        <div class="dropdown-divider"></div>
                        <a href="/user/dashboard/v2/profile.php"><i class="fas fa-user"></i> پروفایل</a>
                        <a href="/user/dashboard/v2/settings.php"><i class="fas fa-cog"></i> تنظیمات</a>
                        <a href="/projects/"><i class="fab fa-github"></i> پروژه‌ها</a>
                        <a href="/shop/cart.php"><i class="fas fa-shopping-cart"></i> سبد خرید</a>
                        <div class="dropdown-divider"></div>
                        <a href="/logout.php" class="dropdown-logout"><i class="fas fa-sign-out-alt"></i> خروج</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="/login.php" class="btn-login-nav">ورود</a></li>
                <li><a href="/signup.php" class="btn-signup-nav">شروع رایگان</a></li>
            <?php endif; ?>
        </ul>
        
        <!-- دکمه همبرگر -->
        <button class="hamburger" aria-label="منو" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- Overlay موبایل -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<script>
(function() {
    'use strict';
    
    var menu = document.getElementById('navMenu');
    var overlay = document.getElementById('mobileOverlay');
    var hamburger = document.querySelector('.hamburger');
    var isMobile = function() { return window.innerWidth <= 768; };
    
    function openMenu() {
        if (!isMobile()) return;
        menu.style.cssText = 'position:fixed!important;top:60px!important;right:0!important;width:280px!important;height:'+(window.innerHeight-60)+'px!important;display:flex!important;flex-direction:column!important;z-index:1001!important;background:var(--bg-card,#fff)!important;padding:16px!important;overflow-y:auto!important;box-shadow:0 20px 60px rgba(0,0,0,0.3)!important;';
        menu.classList.add('active');
        overlay.classList.add('active');
        hamburger.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMenu() {
        menu.classList.remove('active');
        overlay.classList.remove('active');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        if (isMobile()) {
            menu.style.cssText = 'position:fixed;top:60px;right:-100%;width:280px;height:calc(100vh-60px);display:flex;flex-direction:column;z-index:1001;';
        } else {
            menu.style.cssText = '';
        }
    }
    
    hamburger.addEventListener('click', function(e) {
        e.stopPropagation();
        menu.classList.contains('active') ? closeMenu() : openMenu();
    });
    
    overlay.addEventListener('click', closeMenu);
    
    menu.addEventListener('click', function(e) {
        var link = e.target.closest('a');
        if (link && !link.classList.contains('dropdown-toggle') && isMobile()) {
            setTimeout(closeMenu, 200);
        }
    });
    
    document.addEventListener('click', function(e) {
        var toggle = e.target.closest('.dropdown-toggle');
        var userBtn = e.target.closest('[data-user-dropdown]');
        
        if (toggle) {
            e.preventDefault();
            e.stopPropagation();
            var id = toggle.getAttribute('data-dropdown');
            var dropdown = document.getElementById(id);
            if (dropdown) {
                var isOpen = dropdown.classList.contains('active');
                document.querySelectorAll('.dropdown-menu').forEach(function(d) { d.classList.remove('active'); });
                if (!isOpen) dropdown.classList.add('active');
            }
            return;
        }
        
        if (userBtn) {
            e.stopPropagation();
            var ud = document.getElementById('userDropdown');
            if (ud) ud.classList.toggle('active');
            return;
        }
        
        document.querySelectorAll('.dropdown-menu, .user-dropdown').forEach(function(d) {
            if (!d.contains(e.target)) d.classList.remove('active');
        });
    });
    
    window.addEventListener('resize', function() {
        if (!isMobile()) closeMenu();
    });
    
    window.addEventListener('scroll', function() {
        var nav = document.getElementById('mainNav');
        if (nav) nav.classList.toggle('scrolled', window.scrollY > 10);
    });
})();
</script>