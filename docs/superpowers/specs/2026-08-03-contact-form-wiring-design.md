# Contact Form — Notion + Turnstile + n8n Wiring — Design

Date : 2026-08-03
Statut : Validé par l'utilisateur.

## Contexte

`contact.php` (page publique racine) collecte nom/email/entreprise/sujet/message mais n'est
connecté à rien de solide : il envoie un email brut via la fonction PHP `mail()` (peu fiable,
souvent filtré en spam), sans protection anti-bot, et sans jamais écrire dans Notion. Une base
Notion "Contact" dédiée existe déjà (`NOTION_CONTACT_DATABASE_ID`) avec les propriétés `Prenom`
(titre), `Nom`, `Email`, `Message`, `Date de création`, `prise de contact ok ?` — mais il manque
`Entreprise` et `Sujet`, deux champs déjà collectés par le formulaire. La clé Cloudflare Turnstile
existe déjà dans `.env` (réutilisée du login) mais n'est pas branchée ici.

## Décisions actées avec l'utilisateur

- Le formulaire visible (design/UX) ne change pas — seul le traitement serveur est reconstruit.
- L'utilisateur ajoute lui-même les 2 propriétés Notion manquantes : `Entreprise` (texte) et
  `Sujet` (texte, pas Select — les libellés du menu déroulant sont des chaînes traduites en 3
  langues, un Select figerait ça sur le français).
- `prise de contact ok ?` est un flag de suivi interne coché manuellement par l'admin une fois la
  personne recontactée — le formulaire n'y touche jamais à la création.
- Remplacement complet de `mail()` : écriture Notion **et** envoi via le webhook n8n existant
  (`N8N_AUTH_WEBHOOK_URL`, avec `event: 'contact_form'` — même webhook que les emails d'auth, qui
  branche déjà sur le champ `event`, donc pas de nouveau webhook à créer).
- L'utilisateur fournira ensuite le workflow n8n existant pour qu'on y ajoute le champ "raison"
  (sujet) dans le template d'email — hors scope de ce plan-ci, traité séparément une fois le
  workflow partagé.

## Fonctionnalités

### 1. Protection CSRF
- `contact.php` génère un jeton CSRF (`generateCSRFToken()`) et l'inclut en champ caché.
- Vérifié au début du traitement POST, comme partout ailleurs sur le site.

### 2. Cloudflare Turnstile
- Même clé publique que le login (`TURNSTILE_SITE_KEY`), widget `cf-turnstile` inséré dans le
  formulaire.
- Comme `contact.php` reste un POST classique (pas de fetch JS), le token Turnstile arrive
  directement dans `$_POST['cf-turnstile-response']` sans JS supplémentaire — contrairement au
  login qui le lit via JS parce qu'il convertit le formulaire en requête fetch.
- Vérification serveur via l'API `siteverify` de Cloudflare, même pattern que
  `api/auth-login.php`.

### 3. Limitation de débit
- `rateLimitCheck()` par IP (5 tentatives / 15 min), même pattern que login/reset — défense en
  profondeur en plus de Turnstile.

### 4. Écriture Notion
- Nouveau fichier `includes/notion-contact.php`, fonction
  `submitContactMessage(string $prenom, string $nom, string $email, string $company, string $subject, string $message): bool`.
- Découpage du nom complet en `Prenom`/`Nom` — même logique que `getNotionReviews()` (premier mot
  → Prenom/titre, reste → Nom).
- Écrit `Entreprise`, `Sujet`, `Message`, `Email` dans leurs propriétés respectives. Message
  plafonné à 5000 caractères.
- Ne touche jamais `prise de contact ok ?` ni `Date de création` (auto).
- Suit le pattern standard du projet : retour `bool`, `error_log()` + `false` sur échec, jamais de
  succès silencieux.

### 5. Envoi email via n8n
- Après écriture Notion réussie, POST fire-and-forget vers `N8N_AUTH_WEBHOOK_URL` (best-effort,
  comme `api/auth-reset-request.php` — n'affecte jamais le succès de la soumission si le webhook
  échoue ou n'est pas configuré).
- Payload : `{event: 'contact_form', name, email, company, subject, message}`.

## Architecture

- Modifie `contact.php` : ajout CSRF + Turnstile + rate limit + appel à
  `submitContactMessage()` + webhook n8n ; suppression du bloc `mail()`.
- Crée `includes/notion-contact.php` : `submitContactMessage()`.
- Modifie `lang/fr.php`, `lang/en.php`, `lang/de.php` : nouvelles clés `contact.err_captcha`,
  `contact.err_captcha_failed`, `contact.err_rate_limit`, `contact.err_server` (mêmes messages que
  les clés `auth.*` équivalentes, dans le namespace `contact` pour rester cohérent avec le reste
  du fichier).

## Sécurité

- CSRF, Turnstile, rate-limit : trois couches avant tout traitement.
- Aucune donnée sensible : le formulaire ne touche à aucun compte utilisateur, juste une nouvelle
  entrée dans la base Contact.
- Message plafonné en longueur pour éviter l'abus.

## Gestion des erreurs

- Échec Notion → message d'erreur générique affiché, rien n'est perdu côté formulaire (les valeurs
  restent pré-remplies comme aujourd'hui).
- Échec du webhook n8n → n'empêche jamais la soumission de réussir (best-effort, loggé).

## Hors scope

- Modification du workflow n8n lui-même (ajout du champ "raison" dans l'email) — traité
  séparément une fois le workflow partagé par l'utilisateur.
- Erreurs de console JavaScript sur le reste du site — traité séparément (bloqué sur l'approbation
  de l'outil navigateur).
