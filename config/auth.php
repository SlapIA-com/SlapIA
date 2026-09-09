<?php

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'comptes',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'comptes',
        ],
    ],

    /*
     * Le provider "comptes" authentifie sur App\Models\Compte (email +
     * mot_de_passe_hash), pas sur un modèle "User" générique — reprend
     * exactement la table existante includes/db.php.
     */
    'providers' => [
        'comptes' => [
            'driver' => 'eloquent',
            'model' => App\Models\Compte::class,
        ],
    ],

    'passwords' => [
        'comptes' => [
            'provider' => 'comptes',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
