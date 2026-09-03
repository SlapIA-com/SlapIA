<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notion.php';

const BLOG_DATABASE_ID = '328b2071-3b6f-8036-afa7-dcd8a9051eca';

/**
 * Must produce the same output as the n8n newsletter template's JS
 * slugify() (strip diacritics, lowercase, non-alphanumeric runs -> single
 * hyphen, no leading/trailing hyphen) so links stay consistent between the
 * site and any future emails. Uses a manual accent map rather than
 * iconv('UTF-8', 'ASCII//TRANSLIT', ...) — confirmed via live testing that
 * TRANSLIT is not portable (on this server it turns "é" into "'e", not "e").
 */
function slugifyTitle(string $title): string
{
    $accentMap = [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
        'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
        'ý' => 'y', 'ÿ' => 'y',
        'ñ' => 'n', 'ç' => 'c',
        'œ' => 'oe', 'æ' => 'ae',
    ];

    $lower = mb_strtolower($title, 'UTF-8');
    $ascii = strtr($lower, $accentMap);
    $slug  = preg_replace('/[^a-z0-9]+/', '-', $ascii);
    return trim($slug, '-');
}

/**
 * Lists articles from the "RSS SlapIA" Notion database (read-only — this
 * database is written to exclusively by a production n8n automation).
 * Cached 1 hour, same pattern as includes/reviews.php.
 */
function listBlogArticles(int $limit = 100): array
{
    $cacheFile = sys_get_temp_dir() . '/slapia_blog_' . md5((string)$limit) . '.json';
    $cacheTtl  = 3600;

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) return $cached;
    }

    $result = notion()->queryDatabaseAll(BLOG_DATABASE_ID, [
        'sorts' => [['property' => 'Publication Date', 'direction' => 'descending']],
    ]);
    if (!empty($result['error'])) return [];

    $articles = [];
    foreach ($result['results'] ?? [] as $page) {
        $props = $page['properties'] ?? [];
        $title = NotionAPI::title($props['Titre'] ?? []);
        if ($title === '') continue;

        $files = $props['Image']['files'] ?? [];
        $image = null;
        if (!empty($files)) {
            $image = $files[0]['file']['url'] ?? $files[0]['external']['url'] ?? null;
        }

        $articles[] = [
            'id'      => $page['id'],
            'title'   => $title,
            'excerpt' => NotionAPI::richText($props['Extrait '] ?? []),
            'date'    => $props['Publication Date']['date']['start'] ?? $page['created_time'],
            'image'   => $image,
            'slug'    => slugifyTitle($title),
        ];

        if (count($articles) >= $limit) break;
    }

    @file_put_contents($cacheFile, json_encode($articles), LOCK_EX);

    return $articles;
}

/**
 * Finds the article whose computed slug matches $slug, then fetches and
 * renders its full content. Returns null if nothing matches.
 */
function getBlogArticleBySlug(string $slug): ?array
{
    // A high limit, not the display default: queryDatabaseAll() already
    // fetches every article regardless — $limit only truncates the
    // in-memory array — and resolving a slug must never miss an older
    // article that has simply aged out of the listing page's default
    // 100-article display window (production already has 60 and grows by
    // ~15/month, so this would otherwise start breaking already-sent
    // newsletter links within months).
    $articles = listBlogArticles(1000);
    $match = null;
    foreach ($articles as $article) {
        if ($article['slug'] === $slug) {
            $match = $article;
            break;
        }
    }
    if ($match === null) return null;

    $match['content'] = renderBlogArticleBlocks($match['id']);
    return $match;
}

/**
 * Fetches a blog article page's block children and renders them to HTML.
 * The n8n pipeline only ever creates "paragraph" blocks, using literal
 * "#"/"##"/"###"-prefixed text as section headings (a required space after
 * the hashes distinguishes a heading from a hashtag line like "#IA #PME")
 * — handled here alongside the real Notion block types, in case an
 * article is ever edited by hand in Notion.
 *
 * Notion/n8n splits long paragraphs across MULTIPLE blocks at an arbitrary
 * character offset (no boundary marker) — confirmed via a full 60-article
 * live scan that rendering each block in isolation corrupts output on
 * several real articles (stray "**", truncated headings, list numbering
 * restarting). Consecutive `paragraph` blocks are therefore accumulated
 * and rejoined via joinParagraphParts() into one string before being
 * handed to renderBlogParagraph() together, so mid-sentence splits don't
 * become parser-state boundaries.
 */
function renderBlogArticleBlocks(string $pageId): string
{
    $result = notion()->request('GET', '/blocks/' . $pageId . '/children?page_size=100');
    if (!empty($result['error'])) return '';

    $html = '';
    $paragraphTextParts = [];

    $flushParagraphBlocks = function () use (&$html, &$paragraphTextParts) {
        if (empty($paragraphTextParts)) return;
        $html .= renderBlogParagraph(joinParagraphParts($paragraphTextParts));
        $paragraphTextParts = [];
    };

    foreach ($result['results'] ?? [] as $block) {
        $type = $block['type'] ?? '';

        if ($type === 'paragraph') {
            $paragraphTextParts[] = NotionAPI::richText($block['paragraph'] ?? []);
            continue;
        }

        // Any non-paragraph block ends the current run of paragraph text.
        $flushParagraphBlocks();

        if (in_array($type, ['heading_1', 'heading_2', 'heading_3'], true)) {
            $level = substr($type, -1);
            $text  = NotionAPI::richText($block[$type] ?? []);
            $html .= '<h' . $level . '>' . htmlspecialchars($text) . '</h' . $level . '>';
        } elseif ($type === 'bulleted_list_item') {
            $text = NotionAPI::richText($block['bulleted_list_item'] ?? []);
            $html .= '<ul><li>' . htmlspecialchars($text) . '</li></ul>';
        } elseif ($type === 'numbered_list_item') {
            $text = NotionAPI::richText($block['numbered_list_item'] ?? []);
            $html .= '<ol><li>' . htmlspecialchars($text) . '</li></ol>';
        } elseif ($type === 'quote') {
            $text = NotionAPI::richText($block['quote'] ?? []);
            $html .= '<blockquote>' . htmlspecialchars($text) . '</blockquote>';
        } elseif ($type === 'image') {
            $url = $block['image']['file']['url'] ?? $block['image']['external']['url'] ?? '';
            if ($url !== '') {
                $html .= '<img src="' . htmlspecialchars($url) . '" alt="" loading="lazy">';
            }
        } elseif ($type === 'divider') {
            $html .= '<hr>';
        }
    }
    $flushParagraphBlocks();

    // Merge adjacent same-type list blocks (one Notion block per <li>) into single lists.
    $html = str_replace('</ul><ul>', '', $html);
    $html = str_replace('</ol><ol>', '', $html);

    return $html;
}

/**
 * Rejoins consecutive `paragraph` blocks' raw text into one string before
 * line-based parsing. Notion/n8n chunks a long paragraph across several
 * blocks at an arbitrary space with no boundary marker — confirmed live
 * (e.g. "**Problème" / "initial** : TechnoMétal…" as two separate blocks
 * that are really one sentence). A genuine paragraph break is detectable
 * because the previous fragment ends in terminal punctuation (optionally
 * followed by a closing quote/guillemet, which in French typography sits
 * after a space, not immediately after the punctuation — confirmed live
 * on "...opportunité. »" being misdetected as NOT sentence-final without
 * the \s* before the quote-class). Continuation -> single space (rebuilds
 * the word boundary the chunking removed); genuine break -> blank line.
 *
 * Punctuation alone isn't sufficient: a standalone heading block (e.g.
 * "## Impacts et Risques") never ends in terminal punctuation, and a
 * paragraph can end in an emoji with no punctuation at all — confirmed
 * live on 3/60 articles where this collapsed two headings onto one line
 * ("## Impacts ... ### Méthode...", both swallowed into a single <h2>) or
 * left a "##" marker stranded mid-paragraph (heading regex is anchored on
 * ^ and no longer matches once it's not at the start of a line). Fix:
 * also force a break when the previously accumulated segment's last line
 * is itself a heading, or the next part starts with a heading/list marker
 * — these are structural boundaries regardless of trailing punctuation.
 *
 * Three more edge cases confirmed live after that fix, all in this
 * version:
 * (a) A part can itself contain the literal two-character "\n" escape
 *     (the same n8n JSON-repair quirk `renderBlogParagraph()` already
 *     normalizes) — must normalize BEFORE break-detection here too, or
 *     `strrpos($joined, "\n\n")` never finds structure that only exists
 *     as literal backslash-n inside a still-unnormalized part, silently
 *     treating the whole accumulated string as "one line" with no
 *     heading/hashtag boundary ever detected.
 * (b) A complete hashtag/tag line (e.g. "#IA #PME #Data...") ending a
 *     block, with no terminal punctuation before it, must ALSO force a
 *     break before the next part — otherwise the next block's text gets
 *     glued onto the same line as the tags and the tag-line-only regex
 *     in renderBlogParagraph() (anchored on ^...$) no longer matches,
 *     leaking raw "#tags" into a <p>. Confirmed live on an article whose
 *     entire body was duplicated back-to-back on the same page (n8n
 *     publishing glitch): the first copy's trailing tag line had nothing
 *     terminal before the second copy's opening text.
 * (c) n8n's arbitrary mid-word chunk boundary can land INSIDE a heading
 *     marker itself, splitting "## Le cas de la PME..." into one block
 *     ending in a bare "##" and the next block starting with "Le cas de
 *     la PME..." (no marker). A bare "##"/"###" fragment must NOT count
 *     as a complete structural line (it would wrongly force a break,
 *     stranding "##" alone on its own line, which then mis-renders as a
 *     one-item tag pill) — only a hashtag sequence with real content
 *     after the "#" counts. Confirmed live on an article where this
 *     produced a literal "##" in the output.
 */
function joinParagraphParts(array $parts): string
{
    $joined = '';
    foreach ($parts as $i => $part) {
        $part = str_replace('\n', "\n", $part);
        if ($i === 0) { $joined = $part; continue; }

        $lastBreak = strrpos($joined, "\n\n");
        $lastSegment = $lastBreak === false ? $joined : substr($joined, $lastBreak + 2);
        $lastSegmentTrim = trim($lastSegment);
        $lastLineIsStructural = (bool)preg_match('/^#{1,3}\s+\S/', $lastSegmentTrim)
            || ((bool)preg_match('/^#\S+(\s+#\S+)*$/', $lastSegmentTrim) && !preg_match('/^#{1,3}$/', $lastSegmentTrim));

        $partFirstLine = explode("\n", ltrim($part), 2)[0];
        $partFirstLineTrim = trim($partFirstLine);
        $nextIsStructural = (bool)preg_match('/^(#{1,3}\s+|\d+\.\s+|[-*]\s+)/', $partFirstLine)
            || ((bool)preg_match('/^#\S+(\s+#\S+)*$/', $partFirstLineTrim) && !preg_match('/^#{1,3}$/', $partFirstLineTrim));

        $endsSentence = (bool)preg_match('/[.!?:]\s*[\x{00BB}\x{2019}\x{201D}\x27"]?\s*$/u', $joined);

        $isBreak = $endsSentence || $lastLineIsStructural || $nextIsStructural;
        $joined .= $isBreak ? "\n\n" . $part : ' ' . $part;
    }
    return $joined;
}

/**
 * Renders one paragraph block's text, which frequently contains several
 * logical lines (a heading followed by its body, or a list where each
 * item's title line is followed by an indented wrapped-description line)
 * joined by real newlines — confirmed against real published articles.
 * Splits on "\n" and processes line by line: markdown heading ("## Title"
 * -> <h2>), hashtag line ("#IA #PME" -> tag pills), numbered/bulleted list
 * item (consecutive items merged into one <ol>/<ul>; a following plain
 * line is folded into the previous item as its wrapped description), or
 * plain paragraph (consecutive plain lines merged into one <p>).
 */
function renderBlogParagraph(string $text): string
{
    // Some blocks store a literal two-character "\n" escape sequence
    // instead of a real newline byte (n8n JSON-repair quirk, confirmed on
    // real data) — normalize both to real newlines before splitting.
    $text = str_replace('\n', "\n", $text);
    $lines = explode("\n", $text);

    $html = '';
    $paragraphLines = [];
    $listItems = [];
    $listType = null; // 'ul' or 'ol'

    $flushParagraph = function () use (&$html, &$paragraphLines) {
        if (empty($paragraphLines)) return;
        $html .= '<p>' . formatBlogInline(implode(' ', $paragraphLines)) . '</p>';
        $paragraphLines = [];
    };

    $flushList = function () use (&$html, &$listItems, &$listType) {
        if (empty($listItems)) return;
        $tag = $listType === 'ol' ? 'ol' : 'ul';
        $html .= '<' . $tag . '>';
        foreach ($listItems as $item) {
            $html .= '<li>' . formatBlogInline($item) . '</li>';
        }
        $html .= '</' . $tag . '>';
        $listItems = [];
        $listType = null;
    };

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') { $flushParagraph(); $flushList(); continue; }

        if (preg_match('/^(#{1,3})\s+(.+)$/', $line, $m)) {
            $flushParagraph();
            $flushList();
            $level = strlen($m[1]);
            $html .= '<h' . $level . '>' . formatBlogInline($m[2]) . '</h' . $level . '>';
        } elseif (preg_match('/^#\S+(\s+#\S+)*$/', $line)) {
            $flushParagraph();
            $flushList();
            $tags = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
            $html .= '<div class="blog-article__tags">';
            foreach ($tags as $tag) {
                $html .= '<span class="blog-article__tag">' . htmlspecialchars($tag) . '</span>';
            }
            $html .= '</div>';
        } elseif (preg_match('/^\d+\.\s+(.+)$/', $line, $m)) {
            $flushParagraph();
            if ($listType !== 'ol') { $flushList(); $listType = 'ol'; }
            $listItems[] = $m[1];
        } elseif (preg_match('/^[-*]\s+(.+)$/', $line, $m)) {
            $flushParagraph();
            if ($listType !== 'ul') { $flushList(); $listType = 'ul'; }
            $listItems[] = $m[1];
        } elseif (!empty($listItems) && !preg_match('/[.!?]$/u', $listItems[count($listItems) - 1])) {
            // Wrapped continuation line belonging to the last list item —
            // but only if that item doesn't already read as a complete
            // sentence. Confirmed via a full 60-article live scan that
            // unconditional folding swallowed 134 genuine standalone
            // paragraphs (concluding sentences after a list) into the
            // previous <li> across 36 articles; every real list item in
            // this data is a single line, so this gate only fires for the
            // "numbered-title + wrapped description" pattern it exists for.
            $listItems[count($listItems) - 1] .= ' ' . $line;
        } else {
            $flushList();
            $paragraphLines[] = $line;
        }
    }
    $flushParagraph();
    $flushList();

    return $html;
}

/** Escapes text, then applies minimal inline markdown ("**bold**" -> <strong>). */
function formatBlogInline(string $text): string
{
    $escaped = htmlspecialchars($text);
    return preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped);
}
