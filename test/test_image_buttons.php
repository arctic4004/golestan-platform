<?php
// test_image_buttons.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ست کردن سشن تستی
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'تست';
$_SESSION['phone'] = '09177418286';
$_SESSION['credits'] = 1000;
$_SESSION['is_admin'] = true;
$_SESSION['theme'] = 'light';

echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><title>تست دکمه‌های image.php</title>";
echo "<style>
    body { font-family: Tahoma; background: #1a1a2e; color: #eee; padding: 20px; }
    button { padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 8px; cursor: pointer; margin: 5px; font-family: Tahoma; }
    button:hover { background: #4f46e5; }
    .box { background: #16213e; border-radius: 12px; padding: 20px; margin: 15px 0; border: 1px solid #2a2a4a; }
    .success { color: #4caf50; }
    .error { color: #f44336; }
    pre { background: #0f0f1a; padding: 10px; border-radius: 8px; font-size: 11px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🧪 تست دکمه‌های image.php</h1>";

// =============================================
// تست ۱: ساختار HTML
// =============================================
echo "<div class='box'><h2>۱. بررسی ساختار image.php</h2>";

$image_content = file_get_contents('user/dashboard/v2/image.php');

$checks = [
    'switchTab' => 'تابع switchTab',
    'generateImage' => 'تابع generateImage',
    'analyzeImage' => 'تابع analyzeImage',
    'imageToPrompt' => 'تابع imageToPrompt',
    'generateAltText' => 'تابع generateAltText',
    'searchGallery' => 'تابع searchGallery',
    'panel-generate' => 'پنل ساخت عکس',
    'panel-analyze' => 'پنل تحلیل عکس',
    'panel-prompt' => 'پنل عکس به پرامپت',
    'panel-alt' => 'پنل Alt Text',
    'panel-gallery' => 'پنل گالری',
    'class="image-panel' => 'کلاس image-panel',
    'class="tool-btn' => 'کلاس tool-btn',
    'class="active"' => 'کلاس active',
];

foreach ($checks as $search => $label) {
    $found = strpos($image_content, $search) !== false;
    echo "<p>" . ($found ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . " $label ($search)</p>";
}

echo "</div>";

// =============================================
// تست ۲: تب‌ها و پنل‌ها
// =============================================
echo "<div class='box'><h2>۲. تست تب‌ها و پنل‌ها</h2>";

// شمارش پنل‌ها
preg_match_all('/id="panel-(\w+)"/', $image_content, $panel_matches);
$panels = $panel_matches[1] ?? [];
echo "<p>پنل‌های پیدا شده: <strong>" . count($panels) . "</strong> → " . implode(', ', $panels) . "</p>";

// شمارش tool-btn
preg_match_all('/class="tool-btn/', $image_content, $btn_matches);
echo "<p>دکمه‌های تب: <strong>" . count($btn_matches[0]) . "</strong></p>";

// چک onclick
preg_match_all('/onclick="switchTab\(\'(\w+)\'\)"/', $image_content, $onclick_matches);
$onclicks = $onclick_matches[1] ?? [];
echo "<p>onclickهای switchTab: <strong>" . count($onclicks) . "</strong> → " . implode(', ', $onclicks) . "</p>";

// تطابق پنل‌ها با onclickها
foreach ($onclicks as $tab) {
    $panel_exists = in_array($tab, $panels);
    echo "<p>تب <strong>$tab</strong>: " . ($panel_exists ? "<span class='success'>✅ پنلش هست</span>" : "<span class='error'>❌ پنلش نیست!</span>") . "</p>";
}

echo "</div>";

// =============================================
// تست ۳: توابع جاوااسکریپت
// =============================================
echo "<div class='box'><h2>۳. بررسی توابع جاوااسکریپت</h2>";

$js_functions = ['switchTab', 'generateImage', 'analyzeImage', 'imageToPrompt', 'generateAltText', 'searchGallery', 'selectGalleryImage', 'copyText', 'usePrompt', 'setPrompt'];
foreach ($js_functions as $func) {
    $found = preg_match('/function\s+' . $func . '\s*\(/', $image_content);
    echo "<p>" . ($found ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . " function $func()</p>";
}

echo "</div>";

// =============================================
// تست ۴: استایل‌های ضروری
// =============================================
echo "<div class='box'><h2>۴. استایل‌های ضروری</h2>";

$styles = ['.image-panel', '.tool-btn', '.active', '.card', '.drop-zone', '.gallery-grid', '.gallery-item', '.prompt-chip'];
foreach ($styles as $style) {
    $found = strpos($image_content, $style) !== false;
    echo "<p>" . ($found ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . " $style</p>";
}

echo "</div>";

// =============================================
// تست ۵: API endpoints
// =============================================
echo "<div class='box'><h2>۵. تست APIها</h2>";

echo "<button onclick='testAPI()'>🧪 تست API ساخت عکس</button>";
echo "<div id='api_result'></div>";

echo "</div>";

// =============================================
// تست ۶: لینک مستقیم
// =============================================
echo "<div class='box'><h2>۶. لینک تست</h2>";
echo "<a href='/user/dashboard/v2/image.php' target='_blank' style='color:#6366f1;font-size:18px;'>🔗 باز کردن image.php در تب جدید</a>";
echo "</div>";

?>

<script>
async function testAPI() {
    const result = document.getElementById('api_result');
    result.innerHTML = '<p>⏳ در حال تست...</p>';
    
    const formData = new FormData();
    formData.append('action', 'text_to_image');
    formData.append('prompt', 'test cat');
    formData.append('model', 'flux');
    formData.append('width', 256);
    formData.append('height', 256);
    
    try {
        const res = await fetch('/api/image/edit.php', { method: 'POST', body: formData });
        const data = await res.json();
        result.innerHTML = data.success 
            ? `<p class='success'>✅ API کار میکنه! عکس: <a href='${data.image_url}' target='_blank'>مشاهده</a></p>`
            : `<p class='error'>❌ ${data.error}</p>`;
    } catch (e) {
        result.innerHTML = `<p class='error'>❌ ${e.message}</p>`;
    }
}
</script>

</body></html>