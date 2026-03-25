<?php
/**
 * API Endpoint: Check Notifications
 * Aggregates platform updates and personal alerts from Notion.
 * Includes read/unread state from session.
 */
include_once '../includes/config.php';

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

// Read state from session
$readIds    = $_SESSION['read_notif_ids'] ?? [];
$readAllTs  = $_SESSION['notif_read_all_ts'] ?? 0;

$notifications = [];

// 1. Fetch User Data
$ch = curl_init('https://api.notion.com/v1/pages/' . $userId);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $notionApiKey,
        'Notion-Version: 2022-06-28'
    ]
]);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Erreur API Notion']);
    exit;
}

$userData    = json_decode($res, true);
$props       = $userData['properties'] ?? [];
$lastEdited  = $userData['last_edited_time'] ?? date('c');
$createdTime = $userData['created_time'] ?? date('c');

// Helper: build notification with read state
$makeNotif = function(string $id, string $title, string $desc, int $ts, string $icon, string $iconBg, string $iconColor, string $link = '#', bool $pinned = false) use ($readIds, $readAllTs) {
    $isRead = in_array($id, $readIds, true) || $ts < $readAllTs;
    return [
        'id'         => $id,
        'title'      => $title,
        'desc'       => $desc,
        'ts'         => $ts,
        'icon'       => $icon,
        'icon_bg'    => $iconBg,
        'icon_color' => $iconColor,
        'link'       => $link,
        'pinned'     => $pinned,
        'read'       => $isRead,
    ];
};

// 2. Billing pending
$billingStatus = $props['Facturation']['select']['name'] ?? '';
if ($billingStatus === 'En attente') {
    $notifications[] = $makeNotif(
        'user_pending_payment',
        'Facture en attente de règlement',
        'Action requise pour conserver votre accès complet.',
        time() + 5000,
        'fas fa-exclamation-circle', 'bg-danger', 'text-danger',
        '/dashboard?tab=billing',
        true
    );
}

// 3. Recent invoices (last 3)
$invoices = array_reverse($props['Factures']['files'] ?? []);
$invCount = 0;
foreach ($invoices as $inv) {
    if ($invCount >= 3) break;
    $invName = $inv['name'] ?? 'Facture';
    $notifId = 'inv_' . md5($invName);
    $notifications[] = $makeNotif(
        $notifId,
        'Nouvelle facture disponible',
        htmlspecialchars($invName, ENT_QUOTES, 'UTF-8'),
        strtotime($lastEdited),
        'fas fa-file-invoice-dollar', 'bg-success', 'text-success',
        '/dashboard?tab=billing'
    );
    $invCount++;
}

// 4. Admin status
$status = $props['Status']['select']['name'] ?? '';
if ($status === 'Admin') {
    $notifications[] = $makeNotif(
        'system_admin',
        'Accès administrateur actif',
        'Vous avez accès au panneau de contrôle SlapIA.',
        strtotime($lastEdited),
        'fas fa-shield-alt', 'bg-warning', 'text-warning',
        '/dashboard?tab=admin'
    );
}

// 5. Profile incomplete
$fields = ['Prenom NOM', 'Téléphone', 'Job', 'Location', 'Linkedin'];
$missing = 0;
foreach ($fields as $f) {
    $val = '';
    if (isset($props[$f])) {
        $type = $props[$f]['type'] ?? '';
        if ($type === 'title') $val = $props[$f]['title'][0]['plain_text'] ?? '';
        elseif ($type === 'rich_text') $val = $props[$f]['rich_text'][0]['plain_text'] ?? '';
        elseif ($type === 'phone_number') $val = $props[$f]['phone_number'] ?? '';
        elseif ($type === 'url') $val = $props[$f]['url'] ?? '';
    }
    if (empty($val)) $missing++;
}
if ($missing >= 2) {
    $notifications[] = $makeNotif(
        'profile_incomplete',
        'Profil incomplet',
        $missing . ' champ' . ($missing > 1 ? 's' : '') . ' manquant' . ($missing > 1 ? 's' : '') . '. Complétez votre profil.',
        strtotime($createdTime) + 3600,
        'fas fa-user-edit', 'bg-primary', 'text-primary',
        '/dashboard?tab=profile'
    );
}

// 6. Welcome notification (first day)
$isNewUser = (time() - strtotime($createdTime)) < 86400 * 7;
if ($isNewUser) {
    $notifications[] = $makeNotif(
        'welcome_new',
        'Bienvenue sur SlapIA !',
        'Votre compte est actif. Explorez vos formations et ressources.',
        strtotime($createdTime),
        'fas fa-party-horn', 'bg-info', 'text-info',
        '/formation'
    );
}

// Sort: pinned first, then by timestamp desc
usort($notifications, function($a, $b) {
    if ($a['pinned'] !== $b['pinned']) return $a['pinned'] ? -1 : 1;
    return $b['ts'] <=> $a['ts'];
});

$unreadCount = count(array_filter($notifications, fn($n) => !$n['read']));

ob_clean();
echo json_encode([
    'success'      => true,
    'notifications' => array_slice($notifications, 0, 10),
    'unread_count' => $unreadCount,
]);
exit;
