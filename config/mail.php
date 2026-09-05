<?php

// Non utilisé directement — les emails transactionnels (reset mot de passe)
// passent par le webhook n8n existant (voir PasswordResetController et
// N8N_AUTH_WEBHOOK_URL dans .env), pas par le mailer Laravel. Ce fichier
// existe juste parce que le framework s'y réfère par défaut ; "log" évite
// d'exiger des identifiants SMTP inutiles.
return [
    'default' => env('MAIL_MAILER', 'log'),

    'mailers' => [
        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'contact@slapia.com'),
        'name' => env('MAIL_FROM_NAME', 'SlapIA'),
    ],
];
