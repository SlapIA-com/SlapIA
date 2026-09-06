<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La colonne "excerpt" avait été créée en varchar(255) (défaut de
 * ->string()), mais plusieurs extraits d'articles Notion dépassent cette
 * longueur (import du 6 septembre 2026 : "Data too long for column
 * excerpt"). Passage en TEXT. Requête SQL brute plutôt que ->change()
 * fluent pour éviter la dépendance à doctrine/dbal.
 *
 * "ALTER TABLE ... MODIFY" est une syntaxe MySQL, absente de SQLite : sans
 * garde, cette migration casse toute la suite de tests (RefreshDatabase
 * utilise SQLite en mémoire). Elle ne sert qu'à corriger une table MySQL
 * déjà créée avec l'ancien varchar(255) ; sur une base neuve (SQLite des
 * tests, ou un futur déploiement MySQL), la migration 000001 crée déjà la
 * colonne en TEXT, donc ce correctif n'a de sens que sur MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE articles MODIFY excerpt TEXT NULL');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE articles MODIFY excerpt VARCHAR(255) NULL');
        }
    }
};
