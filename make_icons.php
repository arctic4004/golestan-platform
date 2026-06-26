<?php
// make_icons.php - فقط یک بار اجرا کن بعد پاک کن
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$source = 'uploads/gen_YOUR_IMAGE.png'; // ← اسم فایل عکسی که ساختی

if (!file_exists($source)) {
    die("فایل $source پیدا نشد. اول عکس رو بساز.");
}

if (!file_exists('assets/icons')) {
    mkdir('assets/icons', 0755, true);
}

$img = imagecreatefrompng($source);

foreach ($sizes as $size) {
    $resized = imagecreatetruecolor($size, $size);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    imagecopyresampled($resized, $img, 0, 0, 0, 0, $size, $size, imagesx($img), imagesy($img));
    imagepng($resized, "assets/icons/icon-{$size}x{$size}.png");
    imagedestroy($resized);
    echo "✅ icon-{$size}x{$size}.png<br>";
}

imagedestroy($img);
echo "<h3>🎉 همه آیکون‌ها ساخته شدن!</h3>";
echo "<p>حالا گوشی رو چک کن - باید PWA کار کنه.</p>";