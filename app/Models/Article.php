<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'excerpt', 'content', 'image', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    /** Même algorithme que NotionBlogService::slugify() (garde des slugs cohérents avec les anciens articles importés de Notion). */
    public static function slugify(string $title): string
    {
        $accentMap = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
            'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ñ' => 'n', 'ý' => 'y', 'ÿ' => 'y',
        ];
        $slug = strtr(mb_strtolower($title), $accentMap);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        return trim($slug, '-');
    }

    /**
     * Slug unique dérivé du titre (ajoute -2, -3... en cas de collision).
     * Utilisé par l'admin (BlogArticleController) et par l'import
     * automatique n8n (Api\N8nArticleController) — même logique aux deux
     * endroits plutôt que dupliquée.
     */
    public static function uniqueSlugFor(string $title): string
    {
        $slug = static::slugify($title);
        $base = $slug;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }
}
