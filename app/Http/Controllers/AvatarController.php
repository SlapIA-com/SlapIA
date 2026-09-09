<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Support\Facades\Storage;

/** Port de api/avatar.php — sert la photo de profil d'un client, ou 404 (le front gère le repli sur les initiales). */
class AvatarController extends Controller
{
    public function show(int $clientId)
    {
        $client = Client::find($clientId);

        if (!$client || !$client->photo_path || !Storage::disk('local')->exists($client->photo_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($client->photo_path, null, [
            'Content-Type' => $client->photo_mime ?? 'image/jpeg',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
