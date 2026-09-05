<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table "avis_clients" — témoignages affichés publiquement sur la page
 * d'accueil (voir includes/reviews.php). client_id nullable pour tolérer un
 * avis historique dont le client aurait été supprimé (LEFT JOIN côté lecture).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('prenom_nom')->nullable();
            $table->unsignedTinyInteger('satisfaction')->nullable(); // 1..5
            $table->text('commentaire')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis_clients');
    }
};
