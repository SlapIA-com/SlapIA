<?php

namespace App\Services\Notion;

use Illuminate\Support\Facades\Cache;

/**
 * Port fidèle de includes/notion-blog.php. Le blog reste volontairement sur
 * Notion (base "RSS SlapIA", ID ci-dessous) — c'est une décision actée
 * (voir docs/superpowers/plans/2026-08-21-blog-articles.md dans l'ancien
 * repo) : une automatisation n8n en production y écrit un nouvel article
 * tous les 2 jours. Ce service est STRICTEMENT en lecture, ne jamais y
 * ajouter d'écriture.
 */
class NotionBlogService
{
    private const BLOG_DATABASE_ID = '328b2071-3b6f-8036-afa7-dcd8a9051eca';

    public function __construct(private NotionClient $notion)
    {
    }

    /** Doit produire le même slug que le slugify() JS du template n8n. */
    public static function slugify(string $title): string
    {
        $accentMap = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
            'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ñ' => 'n', 'ý' => 'y', 'ÿ' => 'y',
        ];
        $slug = strtr(mb_strtolower($title), $accentMap);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        return trim($slug, '-');
    }

    /**
     * @return array<int, array{id:string,title:string,excerpt:string,date:string,image:?string,slug:string}>
     */
    public function listArticles(int $limit = 100): array
    {
        return Cache::remember('slapia_blog_articles', 3600, function () use ($limit) {
            $result = $this->notion->queryDatabaseAll(self::BLOG_DATABASE_ID, [
                'sorts' => [['property' => 'Publication Date', 'direction' => 'descending']],
            ]);
            if (!empty($result['error'])) {
                return [];
            }

            $articles = [];
            foreach (array_slice($result['results'] ?? [], 0, $limit) as $page) {
                $props = $page['properties'] ?? [];
                $title = NotionClient::title($props['Titre'] ?? []);
                if ($title === '') {
                    continue;
                }

                $files = $props['Image']['files'] ?? [];
                $image = null;
                if (!empty($files)) {
                    $file = $files[0];
                    $image = $file['file']['url'] ?? $file['external']['url'] ?? null;
                }

                $articles[] = [
                    'id' => $page['id'],
                    'title' => $title,
                    'excerpt' => NotionClient::richText($props['Extrait '] ?? []),
                    'date' => $props['Publication Date']['date']['start'] ?? $page['created_time'],
                    'image' => $image,
                    'slug' => self::slugify($title),
                ];
            }

            return $articles;
        });
    }

    /** @return array{id:string,title:string,excerpt:string,date:string,image:?string,slug:string,content:string}|null */
    public function findBySlug(string $slug): ?array
    {
        foreach ($this->listArticles() as $article) {
            if ($article['slug'] === $slug) {
                $article['content'] = $this->renderContent($article['id']);
                return $article;
            }
        }
        return null;
    }

    /**
     * Rend les blocs enfants d'une page en HTML. Gère les particularités
     * connues du pipeline n8n (voir docs/superpowers/plans/2026-08-21-*.md) :
     * plusieurs "lignes logiques" par bloc paragraph séparées par \n, séquence
     * d'échappement littérale \n à normaliser, paragraphes consécutifs à
     * rejoindre avant parsing, **gras** markdown résiduel.
     */
    private function renderContent(string $pageId): string
    {
        $result = $this->notion->request('GET', "/blocks/{$pageId}/children?page_size=100");
        if (!empty($result['error'])) {
            return '';
        }

        $html = '';
        $paragraphParts = [];

        $flush = function () use (&$html, &$paragraphParts) {
            if (empty($paragraphParts)) {
                return;
            }
            $joined = implode("\n", $paragraphParts);
            $html .= $this->renderTextBlock($joined);
            $paragraphParts = [];
        };

        foreach ($result['results'] ?? [] as $block) {
            $type = $block['type'] ?? '';
            if ($type === 'paragraph') {
                $paragraphParts[] = NotionClient::richText($block['paragraph'] ?? []);
                continue;
            }
            $flush();

            $html .= match ($type) {
                'heading_1' => '<h2>'.e(NotionClient::richText($block['heading_1'] ?? [])).'</h2>',
                'heading_2' => '<h3>'.e(NotionClient::richText($block['heading_2'] ?? [])).'</h3>',
                'heading_3' => '<h4>'.e(NotionClient::richText($block['heading_3'] ?? [])).'</h4>',
                'bulleted_list_item' => '<ul><li>'.e(NotionClient::richText($block['bulleted_list_item'] ?? [])).'</li></ul>',
                'numbered_list_item' => '<ol><li>'.e(NotionClient::richText($block['numbered_list_item'] ?? [])).'</li></ol>',
                'quote' => '<blockquote>'.e(NotionClient::richText($block['quote'] ?? [])).'</blockquote>',
                'divider' => '<hr>',
                'image' => $this->renderImageBlock($block),
                default => '',
            };
        }
        $flush();

        return $html;
    }

    private function renderImageBlock(array $block): string
    {
        $img = $block['image'] ?? [];
        $url = $img['file']['url'] ?? $img['external']['url'] ?? null;
        return $url ? '<img src="'.e($url).'" alt="" loading="lazy">' : '';
    }

    /** Normalise \n littéral, découpe en lignes, groupe titres/listes/paragraphes, applique **gras**. */
    private function renderTextBlock(string $text): string
    {
        $text = str_replace('\\n', "\n", $text);
        $lines = explode("\n", $text);

        $html = '';
        $listBuffer = [];
        $listType = null;
        $paraBuffer = [];

        $flushList = function () use (&$html, &$listBuffer, &$listType) {
            if (empty($listBuffer)) {
                return;
            }
            $tag = $listType === 'ol' ? 'ol' : 'ul';
            $html .= "<{$tag}>".implode('', array_map(fn ($i) => '<li>'.$this->inlineFormat($i).'</li>', $listBuffer))."</{$tag}>";
            $listBuffer = [];
            $listType = null;
        };
        $flushPara = function () use (&$html, &$paraBuffer) {
            if (empty($paraBuffer)) {
                return;
            }
            $html .= '<p>'.$this->inlineFormat(implode(' ', $paraBuffer)).'</p>';
            $paraBuffer = [];
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            if (preg_match('/^#\s+(.*)$/', $trimmed, $m)) {
                $flushList(); $flushPara();
                $html .= '<h2>'.$this->inlineFormat($m[1]).'</h2>';
            } elseif (preg_match('/^##\s+(.*)$/', $trimmed, $m)) {
                $flushList(); $flushPara();
                $html .= '<h3>'.$this->inlineFormat($m[1]).'</h3>';
            } elseif (preg_match('/^###\s+(.*)$/', $trimmed, $m)) {
                $flushList(); $flushPara();
                $html .= '<h4>'.$this->inlineFormat($m[1]).'</h4>';
            } elseif (preg_match('/^#\w[\w#\s]*$/', $trimmed) && str_starts_with($trimmed, '#') && !str_contains($trimmed, ' ')) {
                // trailing hashtag line, e.g. "#IA #Automatisation" — skip silently (not real content)
                continue;
            } elseif (preg_match('/^\d+\.\s+(.*)$/', $trimmed, $m)) {
                $flushPara();
                $listType = 'ol';
                $listBuffer[] = $m[1];
            } elseif (preg_match('/^-\s+(.*)$/', $trimmed, $m)) {
                $flushPara();
                $listType = 'ul';
                $listBuffer[] = $m[1];
            } elseif (!empty($listBuffer) && !preg_match('/^(\d+\.|-)\s/', $trimmed)) {
                // wrapped continuation line of the previous list item
                $listBuffer[count($listBuffer) - 1] .= ' '.$trimmed;
            } else {
                $flushList();
                $paraBuffer[] = $trimmed;
            }
        }
        $flushList();
        $flushPara();

        return $html;
    }

    private function inlineFormat(string $text): string
    {
        $escaped = e($text);
        return preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped) ?? $escaped;
    }
}
