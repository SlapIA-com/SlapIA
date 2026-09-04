<?php
/**
 * Avis clients affichés publiquement (page d'accueil), sourcés depuis la
 * table MySQL avis_clients (jointure clients pour entreprise/linkedin/job).
 * Remplace includes/notion-blog.php's usage in includes/notion.php-era
 * reviews.php (celui-ci ne dépend plus de Notion).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * @return array<int, array{prenom:string, nom:string, profession:string, avis:string,
 *                           note:?float, client_id:?int, status:string, entreprise:string, linkedin:string}>
 */
function getClientReviews(int $limit = 12): array
{
    $stmt = db()->prepare(
        'SELECT a.commentaire, a.satisfaction, a.prenom_nom,
                c.id AS client_id, c.job_domaine, c.type_client, c.nom_entreprise, c.linkedin
         FROM avis_clients a
         LEFT JOIN clients c ON c.id = a.client_id
         WHERE a.commentaire IS NOT NULL AND a.commentaire != \'\'
         ORDER BY a.created_at DESC, a.id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $reviews = [];
    foreach ($stmt->fetchAll() as $row) {
        $full   = trim($row['prenom_nom'] ?? '');
        $parts  = $full !== '' ? preg_split('/\s+/', $full, 2) : ['', ''];
        $prenom = $parts[0] ?? '';
        $nom    = $parts[1] ?? '';

        $reviews[] = [
            'prenom'     => $prenom,
            'nom'        => $nom,
            'profession' => $row['job_domaine'] ?? '',
            'avis'       => $row['commentaire'],
            'note'       => $row['satisfaction'] !== null ? (float)$row['satisfaction'] : null,
            'client_id'  => $row['client_id'] !== null ? (int)$row['client_id'] : null,
            'status'     => $row['type_client'] ?? '',
            'entreprise' => $row['nom_entreprise'] ?? '',
            'linkedin'   => $row['linkedin'] ?? '',
        ];
    }

    return $reviews;
}
