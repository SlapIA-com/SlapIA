<?php
/**
 * API Endpoint: Check Notifications
 * Aggregates platform updates and personal alerts (like new invoices) from Notion.
 */
include_once '../includes/config.php';
// session_start(); // Already handled in config.php

ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

$notionApiKey = config('NOTION_API_KEY');
$userId = $_SESSION['user_id'] ?? '';

if (empty($userId)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'ID utilisateur manquant']);
    exit;
}

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
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$userData = json_decode($res, true);
$props = $userData['properties'] ?? [];
$lastEdited = $userData['last_edited_time'] ?? date('c');

// 1. Detect User-Specific Pending Billing (Persistent for the user)
$billingStatus = '';
if (isset($props['Facturation']['select']['name'])) {
    $billingStatus = $props['Facturation']['select']['name'];
}

if ($billingStatus === 'En attente') {
    $notifications[] = [
        'id' => 'user_pending_payment',
        'title' => 'Paiement en attente',
        'desc' => 'Votre accès est suspendu jusqu\'au règlement de votre facture.',
        'ts' => time() + 5000, 
        'icon' => 'fas fa-exclamation-circle',
        'icon_bg' => 'bg-danger',
        'icon_color' => 'text-danger',
        'link' => '/dashboard?tab=billing',
        'pinned' => true
    ];
}

// 2. Detect Invoices (Last 3)
$invoices = [];
if (isset($props['Factures']['files'])) {
    $invoices = array_reverse($props['Factures']['files']);
}

$invCount = 0;
foreach ($invoices as $inv) {
    if ($invCount >= 3) break;
    $invName = $inv['name'] ?? 'Facture';
    $notifId = md5($invName);
    $notifications[] = [
        'id' => 'inv_' . $notifId,
        'title' => 'Facture disponible',
        'desc' => htmlspecialchars($invName),
        'ts' => strtotime($lastEdited), 
        'icon' => 'fas fa-file-invoice-dollar',
        'icon_bg' => 'bg-success',
        'icon_color' => 'text-success',
        'link' => '/dashboard?tab=billing'
    ];
    $invCount++;
}

// Support for Admin Status change notification
$status = '';
if (isset($props['Status']['select']['name'])) {
    $status = $props['Status']['select']['name'];
}

if ($status === 'Admin') {
    $notifications[] = [
        'id' => 'system_admin',
        'title' => 'Privilèges Admin',
        'desc' => 'Vous avez accès au centre de contrôle SlapIA.',
        'ts' => strtotime($lastEdited),
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
