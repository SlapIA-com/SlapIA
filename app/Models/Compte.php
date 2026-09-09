<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Identifiants de connexion. L'authentification Laravel se fait sur ce
 * modèle (email + mot_de_passe_hash), la fiche "métier" vit dans Client.
 */
class Compte extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'comptes';

    protected $fillable = [
        'email',
        'mot_de_passe_hash',
        'mail_avis',
        'reset_token',
        'reset_token_expiry',
        'derniere_connexion',
    ];

    protected $hidden = [
        'mot_de_passe_hash',
        'reset_token',
    ];

    protected function casts(): array
    {
        return [
            'mail_avis' => 'boolean',
            'reset_token_expiry' => 'datetime',
            'derniere_connexion' => 'datetime',
        ];
    }

    /** Laravel's auth system expects getAuthPassword() to return the hash. */
    public function getAuthPassword(): string
    {
        return $this->mot_de_passe_hash ?? '';
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }
}
