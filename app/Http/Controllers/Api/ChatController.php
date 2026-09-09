<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Proxy serveur pour le widget de chat (ChatWidget.tsx). Le navigateur
 * appelait auparavant le webhook n8n directement (nom d'hôte + port du
 * NAS) — bloqué par Chrome/Edge (Private Network Access) dès que ce nom
 * résout vers une adresse locale, ce qui arrive typiquement en testant le
 * site depuis le même réseau que le NAS (DNS "split-horizon" du DDNS
 * Synology). En passant par cette route, le navigateur ne parle qu'à
 * slapia.com : plus aucune dépendance au réseau du visiteur, et l'URL du
 * webhook n'est plus exposée dans le JS public.
 */
class ChatController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sessionId' => ['required', 'string', 'max:100'],
            'chatInput' => ['required', 'string', 'max:2000'],
        ]);

        $webhook = config('services.n8n.chat_webhook_url');
        if (!$webhook) {
            return response()->json(['output' => "Désolé, l'assistant n'est pas disponible pour le moment."], 503);
        }

        try {
            $response = Http::timeout(20)->post($webhook, [
                'action' => 'sendMessage',
                'sessionId' => $data['sessionId'],
                'chatInput' => $data['chatInput'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['output' => "Désolé, je n'ai pas de réponse pour le moment."], 502);
        }

        // Relaie tel quel le corps renvoyé par n8n (tableau ou objet, selon
        // le nœud "Respond to Webhook" côté workflow) : ChatWidget.tsx sait
        // déjà lire les deux formes, pas besoin de les normaliser ici.
        return response()->json($response->json() ?? [], $response->successful() ? 200 : 502);
    }
}
