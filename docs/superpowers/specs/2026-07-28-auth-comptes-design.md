# Socle Comptes & Authentification — Design

Date : 2026-07-28
Statut : Validé par l'utilisateur, en attente du plan d'implémentation.

## Contexte

Le nouveau site SlapIA (`C:\xampp\htdocs\Slapia`) est aujourd'hui un site vitrine sans
authentification. L'ancien site (`C:\Users\thoma\SynologyDrive\Documents\Projets\SlapIA\Site Web`)
avait un espace utilisateur, un espace admin et un blog, tous connectés à un backend Notion
(pas de base SQL). Ce document couvre le premier sous-projet : le socle comptes/authentification
sur lequel s'appuieront ensuite l'espace utilisateur, l'espace admin, et indirectement le blog.

Sous-projets suivants (hors scope de ce document, specs séparées) :
1. **Socle Comptes & Authentification** (ce document)
2. Espace utilisateur (dashboard client)
3. Espace admin
4. Refonte du blog

## Décisions actées avec l'utilisateur

- Backend comptes : **Notion conservé** (base "Satisfaction", dual-usage comptes + témoignages),
  pas de migration vers MySQL.
- Rôles : `Particuliers`, `Entreprise`, et **Status vide = Admin** (convention Notion existante,
  volontaire — ce n'est pas un bug à corriger, juste un comportement à centraliser dans le code).
- Hébergement : mono-serveur, déploiement via gitsync de l'hébergeur, `.env` mappé localement sur
  le serveur (jamais commité). Le rate-limiting et le remember-me en fichiers temporaires restent
  donc un choix valide (pas de multi-instances).
- Emails transactionnels : webhook n8n conservé (comme l'ancien site), généralisé pour supporter
  plusieurs types d'événements (reset password, bienvenue, etc.) via un seul webhook.
- Auto-inscription : désactivée (comme l'ancien site) — comptes créés manuellement dans Notion.

## Modèle de données (Notion, schéma inchangé)

Base "Satisfaction" (ID dans `NOTION_SATISFACTION_DATABASE_ID`), propriétés utilisées pour l'auth :

| Propriété       | Type      | Usage                                              |
|-----------------|-----------|-----------------------------------------------------|
| `Email`         | email     | Identifiant de connexion                            |
| `Mot de passe`  | rich_text | Hash bcrypt (auto-upgrade si legacy plain-text)      |
| `Prenom NOM`    | title     | Nom affiché                                          |
| `Status`        | select    | `Particuliers` \| `Entreprise` \| *(vide = Admin)*   |
| `Facturation`   | select    | Statut de facturation (repris tel quel)              |
| `Reset Token`   | rich_text | Token de réinitialisation (aléatoire, 32 bytes hex)  |
| `Reset Expiry`  | date      | Expiration du token (1h)                             |

Les autres propriétés (`Job`, `Nom d'entreprise`, `Avis clients`, `Satisfaction`, `Photo`,
`Linkedin`) appartiennent au cas d'usage "témoignages" déjà géré par `includes/reviews.php` — non
modifiées par ce sous-projet.

### Résolution de rôle centralisée

Amélioration par rapport à l'ancien site : le check `$status !== 'Admin' && $status !== ''`
était dupliqué dans `admin-data.php`, `admin-update-role-exec.php`, etc. — un endroit oublié et
c'est un trou de sécurité potentiel. On centralise dans une seule fonction :

```php
// includes/auth.php
function resolveUserRole(string $statusValue): string {
    if ($statusValue === '') return 'admin';
    if ($statusValue === 'Entreprise') return 'entreprise';
    return 'particulier'; // 'Particuliers' ou toute autre valeur
}
```

Tout le reste du code (pages, API) appelle `requireAdmin()` / `requireRole()` — jamais de
comparaison directe sur `Status`.

## Sécurité & flux (repris de l'ancien site, fonctionnent bien)

- **Connexion** : email + mot de passe + Cloudflare Turnstile + token CSRF de session.
- **Rate limiting** : fichier temporaire, par IP (10/15min) et par email (5/15min).
- **Remember-me** : token 64 hex chars, fichier JSON `sys_get_temp_dir()/slapia_rt_<token>.json`,
  expiration 30 jours, restauration automatique de session au chargement de `config.php`.
- **CSRF** : token de session, vérifié sur toutes les routes API mutantes.
- **Reset password** : token + expiration stockés dans Notion, lien envoyé via webhook n8n,
  réponse toujours `{success:true}` côté `auth-reset-request.php` pour ne jamais révéler si un
  email existe.
- **Emails transactionnels** : `N8N_RESET_WEBHOOK_URL` généralisé en `N8N_AUTH_WEBHOOK_URL`, appelé
  avec un payload `{event: 'password_reset'|'welcome', email, name, ...}` — un seul webhook n8n
  avec un nœud de branchement selon `event`, au lieu d'un webhook par type d'email.
- **Auto-inscription** : `api/auth-register.php` et route `/register` redirigent vers `/login`
  (comportement identique à l'ancien site).

## Structure du code

Nouveaux fichiers :

- `includes/auth.php` — bootstrap session (repris de l'ancien `config.php` : cookie params,
  restauration remember-me), CSRF (`generateCSRFToken`/`verifyCSRFToken`), `resolveUserRole()`,
  `currentUser()`, `requireLogin()`, `requireAdmin()`, `requireRole(string ...$roles)`.
- `includes/notion-users.php` — construit sur la classe `NotionAPI` déjà présente dans
  `includes/notion.php` (au lieu de refaire des blocs `curl_init` comme l'ancien site) :
  `findUserByEmail()`, `verifyPassword()`, `upgradePasswordHash()`, `setResetToken()`,
  `validateResetToken()`, `clearResetToken()`, `updatePassword()`.
- `pages/login.php`, `pages/reset-password.php` (mode request/reset selon présence de
  `?token=&email=`, identique à l'ancien site).
- `api/auth-login.php`, `api/auth-logout.php`, `api/auth-reset-request.php`,
  `api/auth-reset-exec.php`, `api/auth-register.php` (redirect only).

Modifications :

- `includes/config.php` : ajout du bootstrap session sécurisé + restauration remember-me (repris
  de l'ancien site, actuellement absent du nouveau `config.php`).
- `.htaccess` : ajout de la réécriture `pages/$1.php` pour URLs propres (`/login`,
  `/reset-password`), ajout des headers de sécurité (CSP, HSTS, X-Frame-Options,
  Referrer-Policy) avec les domaines déjà utilisés par le site (`api.notion.com`, `*.n8n.cloud`,
  `challenges.cloudflare.com`), blocage explicite de `.env`, `config.php`, `.git`.
- `includes/header.php` : ajout d'un lien Connexion/Mon compte dans la nav selon l'état de
  session (`currentUser()`).

## Gestion des erreurs

- Réponses JSON cohérentes `{success: bool, error?: string}` sur toutes les routes API.
- Messages utilisateur génériques (jamais de détail technique ni de confirmation d'existence
  d'email) ; détails techniques uniquement en `error_log()`.
- Toute exception Notion (timeout, erreur API) → message générique + log, jamais de fuite de
  la réponse brute Notion au client.

## Tests

Pas d'infrastructure de tests automatisés dans le projet actuel. Checklist de QA manuelle à
exécuter avant mise en prod :

- [ ] Connexion avec identifiants valides → session + redirect dashboard
- [ ] Connexion avec mauvais mot de passe → erreur générique, tentative loggée
- [ ] Rate limit déclenché après 5 échecs / email, 10 / IP → 429
- [ ] Remember-me : session expirée restaurée via cookie
- [ ] Reset password : demande → token créé dans Notion → webhook n8n appelé
- [ ] Reset password : token expiré ou invalide → 404 (comme l'ancien site)
- [ ] Reset password : changement de mot de passe → ancien hash invalidé, nouveau hash bcrypt
- [ ] Compte avec `Status` vide → traité comme Admin ; `Particuliers`/`Entreprise` → non-admin
- [ ] `/register` → redirige vers `/login`
- [ ] Headers de sécurité présents sur toutes les réponses (vérifier CSP ne casse rien : Notion,
      n8n, Turnstile, polices/CDN existants)

## Hors scope (sous-projets suivants)

- Contenu et UI du dashboard utilisateur (factures, avis, notifications) → sous-projet 2.
- Onglets et actions admin (gestion users, newsletter, audit log, facturation) → sous-projet 3.
- Blog (contenu, RSS, abonnement) → sous-projet 4.
