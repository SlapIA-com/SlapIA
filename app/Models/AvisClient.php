<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvisClient extends Model
{
    use HasFactory;

    protected $table = 'avis_clients';

    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'prenom_nom',
        'satisfaction',
        'commentaire',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'satisfaction' => 'integer',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
