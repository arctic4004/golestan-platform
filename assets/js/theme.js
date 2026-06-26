// assets/js/theme.js
// تغییر تم روشن/تاریک - کاملاً مستقل از main.js

(function() {
    // چک تم ذخیره شده
    const savedTheme = localStorage.getItem('golestan_theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
    
    // تابع تغییر تم
    window.toggleTheme = function() {
        const current = document.documentElement.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('golestan_theme', next);
        updateThemeIcon(next);
        
        // ذخیره در سرور (اگر کاربر لاگین باشه)
        fetch('/set_theme.php?theme=' + next + '&ajax=1')
            .catch(() => {});
    };
    
    function updateThemeIcon(theme) {
        const icon = document.querySelector('.theme-toggle i');
        if (icon) {
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }
})();