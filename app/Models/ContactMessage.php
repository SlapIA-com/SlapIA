<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    protected $table = 'contact_siteweb';

    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'prenom',
        'nom',
        'nom_entreprise',
        'email',
        'sujet',
        'message',
        'prise_de_contact_ok',
        'date_creation',
    ];

    protected function casts(): array
    {
        return [
            'prise_de_contact_ok' => 'boolean',
            'date_creation' => 'datetime',
        ];
    }

    public const SUBJECTS = [
        'subject_1' => 'Session individuelle',
        'subject_2' => "Formation d'équipe",
        'subject_3' => 'Programme sur-mesure',
        'subject_4' => 'Devis / montage PC',
        'subject_5' => 'Diagnostic PC',
        'subject_6' => 'Autre question',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
