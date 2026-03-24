<?php
/**
 * API pour mettre à jour les informations du profil dans Notion ERP
 */

include_once __DIR__ . '/../includes/config.php';

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

try {
    header('Content-Type: application/json');

    // Auth check
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Non autorisé.']);
        exit;
    }

    $notionApiKey = config('NOTION_API_KEY');
    $userId = $_SESSION['user_id'];

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $fullName = trim($input['name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $company = trim($input['company'] ?? '');
    $job = trim($input['job'] ?? '');

    if (empty($fullName)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Le nom complet est obligatoire.']);
        exit;
    }

    // Build Payload for Notion PATCH
    // Based on user's ERP screenshot properties
    $updateData = [
        'properties' => [
            'Prenom NOM' => [
                'title' => [['text' => ['content' => $fullName]]]
            ],
            'Téléphone' => [
                'phone_number' => $phone
            ]
        ]
    ];

    // If company exists, assuming 'Nom d\'entreprise' is a rich_text (or select if strictly typed but text is safer)
    if (isset($input['company'])) {
        $updateData['properties']['Nom d\'entreprise'] = [
            'rich_text' => [['text' => ['content' => $company]]]
        ];
    }
    
    // If Job exists
    if (isset($input['job'])) {
        $updateData['properties']['Job'] = [
            'rich_text' => [['text' => ['content' => $job]]]
        ];
    }

    $ch = curl_init('https://api.notion.com/v1/pages/' . $userId);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_POSTFIELDS => json_encode($updateData),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $notionApiKey,
            'Content-Type: application/json',
            'Notion-Version: 2022-06-28'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    unset($ch);

    if ($error || $httpCode >= 400) {
        ob_clean();
        http_response_code(500);
        error_log("Notion Profile Update Failed: " . $response);
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour de votre profil.']);
        exit;
    }

    // Update Session name
    $_SESSION['user_name'] = $fullName;

    // Optional: Only output pure JSON
    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès!']);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Auth Update] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur interne.']);
}
