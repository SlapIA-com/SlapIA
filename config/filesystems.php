<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    // 'local' = storage/app dans le conteneur, monté sur le volume
    // ./storage du NAS (voir docker-compose.additions.md) — c'est là que
    // vont les factures PDF et les photos de profil uploadées.
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
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
