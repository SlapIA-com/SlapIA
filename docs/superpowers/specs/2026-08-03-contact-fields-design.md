# Champs de contact manquants (Téléphone / Adresse / Commandes) — Design

Date : 2026-08-03
Statut : Validé par l'utilisateur.

## Contexte

Trois propriétés existent déjà dans la base Notion "Satisfaction" (l'ERP/comptes) mais ne sont
exposées nulle part sur le site : `Téléphone` (phone_number), `Location` (rich_text, adresse),
`Différentes commandes` (rich_text, historique des commandes/services souscrits). Ce chantier les
rend visibles et éditables aux bons endroits, sans créer de nouvelle propriété Notion.

Premier sous-projet d'une série de trois choisis par l'utilisateur pour "pousser au maximum" les
espaces client et admin déjà livrés (les deux autres : recherche/stats admin avancées, et
préférences email + PDF récap côté client — traités séparément par la suite).

## Répartition des responsabilités

- `Téléphone` et `Location` : éditables par le **client** (comme LinkedIn déjà construit), visibles
  par l'admin.
- `Différentes commandes` : gérée par l'**admin** (historique opérationnel des services/commandes),
  affichée en **lecture seule** côté client.

## Fonctionnalités

### 1. Client — Téléphone et adresse éditables
- Deux nouveaux champs dans la carte Profil du dashboard, même pattern que LinkedIn : valeur
  actuelle pré-remplie, input + bouton "Enregistrer" par champ.
- `Téléphone` : validation basique côté serveur (chiffres, espaces, `+`, `-`, `.`, `(` `)` uniquement ;
  vide autorisé pour effacer).
- `Location` : texte libre, pas de validation de format particulière (vide autorisé pour effacer).

### 2. Client — Commandes en lecture seule
- Nouvelle ligne dans la carte Profil (ou juste sous la carte Facturation) affichant le contenu de
  `Différentes commandes` tel quel, sans possibilité d'édition. Si vide, affiche un message neutre
  ("Aucune commande enregistrée pour le moment.").

### 3. Admin — édition des 3 champs par compte
- Dans le tableau des comptes (`#admin-tab-accounts`), ajout d'un bouton "Détails" dans la colonne
  Actions de chaque ligne.
- Au clic, une ligne supplémentaire se déplie sous la ligne du compte, avec 3 champs éditables
  (Téléphone, Adresse, Commandes) pré-remplis et un bouton "Enregistrer" unique qui envoie les 3
  valeurs en une seule requête.
- Toggle : un second clic sur "Détails" (ou sur un bouton "Fermer") replie la ligne.

## Architecture

- `includes/notion-client.php` :
  - `getOwnAccountDetails()` renvoie en plus `phone`, `location`, `orders`.
  - `updateOwnPhone(string $pageId, string $phone): bool`
  - `updateOwnLocation(string $pageId, string $location): bool`
- `includes/notion-admin.php` :
  - `listAllAccounts()` renvoie en plus `phone`, `location`, `orders` par compte.
  - `updateAccountContactDetails(string $pageId, string $phone, string $location, string $orders): bool`
    (une seule fonction pour les 3 champs admin, écrits ensemble depuis le même formulaire déplié)
- Nouveaux endpoints :
  - `api/dashboard-update-contact.php` (POST JSON `{phone, location}`, CSRF header, cible
    `currentUser()['id']` uniquement)
  - `api/admin-update-contact-exec.php` (POST JSON `{page_id, phone, location, orders}`, CSRF header,
    `requireAdmin()` — ce endpoint agit légitimement sur un compte tiers, comme les endpoints admin
    existants)
- `pages/dashboard.php` : pas de nouveau conteneur, extension du rendu JS de la carte Profil
  existante + nouvelle petite carte "Commandes" en lecture seule.
- `assets/js/dashboard.js` : extension du rendu de la carte Profil (2 nouveaux champs éditables +
  bloc lecture seule).
- `assets/js/admin.js` : extension de `accountRowHtml()` et de `renderAccounts()` pour le
  toggle "Détails" + ligne dépliable.
- `assets/css/admin.css` : styles pour la ligne dépliée.

## Sécurité

- `api/dashboard-update-contact.php` : `requireLogin()`, CSRF header, cible uniquement
  `currentUser()['id']`.
- `api/admin-update-contact-exec.php` : `requireAdmin()`, CSRF header — accepte un `page_id` car
  c'est un endpoint admin légitimement multi-comptes (même modèle que
  `api/admin-update-account-exec.php` déjà en place).
- Validation téléphone côté serveur avant écriture (charset restreint), pas de validation de
  format sur adresse/commandes (texte libre).

## Gestion des erreurs

- Même pattern que le reste du projet : écriture Notion vérifiée (`http_code >= 300` ou `error`),
  `error_log()` + retour `false` sur échec, jamais de succès silencieux.
- Réponses JSON cohérentes, messages via `t('dashboard.xxx')` / `t('admin.xxx')`.

## i18n

- Nouvelles clés `dashboard.*` (label téléphone, label adresse, label commandes, messages
  d'erreur/succès) et `admin.*` (bouton Détails, labels des 3 champs, message de succès) dans
  `lang/fr.php`, `lang/en.php`, `lang/de.php`.

## Hors scope

- Validation de format d'adresse (pas de découpage rue/ville/code postal — un seul champ texte
  libre, comme la propriété Notion `Location` elle-même).
- Historique des modifications des commandes (pas de journal, juste la valeur courante).
