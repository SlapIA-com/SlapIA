<?php
/**
 * Dynamic sitemap, served at /sitemap.xml via the .htaccess rewrite.
 *
 * Replaces the old static sitemap.xml, which only listed 6 URLs (no legal
 * pages, no blog, no individual articles) and was never actually reachable
 * anyway: the rewrite rule pointed at this file before it existed, so
 * /sitemap.xml — the URL robots.txt itself declares — was 404ing.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/notion-blog.php';

header('Content-Type: application/xml; charset=UTF-8');

const SITEMAP_BASE_URL = 'https://www.slapia.com';

$staticPages = [
    ['loc' => '/',                       'priority' => '1.0'],
    ['loc' => '/formations.php',         'priority' => '0.9'],
    ['loc' => '/services-pc.php',        'priority' => '0.9'],
    ['loc' => '/tarifs.php',             'priority' => '0.8'],
    ['loc' => '/blog.php',               'priority' => '0.8'],
    ['loc' => '/a-propos.php',           'priority' => '0.6'],
    ['loc' => '/contact.php',            'priority' => '0.7'],
    ['loc' => '/mentions-legales.php',   'priority' => '0.3'],
    ['loc' => '/confidentialite.php',    'priority' => '0.3'],
    ['loc' => '/cgv.php',                'priority' => '0.3'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($staticPages as $page) {
    echo '  <url><loc>' . htmlspecialchars(SITEMAP_BASE_URL . $page['loc'], ENT_XML1) . '</loc><priority>' . $page['priority'] . '</priority></url>' . "\n";
}

$articles = listBlogArticles(1000);
foreach ($articles as $article) {
    if ($article['slug'] === '') continue;

    $loc = SITEMAP_BASE_URL . '/blog/' . $article['slug'];
    $lastmodTag = '';
    $timestamp = strtotime((string)$article['date']);
    if ($timestamp !== false) {
        $lastmodTag = '<lastmod>' . date('Y-m-d', $timestamp) . '</lastmod>';
    }

    echo '  <url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>' . $lastmodTag . '<priority>0.5</priority></url>' . "\n";
}

echo '</urlset>' . "\n";
