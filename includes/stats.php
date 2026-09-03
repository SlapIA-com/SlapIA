<?php
/**
 * Statistiques Slapia (entreprises / particuliers / satisfaction) sourcées depuis
 * la base Notion "Satisfaction" (colonnes : Prenom NOM, Status, Satisfaction...).
 *
 * Repli silencieux sur les valeurs fournies si Notion n'est pas configuré ou injoignable,
 * pour que le site ne casse jamais si la clé API est absente ou expirée.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notion.php';

/**
 * Tente d'extraire une note de satisfaction sur une échelle de 0 à 5 depuis une propriété Notion,
 * quel que soit son type (number, select, rich_text...) ou son format ("5", "5/5", "★★★★★", "80%").
 */
function parseSatisfactionTo5Scale(array $prop): ?float
{
    $type = $prop['type'] ?? '';

    if ($type === 'number') {
        $num = $prop['number'] ?? null;
        if (!is_numeric($num)) return null;
        return $num > 5 ? max(0, min(5, ($num / 100) * 5)) : max(0, min(5, (float)$num));
    }

    $text = '';
    if ($type === 'select') {
        $text = $prop['select']['name'] ?? '';
    } elseif ($type === 'rich_text' || $type === 'title') {
        $text = NotionAPI::richText($prop, $type);
    }
    $text = trim($text);
    if ($text === '') return null;

    if (preg_match('/([★⭐]+)/u', $text, $m)) {
        return max(0, min(5, mb_strlen($m[1], 'UTF-8')));
    }
    if (preg_match('/(\d+(?:\.\d+)?)\s*\/\s*(\d+)/', $text, $m)) {
        $den = (float)$m[2];
        return $den > 0 ? max(0, min(5, ((float)$m[1] / $den) * 5)) : null;
    }
    if (preg_match('/(\d{1,3})\s*%/', $text, $m)) {
        return max(0, min(5, ((float)$m[1] / 100) * 5));
    }
    if (preg_match('/(\d+(?:\.\d+)?)/', $text, $m)) {
        $num = (float)$m[1];
        return $num > 5 ? max(0, min(5, ($num / 100) * 5)) : max(0, min(5, $num));
    }

    return null;
}

/**
 * @return array{entreprises:int|null, particuliers:int|null, satisfaction:float|null, is_live:bool}
 */
function getSlapiaStats(bool $forceRefresh = false): array
{
    $fallback = ['entreprises' => null, 'particuliers' => null, 'satisfaction' => null, 'is_live' => false];

    $apiKey = config('NOTION_API_KEY', '');
    $dbId   = config('NOTION_SATISFACTION_DATABASE_ID', '');
    if ($apiKey === '' || $dbId === '') {
        return $fallback;
    }

    $cacheFile = sys_get_temp_dir() . '/slapia_stats_' . md5($dbId) . '.json';
    $cacheTtl  = 3600; // 1 heure

    if (!$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) return $cached;
    }

    $response = notion()->queryDatabaseAll($dbId);
    if (!empty($response['error'])) {
        return $fallback;
    }

    $entreprises  = 0;
    $particuliers = 0;
    $satisfactions = [];

    foreach ($response['results'] as $page) {
        $props = $page['properties'] ?? [];

        if (isset($props['Status'])) {
            $status = NotionAPI::select($props['Status']);
            if ($status === 'Entreprise') $entreprises++;
            elseif ($status === 'Particulier') $particuliers++;
        }

        if (isset($props['Satisfaction'])) {
            $note = parseSatisfactionTo5Scale($props['Satisfaction']);
            if ($note !== null && $note > 0) $satisfactions[] = $note;
        }
    }

    $satisfaction = !empty($satisfactions)
        ? round(array_sum($satisfactions) / count($satisfactions), 1)
        : null;

    $result = [
        'entreprises'         => $entreprises,
        'particuliers'        => $particuliers,
        'satisfaction'        => $satisfaction,
        'satisfaction_count'  => count($satisfactions),
        'is_live'             => true,
    ];

    @file_put_contents($cacheFile, json_encode($result), LOCK_EX);

    return $result;
}

/**
 * Rend un <div class="stat__num"> qui s'anime en comptage (0 → valeur) au scroll,
 * avec repli statique (sans animation) si la valeur n'est pas numérique.
 */
function statNumHtml(?float $value, int $decimals, string $suffix, string $fallbackDisplay, string $decimalSep): string
{
    if ($value !== null) {
        $display = number_format($value, $decimals, $decimalSep, '') . $suffix;
        $countValue = $value;
        $countDecimals = $decimals;
        $countSuffix = $suffix;
    } else {
        $display = $fallbackDisplay;
        $countValue = null;
        $countDecimals = 0;
        $countSuffix = '';
        if (preg_match('/^([\d.,]+)(.*)$/u', trim($fallbackDisplay), $m)) {
            $numStr = str_replace(',', '.', $m[1]);
            if (is_numeric($numStr)) {
                $countValue = (float)$numStr;
                $countSuffix = $m[2];
                $countDecimals = (strpos($numStr, '.') !== false) ? strlen(substr(strrchr($numStr, '.'), 1)) : 0;
            }
        }
    }

    if ($countValue === null) {
        return '<div class="stat__num">' . htmlspecialchars($display) . '</div>';
    }

    return '<div class="stat__num js-count"'
        . ' data-count-value="' . htmlspecialchars((string)$countValue) . '"'
        . ' data-count-decimals="' . (int)$countDecimals . '"'
        . ' data-count-suffix="' . htmlspecialchars($countSuffix) . '"'
        . ' data-count-sep="' . htmlspecialchars($decimalSep) . '"'
        . ' data-count-final="' . htmlspecialchars($display) . '"'
        . '>' . htmlspecialchars($display) . '</div>';
}
