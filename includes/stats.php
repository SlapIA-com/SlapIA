<?php
/**
 * Statistiques Slapia (entreprises / particuliers / satisfaction) sourcées
 * depuis MySQL (tables clients + avis_clients). Remplace la version Notion
 * de ce fichier — toujours mise en cache 1h pour éviter de recalculer à
 * chaque chargement de page.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * @return array{entreprises:int|null, particuliers:int|null, satisfaction:float|null, is_live:bool}
 */
function getSlapiaStats(bool $forceRefresh = false): array
{
    $fallback = ['entreprises' => null, 'particuliers' => null, 'satisfaction' => null, 'is_live' => false];

    $cacheFile = sys_get_temp_dir() . '/slapia_stats.json';
    $cacheTtl  = 3600; // 1 heure

    if (!$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) return $cached;
    }

    try {
        $pdo = db();

        $counts = $pdo->query(
            "SELECT
                SUM(type_client = 'Entreprise')  AS entreprises,
                SUM(type_client = 'Particulier') AS particuliers
             FROM clients"
        )->fetch();

        $sat = $pdo->query(
            'SELECT AVG(satisfaction) AS avg_sat, COUNT(*) AS n FROM avis_clients WHERE satisfaction > 0'
        )->fetch();
    } catch (Throwable $e) {
        error_log('[SlapIA Stats] getSlapiaStats failed: ' . $e->getMessage());
        return $fallback;
    }

    $result = [
        'entreprises'        => (int)($counts['entreprises'] ?? 0),
        'particuliers'       => (int)($counts['particuliers'] ?? 0),
        'satisfaction'       => $sat['avg_sat'] !== null ? round((float)$sat['avg_sat'], 1) : null,
        'satisfaction_count' => (int)($sat['n'] ?? 0),
        'is_live'            => true,
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
