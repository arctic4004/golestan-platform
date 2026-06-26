<?php
// test_mobile_menu.php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'تست';
$_SESSION['phone'] = '09177418286';
$_SESSION['credits'] = 999999;
$_SESSION['is_admin'] = true;
$_SESSION['theme'] = 'light';

require_once 'config/constants.php';
require_once 'includes/functions.php';
$page_title = 'تست منوی موبایل';
require_once 'includes/header.php';
?>

<style>
.test-controls {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 16px;
    z-index: 2000;
    box-shadow: var(--shadow-xl);
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
}
.test-controls button {
    padding: 8px 16px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--bg-primary);
    color: var(--text-primary);
    cursor: pointer;
    font-family: var(--font);
}
.test-info {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px;
    margin: 100px 20px 20px;
    text-align: center;
}
.test-info code {
    background: var(--bg-tertiary);
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
}
</style>

<div class="test-info">
    <h2>📱 تست منوی موبایل</h2>
    <p>عرض فعلی: <strong id="widthDisplay">-</strong>px</p>
    <p>وضعیت منو: <strong id="menuStatus">بسته</strong></p>
    <p>وضعیت overlay: <strong id="overlayStatus">بسته</strong></p>
    <p>hamburger aria-expanded: <strong id="hamburgerStatus">false</strong></p>
    <p style="font-size:0.85rem;color:var(--text-muted);">
        اگر عرض کمتر از ۷۶۸px باشد، منوی همبرگری فعال می‌شود.
    </p>
</div>

<div class="test-controls">
    <button onclick="openMenu()">🔓 باز کردن منو</button>
    <button onclick="closeMenu()">🔒 بستن منو</button>
    <button onclick="checkStatus()">🔄 بروزرسانی وضعیت</button>
</div>

<div style="height:500px;padding:20px;">
    <p>این یک محتوای آزمایشی است. روی دکمه‌های پایین صفحه کلیک کنید.</p>
    <p>همچنین می‌توانید روی آیکون همبرگر ☰ در بالای صفحه کلیک کنید.</p>
</div>

<script>
// توابع اصلی (همان توابع navbar.php)
function toggleMobileMenu(){
    const m = document.getElementById('navMenu');
    const o = document.getElementById('mobileOverlay');
    const b = document.querySelector('.hamburger');
    
    if (m.classList.contains('active')) {
        closeMobileMenu();
    } else {
        m.classList.add('active');
        o.classList.add('active');
        b.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }
}

function closeMobileMenu(){
    const m = document.getElementById('navMenu');
    const o = document.getElementById('mobileOverlay');
    const b = document.querySelector('.hamburger');
    
    m.classList.remove('active');
    o.classList.remove('active');
    b.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
}

// توابع تست
function openMenu() {
    const m = document.getElementById('navMenu');
    const o = document.getElementById('mobileOverlay');
    const b = document.querySelector('.hamburger');
    
    m.classList.add('active');
    o.classList.add('active');
    b.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    checkStatus();
}

function closeMenu() {
    closeMobileMenu();
    checkStatus();
}

function checkStatus() {
    const m = document.getElementById('navMenu');
    const o = document.getElementById('mobileOverlay');
    const b = document.querySelector('.hamburger');
    
    document.getElementById('widthDisplay').textContent = window.innerWidth;
    document.getElementById('menuStatus').textContent = m.classList.contains('active') ? '🔓 باز' : '🔒 بسته';
    document.getElementById('overlayStatus').textContent = o.classList.contains('active') ? '🔓 باز' : '🔒 بسته';
    document.getElementById('hamburgerStatus').textContent = b.getAttribute('aria-expanded');
    
    // نمایش وضعیت CSS
    const menuStyles = window.getComputedStyle(m);
    console.log('Menu right:', menuStyles.right);
    console.log('Menu position:', menuStyles.position);
    console.log('Overlay display:', window.getComputedStyle(o).display);
}

// بروزرسانی اولیه
checkStatus();
window.addEventListener('resize', checkStatus);
</script>

<?php require_once 'includes/footer.php'; ?>