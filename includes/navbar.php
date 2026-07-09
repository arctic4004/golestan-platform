<?php
$current_page = basename($_SERVER['PHP_SELF']);
$isLoggedIn = function_exists('isLoggedIn') ? isLoggedIn() : false;
$isAdmin = function_exists('isAdmin') ? isAdmin() : false;
?>
<nav class="navbar" id="mainNav">
    <div class="container nav-container">
        <a href="/" class="logo" aria-label="<?php echo SITE_NAME; ?>">
            <div class="logo-icon">
                <i class="ph-bold ph-cpu" style="font-size:22px"></i>
            </div>
            <span class="logo-text"><span class="logo-title"><?php echo SITE_NAME; ?></span></span>
        </a>
        
        <button class="hamburger" id="hamburgerBtn" aria-label="منو" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        
        <ul class="nav-menu" id="navMenu">
            <li><a href="/"><i class="ph ph-house"></i> خانه</a></li>
            
            <li class="nav-dropdown">
                <a href="#" class="dropdown-toggle"><i class="ph ph-wrench"></i> خدمات <i class="ph ph-caret-down"></i></a>
                <div class="dropdown-menu">
                    <a href="/user/dashboard/v2/chat.php"><i class="ph ph-brain"></i> چت AI</a>
                    <a href="/user/dashboard/v2/image.php"><i class="ph ph-image"></i> ساخت عکس</a>
                    <a href="/shop/"><i class="ph ph-storefront"></i> فروشگاه</a>
                    <a href="/projects/"><i class="ph ph-github-logo"></i> پروژه‌ها</a>
                </div>
            </li>
            
            <li><a href="/shop/"><i class="ph ph-storefront"></i> فروشگاه</a></li>
            <li><a href="/projects/"><i class="ph ph-github-logo"></i> پروژه‌ها</a></li>
            
            <?php if($isLoggedIn): ?>
                <li><a href="/user/dashboard/v2/"><i class="ph ph-kanban"></i> داشبورد</a></li>
                <?php if($isAdmin): ?>
                    <li class="nav-dropdown">
                        <a href="#" class="dropdown-toggle"><i class="ph ph-gear"></i> مدیریت <i class="ph ph-caret-down"></i></a>
                        <div class="dropdown-menu">
                            <a href="/admin/"><i class="ph ph-chart-bar"></i> پنل مدیریت</a>
                            <a href="/admin/?tab=customers"><i class="ph ph-users-three"></i> مشتریان CRM</a>
                            <a href="/admin/?tab=users"><i class="ph ph-user-gear"></i> کاربران</a>
                        </div>
                    </li>
                <?php endif; ?>
                <li class="user-menu">
                    <a href="#" class="user-trigger-link">
                        <div class="user-avatar"><?php echo mb_substr($_SESSION['full_name'] ?? 'U', 0, 1); ?></div>
                        <span class="user-name"><?php echo sanitize($_SESSION['full_name'] ?? 'کاربر'); ?></span>
                    </a>
                    <div class="user-dropdown">
                        <div class="dropdown-header">
                            <strong><?php echo sanitize($_SESSION['full_name'] ?? 'کاربر'); ?></strong>
                            <span><?php echo $_SESSION['phone'] ?? ''; ?></span>
                        </div>
                        <div class="dropdown-credits"><i class="ph ph-coin"></i> <?php echo number_format($_SESSION['credits'] ?? 0); ?> اعتبار</div>
                        <div class="dropdown-divider"></div>
                        <a href="/user/dashboard/v2/chat.php"><i class="ph ph-chats-circle"></i> چت جدید</a>
                        <a href="/user/dashboard/v2/"><i class="ph ph-kanban"></i> داشبورد</a>
                        <a href="/user/dashboard/v2/chat.php"><i class="ph ph-brain"></i> چت AI</a>
                        <a href="/projects/"><i class="ph ph-github-logo"></i> پروژه‌ها</a>
                        <a href="/user/dashboard/v2/image.php"><i class="ph ph-image"></i> ساخت عکس</a>
                        <a href="/user/dashboard/v2/tools.php"><i class="ph ph-wrench"></i> ابزارها</a>
                        <a href="/user/dashboard/v2/tasks.php"><i class="ph ph-kanban"></i> تسک‌ها</a>
                        <a href="/shop/"><i class="ph ph-storefront"></i> فروشگاه</a>
                        <a href="/user/dashboard/v2/history.php"><i class="ph ph-clock-counter-clockwise"></i> تاریخچه</a>
                        <a href="/user/dashboard/v2/profile.php"><i class="ph ph-user"></i> پروفایل</a>
                        <div class="dropdown-divider"></div>
                        <a href="/logout.php" style="color:#ef4444"><i class="ph ph-sign-out"></i> خروج</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="/login.php" class="btn-login-nav"><i class="ph ph-sign-in"></i> ورود</a></li>
                <li><a href="/signup.php" class="btn-signup-nav"><i class="ph ph-user-plus"></i> شروع رایگان</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<div class="mobile-overlay" id="mobileOverlay"></div>

<script>
(function() {
    var menu = document.getElementById('navMenu');
    var overlay = document.getElementById('mobileOverlay');
    var hamburger = document.getElementById('hamburgerBtn');
    var isOpen = false;
    
    function isMobile() { return window.innerWidth <= 768; }
    
    function openMenu() {
        if (!isMobile()) return;
        menu.classList.add('active');
        overlay.classList.add('active');
        hamburger.classList.add('active');
        hamburger.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        isOpen = true;
    }
    
    function closeMenu() {
        menu.classList.remove('active');
        overlay.classList.remove('active');
        hamburger.classList.remove('active');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        isOpen = false;
    }
    
    function toggleMenu() {
        if (isOpen) closeMenu();
        else openMenu();
    }
    
    hamburger.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleMenu();
    });
    
    overlay.addEventListener('click', closeMenu);
    
    menu.addEventListener('click', function(e) {
        var link = e.target.closest('a');
        if (link && !link.classList.contains('dropdown-toggle') && isMobile()) {
            setTimeout(closeMenu, 200);
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isOpen) closeMenu();
    });
    
    document.addEventListener('click', function(e) {
        var toggle = e.target.closest('.dropdown-toggle');
        var userLink = e.target.closest('.user-trigger-link');
        
        if (toggle) {
            e.preventDefault();
            e.stopPropagation();
            var dropdown = toggle.nextElementSibling;
            if (dropdown) {
                var wasActive = dropdown.classList.contains('active');
                document.querySelectorAll('.dropdown-menu, .user-dropdown').forEach(function(d) { d.classList.remove('active'); });
                if (!wasActive) dropdown.classList.add('active');
            }
            return;
        }
        
        if (userLink) {
            e.preventDefault();
            e.stopPropagation();
            var dropdown = userLink.nextElementSibling;
            if (dropdown) dropdown.classList.toggle('active');
            return;
        }
        
        if (!e.target.closest('.dropdown-menu') && !e.target.closest('.user-dropdown')) {
            document.querySelectorAll('.dropdown-menu, .user-dropdown').forEach(function(d) { d.classList.remove('active'); });
        }
    });
    
    window.addEventListener('resize', function() {
        if (!isMobile() && isOpen) closeMenu();
    });
})();
</script>