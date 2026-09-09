<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table "rss_subscriber" (nommée au singulier côté legacy, on garde le nom
 * de table exact pour rester compatible avec les données existantes —
 * seul le nom du modèle Eloquent est au pluriel côté code PHP).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rss_subscriber', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamp('date_creation')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rss_subscriber');
    }
};
