<?php
// sitemap.php
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- صفحات استاتیک -->
    <url>
        <loc>https://golestanyasuj.ir/</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>https://golestanyasuj.ir/login.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc>https://golestanyasuj.ir/signup.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>https://golestanyasuj.ir/user/dashboard/v2/chat.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>https://golestanyasuj.ir/user/dashboard/v2/image.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>https://golestanyasuj.ir/user/dashboard/v2/tasks.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
</urlset>