<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prestation extends Model
{
    use HasFactory;

    protected $table = 'prestations';

    protected $fillable = [
        'client_id',
        'type_service',
        'prix',
        'statut_facturation',
        'description',
        'date_debut',
        'date_fin',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'decimal:2',
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public const STATUTS = ['Facturé', 'Payé', 'En cours', 'En attente', 'Dispensé'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
