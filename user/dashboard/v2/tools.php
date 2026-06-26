<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

if (empty($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "/login.php?redirect=/user/dashboard/v2/tools.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
$page_title = 'جعبه ابزار تصویر | ' . SITE_NAME;
$extra_css = ['user/dashboard/v2/assets/css/dashboard.css'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
$user = getUserData($_SESSION['user_id']);
?>

<style>
.tools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.tool-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; cursor: pointer; transition: all 0.2s; }
.tool-card:hover { box-shadow: var(--shadow-lg); border-color: var(--primary); transform: translateY(-2px); }
.tool-card .icon { font-size: 2.5rem; margin-bottom: 10px; }
.tool-card h3 { font-size: 1.15rem; margin-bottom: 6px; }
.tool-card p { color: var(--text-secondary); font-size: 0.9rem; }
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
.modal-overlay.active { display: flex; }
.modal-box { background: var(--bg-card); border-radius: 20px; padding: 24px; max-width: 480px; width: 90%; max-height: 85vh; overflow-y: auto; }
</style>

<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="user-profile-summary">
            <div class="avatar"><?php echo mb_substr($user['full_name'] ?? 'U', 0, 1); ?></div>
            <h3><?php echo sanitize($user['full_name'] ?? 'کاربر'); ?></h3>
        </div>
        <nav class="dashboard-nav">
            <a href="/user/dashboard/v2/" class="nav-item"><i class="fas fa-home"></i> داشبورد</a>
            <a href="/user/dashboard/v2/chat.php" class="nav-item"><i class="fas fa-comments"></i> چت AI</a>
            <a href="/projects/" class="nav-item"><i class="fab fa-github"></i> پروژه‌ها</a>
            <a href="/user/dashboard/v2/image.php" class="nav-item"><i class="fas fa-image"></i> ساخت عکس</a>
            <a href="/user/dashboard/v2/tools.php" class="nav-item active"><i class="fas fa-tools"></i> ابزارها</a>
            <a href="/user/dashboard/v2/tasks.php" class="nav-item"><i class="fas fa-tasks"></i> تسک‌ها</a>
            <a href="/user/dashboard/v2/history.php" class="nav-item"><i class="fas fa-history"></i> تاریخچه</a>
            <a href="/logout.php" class="nav-item nav-item-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <h1>🛠️ ابزارهای حرفه‌ای تصویر</h1>
        <p class="text-muted mb-3">ویرایش، تبدیل و بهینه‌سازی عکس‌ها با قدرت هوش مصنوعی و پردازش سریع</p>

        <div class="tools-grid">
            <?php
            $tools = [
                'magic'    => ['🎨', 'جادوی رنگ', 'تغییر رنگ و بافت بخش‌هایی از تصویر با هوش مصنوعی'],
                'blur'     => ['🔲', 'حذف پس‌زمینه', 'محو یا حذف خودکار پس‌زمینه عکس'],
                'topdf'    => ['📄', 'عکس به PDF', 'ترکیب یک یا چند عکس در قالب PDF'],
                'frompdf'  => ['🖼️', 'PDF به عکس', 'استخراج صفحات PDF به فایل تصویری'],
                'crop'     => ['✂️', 'برش دقیق', 'برش حرفه‌ای عکس با انتخاب ناحیه'],
                'rotate'   => ['🔄', 'چرخش', 'چرخش ۹۰، ۱۸۰ یا ۲۷۰ درجه‌ای'],
                'watermark'=> ['💧', 'واترمارک', 'افزودن متن یا نشان تجاری روی عکس'],
                'compress' => ['📦', 'فشرده‌سازی', 'کاهش حجم عکس با حفظ کیفیت نسبی'],
            ];
            foreach ($tools as $id => $t):
            ?>
                <div class="tool-card" onclick="openTool('<?php echo $id; ?>')">
                    <div class="icon"><?php echo $t[0]; ?></div>
                    <h3><?php echo $t[1]; ?></h3>
                    <p><?php echo $t[2]; ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- مودال ابزار -->
        <div class="modal-overlay" id="toolModal">
            <div class="modal-box">
                <h3 id="toolTitle"></h3>
                <div id="toolContent"></div>
                <button onclick="closeTool()" class="btn btn-outline mt-3" style="width:100%;">بستن</button>
            </div>
        </div>
    </main>
</div>

<script>
const toolConfig = {
    magic:    { title: '🎨 جادوی رنگ', html: '<input type="file" id="f" accept="image/*"><br><textarea id="prompt" rows="2" placeholder="مثلاً: change the dress color to blue"></textarea><br><button class="btn btn-primary" onclick="apply(\'magic\')">اعمال</button><div class="preview-area" id="preview"></div>' },
    blur:     { title: '🔲 حذف پس‌زمینه', html: '<input type="file" id="f" accept="image/*"><br><label>میزان محو:</label><input type="range" id="level" min="1" max="20" value="10"><br><button class="btn btn-primary" onclick="apply(\'blur\')">اعمال</button><div class="preview-area" id="preview"></div>' },
    topdf:    { title: '📄 عکس به PDF', html: '<input type="file" id="f" accept="image/*" multiple><br><button class="btn btn-primary" onclick="apply(\'topdf\')">تبدیل</button><div id="preview"></div>' },
    frompdf:  { title: '🖼️ PDF به عکس', html: '<input type="file" id="f" accept=".pdf"><br><button class="btn btn-primary" onclick="apply(\'frompdf\')">استخراج</button><div id="preview"></div>' },
    crop:     { title: '✂️ برش عکس', html: '<input type="file" id="f" accept="image/*"><br><button class="btn btn-primary" onclick="apply(\'crop\')">برش</button><div id="preview"></div>' },
    rotate:   { title: '🔄 چرخش', html: '<input type="file" id="f" accept="image/*"><br><select id="angle"><option value="90">۹۰°</option><option value="180">۱۸۰°</option><option value="270">۲۷۰°</option></select><br><button class="btn btn-primary" onclick="apply(\'rotate\')">چرخش</button><div id="preview"></div>' },
    watermark:{ title: '💧 واترمارک', html: '<input type="file" id="f" accept="image/*"><br><input type="text" id="txt" placeholder="متن واترمارک" value="گلستان"><br><button class="btn btn-primary" onclick="apply(\'watermark\')">افزودن</button><div id="preview"></div>' },
    compress:{ title: '📦 فشرده‌سازی', html: '<input type="file" id="f" accept="image/*"><br><label>کیفیت:</label><input type="range" id="quality" min="10" max="90" value="60"><br><button class="btn btn-primary" onclick="apply(\'compress\')">فشرده‌سازی</button><div id="preview"></div>' }
};

function openTool(id) {
    const t = toolConfig[id];
    document.getElementById('toolTitle').textContent = t.title;
    document.getElementById('toolContent').innerHTML = t.html;
    document.getElementById('toolModal').classList.add('active');
}
function closeTool() { document.getElementById('toolModal').classList.remove('active'); }

// توابع کمکی ارسال درخواست
async function uploadFile(input) {
    if (!input.files[0]) return null;
    let fd = new FormData(); fd.append('action','upload'); fd.append('image', input.files[0]);
    let res = await fetch('/api/image/edit.php', {method:'POST',body:fd});
    let data = await res.json();
    return data.success ? data.image_url : null;
}

async function apply(type) {
    const fileInput = document.querySelector('#f');
    const preview = document.getElementById('preview');
    preview.innerHTML = '⏳ در حال پردازش...';
    const imageUrl = await uploadFile(fileInput);
    if (!imageUrl) { preview.innerHTML = '❌ خطا در آپلود'; return; }

    let fd = new FormData();
    if (type === 'magic') {
        let prompt = document.getElementById('prompt').value || 'enhance';
        fd.append('action','magic_color'); fd.append('image_url',imageUrl); fd.append('prompt',prompt);
    } else if (type === 'blur') {
        fd.append('action','remove_bg_blur'); fd.append('image_url',imageUrl); fd.append('blur', document.getElementById('level').value);
    } else if (type === 'topdf') {
        // جمع‌آوری چند فایل به صورت آرایه JSON
        let files = fileInput.files;
        let urls = [];
        for (let f of files) {
            let u = await uploadFile({files:[f]});
            if (u) urls.push(u);
        }
        fd.append('action','image_to_pdf'); fd.append('image_urls', JSON.stringify(urls));
    } else if (type === 'frompdf') {
        fd.append('action','pdf_to_image'); fd.append('pdf_url', imageUrl);
    } else if (type === 'crop') {
        fd.append('action','crop'); fd.append('image_url',imageUrl); fd.append('x',0); fd.append('y',0); fd.append('w',300); fd.append('h',300);
    } else if (type === 'rotate') {
        fd.append('action','rotate'); fd.append('image_url',imageUrl); fd.append('angle', document.getElementById('angle').value);
    } else if (type === 'watermark') {
        fd.append('action','watermark'); fd.append('image_url',imageUrl); fd.append('text', document.getElementById('txt').value);
    } else if (type === 'compress') {
        fd.append('action','compress'); fd.append('image_url',imageUrl); fd.append('quality', document.getElementById('quality').value);
    }

    let res = await fetch('/api/image/tools.php', {method:'POST', body:fd});
    let data = await res.json();
    if (data.success) {
        preview.innerHTML = `<img src="${data.image_url||data.pdf_url||data.images[0]}?t=${Date.now()}" style="max-width:100%; border-radius:12px; margin-top:10px;">`;
        if (data.pdf_url) preview.innerHTML += `<br><a href="${data.pdf_url}" class="btn btn-primary btn-sm mt-2">دانلود PDF</a>`;
    } else {
        preview.innerHTML = `<p class="error mt-2">❌ ${data.error}</p>`;
    }
}
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>