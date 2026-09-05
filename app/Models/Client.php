<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $table = 'clients';

    protected $fillable = [
        'compte_id',
        'nom_complet',
        'nom_entreprise',
        'telephone',
        'location',
        'job_domaine',
        'linkedin',
        'type_client',
        'photo_path',
        'photo_mime',
        'notes',
        'commandes_libres',
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    public function prestations()
    {
        return $this->hasMany(Prestation::class)->orderBy('id');
    }

    public function factures()
    {
        return $this->hasMany(Facture::class)->orderBy('id');
    }

    public function avis()
    {
        return $this->hasMany(AvisClient::class);
    }

    public function latestAvis()
    {
        return $this->hasOne(AvisClient::class)->latestOfMany();
    }

    public function latestPrestation()
    {
        return $this->hasOne(Prestation::class)->latestOfMany();
    }

    /**
     * Convention héritée de Notion : type_client NULL = admin.
     * Ne jamais comparer directement `type_client` ailleurs dans le code —
     * toujours passer par ici (voir aussi App\Support\Role côté back et
     * useAuth() côté front).
     */
    public function role(): string
    {
        return match ($this->type_client) {
            'Entreprise' => 'entreprise',
            'Particulier' => 'particulier',
            default => 'admin',
        };
    }

    public function isAdmin(): bool
    {
        return $this->role() === 'admin';
    }
}
