<?php
/**
 * Connexion MySQL (PDO) centralisée — remplace includes/notion.php pour tout
 * ce qui touche aux comptes, clients, prestations, avis, contact et RSS.
 *
 * includes/notion.php (NotionAPI) reste utilisé par includes/notion-blog.php
 * pour le blog, qui n'a pas été migré et continue de tourner sur Notion.
 */

if (!function_exists('config')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Retourne une instance PDO singleton connectée à la base "slapia".
 * Variables d'environnement attendues dans .env : DB_HOST, DB_PORT (def. 3306),
 * DB_NAME (def. slapia), DB_USER, DB_PASS.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $host = config('DB_HOST', '127.0.0.1');
        $port = config('DB_PORT', '3306');
        $name = config('DB_NAME', 'slapia');
        $user = config('DB_USER', '');
        $pass = config('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('[SlapIA DB] Connexion PDO échouée : ' . $e->getMessage());
            throw $e;
        }
    }
    return $pdo;
}

/**
 * Dossier de stockage local pour les fichiers uploadés (photos de profil,
 * factures PDF). Doit persister entre les déploiements — voir README.
 */
if (!defined('STORAGE_DIR')) {
    define('STORAGE_DIR', __DIR__ . '/../storage');
}

/** Crée le sous-dossier de stockage demandé s'il n'existe pas déjà. */
function storagePath(string $subdir): string
{
    $path = STORAGE_DIR . '/' . trim($subdir, '/');
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }
    return $path;
}
