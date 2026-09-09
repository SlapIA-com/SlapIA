<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props partagées sur TOUTES les pages Inertia : utilisateur courant
     * (forme identique à currentUser() de l'ancien includes/auth.php),
     * langue active et dictionnaire de traductions complet (voir
     * resources/lang/{locale}.php — même structure que lang/fr.php).
     */
    public function share(Request $request): array
    {
        $compte = $request->user();
        $client = $compte?->client;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $compte ? [
                    'id' => $client?->id,
                    'email' => $compte->email,
                    'name' => $client?->nom_complet ?? '',
                    'role' => $client?->role() ?? 'admin',
                ] : null,
            ],
            'locale' => app()->getLocale(),
            // Chat IA (bouton bas gauche, voir ChatWidget.tsx) : partagé
            // partout, comme sur l'ancien site où il apparaissait sur
            // toutes les pages. Seul un booléen est exposé au front — le
            // widget appelle désormais /api/chat (même origine, voir
            // ChatController) plutôt que le webhook n8n directement depuis
            // le navigateur : ce dernier pointait vers le nom d'hôte/port
            // du NAS, que Chrome/Edge bloquent (Private Network Access)
            // dès qu'il résout vers une adresse locale — ce qui arrive par
            // exemple en testant le site depuis le même réseau que le NAS
            // (DNS "split-horizon" du DDNS Synology). Ça évite aussi
            // d'exposer cette URL interne dans le JS public.
            'chatEnabled' => (bool) config('services.n8n.chat_webhook_url'),
            // Fusionné sur le FR : si une clé manque (ou n'est pas encore
            // traduite) dans la langue active, on retombe sur sa valeur
            // française plutôt que de renvoyer un tableau incomplet — c'est
            // ce qui faisait planter le front (ex. Home/Courses/About/Pricing
            // font `t('levels').map(...)`, qui explose si 'levels' est absent
            // de la langue active au lieu de renvoyer un tableau vide/partiel).
            'translations' => array_replace_recursive(trans('messages', [], 'fr'), trans('messages')),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
