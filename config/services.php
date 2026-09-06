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
        // Généralisé sur plusieurs événements ({event: 'password_reset'|'welcome', ...})
        // — même webhook que l'ancien N8N_AUTH_WEBHOOK_URL.
        'auth_webhook_url' => env('N8N_AUTH_WEBHOOK_URL'),
        // Webhook du "Chat Trigger" n8n pour le bouton d'agent IA en bas à
        // gauche du site (voir resources/js/Components/ChatWidget.tsx).
        'chat_webhook_url' => env('N8N_CHAT_WEBHOOK'),
    ],

];
