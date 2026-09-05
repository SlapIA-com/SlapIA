<?php

namespace App\Services\Notion;

use Illuminate\Support\Facades\Http;

/**
 * Client Notion minimal — port de includes/notion.php, seulement les
 * méthodes utilisées par le blog (lecture seule). NOTION_API_KEY dans .env.
 */
class NotionClient
{
    private string $apiKey;
    private string $version = '2022-06-28';

    public function __construct()
    {
        $this->apiKey = (string) config('services.notion.api_key');
    }

    public function request(string $method, string $path, array $payload = []): array
    {
        $response = Http::withToken($this->apiKey)
            ->withHeaders(['Notion-Version' => $this->version])
            ->timeout(15)
            ->{strtolower($method)}('https://api.notion.com/v1'.$path, $payload);

        if ($response->failed()) {
            return ['error' => $response->json('message') ?? 'Notion API error'];
        }

        return $response->json() ?? [];
    }

    public function queryDatabaseAll(string $databaseId, array $params = []): array
    {
        $results = [];
        $cursor = null;

        do {
            $body = $params;
            if ($cursor) {
                $body['start_cursor'] = $cursor;
            }
            $page = $this->request('POST', "/databases/{$databaseId}/query", $body);
            if (!empty($page['error'])) {
                return $page;
            }
            $results = array_merge($results, $page['results'] ?? []);
            $cursor = $page['has_more'] ?? false ? ($page['next_cursor'] ?? null) : null;
        } while ($cursor);

        return ['results' => $results];
    }

    public static function title(array $prop): string
    {
        $items = $prop['title'] ?? [];
        return trim(implode('', array_map(fn ($i) => $i['plain_text'] ?? '', $items)));
    }

    public static function richText(array $prop): string
    {
        $items = $prop['rich_text'] ?? [];
        return trim(implode('', array_map(fn ($i) => $i['plain_text'] ?? '', $items)));
    }
}
