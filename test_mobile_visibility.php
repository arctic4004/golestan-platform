<?php
// test_mobile_visibility.php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'تست';
$_SESSION['phone'] = '09177418286';
$_SESSION['credits'] = 999999;
$_SESSION['is_admin'] = true;
$_SESSION['theme'] = 'light';

// شبیه‌سازی حالت لاگین نشده
$force_logged_out = isset($_GET['logged_out']);

require_once 'config/constants.php';
require_once 'includes/functions.php';
$page_title = 'تست نمایش منو';
require_once 'includes/header.php';
?>

<style>
.debug-panel {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: white;
    border-radius: 16px;
    padding: 16px;
    z-index: 2000;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
    font-family: monospace;
    font-size: 0.8rem;
}
.debug-panel button {
    padding: 8px 16px;
    border-radius: 8px;
    border: 1px solid #555;
    background: #334155;
    color: white;
    cursor: pointer;
}
.debug-info {
    background: #1e293b;
    color: white;
    border-radius: 12px;
    padding: 16px;
    margin: 100px 20px 20px;
    text-align: center;
    font-family: monospace;
    font-size: 0.8rem;
}
.success { color: #4caf50; }
.error { color: #f44336; }
</style>

<div class="debug-info">
    <h2>📱 تست نمایش منوی موبایل</h2>
    <p>عرض: <strong id="widthDisplay">-</strong>px</p>
    <p>وضعیت منو: <strong id="menuStatus">-</strong></p>
    <p>display منو: <strong id="menuDisplay">-</strong></p>
    <p>right منو: <strong id="menuRight">-</strong></p>
    <p>visibility منو: <strong id="menuVisibility">-</strong></p>
    <p>z-index منو: <strong id="menuZIndex">-</strong></p>
    <hr>
    <p>تعداد li ها: <strong id="liCount">-</strong></p>
    <p>li های مخفی: <strong id="hiddenLi">-</strong></p>
    <hr>
    <p><a href="?logged_out=1" style="color:#6366f1;">حالت مهمان (logged out)</a></p>
    <p><a href="?" style="color:#6366f1;">حالت کاربر (logged in)</a></p>
</div>

<div class="debug-panel">
    <button onclick="openMenu()">🔓 باز کردن منو</button>
    <button onclick="closeMenu()">🔒 بستن منو</button>
    <button onclick="checkAll()">🔄 بروزرسانی</button>
    <button onclick="showHidden()">👁️ نمایش مخفی‌ها</button>
</div>

<div style="height:500px;padding:20px;">
    <p>روی دکمه‌های پایین کلیک کنید.</p>
    <p>یا روی همبرگر ☰ بالا کلیک کنید.</p>
</div>

<script>
function openMenu() {
    var m = document.getElementById('navMenu');
    var o = document.getElementById('mobileOverlay');
    var b = document.querySelector('.hamburger');
    
    m.style.cssText = 'position:fixed!important;top:60px!important;right:0!important;width:280px!important;height:'+(window.innerHeight-60)+'px!important;display:flex!important;flex-direction:column!important;z-index:1001!important;background:#fff!important;padding:16px!important;overflow-y:auto!important;box-shadow:0 20px 60px rgba(0,0,0,0.3)!important';
    m.classList.add('active');
    o.classList.add('active');
    b.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    
    // نمایش همه li های مخفی
    m.querySelectorAll('li').forEach(function(li) {
        li.style.display = 'block';
        li.style.visibility = 'visible';
    });
    m.querySelectorAll('a').forEach(function(a) {
        a.style.display = 'flex';
        a.style.visibility = 'visible';
    });
    
    checkAll();
}

function closeMenu() {
    var m = document.getElementById('navMenu');
    var o = document.getElementById('mobileOverlay');
    var b = document.querySelector('.hamburger');
    
    m.classList.remove('active');
    o.classList.remove('active');
    b.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    m.style.cssText = '';
    checkAll();
}

function checkAll() {
    var m = document.getElementById('navMenu');
    var o = document.getElementById('mobileOverlay');
    
    var styles = window.getComputedStyle(m);
    
    document.getElementById('widthDisplay').textContent = window.innerWidth;
    document.getElementById('menuStatus').textContent = m.classList.contains('active') ? '🔓 باز' : '🔒 بسته';
    document.getElementById('menuDisplay').textContent = styles.display;
    document.getElementById('menuRight').textContent = styles.right;
    document.getElementById('menuVisibility').textContent = styles.visibility;
    document.getElementById('menuZIndex').textContent = styles.zIndex;
    
    var lis = m.querySelectorAll('li');
    document.getElementById('liCount').textContent = lis.length;
    
    var hidden = 0;
    lis.forEach(function(li) {
        var s = window.getComputedStyle(li);
        if (s.display === 'none' || s.visibility === 'hidden') hidden++;
    });
    document.getElementById('hiddenLi').textContent = hidden;
    
    console.log('Menu display:', styles.display);
    console.log('Menu right:', styles.right);
    console.log('Menu z-index:', styles.zIndex);
    console.log('Overlay display:', window.getComputedStyle(o).display);
}

function showHidden() {
    var m = document.getElementById('navMenu');
    m.querySelectorAll('li').forEach(function(li, i) {
        var s = window.getComputedStyle(li);
        console.log('Li ' + i + ':', s.display, s.visibility, s.position, 'text:', li.textContent.substring(0, 30));
    });
    m.querySelectorAll('a').forEach(function(a, i) {
        var s = window.getComputedStyle(a);
        console.log('A ' + i + ':', s.display, s.visibility, 'text:', a.textContent.substring(0, 30));
    });
    alert('اطلاعات در Console نمایش داده شد. F12 را بزنید.');
}

checkAll();
window.addEventListener('resize', checkAll);
</script>

<?php require_once 'includes/footer.php'; ?>