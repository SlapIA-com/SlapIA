<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    // 'local' pointe directement sur storage/ (pas storage/app/) pour
    // matcher exactement l'ancien STORAGE_DIR de l'ancien site
    // (includes/db.php : STORAGE_DIR = __DIR__.'/../storage') — les
    // avatars ("avatars/<id>.<ext>") et factures ("invoices/<id>/<fichier>")
    // déjà présents sur le disque restent lisibles sans rien déplacer.
    // Monté en entier sur le volume ./storage du NAS, voir
    // docker-compose.additions.md.
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path(),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
