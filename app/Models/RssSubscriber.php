<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RssSubscriber extends Model
{
    use HasFactory;

    protected $table = 'rss_subscriber';

    public $timestamps = false;

    protected $fillable = ['email', 'date_creation'];

    protected function casts(): array
    {
        return ['date_creation' => 'datetime'];
    }
}
