<?php
include_once '../includes/config.php';

// 1. Auth Protection (ADMIN ONLY)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /login');
    exit;
}

// CSRF Verification
$postedToken = $_POST['csrf_token'] ?? '';
if (empty($postedToken) || $postedToken !== ($_SESSION['csrf_token'] ?? '')) {
    header('Location: /admin-reset-pwd?error=' . urlencode('Erreur de sécurité (CSRF). Veuillez réessayer.'));
    exit;
}

$notionApiKey = config('NOTION_API_KEY');
$adminUserId = $_SESSION['user_id'];

// Verify Admin Status via Notion
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
$status = $props['Status']['select']['name'] ?? $props['Status']['rich_text'][0]['text']['content'] ?? '';

if ($status !== 'Admin') {
    header('Location: /dashboard');
    exit;
}

// 2. Receive Data
$email = $_POST['email'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

if (empty($email) || empty($newPassword)) {
    header('Location: /admin-reset-pwd?error=' . urlencode('Veuillez remplir tous les champs.'));
    exit;
}

// 3. Search for Target User in Notion
$satisfactionDbId = config('NOTION_SATISFACTION_DATABASE_ID');
$queryPayload = [
    'filter' => [
        'property' => 'Email',
        'email' => [
            'equals' => $email
        ]
    ]
];

$chQuery = curl_init('https://api.notion.com/v1/databases/' . $satisfactionDbId . '/query');
curl_setopt_array($chQuery, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($queryPayload),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $notionApiKey,
        'Content-Type: application/json',
        'Notion-Version: 2022-06-28'
    ]
]);
$resQuery = curl_exec($chQuery);
$queryData = json_decode($resQuery, true);
$results = $queryData['results'] ?? [];

if (empty($results)) {
    header('Location: /admin-reset-pwd?email=' . urlencode($email) . '&error=' . urlencode('Utilisateur non trouvé dans le CRM.'));
    exit;
}

$targetPageId = $results[0]['id'];

// 4. Update Password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$updatePayload = [
    'properties' => [
        'Mot de passe' => [
            'rich_text' => [
                [
                    'text' => [
                        'content' => $hashedPassword
                    ]
                ]
            ]
        ]
    ]
];

$chUpdate = curl_init('https://api.notion.com/v1/pages/' . $targetPageId);
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
$updateStatus = curl_getinfo($chUpdate, CURLINFO_HTTP_CODE);

if ($updateStatus === 200) {
    header('Location: /admin-reset-pwd?success=' . urlencode('Le mot de passe de ' . $email . ' a été mis à jour avec succès.'));
} else {
    header('Location: /admin-reset-pwd?email=' . urlencode($email) . '&error=' . urlencode('Erreur lors de la mise à jour dans Notion.'));
}
exit;
