<?php
// test_tools.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'تست';
$_SESSION['credits'] = 9999;
$_SESSION['is_admin'] = true;

require_once 'config/constants.php';

echo "<!DOCTYPE html><html dir='rtl'><head><meta charset='UTF-8'><title>تست ابزارهای تصویر</title>
<style>
body{font-family:Tahoma;background:#1a1a2e;color:#eee;padding:20px}
.box{background:#16213e;border-radius:12px;padding:20px;margin:15px 0}
.ok{color:#4caf50}.err{color:#f44336}
button{padding:10px 20px;background:#6366f1;color:white;border:none;border-radius:8px;cursor:pointer;margin:5px;font-family:Tahoma}
img{max-width:250px;border-radius:8px;margin:10px}
pre{background:#0f0f1a;padding:10px;border-radius:8px;font-size:11px}
</style></head><body>
<h1>🧪 تست ابزارهای تصویر</h1>";

echo "<div class='box'><h2>📁 وضعیت</h2>";
echo "<p>" . (file_exists('api/image/tools.php') ? "<span class='ok'>✅</span>" : "<span class='err'>❌</span>") . " api/image/tools.php</p>";
echo "<p>" . (file_exists('user/dashboard/v2/tools.php') ? "<span class='ok'>✅</span>" : "<span class='err'>❌</span>") . " user/dashboard/v2/tools.php</p>";
echo "<p>GD: " . (function_exists('imagecreatefromstring') ? "<span class='ok'>✅ فعال</span>" : "<span class='err'>❌ غیرفعال</span>") . "</p>";
echo "</div>";

echo "<div class='box'><h2>🖼️ تست با عکس واقعی</h2>";
echo "<p>برای تست واقعی، <strong>یه عکس JPG یا PNG معمولی</strong> (نه AI-generated) آپلود کن:</p>";

echo "<form method='POST' enctype='multipart/form-data'>";
echo "<input type='file' name='test_image' accept='image/*' required>";
echo "<button type='submit' name='do_test'>🧪 اجرای تست</button>";
echo "</form>";

if (isset($_POST['do_test']) && isset($_FILES['test_image']) && $_FILES['test_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['test_image'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $test_path = 'uploads/test_real_' . time() . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $test_path);
    
    echo "<p class='ok'>✅ عکس آپلود شد: $test_path</p>";
    echo "<img src='/$test_path'>";
    
    // تست Crop
    echo "<h3>✂️ تست Crop:</h3>";
    $img = @imagecreatefromstring(file_get_contents($test_path));
    if ($img) {
        $cropped = imagecrop($img, ['x' => 10, 'y' => 10, 'width' => 150, 'height' => 150]);
        if ($cropped) {
            $crop_out = 'uploads/test_crop_' . time() . '.png';
            imagepng($cropped, $crop_out);
            echo "<p class='ok'>✅ Crop موفق!</p><img src='/$crop_out'>";
            imagedestroy($cropped);
        }
        imagedestroy($img);
    } else {
        echo "<p class='err'>❌ نتونست عکس رو بخونه</p>";
    }
    
    // تست Rotate
    echo "<h3>🔄 تست Rotate:</h3>";
    $img = @imagecreatefromstring(file_get_contents($test_path));
    if ($img) {
        $rotated = imagerotate($img, 90, 0);
        if ($rotated) {
            $rot_out = 'uploads/test_rotate_' . time() . '.png';
            imagepng($rotated, $rot_out);
            echo "<p class='ok'>✅ Rotate موفق!</p><img src='/$rot_out'>";
            imagedestroy($rotated);
        }
        imagedestroy($img);
    }
    
    // تست Compress
    echo "<h3>📦 تست Compress:</h3>";
    $img = @imagecreatefromstring(file_get_contents($test_path));
    if ($img) {
        $comp_out = 'uploads/test_compress_' . time() . '.jpg';
        imagejpeg($img, $comp_out, 50);
        $orig = filesize($test_path);
        $new = filesize($comp_out);
        echo "<p class='ok'>✅ Compress موفق! " . round($orig/1024,1) . "KB → " . round($new/1024,1) . "KB</p>";
        imagedestroy($img);
    }
}

echo "</div>";

echo "<div class='box'><h2>📋 نتیجه</h2>";
echo "<p>اگر تست‌های بالا ✅ شدن، API tools هم برای عکس‌های معمولی کار میکنه.</p>";
echo "<p>برو به <a href='/user/dashboard/v2/tools.php' style='color:#6366f1;'>صفحه ابزارها</a> و مستقیم تست کن.</p>";
echo "</div>";

echo "</body></html>";
?>