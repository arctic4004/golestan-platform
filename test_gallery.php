<?php
// test_gallery.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🖼️ تست گالری</h2>";

$upload_dir = __DIR__ . '/uploads';
$patterns = ['gen_*.png', 'ai_*.png', 'up_*.png', 'magic_*.png', 'crop_*.png', 'rotate_*.png', 'wm_*.png', 'compressed_*.jpg', 'blurred_*.png'];

echo "<h3>📁 فایل‌های موجود:</h3>";
$all = [];
foreach ($patterns as $p) {
    $files = glob($upload_dir . '/' . $p) ?: [];
    echo "<strong>$p:</strong> " . count($files) . " فایل<br>";
    $all = array_merge($all, $files);
}
echo "<hr><strong>کل:</strong> " . count($all) . " فایل<br>";

echo "<h3>🖼️ نمونه فایل‌ها:</h3>";
echo "<div style='display:flex;flex-wrap:wrap;gap:10px'>";
foreach (array_slice($all, 0, 20) as $f) {
    $name = basename($f);
    $url = '/uploads/' . $name;
    echo "<div style='text-align:center;width:150px'>";
    echo "<img src='$url' style='width:150px;height:150px;object-fit:cover;border-radius:8px' onerror=\"this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22150%22 height=%22150%22><rect fill=%22%23f1f5f9%22 width=%22150%22 height=%22150%22/><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%2394a3b8%22>❌</text></svg>'\">";
    echo "<small style='display:block;word-break:break-all;font-size:10px'>$name</small>";
    echo "</div>";
}
echo "</div>";

echo "<h3>🗑️ حذف همه عکس‌ها:</h3>";
if (isset($_GET['delete']) && $_GET['delete'] === 'all') {
    $count = 0;
    foreach ($all as $f) {
        if (unlink($f)) $count++;
    }
    echo "<p style='color:#10b981'>✅ $count فایل حذف شد. <a href='?'>رفرش</a></p>";
} else {
    echo "<a href='?delete=all' onclick=\"return confirm('همه عکس‌ها حذف بشن؟')\" style='background:#ef4444;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none'>🗑️ حذف همه عکس‌ها</a>";
}

echo "<h3>📊 اطلاعات دایرکتوری:</h3>";
echo "مسیر: $upload_dir<br>";
echo "قابل نوشتن: " . (is_writable($upload_dir) ? '✅ بله' : '❌ خیر') . "<br>";
echo "حجم کل: " . round(array_sum(array_map('filesize', $all)) / 1024 / 1024, 2) . " MB<br>";
?>