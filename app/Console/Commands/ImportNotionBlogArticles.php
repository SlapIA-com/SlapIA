<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\Notion\NotionBlogService;
use Illuminate\Console\Command;

/**
 * Import ponctuel des articles existants sur Notion vers la table MySQL
 * "articles", lors de la bascule du blog (6 septembre 2026). À lancer UNE
 * FOIS : `php artisan blog:import-notion`.
 *
 * Nécessite NOTION_API_KEY dans l'environnement au moment de l'exécution
 * (peut être retiré du .env juste après, cette commande ne s'en sert que
 * pour cet import unique).
 */
class ImportNotionBlogArticles extends Command
{
    protected $signature = 'blog:import-notion {--dry-run : Afficher ce qui serait importé sans rien écrire en base}';

    protected $description = 'Importe une fois les articles existants de Notion vers la table MySQL articles';

    public function handle(NotionBlogService $blog): int
    {
        $articles = $blog->listArticles();

        if (empty($articles)) {
            $this->error('Aucun article récupéré depuis Notion — vérifie NOTION_API_KEY et la connexion réseau.');
            return self::FAILURE;
        }

        $this->info(sprintf('%d article(s) trouvé(s) sur Notion.', count($articles)));

        $imported = 0;
        $skipped = 0;

        foreach ($articles as $summary) {
            if (Article::where('slug', $summary['slug'])->exists()) {
                $this->line("  - déjà présent, ignoré : {$summary['title']}");
                $skipped++;
                continue;
            }

            $full = $blog->findBySlug($summary['slug']);
            if ($full === null) {
                $this->warn("  - impossible de récupérer le contenu, ignoré : {$summary['title']}");
                continue;
            }

            $this->line("  - à importer : {$full['title']}");

            if (!$this->option('dry-run')) {
                Article::create([
                    'title' => $full['title'],
                    'slug' => $full['slug'],
                    'excerpt' => $full['excerpt'],
                    'content' => $full['content'],
                    'image' => $full['image'],
                    'published_at' => $full['date'],
                ]);
            }

            $imported++;
        }

        $this->info(sprintf(
            '%s%d importé(s), %d déjà présent(s) ignoré(s).',
            $this->option('dry-run') ? '[dry-run] ' : '',
            $imported,
            $skipped
        ));

        return self::SUCCESS;
    }
}
