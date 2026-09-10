<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Petit helper partagé par tous les appels serveur → webhook n8n
 * (ChatController, ContactController, AdminController,
 * PasswordResetController...). Résout le problème de NAT en boucle
 * (hairpin) : avec l'ancien montage en redirection de port, appeler depuis
 * le conteneur applicatif le nom DDNS public du NAS (ex.
 * synologynasthomas.synology.me) revenait à sortir vers Internet puis
 * rentrer sur la même machine par son adresse publique — beaucoup de
 * routeurs refusent ce trajet, ce qui échoue immédiatement (cURL error 7).
 * Si N8N_INTERNAL_IP est renseigné dans .env (l'IP locale du NAS, celle
 * déjà utilisée pour MySQL), on force cURL à s'y connecter directement pour
 * CE nom d'hôte précis, tout en gardant le nom d'hôte dans l'URL/le SNI TLS
 * — le certificat continue donc à être validé normalement, et le routage
 * par Host header côté reverse-proxy n8n n'est pas affecté. Sans la
 * variable (dev, ou une prod qui n'a pas ce souci), aucun changement de
 * comportement. Depuis le passage de n8n derrière un tunnel
 * (n8n.javabien.ovh), ce contournement n'est probablement plus nécessaire
 * — la variable N8N_INTERNAL_IP peut être vidée une fois confirmé, sans
 * avoir à toucher ce fichier.
 */
class N8nWebhook
{
    public static function client(string $url, int $timeoutSeconds = 10): PendingRequest
    {
        $request = Http::timeout($timeoutSeconds);

        $internalIp = config('services.n8n.internal_ip');
        $host = parse_url($url, PHP_URL_HOST);
        if ($internalIp && $host) {
            $port = parse_url($url, PHP_URL_PORT) ?? (str_starts_with($url, 'https://') ? 443 : 80);
            $request = $request->withOptions([
                'curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$internalIp}"]],
            ]);
        }

        return $request;
    }
}
