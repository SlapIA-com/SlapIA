<?php
/**
 * Debug pour voir exactement ce que Notion retourne
 */

// Configuration Notion
include_once __DIR__ . '/../includes/config.php';
define('NOTION_API_KEY', config('NOTION_API_KEY'));
define('NOTION_DATABASE_ID', config('NOTION_SATISFACTION_DATABASE_ID'));

$payload = ['page_size' => 1]; // Fetch only one result for debugging

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
    die("Curl Error: " . $curlError);
}

// Log full response to file
file_put_contents(__DIR__ . '/debug_notion.log', $response);

$data = json_decode($response, true);

echo "Data written to debug_notion.log<br>";
echo "<pre>";
print_r($data);
echo "</pre>";
?>
