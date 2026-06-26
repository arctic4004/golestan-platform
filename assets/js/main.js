// assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Mobile menu toggle
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
    }
    
    // Close mobile menu on click outside
    document.addEventListener('click', (e) => {
        if (navMenu?.classList.contains('active') && 
            !e.target.closest('.nav-menu') && 
            !e.target.closest('.hamburger')) {
            navMenu.classList.remove('active');
            hamburger?.classList.remove('active');
        }
    });
    
    // Dropdown menus
    document.querySelectorAll('.user-trigger').forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const dropdown = trigger.nextElementSibling;
            dropdown?.classList.toggle('show');
        });
    });
    
    // Close dropdowns on click outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.user-menu')) {
            document.querySelectorAll('.dropdown.show').forEach(d => d.classList.remove('show'));
        }
    });
    
    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showToast('لطفاً تمام فیلدهای الزامی را پر کنید.', 'error');
            }
        });
    });
    
    // Toast notifications
    window.showToast = function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };
    
    // Load chat if initial message exists
    const initialMessage = sessionStorage.getItem('initialMessage');
    if (initialMessage) {
        sessionStorage.removeItem('initialMessage');
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.value = initialMessage;
            document.getElementById('chatForm')?.dispatchEvent(new Event('submit'));
        }
    }
});

// Toast styles (add to CSS)
const toastStyles = `
.toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    color: white;
    z-index: 9999;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s;
}

.toast.show {
    transform: translateY(0);
    opacity: 1;
}

.toast-info {
    background: #2196f3;
}

.toast-success {
    background: #4caf50;
}

.toast-error {
    background: #f44336;
}
`;

// Add toast styles
const styleSheet = document.createElement('style');
styleSheet.textContent = toastStyles;
document.head.appendChild(styleSheet);