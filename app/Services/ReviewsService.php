<?php

namespace App\Services;

use App\Models\AvisClient;

/** Port de includes/reviews.php — avis publics affichés sur la page d'accueil. */
class ReviewsService
{
    public function getPublicReviews(int $limit = 12): array
    {
        return AvisClient::with('client')
            ->whereNotNull('commentaire')
            ->where('commentaire', '!=', '')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (AvisClient $avis) {
                $full = trim($avis->prenom_nom ?? '');
                $parts = $full !== '' ? preg_split('/\s+/', $full, 2) : ['', ''];

                return [
                    'prenom' => $parts[0] ?? '',
                    'nom' => $parts[1] ?? '',
                    'profession' => $avis->client?->job_domaine ?? '',
                    'avis' => $avis->commentaire,
                    'note' => $avis->satisfaction !== null ? (float) $avis->satisfaction : null,
                    'client_id' => $avis->client_id,
                    'status' => $avis->client?->type_client ?? '',
                    'entreprise' => $avis->client?->nom_entreprise ?? '',
                    'linkedin' => $avis->client?->linkedin ?? '',
                ];
            })
            ->values()
            ->all();
    }
}
