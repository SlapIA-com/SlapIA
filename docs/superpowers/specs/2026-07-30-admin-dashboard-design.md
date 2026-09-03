# Dashboard Admin — Design

Date : 2026-07-30
Statut : Validé par l'utilisateur, en attente du plan d'implémentation.

## Contexte

Deuxième sous-projet de la refonte SlapIA (après le socle Comptes & Authentification,
voir `2026-07-28-auth-comptes-design.md`). L'ancien site avait un unique `pages/dashboard.php`
mélangeant vue utilisateur et vue admin via des onglets JS. Ce document couvre uniquement le
**dashboard admin**, en page séparée du futur dashboard utilisateur (sous-projet suivant, hors
scope ici).

## Découvertes en explorant le vrai backend Notion (pas seulement l'ancien code)

Le code de l'ancien site était partiellement obsolète par rapport à l'état réel de la base
Notion "ERP" (utilisée par le site sous le nom `NOTION_SATISFACTION_DATABASE_ID`, ID
`2f0b2071-3b6f-8054-bf30-d158398a892a`). Vérifié en interrogeant l'API Notion directement avec
la clé déjà présente dans `.env` :

- **`Status` (select)** : uniquement `Particulier` et `Entreprise` comme valeurs possibles —
  confirme que vide = admin est la seule convention (pas de valeur "Admin" dans le select).
- **`Facturation` (select)** : 5 valeurs réelles — `Facturé, Payé, En cours, En attente, Dispensé`
  (l'ancien site n'utilisait que "En attente" de façon binaire).
- **`Prix` (number)** et **`Type de service` (select)** existent déjà sur chaque fiche client —
  jamais exploités par l'ancien site, utiles pour une vraie vue facturation.
- **`Factures` (files)** : fichiers attachés directement sur la fiche client (inchangé par
  rapport à l'ancien site).
- **Pas de base "Newsletter" séparée.** Les bases visibles par l'intégration sont : `ERP` (=
  Satisfaction/Accounts), `RSS SlapIA`, `RSS Subscriber` (ID `32cb2071-3b6f-80df-9294-e394733f4f2f`,
  propriété unique `Email` en title), `Contact SiteWeb`, `Avis Clients`. La vue "Paiements" vue
  dans Notion est une vue filtrée de la même base ERP, pas une base distincte.
- **Aucune propriété "dernière connexion" n'existe** — à créer par l'utilisateur dans Notion
  (type Date) avant la mise en prod. Seule action manuelle requise côté Notion pour ce sous-projet.

## Décisions actées avec l'utilisateur

- Page admin **séparée** (`pages/admin.php`), pas d'onglets partagés avec le futur dashboard
  utilisateur.
- Gestion des comptes : changer rôle (Particulier/Entreprise/Admin — l'option "Client" de
  l'ancien site est abandonnée, obsolète et incohérente avec `resolveUserRole()`), changer le
  statut de facturation, réinitialiser le mot de passe d'un compte, voir la dernière connexion.
- Upload réel de factures PDF depuis le dashboard (API Upload de fichiers Notion, 2 étapes),
  pas de lien externe ni de simple consultation.
- Graphiques : croissance des comptes/abonnés RSS (6 mois), répartition des 5 statuts de
  facturation, répartition Particulier/Entreprise/Admin.
- Le faux "Journal d'Audit" de l'ancien site (qui ne faisait que ré-afficher les leads/newsletter)
  est abandonné — remplacé par la vraie donnée "dernière connexion" dans le tableau des comptes.
- Graphiques rendus avec Chart.js **auto-hébergé** (`assets/js/vendor/chart.min.js`), pas de CDN
  externe, pour rester cohérent avec la CSP stricte du site.
- Nom de la section abonnés : "Abonnés RSS" (reflète la réalité : `RSS Subscriber`), pas
  "Newsletter".

## Architecture

- **Page** : `pages/admin.php`, protégée par `requireAdmin()` (déjà en place depuis le socle
  auth). Un utilisateur non-admin est redirigé (même comportement que les autres routes protégées).
- **API** : nouveaux endpoints `api/admin-*.php`, chacun protégé par `requireAdmin()` + CSRF,
  sur le modèle de `api/auth-login.php`. Toutes les écritures Notion vérifient la réponse
  (`http_code`/`error`) et ne rapportent jamais un succès silencieux en cas d'échec — même
  discipline que le correctif appliqué au socle auth.
- **Frontend** : JS vanilla (fetch + rendu DOM), pas de nouveau framework. Réutilise
  `includes/header.php`/`footer.php` et les classes CSS existantes (`.field`, `.btn`, `.alert`,
  table styles à créer si besoin en suivant les conventions déjà en place).
- **Notion** : toutes les opérations passent par la classe `NotionAPI` existante
  (`includes/notion.php`) et par `includes/notion-users.php` (étendu si besoin), jamais de
  nouveaux appels curl bruts.

## Composants

### 1. Vue d'ensemble (KPI + graphiques)
- Cartes : nombre de comptes, nombre d'abonnés RSS, nombre de factures "En attente".
- Graphique 1 : croissance comptes + abonnés RSS sur 6 mois (courbes).
- Graphique 2 : répartition des 5 statuts de facturation (camembert ou barres).
- Graphique 3 : répartition Particulier / Entreprise / Admin (camembert).

### 2. Gestion des comptes
- Tableau recherchable, exportable CSV : nom, email, entreprise, Type de service, rôle
  (dropdown éditable : Particulier/Entreprise/Admin), statut de facturation (dropdown éditable :
  les 5 valeurs réelles), dernière connexion, actions (email, reset mot de passe).
- Changement de rôle et de statut de facturation : PATCH Notion via un nouvel endpoint
  `api/admin-update-account-exec.php` (remplace/étend `admin-update-role-exec.php` de l'ancien
  site pour couvrir les deux champs).

### 3. Abonnés RSS
- Liste recherchable/exportable des emails de `RSS Subscriber` (email + date d'inscription via
  `created_time`, cette base n'a pas de propriété date dédiée).

### 4. Facturation / Factures
- Vue par client : Type de service, Prix, statut de Facturation, fichiers Factures attachés
  (visualiser/télécharger), bouton d'upload d'un nouveau PDF.
- Upload : validation serveur (PDF uniquement, taille raisonnable, nom de fichier assaini),
  utilise l'API Notion File Upload (créer l'upload, puis l'attacher à la propriété `Factures`
  de la fiche du client concerné) via un nouvel endpoint `api/admin-upload-invoice-exec.php`.

### 5. Réinitialisation de mot de passe (admin)
- L'admin fixe directement un nouveau mot de passe pour un compte cible (par email ou ID de
  page), distinct du flow self-service déjà construit (`api/auth-reset-request.php`/`-exec.php`).
  Nouvel endpoint `api/admin-reset-password-exec.php`.

### 6. Dernière connexion
- `api/auth-login.php` (existant, socle auth) est étendu pour écrire la date/heure de connexion
  dans la propriété Notion "Dernière connexion" à chaque connexion réussie (échec de cette
  écriture loggé, jamais bloquant pour la connexion elle-même — même logique que
  l'upgrade de hash legacy).

## Nouvelles configurations requises

- `.env` : ajout de `NOTION_RSS_SUBSCRIBER_DATABASE_ID=32cb2071-3b6f-80df-9294-e394733f4f2f`
  (ID déjà connu, pas d'action utilisateur nécessaire).
- Notion : création manuelle par l'utilisateur de la propriété **"Dernière connexion"**
  (type Date) sur la base ERP — seule action Notion requise avant mise en prod complète du
  suivi de connexion (le reste du dashboard fonctionne sans, cette seule fonctionnalité
  échouera silencieusement-mais-loggée jusqu'à la création de la propriété).

## Sécurité

- Toutes les routes/endpoints protégés par `requireAdmin()`.
- CSRF sur tous les endpoints mutants (changement rôle/facturation, upload facture, reset mdp).
- Upload de fichier : whitelist de type MIME (PDF), limite de taille, nom de fichier assaini
  avant envoi à Notion — pas d'exécution de fichier, pas de traversée de chemin.
- Écritures Notion vérifiées (jamais de succès silencieux en cas d'échec réel).

## Gestion des erreurs

- Réponses JSON cohérentes `{success, error?}` sur toutes les routes API, messages génériques
  côté client, détails techniques en `error_log()` uniquement — même convention que le socle auth.
- Échec d'upload de facture : message clair à l'admin (pas de faux succès).

## Tests

Pas d'infrastructure de tests automatisés (même choix que le socle auth). Checklist de QA
manuelle à définir dans le plan d'implémentation, incluant vérification live contre la vraie
base Notion (avec les mêmes garde-fous de sécurité que pour le socle auth : jamais de mutation
réelle non voulue pendant les vérifications automatisées d'agents).

## Hors scope (sous-projets suivants)

- Dashboard utilisateur (factures visibles côté client, statut) → sous-projet suivant.
- Refonte du blog → sous-projet ultérieur.
- Vrai journal d'audit des actions admin (au-delà de la dernière connexion) → amélioration
  future non demandée pour cette version.
