<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La colonne "excerpt" avait été créée en varchar(255) (défaut de
 * ->string()), mais plusieurs extraits d'articles Notion dépassent cette
 * longueur (import du 6 septembre 2026 : "Data too long for column
 * excerpt"). Passage en TEXT. Requête SQL brute plutôt que ->change()
 * fluent pour éviter la dépendance à doctrine/dbal.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE articles MODIFY excerpt TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE articles MODIFY excerpt VARCHAR(255) NULL');
    }
};
