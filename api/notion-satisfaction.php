<?php
/**
 * API pour récupérer les statistiques de satisfaction depuis Notion
 */

// Configuration Notion
include_once __DIR__ . '/../includes/config.php';
define('NOTION_API_KEY', config('NOTION_API_KEY'));
define('NOTION_DATABASE_ID', config('NOTION_SATISFACTION_DATABASE_ID'));

/**
 * Parse une valeur de select pour en extraire un pourcentage
 * Gère les formats : "5", "5/5", "5 stars", "★★★★★", "80%"
 */
function parseSelectRating($value) {
    if (!$value) return null;
    $value = trim($value);

    // Si pourcentage explicite
    if (preg_match('/(\d{1,3})\s*%/', $value, $m)) {
        $num = (int)$m[1];
        return max(0, min(100, $num));
    }

    // Si notation étoiles unicode like ★★★★★ ou ⭐⭐⭐⭐⭐
    if (preg_match('/([★⭐]+)/u', $value, $m)) {
        $count = mb_strlen($m[1], 'UTF-8');
        if ($count >=1 && $count <=5) {
            return convertToPercentage($count);
        }
    }

    // Chercher un nombre seul ou fraction 4/5
    if (preg_match('/(\d+)\s*\/\s*(\d+)/', $value, $m)) {
        $num = (int)$m[1];
        $den = (int)$m[2];
        if ($den > 0 && $num >= 0) {
            // Si dénominateur 5 -> interpret as stars
            if ($den == 5) return convertToPercentage($num);
            // Sinon, compute percent
            return max(0, min(100, round(($num / $den) * 100)));
        }
    }

    // Chercher un nombre (1-5 => étoiles, else percentage)
    if (preg_match('/(\d{1,3})/', $value, $m)) {
        $num = (int)$m[1];
        if ($num >=1 && $num <=5) return convertToPercentage($num);
        if ($num >=0 && $num <=100) return $num;
    }

    return null;
}

/**
 * Tente d'extraire une valeur de satisfaction depuis une propriété Notion
 * Retourne un pourcentage (0-100) ou null
 */
function tryExtractRatingFromProperty($key, $prop) {
    // Helper to safely get nested values
    $get = function($arr, $path) {
        $cur = $arr;
        foreach ($path as $p) {
            if (!isset($cur[$p])) return null;
            $cur = $cur[$p];
        }
        return $cur;
    };

    $type = $prop['type'] ?? null;

    // Number property
    if ($type === 'number') {
        $num = $prop['number'] ?? null;
        if (is_numeric($num)) return convertToPercentage($num);
    }

    // Select
    if ($type === 'select') {
        $name = $get($prop, ['select', 'name']);
        if ($name) return parseSelectRating($name);
    }

    // Multi-select: try first option or any option containing digits
    if ($type === 'multi_select') {
        $items = $prop['multi_select'] ?? [];
        if (!empty($items)) {
            foreach ($items as $it) {
                if (!empty($it['name'])) {
                    $p = parseSelectRating($it['name']);
                    if ($p !== null) return $p;
                }
            }
        }
    }

    // Rich text: concatenate plain_text
    if ($type === 'rich_text') {
        $rt = $prop['rich_text'] ?? [];
        $txt = '';
        foreach ($rt as $r) { $txt .= ($r['plain_text'] ?? ''); }
        $txt = trim($txt);
        if ($txt !== '') {
            $p = parseSelectRating($txt);
            if ($p !== null) return $p;
        }
    }

    // Formula: can contain number or string
    if ($type === 'formula') {
        $num = $get($prop, ['formula', 'number']);
        if (is_numeric($num)) return convertToPercentage($num);
        $str = $get($prop, ['formula', 'string']);
        if ($str) {
            $p = parseSelectRating($str);
            if ($p !== null) return $p;
        }
    }

    // Rollup: try number or array
    if ($type === 'rollup') {
        $num = $get($prop, ['rollup', 'number']);
        if (is_numeric($num)) return convertToPercentage($num);
        $arr = $get($prop, ['rollup', 'array']);
        if (is_array($arr)) {
            foreach ($arr as $el) {
                if (is_array($el) && isset($el['plain_text'])) {
                    $p = parseSelectRating($el['plain_text']);
                    if ($p !== null) return $p;
                }
            }
        }
    }

    return null;
}

/**
 * Récupère les statistiques de satisfaction depuis Notion
 * @return array Tableau contenant le pourcentage moyen et le nombre de réponses
 */
function getSatisfactionStats($forceRefresh = false) {

    // Utiliser la pagination pour récupérer tous les résultats
    $allResults = [];
    $startCursor = null;
    $page = 0;
    do {
        $page++;
        $payload = ['page_size' => 100];
        if ($startCursor) $payload['start_cursor'] = $startCursor;

        $ch = curl_init('https://api.notion.com/v1/databases/' . NOTION_DATABASE_ID . '/query');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . NOTION_API_KEY,
                'Content-Type: application/json',
                'Notion-Version: 2022-06-28',
                'User-Agent: FormationIA/1.0'
            ],
            CURLOPT_TIMEOUT => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'pourcentage' => 'N.A',
                'nombre' => 0,
                'error' => true,
                'message' => $curlError
            ];
        }

        $data = json_decode($response, true);
        if ($httpCode !== 200) {
            $msg = isset($data['message']) ? $data['message'] : substr($response, 0, 200);
            return [
                'pourcentage' => 'N.A',
                'nombre' => 0,
                'error' => true,
                'message' => $msg
            ];
        }

        $pageResults = $data['results'] ?? [];
        $allResults = array_merge($allResults, $pageResults);

        $has_more = $data['has_more'] ?? false;
        $startCursor = $data['next_cursor'] ?? null;
    } while (!empty($has_more));

    $results = $allResults;
    
    // Extraire les évaluations/satisfactions
    $satisfactions = [];
    $detailedRatings = [];
    
    foreach ($results as $page) {
        $props = $page['properties'] ?? [];
        
        // Chercher UNIQUEMENT le champ "Satisfaction" exact
        $satisfaction = null;
        
        // Vérifier si le champ "Satisfaction" existe
        if (isset($props['Satisfaction'])) {
            $extracted = tryExtractRatingFromProperty('Satisfaction', $props['Satisfaction']);
            if ($extracted !== null) {
                $satisfaction = $extracted;
            }
        }
        
        // Si le champ Satisfaction existe et a une valeur, l'ajouter
        if ($satisfaction !== null && $satisfaction > 0) {
            $satisfactions[] = (int)$satisfaction;
        }
    }
    
    // Calculer la moyenne et le nombre
    if (empty($satisfactions)) {
        $result = [
            'pourcentage' => 'N.A',
            'nombre' => 0,
            'average' => 0
        ];
    } else {
        $count = count($satisfactions);
        // Si une seule réponse, utiliser exactement cette valeur (éviter moyenne trompeuse)
        if ($count === 1) {
            $single = (int)$satisfactions[0];
            $result = [
                'pourcentage' => round($single),
                'nombre' => 1,
                'average' => round($single, 2)
            ];
        } else {
            $moyenne = array_sum($satisfactions) / $count;
            $result = [
                'pourcentage' => round($moyenne),
                'nombre' => $count,
                'average' => round($moyenne, 2)
            ];
        }
    }
    
    return $result;
}

/**
 * Traduit un texte vers une langue cible
 * Utilise MyMemory API (gratuit, sans clé requise)
 */
function translateText($text, $targetLang = 'fr') {
    if (empty($text) || strlen($text) < 3) return $text;
    
    // Si la langue cible est 'en', ne pas traduire (avis anglais garder en l'état)
    if ($targetLang === 'en') return $text;
    
    // Vérifier si le texte semble déjà dans la langue cible
    if ($targetLang === 'fr' && preg_match('/[àâäéèêëïîôöùûüç]/i', $text)) {
        return $text; // Probablement déjà en français
    }
    
    try {
        $cacheKey = md5($text . $targetLang);
        $cacheFile = sys_get_temp_dir() . '/translation_' . $cacheKey . '.txt';
        
        // Vérifier le cache (valide 7 jours)
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 604800) {
            return file_get_contents($cacheFile);
        }
        
        $url = 'https://api.mymemory.translated.net/get';
        $params = [
            'q' => $text,
            'langpair' => 'en|' . $targetLang
        ];
        
        $ch = curl_init($url . '?' . http_build_query($params));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['User-Agent: FormationIA/1.0']
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['responseStatus']) && $data['responseStatus'] === 200) {
                $translated = $data['responseData']['translatedText'] ?? null;
                if ($translated && $translated !== $text) {
                    // Mettre en cache
                    file_put_contents($cacheFile, $translated);
                    return $translated;
                }
            }
        }
    } catch (Exception $e) {
        // Ignorer les erreurs de traduction
    }
    
    return $text;
}

/**
 * Convertit une note sur 5 en pourcentage
 * 5 étoiles = 100%, 4 = 80%, 3 = 60%, 2 = 40%, 1 = 20%
 */
function convertToPercentage($rating) {
    if ($rating >= 1 && $rating <= 5) {
        return ($rating / 5) * 100;
    }
    return $rating; // Si c'est déjà un pourcentage
}

/**
 * Récupère des avis (reviews) depuis la même base Notion.
 * Tente d'extraire les colonnes communes : Prenom, Nom, Profession, Avis/Comment, Note/Rating
 * Traduit les avis selon la langue fournie
 * Retourne un tableau d'items : ['prenom','nom','profession','avis','note','photo']
 */
function getNotionReviews($limit = 20, $lang = 'fr') {
    $allResults = [];
    $startCursor = null;
    $fetched = 0;
    do {
        $payload = ['page_size' => min(100, $limit - $fetched)];
        if ($startCursor) $payload['start_cursor'] = $startCursor;

        $ch = curl_init('https://api.notion.com/v1/databases/' . NOTION_DATABASE_ID . '/query');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . NOTION_API_KEY,
                'Content-Type: application/json',
                'Notion-Version: 2022-06-28',
                'User-Agent: FormationIA/1.0'
            ],
            CURLOPT_TIMEOUT => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) break;

        $data = json_decode($response, true);
        if ($httpCode !== 200) break;

        $pageResults = $data['results'] ?? [];
        $allResults = array_merge($allResults, $pageResults);

        $has_more = $data['has_more'] ?? false;
        $startCursor = $data['next_cursor'] ?? null;
        $fetched = count($allResults);
    } while ($has_more && $fetched < $limit);

    $reviews = [];
    if (!empty($allResults)) {
        // DEBUG: Log the first result to understand structure
        // Using absolute path for reliability
    }

    foreach ($allResults as $page) {
        $props = $page['properties'] ?? [];

        $getText = function($prop) {
            if (!$prop || !is_array($prop)) return '';
            $type = $prop['type'] ?? '';
            if ($type === 'title' || $type === 'rich_text') {
                $parts = $prop[$type] ?? [];
                $txt = '';
                foreach ($parts as $p) { $txt .= ($p['plain_text'] ?? ''); }
                return trim($txt);
            }
            if ($type === 'select') {
                return $prop['select']['name'] ?? '';
            }
            if ($type === 'multi_select') {
                $vals = array_map(function($i){return $i['name'] ?? '';}, $prop['multi_select'] ?? []);
                return implode(', ', $vals);
            }
            if ($type === 'number') {
                return (string)($prop['number'] ?? '');
            }
            if ($type === 'url') {
                return $prop['url'] ?? '';
            }
            return '';
        };

        // Heuristiques pour trouver les champs
        $prenom = '';
        $nom = '';
        $profession = '';
        $avis = '';
        $note = null;
        $photo = null;
        $status = '';
        $entreprise = '';
        $linkedin = '';

        // Utiliser les VRAIS noms des colonnes Notion
        // Extraire Prenom et NOM du champ "Prenom NOM"
        if (isset($props['Prenom NOM'])) {
            $full = $getText($props['Prenom NOM']);
            if ($full !== '') {
                $parts = preg_split('/\s+/', $full, 2);
                $prenom = $parts[0] ?? '';
                $nom = $parts[1] ?? '';
            }
        }

        // Job = profession
        if (isset($props['Job'])) {
            $profession = $getText($props['Job']);
        }

        if (isset($props['Status'])) {
            $status = $getText($props['Status']);
        }

        if (isset($props['Nom d\'entreprise'])) {
            $entreprise = $getText($props['Nom d\'entreprise']);
        }

        if (isset($props['Linkedin'])) {
            $linkedin = $getText($props['Linkedin']);
        }

        // Avis clients
        if (isset($props['Avis clients'])) {
            $avisRaw = $getText($props['Avis clients']);
            // Traduire l'avis selon la langue du site
            $avis = translateText($avisRaw, $lang);
        }

        // Satisfaction = note
        if (isset($props['Satisfaction'])) {
            $val = $getText($props['Satisfaction']);
            if ($val !== '') {
                // Try to parse numeric or star count
                if (is_numeric($val)) {
                    $num = (float)$val;
                    if ($num > 5) $num = ($num / 100) * 5; // treat percent -> stars
                    $note = max(0, min(5, $num));
                } else {
                    // stars unicode
                    if (preg_match('/([★⭐]+)/u', $val, $m)) {
                        $count = mb_strlen($m[1], 'UTF-8');
                        $note = max(0, min(5, $count));
                    } elseif (preg_match('/(\\d)\\s*\\/\\s*(\\d)/', $val, $m)) {
                        $n = (int)$m[1]; $d = (int)$m[2];
                        if ($d>0) $note = max(0, min(5, round(($n/$d)*5,2)));
                    }
                }
            }
        }

        // Try to extract photo/image URL from common fields
        $photoFields = ['Photo', 'Image', 'Avatar', 'Profile Image'];
        foreach ($photoFields as $field) {
            if (isset($props[$field]) && ($props[$field]['type'] ?? '') === 'files') {
                $files = $props[$field]['files'] ?? [];
                if (!empty($files) && isset($files[0]['file']['url'])) {
                    $photo = $files[0]['file']['url'];
                    break;
                }
            }
        }

        // Si pas de photo via propriété, essayer l'icône de la page
        if (empty($photo) && isset($page['icon'])) {
            $icon = $page['icon'];
            if ($icon['type'] === 'emoji') {
               // On ne gère pas les emojis comme images pour l'instant
            } elseif ($icon['type'] === 'file' || $icon['type'] === 'external') {
                $url = $icon[$icon['type']]['url'] ?? null;
                if ($url) $photo = $url;
            }
        }

        $reviews[] = [
            'prenom' => $prenom,
            'nom' => $nom,
            'profession' => $profession,
            'avis' => $avis,
            'note' => $note,
            'photo' => $photo,
            'status' => $status,
            'entreprise' => $entreprise,
            'linkedin' => $linkedin
        ];

        if (count($reviews) >= $limit) break;
     }
 
     return $reviews;
 }
 
 // Si appelé directement, retourner le JSON
 if (basename($_SERVER['PHP_SELF']) === 'notion-satisfaction.php') {
     header('Content-Type: application/json');
     echo json_encode(getSatisfactionStats());
 }

