<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protège les routes API appelées par le workflow n8n de génération
 * d'articles (voir routes/api.php, Api\N8nArticleController). Ces routes
 * n'ont pas de session/CSRF (elles sont appelées depuis n8n, pas un
 * navigateur) : la clé partagée (N8N_ARTICLES_API_KEY) est le seul rempart.
 *
 * Comparaison en temps constant (hash_equals) pour éviter une timing
 * attack qui devinerait la clé caractère par caractère.
 */
class EnsureN8nApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.n8n.articles_api_key');
        $provided = (string) $request->header('X-N8N-Key', '');

        if ($expected === '' || !hash_equals($expected, $provided)) {
            abort(401, 'Clé API invalide ou manquante.');
        }

        return $next($request);
    }
}
