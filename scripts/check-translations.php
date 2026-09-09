<?php

/**
 * Vérifie que lang/fr, lang/en et lang/de/messages.php définissent
 * exactement les mêmes clés (récursivement), avec le même nombre d'éléments
 * pour les tableaux de contenu (ex: 'levels').
 *
 * Pourquoi ce script : le 5 septembre 2026, la section 'admin' du français
 * avait 39 clés quand l'anglais et l'allemand en avaient 73 — une régression
 * passée inaperçue jusqu'à ce qu'on la découvre en testant à la main. Sans
 * traduction manquante, useTranslation() (front) et le HandleInertiaRequests
 * (fallback FR) rattrapent le coup silencieusement ; ça peut donc dériver
 * pendant longtemps sans que personne ne s'en aperçoive.
 *
 * Usage :
 *   php scripts/check-translations.php
 * Code de sortie 0 si tout est cohérent, 1 sinon (utilisable en CI).
 * Ne nécessite PAS vendor/ — un script PHP autonome, exécutable sans
 * `composer install`.
 */

$root = dirname(__DIR__);
$locales = ['fr', 'en', 'de'];
$files = [];

foreach ($locales as $locale) {
    $path = $root."/lang/{$locale}/messages.php";
    if (!is_file($path)) {
        fwrite(STDERR, "Fichier introuvable : {$path}\n");
        exit(1);
    }
    $files[$locale] = require $path;
}

/** Retourne l'ensemble des "chemins de clés" (ex: "admin.label_photo") d'un tableau imbriqué. */
function collectKeyPaths(array $data, string $prefix = ''): array
{
    $paths = [];
    foreach ($data as $key => $value) {
        // On ignore les clés numériques (listes de contenu, ex: 'modules'
        // d'un niveau de formation) : seule la STRUCTURE des clés
        // associatives nous intéresse, pas le contenu traduit lui-même.
        if (is_int($key)) {
            continue;
        }
        $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
        $paths[] = $path;
        if (is_array($value)) {
            $paths = array_merge($paths, collectKeyPaths($value, $path));
            // Pour un tableau de LISTE (clés numériques 0,1,2...), on vérifie
            // aussi que le nombre d'éléments est identique entre langues
            // (ex: 'levels' doit avoir le même nombre de niveaux partout).
            if ($value !== [] && array_is_list($value)) {
                $paths[] = $path.'[]count='.count($value);
            }
        }
    }

    return $paths;
}

$keysByLocale = [];
foreach ($files as $locale => $data) {
    $keysByLocale[$locale] = collectKeyPaths($data);
}

$reference = $keysByLocale['fr'];
$hasError = false;

foreach ($locales as $locale) {
    if ($locale === 'fr') {
        continue;
    }

    $missing = array_diff($reference, $keysByLocale[$locale]);
    $extra = array_diff($keysByLocale[$locale], $reference);

    if ($missing !== []) {
        $hasError = true;
        echo "[{$locale}] Clés manquantes par rapport au français (".count($missing)."):\n";
        foreach ($missing as $key) {
            echo "  - {$key}\n";
        }
    }

    if ($extra !== []) {
        $hasError = true;
        echo "[{$locale}] Clés en trop par rapport au français (".count($extra)."):\n";
        foreach ($extra as $key) {
            echo "  - {$key}\n";
        }
    }
}

if ($hasError) {
    echo "\nÉchec : les traductions ont divergé entre fr/en/de.\n";
    exit(1);
}

echo "OK : fr/en/de/messages.php ont exactement les mêmes clés (".count($reference)." au total).\n";
exit(0);
