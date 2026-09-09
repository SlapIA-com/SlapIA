<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Même souci que excerpt (voir migration 000002) : les URLs d'image Notion
 * sont parfois des liens S3 pré-signés très longs (avec toute la query
 * string de signature AWS), largement au-delà de varchar(255). Import du
 * 6 septembre 2026 : "Data too long for column image" sur le 54e article.
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
            DB::statement('ALTER TABLE articles MODIFY image TEXT NULL');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE articles MODIFY image VARCHAR(255) NULL');
        }
    }
};
