# Espace client — Photo, LinkedIn, Avis, Factures en ligne — Design

Date : 2026-08-02
Statut : Validé par l'utilisateur.

## Contexte

Extension du dashboard client (déjà livré) avec 4 fonctionnalités demandées : modification de la
photo de profil, ajout du lien LinkedIn, modification/ajout d'un avis client, et ouverture des
factures PDF sans forcer le téléchargement.

## Découvertes techniques (changent l'approche initialement envisagée)

- **Pas de propriété "Photo" dans Notion.** L'avatar affiché partout sur le site (header,
  dashboard admin, témoignages publics) vient en réalité de l'**icône de la page Notion**
  (`api/notion-avatar.php` la lit en repli). L'API Notion Update Page accepte un `icon` de type
  `file_upload` — exactement le même mécanisme que l'upload de factures déjà construit. Donc :
  **aucune nouvelle propriété Notion à créer**, l'upload de photo écrit directement sur l'icône
  de la page.
- **Les témoignages publics (page d'accueil) utilisent déjà `api/notion-avatar.php?id=...`**
  pour l'avatar de chaque avis réel (`index.php:223`), pas une propriété "Photo" dédiée. Donc une
  photo uploadée par le client apparaît **automatiquement** sur son témoignage public — aucune
  modification de `includes/reviews.php` n'est nécessaire.
- Le cache des témoignages publics (`includes/reviews.php`) dure 1h — un avis modifié met donc
  jusqu'à 1h à apparaître publiquement. Comportement déjà existant, non modifié ici.

## Décisions actées avec l'utilisateur

- Avis : publication immédiate, pas de validation admin.
- Ajout : aperçu en direct du témoignage (comme il apparaîtra publiquement) à côté du formulaire
  d'avis, mis à jour pendant la saisie.
- Pas d'édition du champ "poste/métier" (Job) dans ce chantier (écarté explicitement).

## Fonctionnalités

### 1. Photo de profil
- Bouton "Changer la photo" dans la carte Profil du dashboard, avec aperçu de la photo actuelle
  (`api/notion-avatar.php?id=<son_id>`).
- Upload réel (PDF→remplacé par image ici) via l'API Notion File Upload (create + send, déjà
  construite dans `includes/notion.php`), puis `PATCH /pages/{id}` avec
  `icon: {type: "file_upload", file_upload: {id: ...}}`.
- Validation serveur : JPEG/PNG/WebP uniquement, taille max 5 Mo.

### 2. LinkedIn
- Champ texte dans la carte Profil (propriété Notion `Linkedin`, type url), avec validation
  basique côté serveur (doit commencer par `http://` ou `https://`, ou être vide pour effacer).

### 3. Avis client
- Nouvelle carte "Mon avis" : sélecteur d'étoiles (1 à 5, cliquable) + zone de texte libre.
- Écrit sur `Satisfaction` (select, une des 5 valeurs réelles `⭐` à `⭐⭐⭐⭐⭐`) et
  `Avis clients` (rich_text).
- Aperçu en direct à côté du formulaire, réutilisant telles quelles les classes CSS déjà en
  place pour les témoignages publics (`.review-item`, `.review-header`, `.review-avatar`,
  `.review-info`, `.review-name`, `.review-profession`, `.review-content-scroll`,
  `.review-text`, `.review-stars`) — rendu identique à ce qui s'affichera réellement sur la page
  d'accueil, pas une approximation.

### 4. Factures consultables sans téléchargement
- Nouveau endpoint `api/dashboard-view-invoice.php?index=N` : ré-interroge les données du compte
  connecté (jamais un ID/URL fourni par le client — uniquement un index dans SA propre liste de
  factures, éliminant tout risque de SSRF ou d'accès à un fichier d'un autre compte), relaie le
  fichier PDF avec `Content-Disposition: inline` et le bon `Content-Type` pour qu'il s'ouvre dans
  l'onglet au lieu de se télécharger. Remplace le lien direct vers l'URL Notion/S3 dans le
  dashboard.

## Architecture

- `includes/notion-client.php` étendu :
  - `getOwnAccountDetails()` retourne en plus `linkedin`, `review`, `satisfaction`.
  - `updateOwnLinkedin(string $pageId, string $linkedin): bool`
  - `updateOwnReview(string $pageId, string $reviewText, string $satisfaction): bool` (valide
    `$satisfaction` contre les 5 valeurs réelles avant écriture)
  - `uploadOwnPhoto(string $pageId, string $localFilePath, string $filename, string $mimeType): bool`
    (réutilise `createFileUpload()`/`sendFileUpload()` de `includes/notion.php`, puis
    `notion()->updatePage($pageId, ['icon' => [...]])`)
- Nouveaux endpoints : `api/dashboard-update-linkedin.php`, `api/dashboard-update-review.php`,
  `api/dashboard-upload-photo.php` (multipart), `api/dashboard-view-invoice.php` (proxy PDF).
- `pages/dashboard.php` : ajout d'un conteneur `#dashboard-avis` (nouvelle carte).
- `assets/css/dashboard.css` : styles pour le widget d'upload photo et le sélecteur d'étoiles.
- `assets/js/dashboard.js` : étendu pour rendre les nouveaux champs/formulaires, avec
  rafraîchissement des données après chaque sauvegarde réussie (même discipline que l'upload de
  facture côté admin).

## Sécurité

- Toutes les nouvelles routes gated par `requireLogin()`, écritures gated par CSRF.
- Cible toujours `currentUser()['id']` — jamais un ID fourni par le client.
- Endpoint de consultation de facture : accepte un index, jamais une URL — élimine tout risque de
  proxy ouvert (SSRF).
- Upload photo : validation MIME réelle (pas juste l'extension), taille plafonnée, nom de fichier
  assaini — même discipline que l'upload de factures déjà construit côté admin.
- Écritures Notion vérifiées (jamais de succès silencieux en cas d'échec réel) — même discipline
  que tout le reste du projet.

## Gestion des erreurs

- Réponses JSON cohérentes, messages génériques côté client, détails en `error_log()`.
- Échec d'upload photo ou de sauvegarde : message clair, jamais de faux succès.

## Hors scope

- Édition du champ "poste/métier" (Job) — explicitement écarté par l'utilisateur.
- Modération admin des avis — publication immédiate, pas de file d'attente de validation.
- Modification de l'email ou d'autres champs du profil au-delà de LinkedIn/photo/avis.
