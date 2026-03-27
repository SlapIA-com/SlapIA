<?php
/**
 * Générateur de flux RSS dynamique pour le blog
 */
header('Content-Type: application/rss+xml; charset=utf-8');

include_once __DIR__ . '/api/notion-blog.php';
include_once __DIR__ . '/includes/lang.php'; 

// Récupérer les 20 derniers articles pour le flux
$articles = getBlogArticles(20);

$siteUrl = 'https://www.slapia.com';
$blogUrl = $siteUrl . '/blog';
$rssUrl = $siteUrl . '/rss.xml';

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
  <channel>
    <title>SlapIA Blog</title>
    <link><?php echo htmlspecialchars($blogUrl); ?></link>
    <description><?php echo htmlspecialchars(t('meta_description') ?? "Derniers articles sur l'IA et l'Automatisation par SlapIA."); ?></description>
    <language><?php echo isset($lang) ? $lang : 'fr'; ?>-FR</language>
    <atom:link href="<?php echo htmlspecialchars($rssUrl); ?>" rel="self" type="application/rss+xml" />
    
    <?php if (!empty($articles)): ?>
      <lastBuildDate><?php echo htmlspecialchars($articles[0]['date_rss']); ?></lastBuildDate>
    <?php endif; ?>
    
    <?php foreach ($articles as $article): 
        // Récupérer le contenu complet via les blocs Notion pour le flux RSS
        $fullContent = getNotionBlocks($article['id']);
        if (empty($fullContent)) {
            $fullContent = !empty($article['extrait']) ? $article['extrait'] : $article['contenu_raw'];
        }
        
        // Nettoyage et formatage minimal pour XML
        $description = htmlspecialchars($article['extrait'] ?: mb_strimwidth(strip_tags($fullContent), 0, 300, "..."));
    ?>
    <item>
      <title><?php echo htmlspecialchars($article['titre']); ?></title>
      <link><?php echo htmlspecialchars($article['url']); ?></link>
      <guid isPermaLink="true"><?php echo htmlspecialchars($article['url']); ?></guid>
      <pubDate><?php echo htmlspecialchars($article['date_rss']); ?></pubDate>
      <description><?php echo $description; ?></description>
      <content:encoded><![CDATA[<?php echo $fullContent; ?>]]></content:encoded>
      <?php if (!empty($article['image'])): 
          // Extract extension or fallback to jpeg
          $ext = pathinfo(parse_url($article['image'], PHP_URL_PATH), PATHINFO_EXTENSION);
          $mime = 'image/jpeg';
          if (in_array(strtolower($ext), ['png'])) $mime = 'image/png';
          elseif (in_array(strtolower($ext), ['gif'])) $mime = 'image/gif';
          elseif (in_array(strtolower($ext), ['webp'])) $mime = 'image/webp';
      ?>
        <enclosure url="<?php echo htmlspecialchars($article['image']); ?>" type="<?php echo $mime; ?>" length="0" />
      <?php endif; ?>
    </item>
    <?php endforeach; ?>
  </channel>
</rss>
