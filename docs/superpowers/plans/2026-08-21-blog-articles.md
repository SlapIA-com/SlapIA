# Blog Articles Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add public-facing blog pages (`/blog` listing + `/blog/<slug>` article) that read from the existing, actively-updated "RSS SlapIA" Notion database — no new Notion schema, read-only.

**Architecture:** Two new root-level PHP pages (`blog.php`, `blog-article.php`), matching the existing convention of `contact.php`/`index.php` etc. A new `includes/notion-blog.php` owns all Notion reads plus a slug generator that must exactly match the JS `slugify()` already used in the n8n newsletter template. A new `.htaccess` rule maps `/blog/<slug>` to `blog-article.php`.

**Tech Stack:** PHP 8, Notion API (`includes/notion.php`'s `NotionAPI` class), file-based cache (same pattern as `includes/reviews.php`).

## Global Constraints

- The blog database already exists and is actively written to by a production n8n automation every 2 days: `328b2071-3b6f-8036-afa7-dcd8a9051eca` ("RSS SlapIA"). Do not create a new Notion database. Do not write to this database anywhere in this plan — every function this plan adds is read-only.
- Real live property names (verified against the live schema, use exactly): `Titre` (title), `Publication Date` (date), `Image` (files), `Extrait ` (rich_text — **note the trailing space in the property name**, it is real and must be reproduced exactly in every `$props['Extrait ']` access; do not "fix" it, a production automation depends on this exact name).
- Article body content lives in the Notion page's block children, not a property. The n8n pipeline only ever creates `paragraph` blocks, but **a single block's text routinely contains multiple logical lines** — a markdown heading (`# `/`## `/`### `, space required after the hashes) followed by its body paragraph, a numbered/bulleted list where each `N. `/`- ` line is followed by an indented wrapped-description line, or a trailing hashtag line (`#tag1 #tag2`, no space after `#`) — separated by real newline characters *inside the same block*. **Confirmed via live testing against a real published article**: naive one-regex-per-whole-block matching fails silently (headings render as plain paragraphs with a literal `##` prefix) because the block is not one logical line. The renderer must split each block's text on `\n` and process line-by-line (heading / hashtag-line / numbered-item / bulleted-item / plain text-that-continues-the-previous-list-item / plain paragraph line), grouping consecutive paragraph lines into one `<p>` and consecutive list items into one `<ul>`/`<ol>`.
- Some blocks store a **literal two-character `\n` escape sequence** (backslash + "n") instead of a real newline byte, inherited from an n8n JSON-repair quirk — confirmed on a real block. Normalize with `str_replace('\n', "\n", $text)` (matching the exact same known workaround already used by the old site's blog parser) before splitting on newlines.
- The LLM occasionally emits `**bold**` markdown despite being instructed not to (confirmed in real list-item titles) — render it as `<strong>` via a small inline formatter applied after `htmlspecialchars()`, not before.
- Real Notion block types (`heading_1/2/3`, `bulleted_list_item`, `numbered_list_item`, `quote`, `image`, `divider`) must also be handled, in case an article is ever hand-edited in Notion.
- `slugifyTitle()` must produce the same output as the n8n newsletter template's JS `slugify()` (diacritic stripping, lowercase, non-alphanumeric runs collapsed to a single hyphen, no leading/trailing hyphens). **Do not use `iconv('UTF-8', 'ASCII//TRANSLIT', ...)`** — confirmed via live testing on this server that it is not portable: on this Windows PHP build it turns `é` into `'e` (apostrophe+e) instead of a clean `e`, producing garbled slugs like `pr-ef-er-e` instead of `prefere`. Use a manual accent-to-ASCII character map (`strtr()` with an array of common accented Latin characters) instead — deterministic and portable.
- Every Notion-sourced string rendered into HTML output must pass through `htmlspecialchars()` before any lightweight markdown-style transformation is applied on top.
- File cache TTL is 1 hour, same pattern and cache-key style as `includes/reviews.php` (`sys_get_temp_dir()`, `md5()`-based filename, `filemtime()` freshness check).
- This plan is entirely read-only against Notion — unlike prior plans, automated task verification MAY call the real `listBlogArticles()`/`getBlogArticleBySlug()` functions against the live database, since no write path exists anywhere in this plan to accidentally trigger.
- No hardcoded user-visible strings — every visible label goes through `t('blog.xxx')` (new i18n namespace) or `t('nav.blog')`, added to `lang/fr.php`, `lang/en.php`, `lang/de.php`.

---

### Task 1: Create `includes/notion-blog.php`

**Files:**
- Create: `includes/notion-blog.php`

**Interfaces:**
- Consumes: `notion()` singleton, `NotionAPI::title`/`NotionAPI::richText` (existing, from `includes/notion.php`).
- Produces (used by Tasks 2 and 3):
  - `slugifyTitle(string $title): string`
  - `listBlogArticles(int $limit = 100): array` — each entry: `{id, title, excerpt, date, image, slug}`
  - `getBlogArticleBySlug(string $slug): ?array` — same shape plus `content` (rendered HTML string), or `null` if no match.

- [ ] **Step 1: Write the file**

```php
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
```

- [ ] **Step 2: Lint the file**

Run: `php -l includes/notion-blog.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Verify against the live database (read-only, safe per Global Constraints)**

Run:
```bash
php -r '
require "includes/config.php";
require "includes/notion-blog.php";
$articles = listBlogArticles(5);
echo "count: " . count($articles) . PHP_EOL;
foreach ($articles as $a) {
    echo "- " . $a["title"] . " -> slug: " . $a["slug"] . " (image: " . ($a["image"] ? "yes" : "no") . ")" . PHP_EOL;
}
if (!empty($articles)) {
    $full = getBlogArticleBySlug($articles[0]["slug"]);
    echo "content length for first article: " . strlen($full["content"] ?? "") . PHP_EOL;
}
'
```
Expected: 5 real article titles with non-empty slugs (lowercase, hyphenated, no accented characters), and a non-zero content length for the first article. Delete the cache file this creates afterward if you want a clean state for the next task's test (`rm` the file matching `slapia_blog_*.json` in the system temp dir) — not required, just keeps re-runs fresh.

- [ ] **Step 4: Commit**

```bash
git add includes/notion-blog.php
git commit -m "feat(blog): add read-only Notion access for blog articles"
```

---

### Task 2: Create `blog.php` (listing page)

**Files:**
- Create: `blog.php`

**Interfaces:**
- Consumes: `listBlogArticles()` (Task 1), `t()`, `includes/header.php`/`includes/footer.php` (existing).
- Produces: the public `/blog` (well, `blog.php`, linked from nav as `blog.php` matching the existing root-page convention) listing page.

- [ ] **Step 1: Write the file**

```php
<?php
require_once 'includes/i18n.php';
require_once 'includes/notion-blog.php';

$page_title = t('blog.meta_title');
$page_description = t('blog.meta_description');

$articles = listBlogArticles();

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/blog.css">

<section class="page-hero">
  <div class="page-hero-canvas" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span></div>
  <div class="container">
    <span class="eyebrow"><?php echo t('blog.eyebrow'); ?></span>
    <h1 class="page-hero__title"><?php echo t('blog.title_pre'); ?><mark><?php echo t('blog.title_mark'); ?></mark></h1>
    <p class="page-hero__lede"><?php echo t('blog.lede'); ?></p>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <?php if (empty($articles)): ?>
      <p class="blog-empty"><?php echo t('blog.empty'); ?></p>
    <?php else: ?>
      <div class="blog-grid">
        <?php foreach ($articles as $article): ?>
          <a href="/blog/<?php echo htmlspecialchars($article['slug']); ?>" class="blog-card reveal">
            <?php if ($article['image']): ?>
              <div class="blog-card__image">
                <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="" loading="lazy">
              </div>
            <?php endif; ?>
            <div class="blog-card__body">
              <span class="blog-card__date"><?php echo date('d M Y', strtotime($article['date'])); ?></span>
              <h2 class="blog-card__title"><?php echo htmlspecialchars($article['title']); ?></h2>
              <p class="blog-card__excerpt"><?php echo htmlspecialchars(mb_strimwidth($article['excerpt'], 0, 160, '...')); ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
```

- [ ] **Step 2: Lint the file**

Run: `php -l blog.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add blog.php
git commit -m "feat(blog): add blog listing page"
```

---

### Task 3: Create `blog-article.php` + `.htaccess` rewrite rule

**Files:**
- Create: `blog-article.php`
- Modify: `.htaccess`
- Modify: `includes/header.php` (see Step 1b — required for this task's own stated goal of real per-article social-share/canonical metadata; this file is shared sitewide, the fix below is additive and backward-compatible with every existing caller)

**Interfaces:**
- Consumes: `getBlogArticleBySlug()` (Task 1), `t()`.
- Produces: `/blog/<slug>` — a real, crawlable URL per article.

- [ ] **Step 1: Write `blog-article.php`**

```php
<?php
require_once 'includes/i18n.php';
require_once 'includes/notion-blog.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
    header('Location: /404');
    exit;
}

$article = getBlogArticleBySlug($slug);
if ($article === null) {
    header('Location: /404');
    exit;
}

$page_title = $article['title'];
$page_description = mb_strimwidth($article['excerpt'], 0, 160, '...');
$page_canonical = 'https://www.slapia.com/blog/' . $article['slug'];
if ($article['image']) {
    $page_image = $article['image'];
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/blog.css">

<section class="page-hero">
  <div class="container">
    <span class="eyebrow"><?php echo date('d M Y', strtotime($article['date'])); ?></span>
    <h1 class="page-hero__title"><?php echo htmlspecialchars($article['title']); ?></h1>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container blog-article-layout">
    <?php if ($article['image']): ?>
      <div class="blog-article__cover">
        <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="" loading="lazy">
      </div>
    <?php endif; ?>
    <div class="blog-article__content">
      <?php echo $article['content']; ?>
    </div>
    <a href="blog.php" class="btn btn--ghost blog-article__back">← <?php echo t('blog.back_to_list'); ?></a>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
```

- [ ] **Step 1b: Make `includes/header.php` support dynamic per-page metadata safely**

Confirmed via live testing: `blog-article.php` is the first page in this codebase to pass externally-sourced content (a Notion database populated by an n8n automation) into `$page_title`/`$page_description`/`$page_image`, and the first to need a real per-page canonical URL instead of the fallback derived from the filename. `includes/header.php`'s existing logic doesn't handle any of this safely — it needs three additive, backward-compatible fixes: escape `$title`/`$description` (currently unescaped — every prior caller only ever passed static, developer-controlled strings via `t()`, so this was never exploitable before now), accept an optional `$page_canonical` override, and detect an already-absolute `$page_image` URL instead of always prefixing `$site_url` onto it (every prior caller passes a site-relative path).

Find this exact block in `includes/header.php`:

```php
$site_url = 'https://www.slapia.com/';
$path = ($current === 'index.php') ? '' : $current;
$canonical = $site_url . $path . '?lang=' . $lang;
$title = isset($page_title) ? $page_title . ' — Slapia' : t('home.meta_title') . ' — Slapia';
$description = isset($page_description) ? $page_description : t('home.meta_description');
```

Replace it with:

```php
$site_url = 'https://www.slapia.com/';
$path = ($current === 'index.php') ? '' : $current;
$canonical = isset($page_canonical) ? $page_canonical : ($site_url . $path . '?lang=' . $lang);
$title = isset($page_title) ? htmlspecialchars($page_title) . ' — Slapia' : t('home.meta_title') . ' — Slapia';
$description = isset($page_description) ? htmlspecialchars($page_description) : t('home.meta_description');
$og_image = isset($page_image) ? $page_image : 'assets/img/brand/logo.png';
if (!preg_match('#^https?://#i', $og_image)) {
    $og_image = $site_url . $og_image;
}
```

Then find this exact line:

```php
<meta property="og:image" content="<?php echo $site_url . (isset($page_image) ? $page_image : 'assets/img/brand/logo.png'); ?>">
```

Replace it with:

```php
<meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
```

This is safe for every existing caller: pages that don't set `$page_canonical` keep the exact original canonical computation; pages that set `$page_image` to a relative path (e.g. `a-propos.php`) still get `$site_url` prefixed exactly as before; `$title`/`$description` only ever contained plain marketing copy with no `<`/`>`/`&` in existing callers, so `htmlspecialchars()` changes nothing visible there.

Run `php -l includes/header.php` — expect `No syntax errors detected`.

- [ ] **Step 2: Add the `.htaccess` rewrite rule**

Find this exact block in `.htaccess`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Clean URLs: /login -> pages/login.php (only if the file exists, so
```

Replace it with:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Blog articles: /blog/<slug> -> blog-article.php?slug=<slug>
    RewriteRule ^blog/([a-z0-9-]+)$ blog-article.php?slug=$1 [L,QSA]

    # Clean URLs: /login -> pages/login.php (only if the file exists, so
```

- [ ] **Step 3: Lint the PHP file**

Run: `php -l blog-article.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Guard check — the new route resolves correctly**

With the local server running (`http://slapia.local/`):

```bash
php -r '
require "includes/config.php";
require "includes/notion-blog.php";
$articles = listBlogArticles(1);
echo $articles[0]["slug"] ?? "NO_ARTICLES";
' > /tmp/slug.txt
SLUG=$(cat /tmp/slug.txt)
echo "Testing slug: $SLUG"
curl -s -o /dev/null -w "%{http_code}\n" "http://slapia.local/blog/$SLUG"
curl -s -o /dev/null -w "%{http_code}\n" "http://slapia.local/blog/this-slug-does-not-exist-at-all"
```
Expected: first `curl` returns `200` (a real article resolves), second returns `302` (redirect to `/404` for an unknown slug).

- [ ] **Step 5: Commit**

```bash
git add blog-article.php .htaccess includes/header.php
git commit -m "feat(blog): add blog article page, /blog/<slug> route, and safe dynamic metadata in header.php"
```

---

### Task 4: Create `assets/css/blog.css`

**Files:**
- Create: `assets/css/blog.css`

**Interfaces:**
- Produces: every class referenced by Tasks 2 and 3 — `.blog-empty`, `.blog-grid`, `.blog-card`, `.blog-card__image`, `.blog-card__body`, `.blog-card__date`, `.blog-card__title`, `.blog-card__excerpt`, `.blog-article-layout`, `.blog-article__cover`, `.blog-article__content` (plus its nested `h1-3`/`p`/`ul`/`ol`/`li`/`blockquote`/`img`/`hr`), `.blog-article__tags`, `.blog-article__tag`, `.blog-article__back`.

- [ ] **Step 1: Write the file**

```css
/* Blog — loaded only by blog.php and blog-article.php */

.blog-empty {
  text-align: center;
  padding: 60px 20px;
  color: var(--ink-fade);
  font-size: 1.05rem;
}

.blog-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}
@media (max-width: 900px) { .blog-grid { grid-template-columns: 1fr; } }

.blog-card {
  display: flex;
  flex-direction: column;
  border-radius: 16px;
  border: 1px solid var(--line);
  background: var(--white);
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  transition: transform 0.2s ease, border-color 0.2s ease;
}
.blog-card:hover { transform: translateY(-2px); border-color: var(--line-strong); }

.blog-card__image { width: 100%; aspect-ratio: 16 / 9; overflow: hidden; background: var(--paper); }
.blog-card__image img { width: 100%; height: 100%; object-fit: cover; }

.blog-card__body { padding: 20px 22px 24px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
.blog-card__date {
  font-family: var(--font-mono);
  font-size: 0.72rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--ink-fade);
}
.blog-card__title { font-family: var(--font-display); font-size: 1.15rem; line-height: 1.35; margin: 0; color: var(--ink); }
.blog-card__excerpt { font-size: 0.9rem; line-height: 1.6; color: var(--ink-soft); margin: 0; flex: 1; }

.blog-article-layout { max-width: 760px; margin: 0 auto; }
.blog-article__cover { border-radius: 16px; overflow: hidden; margin-bottom: 32px; }
.blog-article__cover img { width: 100%; height: auto; display: block; }

.blog-article__content { font-size: 1.05rem; line-height: 1.8; color: var(--ink); }
.blog-article__content h1,
.blog-article__content h2,
.blog-article__content h3 { font-family: var(--font-display); color: var(--ink); margin: 36px 0 14px; }
.blog-article__content h1 { font-size: 1.7rem; }
.blog-article__content h2 { font-size: 1.4rem; }
.blog-article__content h3 { font-size: 1.15rem; }
.blog-article__content p { margin: 0 0 18px; }
.blog-article__content ul, .blog-article__content ol { margin: 0 0 18px; padding-left: 24px; }
.blog-article__content li { margin-bottom: 8px; }
.blog-article__content blockquote {
  margin: 24px 0;
  padding: 16px 20px;
  border-left: 3px solid var(--signal);
  background: var(--paper);
  border-radius: 0 12px 12px 0;
  font-style: italic;
}
.blog-article__content img { width: 100%; border-radius: 12px; margin: 24px 0; }
.blog-article__content hr { border: 0; border-top: 1px solid var(--line); margin: 32px 0; }

.blog-article__tags { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 18px; }
.blog-article__tag {
  font-family: var(--font-mono);
  font-size: 0.78rem;
  color: var(--signal-deep);
  background: color-mix(in srgb, var(--signal) 12%, transparent);
  padding: 4px 12px;
  border-radius: 999px;
}

.blog-article__back { margin-top: 40px; }
```

- [ ] **Step 2: Commit**

```bash
git add assets/css/blog.css
git commit -m "feat(blog): add blog stylesheet"
```

---

### Task 5: Nav link + translations (fr/en/de)

**Files:**
- Modify: `includes/header.php`
- Modify: `lang/fr.php`
- Modify: `lang/en.php`
- Modify: `lang/de.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: a "Blog" entry in the site navigation (desktop + mobile, both driven by the same `$nav_items` array), and every `blog.*`/`nav.blog` key referenced by Tasks 2 and 3.

- [ ] **Step 1: Add the nav entry in `includes/header.php`**

Find this exact block:

```php
$nav_items = [
  'index.php'        => t('nav.home'),
  'formations.php'   => t('nav.courses'),
  'services-pc.php'  => t('nav.services'),
  'tarifs.php'       => t('nav.pricing'),
  'a-propos.php'     => t('nav.about'),
  'contact.php'      => t('nav.contact'),
];
```

Replace it with:

```php
$nav_items = [
  'index.php'        => t('nav.home'),
  'formations.php'   => t('nav.courses'),
  'services-pc.php'  => t('nav.services'),
  'tarifs.php'       => t('nav.pricing'),
  'blog.php'         => t('nav.blog'),
  'a-propos.php'     => t('nav.about'),
  'contact.php'      => t('nav.contact'),
];
```

- [ ] **Step 2: Lint the file**

Run: `php -l includes/header.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Add the new keys to `lang/fr.php`**

In `lang/fr.php`'s `'nav' => [ ... ]` array, add before the closing `],`:

```php
    'blog' => 'Blog',
```

Then add a new top-level `'blog' => [ ... ]` array. Find this exact block (the end of the `'contact'` array, right before `'auth' => [`):

```php
    'info_delay_label' => 'Délai de réponse',
    'info_delay_text' => 'Sous 48 heures ouvrées, avec une proposition concrète.',
  ],

  'auth' => [
```

Replace it with:

```php
    'info_delay_label' => 'Délai de réponse',
    'info_delay_text' => 'Sous 48 heures ouvrées, avec une proposition concrète.',
  ],

  'blog' => [
    'meta_title' => 'Blog',
    'meta_description' => "Actualités et analyses sur l'intelligence artificielle, écrites pour les dirigeants et équipes de PME.",
    'eyebrow' => 'Blog',
    'title_pre' => 'Décryptage ',
    'title_mark' => 'IA',
    'lede' => "Des articles réguliers pour comprendre l'actualité de l'intelligence artificielle et ce qu'elle change concrètement pour votre entreprise.",
    'empty' => 'Aucun article pour le moment. Revenez bientôt.',
    'back_to_list' => 'Retour au blog',
  ],

  'auth' => [
```

- [ ] **Step 4: Add the equivalent keys to `lang/en.php`**

In `lang/en.php`'s `'nav' => [ ... ]` array, add before the closing `],`:

```php
    'blog' => 'Blog',
```

Find this exact block:

```php
    'info_delay_label' => 'Response time',
    'info_delay_text' => 'Within 48 business hours, with a concrete proposal.',
  ],

  'auth' => [
```

Replace it with:

```php
    'info_delay_label' => 'Response time',
    'info_delay_text' => 'Within 48 business hours, with a concrete proposal.',
  ],

  'blog' => [
    'meta_title' => 'Blog',
    'meta_description' => 'AI news and analysis, written for SME leaders and teams.',
    'eyebrow' => 'Blog',
    'title_pre' => 'AI ',
    'title_mark' => 'insights',
    'lede' => 'Regular articles to understand AI news and what it actually changes for your business.',
    'empty' => 'No articles yet. Check back soon.',
    'back_to_list' => 'Back to blog',
  ],

  'auth' => [
```

Note: if `lang/en.php`'s `info_delay_label`/`info_delay_text` values differ slightly from what's shown above, match on the surrounding structure (`],` closing the `contact` array immediately followed by `'auth' => [`) instead of the exact wording — read the file first to confirm the precise text before replacing.

- [ ] **Step 5: Add the equivalent keys to `lang/de.php`**

In `lang/de.php`'s `'nav' => [ ... ]` array, add before the closing `],`:

```php
    'blog' => 'Blog',
```

Find the equivalent `],` closing the `contact` array immediately followed by `'auth' => [` (read the file first to get the exact preceding two lines, same as Step 4's note) and insert before `'auth' => [`:

```php
  'blog' => [
    'meta_title' => 'Blog',
    'meta_description' => 'KI-Nachrichten und Analysen für Führungskräfte und Teams von KMU.',
    'eyebrow' => 'Blog',
    'title_pre' => 'KI-',
    'title_mark' => 'Einblicke',
    'lede' => 'Regelmäßige Artikel, um KI-Neuigkeiten zu verstehen und was sie konkret für Ihr Unternehmen bedeuten.',
    'empty' => 'Noch keine Artikel. Schauen Sie bald wieder vorbei.',
    'back_to_list' => 'Zurück zum Blog',
  ],

  'auth' => [
```

- [ ] **Step 6: Lint all three files**

Run: `php -l lang/fr.php && php -l lang/en.php && php -l lang/de.php`
Expected: `No syntax errors detected` × 3.

- [ ] **Step 7: Verify key parity across languages**

Run:
```bash
php -r '
$fr = array_keys((require "lang/fr.php")["blog"]);
$en = array_keys((require "lang/en.php")["blog"]);
$de = array_keys((require "lang/de.php")["blog"]);
sort($fr); sort($en); sort($de);
echo ($fr === $en && $fr === $de) ? "OK: identical key sets\n" : "MISMATCH\n";
foreach (["fr","en","de"] as $l) {
    $navBlog = (require "lang/$l.php")["nav"]["blog"] ?? null;
    echo "$l nav.blog: " . ($navBlog ?? "MISSING") . PHP_EOL;
}
'
```
Expected: `OK: identical key sets` and `nav.blog: Blog` for all three.

- [ ] **Step 8: Commit**

```bash
git add includes/header.php lang/fr.php lang/en.php lang/de.php
git commit -m "feat(blog): add nav link and translations"
```

---

## Final Review Fix Wave

The final whole-branch review found one Critical and two more Important findings beyond Task 1's
already-documented fixes above, confirmed via live rendering of the actual page (not just status
codes):

**Critical: every relative link/asset breaks at `/blog/<slug>`.** `/blog/<slug>` is the first URL
in this codebase with path depth > 0. `includes/header.php` and `includes/footer.php` emit
site-relative hrefs/srcs (`href="index.php"`, `src="assets/css/style.css"`, etc.), which the
browser resolves against the CURRENT page's directory — `/blog/` for an article, not `/`. Verified
live: the article page's own stylesheet, every nav link, the logo, and the "back to blog" link
(itself `blog-article.php`'s own `href="blog.php"`, also relative — the *previous* fix round only
retested `blog.php` at the site root, not as a link resolved from `/blog/<slug>`) all 404.

Fix: make every internal href/src in `includes/header.php` and `includes/footer.php`
root-absolute (a literal leading `/`). This is 100% backward-compatible: every existing page lives
at the true root (`/index.php`, `/contact.php`, …), where a root-absolute path and a site-relative
path resolve identically — only the new depth-1 route benefits. **Do not use `<base href="/">`** —
it would silently break the `href="?lang=xx"` language-switcher links, which rely on resolving
against the current page.

In `includes/header.php`, apply root-absolute (`/`) prefixes to: the logo `<a>` (×2, desktop +
mobile), the logo `<img>` `src` (×2), `<link rel="stylesheet" href="assets/css/style.css">`,
`<link rel="icon" ... href="assets/img/brand/logo.png">`, every `$nav_items`-driven `<a href="...">`
(×2 loops — desktop nav + mobile nav — prefix at render time, e.g.
`href="/<?php echo $href; ?>"`; do **not** change the `$nav_items` array keys themselves, since
`$current === $href` comparison logic depends on them staying bare filenames), the `contact.php`
CTA `<a>` (×2), and the user-menu avatar `<img src="api/notion-avatar.php?...">`.

In `includes/footer.php`, apply the same to: the logo `<a>`/`<img>`, every `formations.php`
/`services-pc.php`/`a-propos.php`/`tarifs.php`/`contact.php`/`mentions-legales.php`
/`confidentialite.php`/`cgv.php` link (preserve any `#anchor` suffix), and
`<script src="assets/js/main.js">`. Leave `mailto:`/`tel:` links untouched (scheme-based, unaffected).

In `blog-article.php`, fix the back-link to `href="/blog.php"` (root-absolute — the earlier fix to
`blog.php` alone only worked when tested from the site root) and the page's own stylesheet link to
`href="/assets/css/blog.css"`.

**Important: `hreflang`/`x-default` alternates still 404 on every article.** `header.php` derives
`$path` from `basename(PHP_SELF)` (always `blog-article.php`), independently of the
`$page_canonical` override added in the prior fix round — so every article's hreflang alternates
point at the same broken generic URL. Fix: also accept an optional `$page_path` override (mirroring
`$page_canonical`), and set it in `blog-article.php`.

In `includes/header.php`, find:
```php
$path = ($current === 'index.php') ? '' : $current;
```
Replace with:
```php
$path = isset($page_path) ? $page_path : (($current === 'index.php') ? '' : $current);
```

In `blog-article.php`, find:
```php
$page_canonical = 'https://www.slapia.com/blog/' . $article['slug'];
```
Replace with:
```php
$page_canonical = 'https://www.slapia.com/blog/' . $article['slug'];
$page_path = 'blog/' . $article['slug'];
```

**Verification (live, safe read-only + status-code checks):** re-fetch a real article page and
`grep` its `<head>` for every asset/nav href rendered — confirm none read a bare relative path
(i.e. every `href=`/`src=` referencing an internal resource starts with `/` or `https://`). Then
`curl` each of the previously-404ing sub-resource URLs directly (e.g.
`http://slapia.local/blog/assets/css/blog.css` should now correctly 404 as a nonexistent path,
while `http://slapia.local/assets/css/blog.css` returns 200 — the fix means the HTML no longer
*references* the broken relative path at all, not that the broken path itself starts working).
Confirm hreflang/x-default in a real article's `<head>` now show `/blog/<slug>?lang=xx`, not
`blog-article.php?lang=xx`. Re-run the Task 1 live verification (60-article scan for stray `**`/`##`,
plus the specific `openai-freine-astra`/`mirendil-et-google-cloud`/`cursor-chez-spacex` articles)
to confirm no regression from any of these changes.

## Final Whole-Branch Review

After all 5 tasks are complete, dispatch a final whole-branch code review covering the full diff, checking against every item in Global Constraints above — in particular: the exact live property names (`Titre`, `Publication Date`, `Image`, `Extrait ` with its trailing space) are used consistently and never "corrected," no write path was introduced anywhere, the paragraph-vs-heading-vs-hashtag detection logic is sound (space-after-hash requirement correctly distinguishes headings from hashtag lines), `htmlspecialchars()` is applied before any markup is added on top of Notion-sourced text, the `.htaccess` rewrite rule doesn't shadow or break any existing route, and full i18n key parity (including `nav.blog`) across fr/en/de. Also confirm the reviewer runs Task 1's live read-only verification script once more against the current code to sanity-check real output end-to-end.
