<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Formulaire de contact public → table MySQL contact_siteweb.
 * Remplace includes/notion-contact.php.
 */
const CONTACT_SUBJECT_OPTIONS = [
    'subject_1' => 'Session individuelle',
    'subject_2' => "Formation d'équipe",
    'subject_3' => 'Programme sur-mesure',
    'subject_4' => 'Devis / montage PC',
    'subject_5' => 'Diagnostic PC',
    'subject_6' => 'Autre question',
];

function resolveContactSubjectLabel(string $subjectSlug): ?string
{
    return CONTACT_SUBJECT_OPTIONS[$subjectSlug] ?? null;
}

/**
 * Enregistre un message du formulaire de contact public.
 * $subjectSlug doit être une clé de CONTACT_SUBJECT_OPTIONS.
 */
function submitContactMessage(string $prenom, string $nom, string $email, string $company, string $subjectSlug, string $message): bool
{
    $subjectLabel = resolveContactSubjectLabel($subjectSlug);
    if ($subjectLabel === null) {
        error_log('[SlapIA Contact] submitContactMessage rejected empty or unknown subject slug: ' . $subjectSlug);
        return false;
    }

    try {
        // Note : les paramètres nommés ne peuvent pas être réutilisés deux fois
        // dans une même requête (PDO::ATTR_EMULATE_PREPARES=false) — d'où
        // :email_lookup et :email_insert bindés séparément à la même valeur.
        $stmt = db()->prepare(
            'INSERT INTO contact_siteweb (client_id, prenom, nom, nom_entreprise, email, sujet, message, prise_de_contact_ok, date_creation)
             VALUES ((SELECT id FROM clients WHERE compte_id = (SELECT id FROM comptes WHERE email = :email_lookup)),
                     :prenom, :nom, :company, :email_insert, :sujet, :message, 0, NOW())'
        );
        $stmt->execute([
            'prenom'       => $prenom,
            'nom'          => $nom !== '' ? $nom : null,
            'email_lookup' => $email,
            'company'      => $company !== '' ? $company : null,
            'email_insert' => $email,
            'sujet'        => $subjectLabel,
            'message'      => $message,
        ]);
        return true;
    } catch (Throwable $e) {
        error_log('[SlapIA Contact] submitContactMessage failed: ' . $e->getMessage());
        return false;
    }
}
