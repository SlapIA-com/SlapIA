<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Même souci que excerpt (voir migration 000002) : les URLs d'image Notion
 * sont parfois des liens S3 pré-signés très longs (avec toute la query
 * string de signature AWS), largement au-delà de varchar(255). Import du
 * 6 septembre 2026 : "Data too long for column image" sur le 54e article.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE articles MODIFY image TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE articles MODIFY image VARCHAR(255) NULL');
    }
};
