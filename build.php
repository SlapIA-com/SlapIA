#!/usr/bin/env php
<?php
/**
 * SlapIA JS Minifier
 * Minifie tous les fichiers assets/js/*.js → assets/js/dist/*.js
 * Usage : php build.php
 *
 * Minification basique (supprime commentaires, espaces superflus).
 * Pour production sérieuse, utiliser esbuild ou terser via Node.js.
 */

$srcDir = __DIR__ . '/assets/js';
$distDir = __DIR__ . '/assets/js/dist';

if (!is_dir($distDir)) {
    mkdir($distDir, 0755, true);
    echo "[OK] Dossier dist/ créé\n";
}

$files = glob($srcDir . '/*.js');
$total = 0;
$saved = 0;

foreach ($files as $file) {
    $filename = basename($file);
    $dest = $distDir . '/' . $filename;

    $src = file_get_contents($file);
    $mini = minifyJs($src);

    file_put_contents($dest, $mini);

    $srcSize = strlen($src);
    $miniSize = strlen($mini);
    $ratio = $srcSize > 0 ? round((1 - $miniSize / $srcSize) * 100) : 0;

    echo sprintf("[OK] %-30s %6d → %6d bytes (%d%% saved)\n",
        $filename, $srcSize, $miniSize, $ratio);

    $total += $srcSize;
    $saved += ($srcSize - $miniSize);
}

echo str_repeat('-', 60) . "\n";
echo sprintf("Total : %d bytes → %d bytes sauvegardés (%d%% global)\n",
    $total, $saved, $total > 0 ? round($saved / $total * 100) : 0);

/**
 * Minification JS basique :
 * - Supprime les commentaires // et /* *\/
 * - Réduit les espaces multiples et sauts de ligne inutiles
 * - Préserve les strings et template literals
 */
function minifyJs(string $js): string
{
    // Remove single-line comments (not inside strings)
    $js = preg_replace('/(?<!["\'])\/\/[^\n]*(?![^"\']*["\'])/', '', $js);

    // Remove multi-line comments
    $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);

    // Collapse whitespace (spaces, tabs) to single space
    $js = preg_replace('/[ \t]+/', ' ', $js);

    // Remove spaces around operators and punctuation
    $js = preg_replace('/\s*([{}();,=\+\-\*\/<>!&\|:?])\s*/', '$1', $js);

    // Remove multiple newlines
    $js = preg_replace('/\n{2,}/', "\n", $js);

    // Remove leading/trailing whitespace per line
    $lines = explode("\n", $js);
    $lines = array_map('trim', $lines);
    $lines = array_filter($lines, fn($l) => $l !== '');

    return implode("\n", $lines);
}
