<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-client.php';

requireLogin();

$index = filter_input(INPUT_GET, 'index', FILTER_VALIDATE_INT);
if ($index === null || $index === false || $index < 0) {
    http_response_code(400);
    exit;
}

$me      = currentUser();
$account = getOwnAccountDetails($me['id']);

if ($account === null || !isset($account['invoices'][$index])) {
    http_response_code(404);
    exit;
}

$invoice = $account['invoices'][$index];
$url     = $invoice['url'] ?? '';
if (!preg_match('#^https://#i', $url)) {
    http_response_code(502);
    exit;
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER  => true,
    CURLOPT_TIMEOUT         => 15,
    CURLOPT_FOLLOWLOCATION  => true,
    CURLOPT_MAXREDIRS       => 3,
    CURLOPT_PROTOCOLS       => CURLPROTO_HTTPS,
    CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
]);
$data     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
unset($ch);

if ($httpCode !== 200 || $data === false || $data === '') {
    http_response_code(502);
    exit;
}

$safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($invoice['name'] ?: 'facture.pdf'));
if ($safeName === '') $safeName = 'facture.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $safeName . '"');
header('Content-Length: ' . strlen($data));
header('Cache-Control: private, max-age=0, no-cache');
echo $data;
