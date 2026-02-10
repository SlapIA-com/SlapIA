<?php
// Script de test pour vérifier la connexion Notion Analytics
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . '/includes/config.php';

$apiKey = config('NOTION_API_KEY');
$dbId = config('NOTION_ANALYTICS_DB_ID');

echo "<h1>Test Connexion Notion</h1>";
echo "<p>API Key (masked): " . ($apiKey ? substr($apiKey, 0, 5) . "..." . substr($apiKey, -5) : "<strong>MANQUANT</strong>") . "</p>";
echo "<p>DB ID: " . ($dbId ? $dbId : "<strong>MANQUANT</strong>") . "</p>";

echo "<h3>Environment Variables Debug</h3>";
echo "<pre>";
print_r([
    'getenv' => getenv('NOTION_API_KEY'),
    '_ENV' => $_ENV['NOTION_API_KEY'] ?? null,
    '_SERVER' => $_SERVER['NOTION_API_KEY'] ?? null
]);
echo "</pre>";

if (!$apiKey || !$dbId) {
    die("❌ Configuration manquante ! Si API Key est manquant, c'est probablement parce que j'ai supprimé le fichier .env qui la contenait.");
}

$payload = [
    'parent' => ['database_id' => $dbId],
    'properties' => [
        'Page' => [
            'title' => [
                ['text' => ['content' => 'Test de connexion ' . date('H:i:s')]]
            ]
        ],
        'VisitorID' => [
            'rich_text' => [
                ['text' => ['content' => 'TEST_VISITOR']]
            ]
        ]
    ]
];

$ch = curl_init('https://api.notion.com/v1/pages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'Notion-Version: 2022-06-28'
    ],
    CURLOPT_VERBOSE => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>Résultat HTTP $httpCode</h3>";

if ($error) {
    echo "<p style='color:red'>Erreur cURL : $error</p>";
}

$json = json_decode($response, true);
if ($httpCode >= 200 && $httpCode < 300) {
    echo "<p style='color:green'>✅ Succès ! Une ligne a été ajoutée dans Notion.</p>";
}
else {
    echo "<p style='color:red'>❌ Erreur API Notion :</p>";
    echo "<pre>" . print_r($json, true) . "</pre>";
}
?>
