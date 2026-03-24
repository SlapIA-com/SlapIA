<?php
include_once '../includes/config.php';
session_start();
ini_set('display_errors', 0);
ob_start();

/**
 * API Endpoint: Check Notifications
 * Aggregates platform updates and personal alerts (like new invoices) from Notion.
 */

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    ob_clean();
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

// Detect Invoices (Last 3)
$invoices = array_reverse($props['Factures']['files'] ?? []);
$invCount = 0;
foreach ($invoices as $inv) {
    if ($invCount >= 3) break;
    $notifId = md5($inv['name']);
    $notifications[] = [
        'id' => 'inv_' . $notifId,
        'title' => 'Facture disponible',
        'desc' => htmlspecialchars($inv['name']),
        'ts' => strtotime($userData['last_edited_time']), 
        'icon' => 'fas fa-file-invoice-dollar',
        'icon_bg' => 'bg-success',
        'icon_color' => 'text-success',
        'link' => '/dashboard?tab=billing'
    ];
    $invCount++;
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
    'id' => 'system_ready',
    'title' => 'Système Connecté',
    'desc' => 'Le pont avec Notion CRM est actif.',
    'ts' => time(), // Always "new" for debug
    'icon' => 'fas fa-plug',
    'icon_bg' => 'bg-info',
    'icon_color' => 'text-info',
    'link' => '#'
];

$notifications[] = [
    'id' => 'pwa_launch',
    'title' => 'SlapIA est mobile',
    'desc' => 'Installez l\'application sur votre écran d\'accueil.',
    'ts' => 1711294800, 
    'icon' => 'fas fa-mobile-alt',
    'icon_bg' => 'bg-primary',
    'icon_color' => 'text-primary',
    'link' => '#'
];

// Sort by timestamp descending
usort($notifications, fn($a, $b) => $b['ts'] <=> $a['ts']);

ob_clean();
echo json_encode([
    'success' => true,
    'notifications' => array_slice($notifications, 0, 8)
]);
exit;
