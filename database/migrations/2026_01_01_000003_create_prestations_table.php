<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table "prestations" — services/formations vendus à un client, avec statut
 * de facturation. Un client peut avoir plusieurs prestations.
 * Reverse-engineered from includes/admin-accounts.php (5 valeurs réelles de
 * statut_facturation confirmées dans docs/superpowers/specs/2026-07-30-*).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('type_service')->nullable();
            $table->decimal('prix', 10, 2)->nullable();
            $table->enum('statut_facturation', ['Facturé', 'Payé', 'En cours', 'En attente', 'Dispensé'])
                ->default('En attente');
            $table->text('description')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestations');
    }
};
