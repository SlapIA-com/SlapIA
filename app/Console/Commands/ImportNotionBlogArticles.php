<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\Notion\NotionBlogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Import ponctuel des articles existants sur Notion vers la table MySQL
 * "articles", lors de la bascule du blog (6 septembre 2026). Se relance sans
 * risque (idempotent) : à la fois pour importer les nouveaux articles et
 * pour "réparer" les images des articles déjà importés (voir downloadImage).
 *
 * IMPORTANT sur les images : les fichiers uploadés directement dans Notion
 * (pas les liens externes type ibb.co) sont servis par Notion via des URLs
 * S3 pré-signées qui EXPIRENT AU BOUT D'1H (X-Amz-Expires=3600). Une image
 * enregistrée telle quelle en base devient donc une image cassée au bout
 * d'une heure. Cette commande télécharge chaque image et la stocke en local
 * (storage/app/public/blog, servi via /storage/blog/...) pour qu'elle reste
 * disponible indéfiniment, plutôt que de garder l'URL Notion éphémère.
 *
 * Nécessite NOTION_API_KEY dans l'environnement au moment de l'exécution
 * (peut être retiré du .env juste après, cette commande ne s'en sert que
 * pour cet import/cette réparation).
 */
class ImportNotionBlogArticles extends Command
{
    protected $signature = 'blog:import-notion {--dry-run : Afficher ce qui serait fait sans rien écrire en base ni télécharger d\'images}';

    protected $description = 'Importe les articles Notion vers MySQL et rapatrie leurs images en local (URLs Notion éphémères)';

    public function handle(NotionBlogService $blog): int
    {
        $articles = $blog->listArticles();

        if (empty($articles)) {
            $this->error('Aucun article récupéré depuis Notion — vérifie NOTION_API_KEY et la connexion réseau.');
            return self::FAILURE;
        }

        $this->info(sprintf('%d article(s) trouvé(s) sur Notion.', count($articles)));

        $imported = 0;
        $repaired = 0;
        $skipped = 0;
        $dryRun = (bool) $this->option('dry-run');

        foreach ($articles as $summary) {
            $existing = Article::where('slug', $summary['slug'])->first();

            $full = $blog->findBySlug($summary['slug']);
            if ($full === null) {
                $this->warn("  - impossible de récupérer le contenu, ignoré : {$summary['title']}");
                continue;
            }

            $localImage = null;
            if ($full['image'] && !$dryRun) {
                $localImage = $this->downloadImage($full['image'], $full['slug']);
            }

            if ($existing) {
                if ($localImage && !$dryRun) {
                    $existing->update(['image' => $localImage]);
                    $this->line("  - déjà présent, image rapatriée en local : {$full['title']}");
                    $repaired++;
                } else {
                    $this->line("  - déjà présent, ignoré : {$full['title']}".(!$dryRun && $full['image'] && !$localImage ? ' (image introuvable, laissée telle quelle)' : ''));
                    $skipped++;
                }
                continue;
            }

            $this->line("  - à importer : {$full['title']}".(!$dryRun && $full['image'] && !$localImage ? ' (image introuvable, article importé sans image)' : ''));

            if (!$dryRun) {
                Article::create([
                    'title' => $full['title'],
                    'slug' => $full['slug'],
                    'excerpt' => $full['excerpt'],
                    'content' => $full['content'],
                    'image' => $localImage,
                    'published_at' => $full['date'],
                ]);
            }

            $imported++;
        }

        $this->info(sprintf(
            '%s%d importé(s), %d image(s) rapatriée(s) sur des articles déjà présents, %d ignoré(s) sans changement.',
            $dryRun ? '[dry-run] ' : '',
            $imported,
            $repaired,
            $skipped
        ));

        return self::SUCCESS;
    }

    /** Télécharge une image (URL Notion/S3 éphémère ou externe) et la stocke en local. Renvoie null si le téléchargement échoue (lien mort, timeout...). */
    private function downloadImage(string $url, string $slug): ?string
    {
        try {
            $response = Http::timeout(15)->get($url);
            if (!$response->successful()) {
                return null;
            }

            $contentType = $response->header('Content-Type') ?? '';
            $ext = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif') => 'gif',
                default => 'jpg',
            };

            $path = "blog/{$slug}.{$ext}";
            Storage::disk('public')->put($path, $response->body());

            // Chemin relatif (pas Storage::url(), qui dépend de APP_URL) :
            // fonctionne quel que soit le domaine depuis lequel le site est servi.
            return '/storage/'.$path;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
