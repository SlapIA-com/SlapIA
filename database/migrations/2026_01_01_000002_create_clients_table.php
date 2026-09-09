<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table "clients" — fiche client, 1-1 avec "comptes" (compte_id).
 * type_client NULL = compte admin (convention héritée de Notion, volontaire).
 * Reverse-engineered from includes/admin-accounts.php, includes/client-account.php,
 * includes/users.php, includes/stats.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compte_id')->constrained('comptes')->cascadeOnDelete();
            $table->string('nom_complet');
            $table->string('nom_entreprise')->nullable();
            $table->string('telephone')->nullable();
            $table->string('location')->nullable();
            $table->string('job_domaine')->nullable();
            $table->string('linkedin')->nullable();
            // NULL = admin ; 'Particulier' ; 'Entreprise'
            $table->enum('type_client', ['Particulier', 'Entreprise'])->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_mime')->nullable();
            $table->text('notes')->nullable();
            $table->text('commandes_libres')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
