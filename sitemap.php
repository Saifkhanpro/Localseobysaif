<?php
/**
 * LocalSEO.pk — Dynamic Sitemap Handler
 * Version: 1.0.0
 */

define('LSEO_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Database.php';

$db = \Core\Database::getInstance();
$prefix = $db->getTablePrefix();

$type = $_GET['type'] ?? 'index';

function renderSitemapLinks($links) {
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
    foreach ($links as $link) {
        echo '<url>';
        echo '<loc>' . htmlspecialchars($link['loc']) . '</loc>';
        echo '<lastmod>' . $link['lastmod'] . '</lastmod>';
        echo '<changefreq>' . ($link['changefreq'] ?? 'weekly') . '</changefreq>';
        echo '<priority>' . ($link['priority'] ?? '0.8') . '</priority>';
        echo '</url>';
    }
    echo '</urlset>';
}

if ($type === 'index' || $type === 'sitemap') {
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    $sitemaps = [
        'post-sitemap.xml', 
        'page-sitemap.xml', 
        'service-sitemap.xml', 
        'city-sitemap.xml', 
        'category-sitemap.xml', 
        'case-study-sitemap.xml',
        'image-sitemap.xml'
    ];
    foreach ($sitemaps as $sm) {
        echo '<sitemap><loc>' . APP_URL . '/' . $sm . '</loc><lastmod>' . date('c') . '</lastmod></sitemap>';
    }
    echo '</sitemapindex>';
} else {
    $links = [];
    $tableMap = [
        'posts' => ['table' => 'posts', 'route' => '/blog/', 'priority' => '0.8'],
        'pages' => ['table' => 'pages', 'route' => '/', 'priority' => '0.9'],
        'services' => ['table' => 'services', 'route' => '/services/', 'priority' => '0.9'],
        'cities' => ['table' => 'city_pages', 'route' => '/local-seo/', 'priority' => '1.0'],
        'categories' => ['table' => 'categories', 'route' => '/category/', 'priority' => '0.6'],
        'case_studies' => ['table' => 'case_studies', 'route' => '/case-studies/', 'priority' => '0.8'],
    ];

    if ($type === 'images') {
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
        
        $media = $db->fetchAll("SELECT m.file_path, m.title, m.alt_text, p.slug as post_slug FROM {$prefix}media m LEFT JOIN {$prefix}posts p ON m.id = p.featured_image_id WHERE m.file_type = 'image' AND p.status = 'published'");
        
        $grouped = [];
        foreach ($media as $m) {
            if (!$m['post_slug']) continue;
            $url = APP_URL . '/blog/' . $m['post_slug'];
            if (!isset($grouped[$url])) $grouped[$url] = [];
            $grouped[$url][] = $m;
        }

        foreach ($grouped as $url => $images) {
            echo '<url>';
            echo '<loc>' . htmlspecialchars($url) . '</loc>';
            foreach ($images as $img) {
                echo '<image:image>';
                echo '<image:loc>' . APP_URL . '/' . ltrim($img['file_path'], '/') . '</image:loc>';
                if ($img['title']) echo '<image:title><![CDATA[' . htmlspecialchars($img['title']) . ']]></image:title>';
                if ($img['alt_text']) echo '<image:caption><![CDATA[' . htmlspecialchars($img['alt_text']) . ']]></image:caption>';
                echo '</image:image>';
            }
            echo '</url>';
        }
        echo '</urlset>';
        exit;
    }

    if (isset($tableMap[$type])) {
        $cfg = $tableMap[$type];
        
        $statusClause = "status = 'published'";
        if ($type === 'categories') $statusClause = "1=1"; 

        $items = $db->fetchAll("SELECT slug, updated_at FROM {$prefix}{$cfg['table']} WHERE {$statusClause} AND robots_index = 'index'");
        
        foreach ($items as $item) {
            $links[] = [
                'loc' => APP_URL . $cfg['route'] . ltrim($item['slug'], '/'),
                'lastmod' => date('c', strtotime($item['updated_at'] ?? 'now')),
                'priority' => $cfg['priority']
            ];
        }
    }

    renderSitemapLinks($links);
}

