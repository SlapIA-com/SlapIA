<?php
include_once '../includes/config.php';

/**
 * API Endpoint: Admin Update User Role
 * Handles the PATCH request to update the 'Status' property of a user in Notion.
 * Requiert une authentification 'Admin' et un jeton CSRF valide.
 */

header('Content-Type: application/json');

// 1. Basic Auth Protection
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

// 2. Receive JSON Data
$data = json_decode(file_get_contents('php://input'), true);
$pageId = $data['page_id'] ?? '';
$status = $data['status'] ?? '';
$csrfToken = $data['csrf_token'] ?? '';

// CSRF Verification
if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    echo json_encode(['success' => false, 'error' => 'Erreur de sécurité (CSRF).']);
    exit;
}

if (empty($pageId) || empty($status)) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes (ID ou Statut).']);
    exit;
}

$notionApiKey = config('NOTION_API_KEY');
$adminUserId = $_SESSION['user_id'];

// 3. Double-check Admin status in Notion for server-side security
$ch = curl_init('https://api.notion.com/v1/pages/' . $adminUserId);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $notionApiKey,
        'Notion-Version: 2022-06-28'
    ]
]);
$resAdmin = curl_exec($ch);
$adminPage = json_decode($resAdmin, true);
$props = $adminPage['properties'] ?? [];

// Flexible status check (Select or Rich Text)
$adminStatus = $props['Status']['select']['name'] ?? $props['Status']['rich_text'][0]['text']['content'] ?? '';

if ($adminStatus !== 'Admin' && $adminStatus !== '') {
    echo json_encode(['success' => false, 'error' => 'Accès refusé : Privilèges administrateur requis.']);
    exit;
}

// 4. Perform the Update in Notion (PATCH)
$updatePayload = [
    'properties' => [
        'Status' => [
            'select' => [
                'name' => $status
            ]
        ]
    ]
];

$chUpdate = curl_init('https://api.notion.com/v1/pages/' . $pageId);
curl_setopt_array($chUpdate, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_TIMEOUT => 10,
    CURLOPT_POSTFIELDS => json_encode($updatePayload),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $notionApiKey,
        'Content-Type: application/json',
        'Notion-Version: 2022-06-28'
    ]
]);
$resUpdate = curl_exec($chUpdate);
$updateCode = curl_getinfo($chUpdate, CURLINFO_HTTP_CODE);

if ($updateCode === 200) {
    echo json_encode(['success' => true]);
} else {
    $errorData = json_decode($resUpdate, true);
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur Notion API (' . $updateCode . ') : ' . ($errorData['message'] ?? 'Erreur inconnue')
    ]);
}
exit;
