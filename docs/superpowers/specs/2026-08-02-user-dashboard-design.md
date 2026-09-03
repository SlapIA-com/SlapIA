# Espace utilisateur (client) — Design

Date : 2026-08-02
Statut : Validé par l'utilisateur.

## Contexte

Dernier morceau manquant de la demande initiale : un client connecté (`particulier` ou
`entreprise`) atterrit aujourd'hui sur `/dashboard`, qui n'existe pas. Ce sous-projet construit
cette page, sur le modèle de ce qui a déjà été fait pour le dashboard admin (mêmes conventions
Notion, mêmes conventions de sécurité), mais scopée à un seul compte : le sien.

## Direction visuelle

L'utilisateur veut un rendu "vraiment pro, comme un vrai site de vente" — dans l'esprit d'un
portail client SaaS (type Stripe customer portal) ou d'un espace "Mes commandes" e-commerce :
hiérarchie claire, badges de statut soignés (pas de simple texte brut), un résumé en tête de
page, des lignes de facture qui ressemblent à de vraies lignes de facturation (pas une liste à
puces basique). Réutilise le design system déjà en place (cartes, badges `.admin-badge` /
équivalent client, tokens de couleur `--signal`, `--forest`, etc.) plutôt que d'inventer un
nouveau langage visuel — la qualité vient de l'exécution soignée, pas de nouveaux éléments.

## Décisions actées avec l'utilisateur

- Page unique en sections empilées (pas d'onglets — un seul compte à afficher, contrairement à
  l'admin qui gère une liste).
- Le client peut changer son propre mot de passe depuis son espace (ancien mot de passe requis).
- Consultation uniquement pour le reste (profil, facturation, factures) — toute autre
  modification continue de passer par l'admin ou Notion.

## Architecture

- **Page** : `pages/dashboard.php`, protégée par `requireLogin()` (déjà en place). Si un admin y
  accède directement, redirection vers `/admin` (symétrique avec `/login` et `/reset-password`
  qui redirigent déjà les admins vers `/admin`).
- **Backend** : nouveau `includes/notion-client.php` — `getOwnAccountDetails(string $pageId): ?array`
  (mêmes champs que `listAllAccounts()` côté admin — nom, email, entreprise, service, statut de
  facturation, prix, factures avec nom+URL, dernière connexion — mais pour un seul compte, jamais
  une liste).
- **API** :
  - `api/dashboard-data.php` (GET, `requireLogin()`) → les données du compte de l'utilisateur
    connecté (son ID vient de la session, jamais d'un paramètre client).
  - `api/dashboard-change-password.php` (POST, `requireLogin()` + CSRF) → vérifie l'ancien mot de
    passe via `verifyPassword()` (déjà existant) avant d'appeler `updatePassword()` (déjà
    existant, réutilisé tel quel — aucune logique de hash dupliquée).
- **Nav** : le lien "Mon espace" existe déjà dans le header pour les rôles non-admin — rien à
  changer.

## Contenu de la page

1. En-tête : salutation + nom du client, résumé compact (statut de facturation en évidence).
2. Carte profil : email, entreprise, formation/service suivi.
3. Statut de facturation : badge visuel + prix, dans le même esprit que les badges déjà utilisés
   côté admin (`admin-badge`), adapté au contexte client.
4. Liste de factures : chaque ligne dans un style "ligne de facturation" (nom du fichier, action
   de téléchargement/consultation claire) — réutilise le même mécanisme d'URL Notion (fichier
   `file`/`external`) déjà construit pour l'admin, pas de nouvelle logique d'extraction.
5. Formulaire de changement de mot de passe : ancien mot de passe, nouveau mot de passe (8
   caractères min., même règle que le reste du site), confirmation.

## Sécurité

- Chaque endpoint déduit l'utilisateur cible de la session (`currentUser()['id']`) — jamais d'ID
  de compte accepté en paramètre côté client, donc aucune possibilité de consulter/modifier le
  compte de quelqu'un d'autre.
- Changement de mot de passe : vérification de l'ancien mot de passe obligatoire avant tout
  changement (contrairement au reset admin qui ne la demande pas, ici l'utilisateur agit sur son
  propre compte donc cette vérification supplémentaire est justifiée).
- CSRF sur l'endpoint de changement de mot de passe.

## Gestion des erreurs

- Réponses JSON cohérentes `{success, error?}`, messages génériques, détails techniques en
  `error_log()` uniquement — même convention que partout ailleurs sur le site.
- Échec d'écriture Notion : jamais de succès silencieux (même discipline que le reste du projet).

## Hors scope

- Aucune modification du profil autre que le mot de passe (nom, email, entreprise restent
  modifiables uniquement par l'admin/Notion).
- Aucun contenu pédagogique (accès aux supports de formation, etc.) — uniquement compte/facturation.
- Aucun changement du dashboard admin.
