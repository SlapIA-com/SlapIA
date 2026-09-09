<?php

return [

    'notion' => [
        'api_key' => env('NOTION_API_KEY'),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'n8n' => [
        // Généralisé sur plusieurs événements : {event: 'password_reset', ...}
        // (PasswordResetController) et désormais aussi {event: 'welcome', ...}
        // (AdminController::sendRegistrationInvite, quand l'admin coche
        // "prise de contact ok" sur un message de contact) — même webhook
        // que l'ancien N8N_AUTH_WEBHOOK_URL, différencié par "event".
        'auth_webhook_url' => env('N8N_AUTH_WEBHOOK_URL'),
        // Webhook du "Chat Trigger" n8n pour le bouton d'agent IA en bas à
        // droite du site (voir resources/js/Components/ChatWidget.tsx).
        'chat_webhook_url' => env('N8N_CHAT_WEBHOOK'),
        // Clé partagée pour l'API de création d'articles (routes/api.php,
        // Api\N8nArticleController, EnsureN8nApiKey) — appelée par le
        // workflow n8n "Split & Publish" pour écrire directement dans
        // MySQL, à la place de l'ancienne page Notion.
        'articles_api_key' => env('N8N_ARTICLES_API_KEY'),
        // Nouveau message de contact (ContactController::store) — remplace
        // le déclencheur Notion "NEW Contact web". Un seul événement
        // {event: 'new_contact', ...} : le workflow n8n envoie en parallèle
        // l'email pour Thomas et l'email de confirmation au client.
        'contact_webhook_url' => env('N8N_CONTACT_WEBHOOK_URL'),
        // Avis clients — remplace les déclencheurs Notion "NEW Evaluation"
        // et "SEND Avis". Deux événements possibles :
        // {event: 'review_received', ...} (DashboardController::updateReview,
        // envoie l'email de remerciement) et {event: 'review_requested', ...}
        // (AdminController::requestReview, bouton manuel "demander un avis").
        'avis_webhook_url' => env('N8N_AVIS_WEBHOOK_URL'),
        // Optionnel : IP locale du NAS (ex. 192.168.1.253, déjà utilisée
        // pour MySQL) pour contourner un NAT en boucle (hairpin) quand le
        // nom DDNS du webhook n8n résout vers l'IP publique du NAS —
        // inaccessible depuis l'intérieur du même réseau. Voir
        // App\Services\N8nWebhook. Laisser vide si non concerné.
        'internal_ip' => env('N8N_INTERNAL_IP'),
    ],

];
