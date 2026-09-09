<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige "Unknown column 'remember_token' in 'SET'" à la connexion
 * (Auth::attempt() dans AuthenticatedSessionController::store) : la
 * migration d'origine de "comptes" (2026_01_01_000001) n'a jamais déclaré
 * cette colonne, alors que Compte extends Authenticatable en a besoin dès
 * qu'un utilisateur se connecte (avec ou sans "se souvenir de moi" — Laravel
 * la régénère systématiquement à chaque connexion). Ajoutée seulement si
 * absente, pour être rejouable sans casser un environnement où elle existe
 * déjà (ex. les tests, sur SQLite, où la colonne pourrait avoir été ajoutée
 * autrement).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('comptes', 'remember_token')) {
            Schema::table('comptes', function (Blueprint $table) {
                $table->rememberToken();
            });
        }
    }

    public function down(): void
    {
        // Pas de rollback : colonne standard Laravel, aucune raison de la
        // supprimer même si cette migration est annulée sur un
        // environnement où elle a fini par exister par un autre biais.
    }
};
