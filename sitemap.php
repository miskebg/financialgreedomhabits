<?php
header('Content-Type: application/xml; charset=utf-8');

require_once 'config.php';

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <!-- Home page -->
    <url>
        <loc>https://financialfreedomhabits.site/</loc>
        <priority>1.0</priority>
    </url>

<?php
$sql = "SELECT slug, updated_at, created_at FROM $TABLE_POSTS WHERE status = 1 ORDER BY created_at DESC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $url = "https://financialfreedomhabits.site/post.php?slug=" . htmlspecialchars($row['slug']);
    $date = $row['updated_at'] ?: $row['created_at'];
?>
    <url>
        <loc><?= $url ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($date)) ?></lastmod>
        <priority>0.80</priority>
    </url>
<?php
}
?>
</urlset>
