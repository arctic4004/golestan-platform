<?php
// user/dashboard/v2/image.php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

$logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
if (!$logged_in) {
    header("Location: " . SITE_URL . "/login.php?redirect=/user/dashboard/v2/image.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
$page_title = 'ساخت عکس با AI | ' . SITE_NAME;
$extra_css = ['user/dashboard/v2/assets/css/dashboard.css'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

$user = getUserData($_SESSION['user_id']);
?>

<style>
.image-panel { display: none; }
.image-panel.active { display: block; }
.generated-img { max-width: 100%; max-height: 500px; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.2); }
.card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 16px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }
.drop-zone { border: 2px dashed var(--border); border-radius: 12px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.2s; }
.drop-zone:hover { border-color: var(--primary); background: var(--primary-light); }
.gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
.gallery-item { border-radius: 8px; overflow: hidden; cursor: pointer; transition: transform 0.2s; }
.gallery-item:hover { transform: scale(1.03); }
.gallery-item img { width: 100%; height: 150px; object-fit: cover; }
.text-center { text-align: center; }
.py-3 { padding: 20px 0; }
.py-4 { padding: 30px 0; }
.mt-2 { margin-top: 10px; }
.mt-3 { margin-top: 15px; }
.mb-3 { margin-bottom: 15px; }
.text-muted { color: var(--text-muted); font-size: 0.85rem; }
.tool-btn { padding: 8px 18px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-secondary); cursor: pointer; font-family: var(--font); font-size: 0.9rem; transition: all 0.2s; }
.tool-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
.tool-btn:hover:not(.active) { background: var(--bg-hover); color: var(--text-primary); }
.prompt-chip { background: var(--bg-tertiary); border: 1px solid var(--border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; cursor: pointer; transition: 0.2s; font-family: var(--font); }
.prompt-chip:hover { background: var(--primary-light); border-color: var(--primary); color: var(--primary); }
.form-select, input[type="text"], textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-input); color: var(--text-primary); font-family: var(--font); font-size: 0.9rem; }
textarea { resize: vertical; }
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
            <a href="/user/dashboard/v2/image.php" class="nav-item active"><i class="fas fa-image"></i> ساخت عکس</a>
            <a href="/user/dashboard/v2/tasks.php" class="nav-item"><i class="fas fa-tasks"></i> تسک‌ها</a>
            <a href="/user/dashboard/v2/history.php" class="nav-item"><i class="fas fa-history"></i> تاریخچه</a>
            <a href="/user/dashboard/v2/profile.php" class="nav-item"><i class="fas fa-user"></i> پروفایل</a>
            <a href="/user/dashboard/v2/settings.php" class="nav-item"><i class="fas fa-cog"></i> تنظیمات</a>
            <a href="/logout.php" class="nav-item nav-item-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <h1>🎨 هوش مصنوعی تصویر</h1>
        <p class="text-muted mb-3">✅ ساخت عکس با ۳ مدل | 🔍 تحلیل | 🔄 عکس به پرامپت | 📝 Alt Text | 🖼️ گالری</p>

        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px;">
            <button class="tool-btn active" onclick="switchTab('generate')">🎨 ساخت عکس</button>
            <button class="tool-btn" onclick="switchTab('analyze')">🔍 تحلیل عکس</button>
            <button class="tool-btn" onclick="switchTab('prompt')">🔄 عکس → پرامپت</button>
            <button class="tool-btn" onclick="switchTab('alt')">📝 Alt Text</button>
            <button class="tool-btn" onclick="switchTab('gallery')">🖼️ گالری</button>
        </div>

        <div id="panel-generate" class="image-panel active">
            <div class="card">
                <div class="grid-2">
                    <div class="form-group">
                        <label>🎯 مدل</label>
                        <select id="model_generate" class="form-select">
                            <option value="flux">🌟 Flux (سریع و واقع‌گرا)</option>
                            <option value="sd-xl">🎨 SD XL (کیفیت بالا)</option>
                            <option value="sd-lightning">⚡ SD Lightning (بسیار سریع)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>📐 ابعاد</label>
                        <select id="size_generate" class="form-select">
                            <option value="512x512">۵۱۲×۵۱۲ (مربع)</option>
                            <option value="512x768">۵۱۲×۷۶۸ (عمودی)</option>
                            <option value="768x512">۷۶۸×۵۱۲ (افقی)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>توضیح عکس <span style="font-weight:400;color:var(--text-muted);">(فارسی یا انگلیسی)</span></label>
                    <textarea id="prompt_generate" rows="3" placeholder="منظره زیبای کوهستان در غروب آفتاب..."></textarea>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px;">
                    <span class="prompt-chip" onclick="setPrompt('a beautiful mountain lake at sunset, photorealistic, 8k')">🏔️ طبیعت</span>
                    <span class="prompt-chip" onclick="setPrompt('a cute cat sitting on a sofa, photorealistic, 8k')">🐱 گربه</span>
                    <span class="prompt-chip" onclick="setPrompt('a modern luxury house with pool, architectural photography, 8k')">🏠 خانه مدرن</span>
                    <span class="prompt-chip" onclick="setPrompt('a beautiful Persian garden with flowers and fountain, 8k')">🌸 باغ ایرانی</span>
                </div>
                <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px;">
                    <label>🎚️ خلاقیت:</label>
                    <input type="range" id="guidance_generate" min="1" max="15" value="7" style="flex:1;">
                    <span id="guidance_val">7</span>
                </div>
                <button onclick="generateImage()" class="btn btn-primary" style="margin-top: 15px; width: 100%;">✨ ساخت عکس</button>
                <div id="result_generate"></div>
            </div>
        </div>

        <div id="panel-analyze" class="image-panel">
            <div class="card">
                <h3>🔍 تحلیل عکس با هوش مصنوعی</h3>
                <p class="text-muted">عکس را آپلود کنید تا اشیا و محتوای آن تشخیص داده شود</p>
                <div class="drop-zone" onclick="document.getElementById('image_analyze').click()">
                    <i class="fas fa-cloud-upload-alt" style="font-size:2rem; color:var(--primary);"></i>
                    <p>کلیک کنید یا عکس را بکشید</p>
                    <input type="file" id="image_analyze" accept="image/*" style="display:none;" onchange="analyzeImage(this)">
                </div>
                <img id="analyze-preview" style="display:none; max-width:250px; margin-top:15px; border-radius:8px;">
                <div id="result_analyze"></div>
            </div>
        </div>

        <div id="panel-prompt" class="image-panel">
            <div class="card">
                <h3>🔄 تبدیل عکس به پرامپت</h3>
                <p class="text-muted">عکس را آپلود کنید تا پرامپت مناسب برای ساخت عکس مشابه دریافت کنید</p>
                <div class="drop-zone" onclick="document.getElementById('image_prompt').click()">
                    <i class="fas fa-cloud-upload-alt" style="font-size:2rem; color:var(--primary);"></i>
                    <p>کلیک کنید یا عکس را بکشید</p>
                    <input type="file" id="image_prompt" accept="image/*" style="display:none;" onchange="imageToPrompt(this)">
                </div>
                <img id="prompt-preview" style="display:none; max-width:250px; margin-top:15px; border-radius:8px;">
                <div id="result_prompt"></div>
            </div>
        </div>

        <div id="panel-alt" class="image-panel">
            <div class="card">
                <h3>📝 تولید متن جایگزین (Alt Text)</h3>
                <p class="text-muted">برای سئوی بهتر، متن جایگزین عکس‌های خود را خودکار تولید کنید</p>
                <div class="drop-zone" onclick="document.getElementById('image_alt').click()">
                    <i class="fas fa-cloud-upload-alt" style="font-size:2rem; color:var(--primary);"></i>
                    <p>کلیک کنید یا عکس را بکشید</p>
                    <input type="file" id="image_alt" accept="image/*" style="display:none;" onchange="generateAltText(this)">
                </div>
                <img id="alt-preview" style="display:none; max-width:250px; margin-top:15px; border-radius:8px;">
                <div id="result_alt"></div>
            </div>
        </div>

        <div id="panel-gallery" class="image-panel">
            <div class="card">
                <h3>🖼️ گالری هوشمند</h3>
                <p class="text-muted">جستجو در عکس‌های ساخته شده با کلمات کلیدی</p>
                <input type="text" id="gallery_search" placeholder="مثال: cat, mountain, sunset..." oninput="searchGallery()" style="width:100%; padding:10px; margin-bottom:15px; border:1px solid var(--border); border-radius:8px; background:var(--bg-input); color:var(--text-primary); font-family:var(--font);">
                <div id="gallery_result" class="gallery-grid">
                    <?php
                    $all_images = array_merge(
                        glob($_SERVER['DOCUMENT_ROOT'] . '/uploads/gen_*.png') ?: [],
                        glob($_SERVER['DOCUMENT_ROOT'] . '/uploads/ai_*.png') ?: []
                    );
                    rsort($all_images);
                    foreach (array_slice($all_images, 0, 40) as $img):
                        $name = basename($img);
                        $url = '/uploads/' . $name;
                    ?>
                        <div class="gallery-item" onclick="selectGalleryImage('<?php echo $url; ?>')">
                            <img src="<?php echo $url; ?>" loading="lazy" alt="AI Generated Image">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.image-panel').forEach(function(p) { p.classList.remove('active'); });
    var panel = document.getElementById('panel-' + tab);
    if (panel) panel.classList.add('active');
    document.querySelectorAll('.tool-btn').forEach(function(b) { b.classList.remove('active'); });
    var buttons = document.querySelectorAll('.tool-btn');
    buttons.forEach(function(btn) {
        if (btn.getAttribute('onclick') && btn.getAttribute('onclick').indexOf("'" + tab + "'") !== -1) {
            btn.classList.add('active');
        }
    });
}

function setPrompt(text) { document.getElementById('prompt_generate').value = text; }

document.getElementById('guidance_generate').addEventListener('input', function() {
    document.getElementById('guidance_val').textContent = this.value;
});

async function generateImage() {
    var prompt = document.getElementById('prompt_generate').value.trim();
    if (!prompt) { alert('لطفاً توضیح عکس را وارد کنید'); return; }
    var result = document.getElementById('result_generate');
    result.innerHTML = '<div class="text-center py-4">⏳ در حال ساخت عکس... (۱۰-۳۰ ثانیه)</div>';
    var size = document.getElementById('size_generate').value.split('x');
    var formData = new FormData();
    formData.append('action', 'text_to_image');
    formData.append('prompt', prompt);
    formData.append('model', document.getElementById('model_generate').value);
    formData.append('width', size[0]);
    formData.append('height', size[1]);
    formData.append('guidance', document.getElementById('guidance_generate').value);
    try {
        var res = await fetch('/api/image/edit.php', { method: 'POST', body: formData });
        var data = await res.json();
        if (data.success) {
            result.innerHTML = '<div class="text-center mt-3">' +
                '<img src="' + data.image_url + '?t=' + Date.now() + '" class="generated-img" onerror="this.style.display=\'none\';">' +
                '<p class="success mt-2">✅ ' + (data.message || 'عکس ساخته شد') + '</p>' +
                '<a href="' + data.image_url + '" download class="btn btn-outline btn-sm mt-2">⬇️ دانلود</a></div>';
        } else {
            result.innerHTML = '<div class="error mt-3">❌ ' + data.error + '</div>';
        }
    } catch (e) {
        result.innerHTML = '<div class="error mt-3">❌ خطا در ارتباط با سرور</div>';
    }
}

async function analyzeImage(input) {
    var file = input.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('analyze-preview').src = e.target.result;
        document.getElementById('analyze-preview').style.display = 'block';
    };
    reader.readAsDataURL(file);
    var result = document.getElementById('result_analyze');
    result.innerHTML = '<div class="text-center py-3">⏳ در حال تحلیل...</div>';
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
            if (analyzeData.success) {
                result.innerHTML = '<div class="card mt-3"><h4>📊 نتایج تحلیل</h4>' +
                    '<p><strong>🏷️ اشیا:</strong> ' + analyzeData.objects + '</p>' +
                    '<p><strong>📂 دسته‌بندی:</strong> ' + analyzeData.categories + '</p></div>';
            } else {
                result.innerHTML = '<div class="error mt-3">❌ ' + analyzeData.error + '</div>';
            }
        }
    } catch (e) {
        result.innerHTML = '<div class="error mt-3">❌ خطا</div>';
    }
}

async function imageToPrompt(input) {
    var file = input.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('prompt-preview').src = e.target.result;
        document.getElementById('prompt-preview').style.display = 'block';
    };
    reader.readAsDataURL(file);
    var result = document.getElementById('result_prompt');
    result.innerHTML = '<div class="text-center py-3">⏳ در حال تحلیل...</div>';
    var formData = new FormData();
    formData.append('action', 'upload');
    formData.append('image', file);
    try {
        var uploadRes = await fetch('/api/image/edit.php', { method: 'POST', body: formData });
        var uploadData = await uploadRes.json();
        if (uploadData.success) {
            var promptFormData = new FormData();
            promptFormData.append('action', 'image_to_prompt');
            promptFormData.append('image_url', uploadData.image_url);
            var promptRes = await fetch('/api/image/edit.php', { method: 'POST', body: promptFormData });
            var promptData = await promptRes.json();
            if (promptData.success) {
                result.innerHTML = '<div class="card mt-3"><h4>✨ پرامپت پیشنهادی:</h4>' +
                    '<textarea rows="3" style="width:100%;" id="generated_prompt">' + promptData.prompt + '</textarea>' +
                    '<button onclick="usePrompt()" class="btn btn-primary mt-2">🎨 ساخت عکس</button></div>';
            }
        }
    } catch (e) {
        result.innerHTML = '<div class="error mt-3">❌ خطا</div>';
    }
}

function usePrompt() {
    var prompt = document.getElementById('generated_prompt').value;
    switchTab('generate');
    document.getElementById('prompt_generate').value = prompt;
}

async function generateAltText(input) {
    var file = input.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('alt-preview').src = e.target.result;
        document.getElementById('alt-preview').style.display = 'block';
    };
    reader.readAsDataURL(file);
    var result = document.getElementById('result_alt');
    result.innerHTML = '<div class="text-center py-3">⏳ در حال تولید...</div>';
    var formData = new FormData();
    formData.append('action', 'upload');
    formData.append('image', file);
    try {
        var uploadRes = await fetch('/api/image/edit.php', { method: 'POST', body: formData });
        var uploadData = await uploadRes.json();
        if (uploadData.success) {
            var altFormData = new FormData();
            altFormData.append('action', 'generate_alt');
            altFormData.append('image_url', uploadData.image_url);
            var altRes = await fetch('/api/image/edit.php', { method: 'POST', body: altFormData });
            var altData = await altRes.json();
            if (altData.success) {
                result.innerHTML = '<div class="card mt-3"><h4>📝 Alt Text:</h4>' +
                    '<textarea rows="2" style="width:100%;" id="alt_text_output">' + altData.alt_text + '</textarea>' +
                    '<button onclick="copyText(\'alt_text_output\')" class="btn btn-outline btn-sm mt-2">📋 کپی</button></div>';
            }
        }
    } catch (e) {
        result.innerHTML = '<div class="error mt-3">❌ خطا</div>';
    }
}

function searchGallery() {
    var query = document.getElementById('gallery_search').value.toLowerCase();
    document.querySelectorAll('.gallery-item').forEach(function(item) {
        var alt = item.querySelector('img')?.alt?.toLowerCase() || '';
        item.style.display = !query || alt.includes(query) ? 'block' : 'none';
    });
}

async function selectGalleryImage(url) {
    switchTab('prompt');
    try {
        var response = await fetch(url);
        var blob = await response.blob();
        var file = new File([blob], 'gallery.png', { type: 'image/png' });
        var dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('image_prompt').files = dt.files;
        imageToPrompt(document.getElementById('image_prompt'));
    } catch (e) {
        alert('خطا در بارگذاری عکس');
    }
}

function copyText(elementId) {
    var textarea = document.getElementById(elementId);
    if (!textarea) return;
    textarea.select();
    document.execCommand('copy');
}
</script>