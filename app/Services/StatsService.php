<?php

namespace App\Services;

use App\Models\AvisClient;
use App\Models\Client;
use Illuminate\Support\Facades\Cache;

/** Port de includes/stats.php — cache 1h, mêmes calculs. */
class StatsService
{
    public function get(): array
    {
        return Cache::remember('slapia_stats', 3600, function () {
            $entreprises = Client::where('type_client', 'Entreprise')->count();
            $particuliers = Client::where('type_client', 'Particulier')->count();

            $avgSat = AvisClient::where('satisfaction', '>', 0)->avg('satisfaction');
            $satCount = AvisClient::where('satisfaction', '>', 0)->count();

            return [
                'entreprises' => $entreprises,
                'particuliers' => $particuliers,
                'satisfaction' => $avgSat !== null ? round((float) $avgSat, 1) : null,
                'satisfaction_count' => $satCount,
                'is_live' => true,
            ];
        });
    }
}
