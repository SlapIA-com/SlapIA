<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Le site est servi en HTTPS via Cloudflare/Caddy, mais si
        // TRUSTED_PROXIES (voir bootstrap/app.php) n'est pas — ou mal —
        // configuré côté prod, Laravel ne sait pas que la requête d'origine
        // était en HTTPS et régénère ses redirections/URLs en http:// — le
        // navigateur bloque alors ces requêtes comme "contenu mixte" (ex:
        // après l'envoi du formulaire de contact ou une connexion). Filet
        // de sécurité indépendant de la config proxy : on force
        // explicitement le schéma https dès que l'environnement n'est pas
        // local. Ici plutôt que dans bootstrap/app.php : le callback
        // withMiddleware() y tourne avant que les façades ne soient prêtes
        // (ça cassait même `composer install`/`artisan package:discover`,
        // pas seulement les requêtes) — boot() d'un provider est le point
        // garanti sûr pour ça, aussi bien en HTTP qu'en CLI.
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }
    }
}
