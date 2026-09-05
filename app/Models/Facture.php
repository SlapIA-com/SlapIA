<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $table = 'factures';

    protected $fillable = [
        'client_id',
        'nom_fichier',
        'chemin_fichier',
        'mime_type',
        'taille_octets',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
