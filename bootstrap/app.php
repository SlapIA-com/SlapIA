<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        // Le site tourne derrière le reverse-proxy du NAS (Docker) : sans ça,
        // $request->ip() renvoie l'IP interne du proxy pour TOUS les
        // visiteurs, ce qui rend le rate-limiting par IP (login, formulaire
        // de contact) inefficace — une seule personne qui spam bloquerait
        // tout le monde pendant 15 min. TRUSTED_PROXIES='*' fait confiance
        // aux en-têtes X-Forwarded-* de N'IMPORTE QUELLE IP amont : à
        // n'utiliser QUE si le conteneur applicatif n'est JAMAIS exposé
        // directement à Internet (seul le reverse-proxy l'est) — sinon
        // n'importe qui pourrait usurper son IP via un en-tête
        // X-Forwarded-For et contourner tous les rate-limits.
        // Pour restreindre à l'IP/sous-réseau réel du proxy à la place :
        // TRUSTED_PROXIES=172.18.0.0/16 (ou l'IP fixe du conteneur proxy).
        // Par défaut (variable absente) : aucun proxy de confiance, donc
        // aucun changement de comportement tant que ce n'est pas configuré.
        $trustedProxies = trim((string) env('TRUSTED_PROXIES', ''));
        $middleware->trustProxies(
            at: $trustedProxies === '*' ? '*' : array_filter(explode(',', $trustedProxies)),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Rend les erreurs HTTP (404, 403, 419, 500...) via la page React
        // Pages/Error.tsx plutôt que les pages d'erreur Blade par défaut,
        // pour garder une expérience cohérente avec le reste du site.
        $exceptions->respond(function ($response, $e, $request) {
            $status = $response->getStatusCode();

            if (in_array($status, [403, 404, 419, 429, 500, 503], true) && !app()->environment('local')) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
