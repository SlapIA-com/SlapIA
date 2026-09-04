<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

requireAdmin();

$invoiceId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$invoiceId) {
    http_response_code(400);
    exit;
}

$stmt = db()->prepare('SELECT nom_fichier, chemin_fichier, mime_type FROM factures WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $invoiceId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    http_response_code(404);
    exit;
}

$path = STORAGE_DIR . '/' . $invoice['chemin_fichier'];
if (!is_readable($path)) {
    http_response_code(404);
    exit;
}

$safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($invoice['nom_fichier'] ?: 'facture.pdf'));
if ($safeName === '') $safeName = 'facture.pdf';

header('Content-Type: ' . ($invoice['mime_type'] ?: 'application/pdf'));
header('Content-Disposition: inline; filename="' . $safeName . '"');
header('Cache-Control: private, max-age=0, no-cache');
readfile($path);
