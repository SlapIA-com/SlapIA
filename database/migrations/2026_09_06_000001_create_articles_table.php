<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blog migré de Notion vers MySQL (6 septembre 2026). Le blog vivait
 * entièrement sur Notion (NotionBlogService) avec une automatisation n8n qui
 * y publiait un nouvel article tous les 2 jours — voir
 * app/Console/Commands/ImportNotionBlogArticles.php pour l'import unique du
 * contenu existant. Tant que le workflow n8n n'est pas réécrit pour publier
 * ici, les nouveaux articles se créent à la main via l'admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable(); // certains extraits Notion dépassent 255 caractères
            $table->longText('content')->nullable();
            $table->text('image')->nullable(); // certaines URLs Notion/S3 signées dépassent largement 255 caractères
            $table->timestamp('published_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
