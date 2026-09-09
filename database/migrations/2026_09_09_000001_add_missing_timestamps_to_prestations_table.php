<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige "Unknown column 'updated_at' in 'INSERT INTO'" à la création d'une
 * prestation (AdminController::storePrestation) : la table "prestations" en
 * prod n'a pas les colonnes created_at/updated_at pourtant définies dans la
 * migration d'origine (2026_01_01_000003) — le schéma réel a divergé des
 * migrations Laravel (reprise de l'ancien site). Colonnes ajoutées seulement
 * si absentes, pour être rejouable sans casser un environnement où elles
 * existent déjà (ex. les tests, sur SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            if (!Schema::hasColumn('prestations', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('prestations', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Pas de rollback : on ne sait pas si ces colonnes existaient avant
        // cette migration sur un environnement donné, mieux vaut ne rien
        // supprimer par erreur.
    }
};
