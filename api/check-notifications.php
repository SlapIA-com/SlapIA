<?php
include_once '../includes/config.php';
session_start();

/**
 * API Endpoint: Check Notifications
 * Aggregates platform updates and personal alerts (like new invoices) from Notion.
 */

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

$notionApiKey = config('NOTION_API_KEY');
$userId = $_SESSION['user_id'];

$notifications = [];

// 1. Fetch User Data for Invoice Monitoring
$ch = curl_init('https://api.notion.com/v1/pages/' . $userId);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $notionApiKey,
        'Notion-Version: 2022-06-28'
    ]
]);
$res = curl_exec($ch);
$userData = json_decode($res, true);
$props = $userData['properties'] ?? [];

// Detect New Invoices
$invoices = $props['Factures']['files'] ?? [];
if (!empty($invoices)) {
    // We take the last one added (Notion usually appends to the end)
    $latest = end($invoices);
    $notifId = md5($latest['name']); // Stable ID for this file
    
    $notifications[] = [
        'id' => 'inv_' . $notifId,
        'title' => 'Nouvelle facture',
        'desc' => htmlspecialchars($latest['name']),
        'ts' => strtotime($userData['last_edited_time']), // Approximation of when it was added
        'icon' => 'fas fa-file-invoice-dollar',
        'icon_bg' => 'bg-success',
        'icon_color' => 'text-success',
        'link' => '/dashboard?tab=billing'
    ];
}

// Support for Admin Status change notification
$status = $props['Status']['select']['name'] ?? '';
if ($status === 'Admin') {
    $notifications[] = [
        'id' => 'system_admin',
        'title' => 'Privilèges Admin',
        'desc' => 'Vous avez maintenant accès au centre de contrôle SlapIA.',
        'ts' => strtotime($userData['last_edited_time']),
        'icon' => 'fas fa-shield-alt',
        'icon_bg' => 'bg-warning',
        'icon_color' => 'text-warning',
        'link' => '/dashboard?tab=admin'
    ];
}

// 2. Global Static Platform Notifications (Example)
$notifications[] = [
    'id' => 'pwa_launch',
    'title' => 'SlapIA est mobile',
    'desc' => 'Installez l\'application sur votre écran d\'accueil.',
    'ts' => 1711294800, // Fixed TS for this announcement
    'icon' => 'fas fa-mobile-alt',
    'icon_bg' => 'bg-primary',
    'icon_color' => 'text-primary',
    'link' => '#'
];

// Sort by timestamp descending
usort($notifications, fn($a, $b) => $b['ts'] <=> $a['ts']);

echo json_encode([
    'success' => true,
    'notifications' => array_slice($notifications, 0, 8)
]);
exit;
