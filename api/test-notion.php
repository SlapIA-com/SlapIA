<?php
include_once __DIR__ . '/../includes/config.php';
$notionApiKey = config('NOTION_API_KEY');
$notionDbId = config('NOTION_CONTACT_DATABASE_ID');

$queryData = [
    'filter' => [
        'property' => 'Email',
        'email' => [
            'equals' => 'grobost.gabin@gmail.com'
        ]
    ]
];

$ch = curl_init('https://api.notion.com/v1/databases/' . $notionDbId . '/query');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($queryData),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $notionApiKey,
        'Content-Type: application/json',
        'Notion-Version: 2022-06-28'
    ]
]);

$response = curl_exec($ch);
echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
