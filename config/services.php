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
        // droite du site (voir resources/js/Components/ChatWidget.tsx).
        'chat_webhook_url' => env('N8N_CHAT_WEBHOOK'),
        // Clé partagée pour l'API de création d'articles (routes/api.php,
        // Api\N8nArticleController, EnsureN8nApiKey) — appelée par le
        // workflow n8n "Split & Publish" pour écrire directement dans
        // MySQL, à la place de l'ancienne page Notion.
        'articles_api_key' => env('N8N_ARTICLES_API_KEY'),
    ],

];
