<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
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
