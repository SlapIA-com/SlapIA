<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notion.php';

/**
 * Canonical Notion "Sujet" select options, keyed by the form's stable slug
 * (not by translated display text — the same slug must resolve to the same
 * Notion option regardless of which language the visitor submitted the form
 * in). Must match exactly what's configured as Select options in Notion.
 */
const CONTACT_SUBJECT_OPTIONS = [
    'subject_1' => 'Session individuelle',
    'subject_2' => "Formation d'équipe",
    'subject_3' => 'Programme sur-mesure',
    'subject_4' => 'Devis / montage PC',
    'subject_5' => 'Diagnostic PC',
    'subject_6' => 'Autre question',
];

/** Resolves a subject slug (e.g. "subject_1") to its French Notion select label, or null if unknown. */
function resolveContactSubjectLabel(string $subjectSlug): ?string
{
    return CONTACT_SUBJECT_OPTIONS[$subjectSlug] ?? null;
}

/**
 * Creates a new entry in the public "Contact" Notion database from a
 * contact-form submission. Never touches "prise de contact ok ?" (an
 * internal admin follow-up flag, manually checked once someone has been
 * recontacted) or "Date de création" (auto-set by Notion on page creation).
 *
 * $subjectSlug is required — the form makes subject mandatory — and must be
 * a key from CONTACT_SUBJECT_OPTIONS.
 */
function submitContactMessage(string $prenom, string $nom, string $email, string $company, string $subjectSlug, string $message): bool
{
    $subjectLabel = resolveContactSubjectLabel($subjectSlug);
    if ($subjectLabel === null) {
        error_log('[SlapIA Contact] submitContactMessage rejected empty or unknown subject slug: ' . $subjectSlug);
        return false;
    }

    $dbId = config('NOTION_CONTACT_DATABASE_ID', '');
    if ($dbId === '') {
        error_log('[SlapIA Contact] submitContactMessage: NOTION_CONTACT_DATABASE_ID is not configured.');
        return false;
    }

    $result = notion()->request('POST', '/pages', [
        'parent' => ['database_id' => $dbId],
        'properties' => [
            'Prenom'           => ['title' => [['text' => ['content' => $prenom]]]],
            'Nom'              => ['rich_text' => $nom !== '' ? [['text' => ['content' => $nom]]] : []],
            'Email'            => ['email' => $email],
            "Nom d'entreprise" => ['rich_text' => $company !== '' ? [['text' => ['content' => $company]]] : []],
            'Sujet'            => $subjectLabel !== null ? ['select' => ['name' => $subjectLabel]] : ['select' => null],
            'Message'          => ['rich_text' => [['text' => ['content' => $message]]]],
        ],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Contact] submitContactMessage failed: ' . json_encode($result));
        return false;
    }

    return true;
}
