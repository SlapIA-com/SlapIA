<?php
/**
 * Notion API Helper
 *
 * Centralise tous les appels cURL vers l'API Notion avec cache APCu (si dispo)
 * ou fallback fichier. TTL par défaut : 5 min pour les listes, 1h pour les avatars.
 */

if (!defined('NOTION_API_BASE')) {
    define('NOTION_API_BASE', 'https://api.notion.com/v1');
    define('NOTION_VERSION', '2022-06-28');
}

class NotionAPI
{
    private string $apiKey;
    private int    $timeout;
    private bool   $apcuAvailable;

    public function __construct(string $apiKey, int $timeout = 10)
    {
        $this->apiKey        = $apiKey;
        $this->timeout       = $timeout;
        $this->apcuAvailable = function_exists('apcu_fetch') && ini_get('apc.enabled');
    }

    // ─────────────────────────────────────────────────────────────
    //  Public API methods
    // ─────────────────────────────────────────────────────────────

    /** GET /pages/{id} */
    public function getPage(string $pageId): array
    {
        return $this->request('GET', '/pages/' . $pageId);
    }

    /** PATCH /pages/{id} */
    public function updatePage(string $pageId, array $body): array
    {
        return $this->request('PATCH', '/pages/' . $pageId, $body);
    }

    /** POST /databases/{id}/query */
    public function queryDatabase(string $dbId, array $body = [], int $cacheTtl = 0): array
    {
        $cacheKey = 'notion_db_' . md5($dbId . json_encode($body));
        if ($cacheTtl > 0) {
            $cached = $this->cacheGet($cacheKey);
            if ($cached !== false) return $cached;
        }

        $result = $this->request('POST', '/databases/' . $dbId . '/query', $body);

        if ($cacheTtl > 0 && isset($result['results'])) {
            $this->cacheSet($cacheKey, $result, $cacheTtl);
        }

        return $result;
    }

    /** POST /pages — create a new page */
    public function createPage(array $body): array
    {
        return $this->request('POST', '/pages', $body);
    }

    /** DELETE /blocks/{id} (archive a page) */
    public function archivePage(string $pageId): array
    {
        return $this->updatePage($pageId, ['archived' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    //  Property helpers
    // ─────────────────────────────────────────────────────────────

    /** Extract plain text from a rich_text or title property */
    public static function richText(array $prop, string $key = 'rich_text'): string
    {
        $items = $prop[$key] ?? [];
        return implode('', array_map(fn($t) => $t['plain_text'] ?? '', $items));
    }

    public static function title(array $prop): string
    {
        return self::richText($prop, 'title');
    }

    public static function select(array $prop): string
    {
        return $prop['select']['name'] ?? '';
    }

    public static function multiSelect(array $prop): array
    {
        return array_map(fn($s) => $s['name'], $prop['multi_select'] ?? []);
    }

    public static function email(array $prop): string
    {
        return $prop['email'] ?? '';
    }

    public static function phone(array $prop): string
    {
        return $prop['phone_number'] ?? '';
    }

    public static function url(array $prop): string
    {
        return $prop['url'] ?? '';
    }

    public static function number(array $prop): ?float
    {
        return $prop['number'] ?? null;
    }

    public static function checkbox(array $prop): bool
    {
        return (bool)($prop['checkbox'] ?? false);
    }

    public static function date(array $prop): string
    {
        return $prop['date']['start'] ?? '';
    }

    /** First file URL from a files property (returns empty string if none) */
    public static function fileUrl(array $prop): string
    {
        $files = $prop['files'] ?? [];
        if (empty($files)) return '';
        $f = $files[0];
        return $f['file']['url'] ?? $f['external']['url'] ?? '';
    }

    // ─────────────────────────────────────────────────────────────
    //  HTTP core
    // ─────────────────────────────────────────────────────────────

    public function request(string $method, string $path, ?array $body = null): array
    {
        $url = NOTION_API_BASE . $path;
        $ch  = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Notion-Version: ' . NOTION_VERSION,
            'Content-Type: application/json',
        ];

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $this->timeout,
        ];

        if ($method === 'POST') {
            $opts[CURLOPT_POST]       = true;
            $opts[CURLOPT_POSTFIELDS] = $body !== null ? json_encode($body) : '{}';
        } elseif ($method === 'PATCH') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'PATCH';
            $opts[CURLOPT_POSTFIELDS]    = $body !== null ? json_encode($body) : '{}';
        } elseif ($method === 'DELETE') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }

        curl_setopt_array($ch, $opts);
        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['error' => $curlErr, 'http_code' => 0];
        }

        $decoded = json_decode($raw, true) ?? [];
        $decoded['http_code'] = $httpCode;
        return $decoded;
    }

    // ─────────────────────────────────────────────────────────────
    //  Cache (APCu preferred, file fallback)
    // ─────────────────────────────────────────────────────────────

    private function cacheGet(string $key): mixed
    {
        if ($this->apcuAvailable) {
            $success = false;
            $val     = apcu_fetch($key, $success);
            return $success ? $val : false;
        }

        $file = $this->cacheFile($key);
        if (!file_exists($file)) return false;

        $data = @unserialize(file_get_contents($file));
        if (!$data || !isset($data['expires'], $data['payload'])) return false;
        if (time() > $data['expires']) {
            @unlink($file);
            return false;
        }

        return $data['payload'];
    }

    private function cacheSet(string $key, mixed $value, int $ttl): void
    {
        if ($this->apcuAvailable) {
            apcu_store($key, $value, $ttl);
            return;
        }

        $file = $this->cacheFile($key);
        @file_put_contents($file, serialize(['expires' => time() + $ttl, 'payload' => $value]), LOCK_EX);
    }

    public function cacheClear(string $key): void
    {
        if ($this->apcuAvailable) {
            apcu_delete($key);
            return;
        }
        $file = $this->cacheFile($key);
        if (file_exists($file)) @unlink($file);
    }

    private function cacheFile(string $key): string
    {
        return sys_get_temp_dir() . '/notion_cache_' . md5($key) . '.cache';
    }
}

/**
 * Returns a singleton NotionAPI instance using the app config.
 */
function notion(): NotionAPI
{
    static $instance = null;
    if ($instance === null) {
        if (!function_exists('config')) {
            include_once __DIR__ . '/config.php';
        }
        $instance = new NotionAPI(config('NOTION_API_KEY'));
    }
    return $instance;
}
