# Page de connexion immersive — Design

Date : 2026-07-31
Statut : Validé par l'utilisateur.

## Contexte

`pages/login.php` et `pages/reset-password.php` réutilisent aujourd'hui le layout complet du
site (`includes/header.php`/`footer.php` : logo, menu, sélecteur de langue, footer). L'utilisateur
veut une page de connexion "moderne, stylisée, avec de vraies animations et un arrière-plan" —
un traitement visuel poussé, distinct du reste du site.

## Décisions actées avec l'utilisateur (via companion visuel)

- Style retenu : **mesh gradient animé** (violet → rose → noir, ondulant en boucle), carte de
  connexion en glassmorphism (verre dépoli, lueur violette).
- S'applique aux **deux pages** : `/login` et `/reset-password`.
- **Écran immersif épuré** : pas de header/footer du site — juste le logo Slapia (cliquable vers
  l'accueil) en haut, carte centrée, lien de retour discret en bas.
- Toujours en **mode sombre**, indépendamment du thème clair/sombre choisi ailleurs sur le site
  (page à part, pas soumise au toggle global).
- Widget Cloudflare Turnstile : activation de son thème sombre natif (`data-theme="dark"` sur
  le div `.cf-turnstile`) plutôt qu'un simple cadre autour d'un widget resté clair, avec un léger
  conteneur autour pour l'aligner visuellement avec la carte.
- Animation en CSS pur (pas de JS/canvas) — respecte `prefers-reduced-motion: reduce`.

## Architecture

- **Nouveau layout minimal**, séparé de `includes/header.php`/`footer.php` (qui restent
  inchangés et continuent de servir tout le reste du site) :
  - `includes/auth-header.php` : doctype/head minimal (réutilise les mêmes meta/title/favicon
    que `header.php`), ouvre `<body>` avec juste le logo.
  - `includes/auth-footer.php` : lien de retour au site, fermeture des balises, scripts de bas
    de page nécessaires (aucun `main.js` du site nécessaire ici, page autonome).
- `pages/login.php` et `pages/reset-password.php` : remplacent leurs `include header.php`/
  `include footer.php` par ces deux nouveaux includes. Le contenu du formulaire (champs, JS de
  soumission, endpoints appelés) reste identique — seul l'habillage change.
- **CSS** : nouveau fichier `assets/css/auth.css`, chargé uniquement par ces deux pages (pas par
  le reste du site). Réutilise les couleurs de marque déjà définies (`--signal`, `--signal-pink`
  ou équivalent, `--forest-glow`) plutôt que d'introduire de nouvelles valeurs.
- **Turnstile** : `data-theme="dark"` ajouté à l'attribut existant du div `.cf-turnstile` dans
  `pages/login.php` (aucun changement côté serveur).

## Détails visuels

- Fond : 2-3 dégradés radiaux superposés (violet `--signal`, rose `--signal-pink`, noir de fond
  `--surface-dark`), animés via `@keyframes` sur `background-position` ou une légère rotation,
  boucle continue lente (~20-30s), désactivée si `prefers-reduced-motion: reduce`.
- Carte : `background: rgba(...)` semi-transparent + `backdrop-filter: blur(16px)`, bordure fine
  `rgba(179,111,224,0.25)`, `box-shadow` avec lueur violette douce.
- Logo en haut : lien vers `index.php`, taille modérée, toujours visible.
- Lien de retour en bas : "← Retour au site" vers `index.php`.

## Hors scope

- Aucun changement du reste du site (header/footer/pages publiques inchangés).
- Aucun changement de la logique métier (endpoints API, validation, CSRF, Turnstile côté
  serveur) — uniquement l'habillage visuel des deux pages.
- `pages/register.php` (redirection simple vers `/login`) non concerné — pas de contenu visuel
  à styliser.
