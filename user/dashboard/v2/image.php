<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "/login.php?redirect=/user/dashboard/v2/image.php");
    exit;
}

$user = getUserData($_SESSION['user_id']);
$page_title = 'ساخت عکس با AI | ' . SITE_NAME;
$extra_css = ['user/dashboard/v2/assets/css/dashboard.css'];
$extra_js = ['user/dashboard/v2/assets/js/dashboard.js'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

// گالری - همه نوع عکس (هر بار رفرش)
$images = array_merge(
    glob($_SERVER['DOCUMENT_ROOT'] . '/uploads/gen_*.png') ?: [],
    glob($_SERVER['DOCUMENT_ROOT'] . '/uploads/ai_*.png') ?: [],
    glob($_SERVER['DOCUMENT_ROOT'] . '/uploads/up_*.png') ?: [],
    glob($_SERVER['DOCUMENT_ROOT'] . '/uploads/magic_*.png') ?: [],
    glob($_SERVER['DOCUMENT_ROOT'] . '/uploads/crop_*.png') ?: [],
    glob($_SERVER['DOCUMENT_ROOT'] . '/uploads/rotate_*.png') ?: [],
    glob($_SERVER['DOCUMENT_ROOT'] . '/uploads/wm_*.png') ?: [],
    glob($_SERVER['DOCUMENT_ROOT'] . '/uploads/compressed_*.jpg') ?: [],
    glob($_SERVER['DOCUMENT_ROOT'] . '/uploads/blurred_*.png') ?: [],
    glob($_SERVER['DOCUMENT_ROOT'] . '/uploads/pdfpage_*.jpg') ?: []
);
$images = array_unique($images);
rsort($images);
$total_images = count($images);
$images = array_slice($images, 0, 50);
?>

<style>
.tab-bar { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 20px; }
.tab-btn { padding: 10px 20px; border-radius: 25px; border: 2px solid var(--border,#e2e8f0); background: var(--bg-card,#fff); color: var(--text-secondary,#475569); cursor: pointer; font-family: inherit; font-size: 0.88rem; font-weight: 500; transition: all 0.2s; }
.tab-btn:hover { border-color: var(--primary,#0ea5e9); color: var(--primary,#0ea5e9); }
.tab-btn.active { background: var(--primary,#0ea5e9); color: #fff; border-color: var(--primary,#0ea5e9); }
.tab-content { display: none; }
.tab-content.active { display: block; }

.card { background: var(--bg-card,#fff); border: 1px solid var(--border,#e2e8f0); border-radius: 16px; padding: 24px; margin-bottom: 20px; }
.row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.prompt-area { width: 100%; padding: 14px; border: 2px solid var(--border,#e2e8f0); border-radius: 12px; font-family: inherit; font-size: 0.95rem; background: var(--bg-input,#f8fafc); color: var(--text-primary,#0f172a); resize: vertical; min-height: 80px; transition: border-color 0.2s; }
.prompt-area:focus { border-color: var(--primary,#0ea5e9); outline: none; box-shadow: 0 0 0 3px var(--primary-light,#e0f2fe); }
.chip-row { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
.chip { padding: 7px 16px; border-radius: 20px; border: 1px solid var(--border,#e2e8f0); background: var(--bg-secondary,#f8fafc); color: var(--text-secondary,#475569); cursor: pointer; font-size: 0.8rem; font-family: inherit; transition: all 0.2s; }
.chip:hover { border-color: var(--primary,#0ea5e9); color: var(--primary,#0ea5e9); background: var(--primary-light,#e0f2fe); }

.result-area { margin-top: 20px; text-align: center; }
.result-area img { max-width: 100%; max-height: 500px; border-radius: 14px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); animation: fadeIn 0.5s ease; }
@keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.progress-bar { height: 6px; background: var(--border,#e2e8f0); border-radius: 6px; overflow: hidden; margin-top: 16px; }
.progress-fill { height: 100%; background: linear-gradient(90deg, var(--primary,#0ea5e9), var(--secondary,#06b6d4)); border-radius: 6px; width: 0%; transition: width 0.3s; animation: progressPulse 1.5s infinite; }
@keyframes progressPulse { 0%,100% { opacity: 1; } 50% { opacity: 0.7; } }

.gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
.gallery-item { border-radius: 12px; overflow: hidden; cursor: pointer; aspect-ratio: 1; transition: all 0.2s; position: relative; background: var(--bg-secondary,#f1f5f9); }
.gallery-item:hover { transform: scale(1.04); box-shadow: 0 8px 25px rgba(0,0,0,0.15); z-index: 2; }
.gallery-item img { width: 100%; height: 100%; object-fit: cover; }
.gallery-item .overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; }
.gallery-item:hover .overlay { opacity: 1; }
.gallery-item .overlay span { color: #fff; font-size: 1.5rem; }

.drop-zone { border: 3px dashed var(--border,#e2e8f0); border-radius: 16px; padding: 40px; text-align: center; cursor: pointer; transition: all 0.2s; }
.drop-zone:hover { border-color: var(--primary,#0ea5e9); background: var(--primary-light,#e0f2fe); }
.drop-zone i { font-size: 2.5rem; color: var(--primary,#0ea5e9); margin-bottom: 10px; }
.preview-img { max-width: 300px; max-height: 300px; border-radius: 12px; margin-top: 16px; display: none; }

#galleryCount { background: var(--primary-light,#e0f2fe); color: var(--primary,#0ea5e9); padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; white-space: nowrap; }
.gallery-search-row { display: flex; gap: 12px; align-items: center; margin-bottom: 16px; }
.gallery-search-row input { flex: 1; margin-bottom: 0 !important; }

@media (max-width: 768px) { .row { grid-template-columns: 1fr; } .gallery { grid-template-columns: repeat(3, 1fr); } }
</style>

<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="user-profile-summary">
            <div class="avatar"><?= mb_substr($user['full_name'] ?? 'U', 0, 1) ?></div>
            <h3><?= sanitize($user['full_name'] ?? 'کاربر') ?></h3>
        </div>
        <nav class="dashboard-nav">
            <a href="/user/dashboard/v2/" class="nav-item"><i class="fas fa-home"></i> داشبورد</a>
            <a href="/user/dashboard/v2/chat.php" class="nav-item"><i class="fas fa-comments"></i> چت AI</a>
            <a href="/user/dashboard/v2/image.php" class="nav-item active"><i class="fas fa-image"></i> ساخت عکس</a>
            <a href="/projects/" class="nav-item"><i class="fab fa-github"></i> پروژه‌ها</a>
            <a href="/user/dashboard/v2/tasks.php" class="nav-item"><i class="fas fa-tasks"></i> تسک‌ها</a>
            <a href="/shop/" class="nav-item"><i class="fas fa-store"></i> فروشگاه</a>
            <a href="/user/dashboard/v2/profile.php" class="nav-item"><i class="fas fa-user"></i> پروفایل</a>
            <a href="/user/dashboard/v2/settings.php" class="nav-item"><i class="fas fa-cog"></i> تنظیمات</a>
            <a href="/logout.php" class="nav-item nav-item-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>🎨 ساخت عکس با هوش مصنوعی</h1>
                <p style="color:var(--text-secondary);font-size:0.9rem">با Cloudflare AI · ۳ مدل مختلف · رایگان</p>
            </div>
        </div>

        <div class="tab-bar">
            <button class="tab-btn active" onclick="switchTab('generate')">🎨 ساخت عکس</button>
            <button class="tab-btn" onclick="switchTab('gallery')">🖼️ گالری</button>
            <button class="tab-btn" onclick="switchTab('analyze')">🔍 تحلیل عکس</button>
        </div>

        <!-- تب ساخت -->
        <div id="tab-generate" class="tab-content active">
            <div class="card">
                <div class="row">
                    <div>
                        <label style="font-weight:600;display:block;margin-bottom:6px">🎯 مدل</label>
                        <select id="model" class="prompt-area" style="min-height:auto;padding:12px">
                            <option value="sd-xl">🌟 SDXL - کیفیت بالا (۲۰-۳۰ ثانیه)</option>
                            <option value="sd-lightning">⚡ SD Lightning - سریع (۵-۱۰ ثانیه)</option>
                            <option value="dreamshaper">💭 DreamShaper - هنری (۱۰-۲۰ ثانیه)</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:600;display:block;margin-bottom:6px">📐 سایز</label>
                        <select id="size" class="prompt-area" style="min-height:auto;padding:12px">
                            <option value="512,512">مربع ۵۱۲×۵۱۲</option>
                            <option value="512,768">عمودی ۵۱۲×۷۶۸</option>
                            <option value="768,512">افقی ۷۶۸×۵۱۲</option>
                            <option value="768,768">بزرگ ۷۶۸×۷۶۸</option>
                        </select>
                    </div>
                </div>
                
                <label style="font-weight:600;display:block;margin:16px 0 6px">📝 توضیح عکس <small style="font-weight:400;color:var(--text-muted)">(انگلیسی بهتره - فارسی هم ترجمه میشه)</small></label>
                <textarea id="prompt" class="prompt-area" placeholder="a beautiful sunset over mountains, photorealistic, 8k..."></textarea>
                
                <div class="chip-row">
                    <span class="chip" onclick="setChip('sunset over mountains, 8k')">🏔️ غروب کوهستان</span>
                    <span class="chip" onclick="setChip('cute cat, photorealistic, 8k')">🐱 گربه بامزه</span>
                    <span class="chip" onclick="setChip('modern house with pool, architectural, 8k')">🏠 خانه مدرن</span>
                    <span class="chip" onclick="setChip('Persian garden, flowers, fountain, 8k')">🌸 باغ ایرانی</span>
                    <span class="chip" onclick="setChip('cyberpunk city at night, neon lights, 8k')">🌃 شهر سایبرپانک</span>
                    <span class="chip" onclick="setChip('dragon in sky, fantasy art, detailed, 8k')">🐉 اژدها</span>
                    <span class="chip" onclick="setChip('portrait of a beautiful woman, professional photography, 8k')">👩 پورتره</span>
                    <span class="chip" onclick="setChip('delicious food photography, restaurant quality, 8k')">🍕 غذا</span>
                </div>
                
                <button onclick="generate()" class="btn btn-primary" style="width:100%;margin-top:16px;padding:14px;font-size:1rem">✨ ساخت عکس</button>
                <div id="result" class="result-area"></div>
            </div>
        </div>

        <!-- تب گالری -->
        <div id="tab-gallery" class="tab-content">
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:10px">
                    <div class="gallery-search-row" style="margin-bottom:0;flex:1">
                        <input type="text" id="gallerySearch" class="prompt-area" style="min-height:auto" placeholder="🔍 جستجوی هوشمند... (مثال: cat mountain)" oninput="filterGallery()">
                    </div>
                    <span id="galleryCount"><?= $total_images ?> عکس</span>
                    <button onclick="if(confirm('گالری بروزرسانی شود؟')) location.reload()" class="btn btn-ghost btn-sm">🔄 بروزرسانی</button>
                </div>
                <div class="gallery" id="galleryGrid">
                    <?php foreach ($images as $img): 
                        $name = basename($img);
                        $url = '/uploads/' . $name;
                        $ts = 0;
                        if (preg_match('/(\d{10})/', $name, $m)) $ts = $m[1];
                        $date = $ts ? jalali_date('Y/m/d', $ts) : '';
                        $searchName = str_replace(['_', '.png', '.jpg', '-'], ' ', $name);
                    ?>
                    <div class="gallery-item" onclick="showImage('<?= $url ?>')" data-name="<?= $searchName ?>" title="<?= htmlspecialchars($name) ?>">
                        <img src="<?= $url ?>" loading="lazy" alt="AI Image <?= $date ?>" onerror="this.parentElement.style.display='none'">
                        <div class="overlay"><span>🔍</span></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($images)): ?>
                    <div style="text-align:center;padding:40px;color:var(--text-muted)">
                        <div style="font-size:3rem;margin-bottom:10px">🖼️</div>
                        <p>هنوز عکسی ساخته نشده. برو به تب "ساخت عکس" و اولین عکس رو بساز!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- تب تحلیل -->
        <div id="tab-analyze" class="tab-content">
            <div class="card">
                <div class="drop-zone" onclick="document.getElementById('analyzeFile').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>کلیک کنید یا عکس را اینجا رها کنید</p>
                    <small style="color:var(--text-muted)">فرمت‌های مجاز: JPG, PNG, WEBP, GIF</small>
                    <input type="file" id="analyzeFile" accept="image/*" style="display:none" onchange="analyzeImage(this)">
                </div>
                <img id="analyzePreview" class="preview-img">
                <div id="analyzeResult" class="result-area"></div>
            </div>
        </div>
    </main>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    document.getElementById('tab-' + tab).classList.add('active');
    event.target.classList.add('active');
}

function setChip(text) { 
    document.getElementById('prompt').value = text; 
    document.getElementById('prompt').focus();
}

function refreshGalleryData() {
    // به جای رفرش کامل، فقط گالری رو رفرش کن
    var galleryGrid = document.getElementById('galleryGrid');
    if (galleryGrid) {
        galleryGrid.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted)">⏳ در حال بارگذاری...</div>';
        setTimeout(function() { location.reload(); }, 500);
    }
}

async function generate() {
    var prompt = document.getElementById('prompt').value.trim();
    if (!prompt) { alert('لطفاً توضیح عکس را وارد کنید'); return; }
    
    var result = document.getElementById('result');
    result.innerHTML = '<div style="text-align:center;padding:20px"><div class="progress-bar"><div class="progress-fill" style="width:60%"></div></div><p style="margin-top:10px;color:var(--text-secondary)">⏳ در حال ساخت عکس...</p></div>';
    
    var size = document.getElementById('size').value.split(',');
    var formData = new FormData();
    formData.append('action', 'text_to_image');
    formData.append('prompt', prompt);
    formData.append('model', document.getElementById('model').value);
    formData.append('width', size[0]);
    formData.append('height', size[1]);
    
    try {
        var res = await fetch('/api/image/edit.php', { method: 'POST', body: formData });
        var data = await res.json();
        if (data.success) {
            result.innerHTML = '<img src="' + data.image_url + '?t=' + Date.now() + '" style="max-width:100%;max-height:500px;border-radius:14px;box-shadow:0 10px 40px rgba(0,0,0,0.15)" onerror="this.style.display=\'none\'"><p style="margin-top:10px;color:#10b981">✅ عکس ساخته شد</p><div style="display:flex;gap:8px;justify-content:center;margin-top:8px"><a href="' + data.image_url + '" download class="btn btn-primary btn-sm">⬇️ دانلود</a><button onclick="switchTab(\'gallery\');refreshGalleryData()" class="btn btn-ghost btn-sm">🖼️ گالری</button></div>';
        } else {
            result.innerHTML = '<div style="color:#ef4444;text-align:center;padding:20px">❌ ' + (data.error || 'خطا در ساخت عکس') + '</div>';
        }
    } catch (e) {
        result.innerHTML = '<div style="color:#ef4444;text-align:center;padding:20px">❌ خطا در ارتباط با سرور</div>';
    }
}

function filterGallery() {
    var q = document.getElementById('gallerySearch').value.toLowerCase().trim();
    var items = document.querySelectorAll('#galleryGrid .gallery-item');
    var found = 0;
    
    items.forEach(function(item) {
        var text = (item.dataset.name || '').toLowerCase();
        
        if (!q) { item.style.display = 'block'; found++; return; }
        
        var keywords = q.split(/\s+/).filter(function(k) { return k.length > 0; });
        var match = keywords.every(function(kw) { return text.includes(kw); });
        
        item.style.display = match ? 'block' : 'none';
        if (match) found++;
    });
    
    document.getElementById('galleryCount').textContent = q ? found + ' عکس پیدا شد' : '<?= $total_images ?> عکس';
}

function showImage(url) { window.open(url, '_blank'); }

async function analyzeImage(input) {
    var file = input.files[0];
    if (!file) return;
    
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('analyzePreview').src = e.target.result;
        document.getElementById('analyzePreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
    
    document.getElementById('analyzeResult').innerHTML = '<p style="text-align:center;padding:20px;color:var(--text-secondary)">⏳ در حال تحلیل...</p>';
    
    var formData = new FormData();
    formData.append('action', 'upload');
    formData.append('image', file);
    
    try {
        var uploadRes = await fetch('/api/image/edit.php', { method: 'POST', body: formData });
        var uploadData = await uploadRes.json();
        
        if (uploadData.success) {
            var analyzeFormData = new FormData();
            analyzeFormData.append('action', 'analyze');
            analyzeFormData.append('image_url', uploadData.image_url);
            var analyzeRes = await fetch('/api/image/edit.php', { method: 'POST', body: analyzeFormData });
            var analyzeData = await analyzeRes.json();
            
            document.getElementById('analyzeResult').innerHTML = analyzeData.success 
                ? '<div style="background:var(--bg-secondary);padding:20px;border-radius:12px;margin-top:16px"><h4>📊 نتایج تحلیل</h4><p>🏷️ <strong>اشیا:</strong> ' + analyzeData.objects + '</p><p>📂 <strong>دسته‌بندی:</strong> ' + analyzeData.categories + '</p></div>'
                : '<div style="color:#ef4444;margin-top:16px">❌ ' + (analyzeData.error || 'خطا در تحلیل') + '</div>';
        }
    } catch (e) {
        document.getElementById('analyzeResult').innerHTML = '<div style="color:#ef4444;margin-top:16px">❌ خطا در ارتباط</div>';
    }
}

document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'Enter' && document.activeElement?.id === 'prompt') {
        e.preventDefault();
        generate();
    }
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>