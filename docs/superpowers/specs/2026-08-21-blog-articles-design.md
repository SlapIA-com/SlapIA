# Blog — Articles (liste + lecture) — Design

Date : 2026-08-21
Statut : Validé par l'utilisateur.

## Contexte

Un pipeline n8n entièrement automatisé (déclenché tous les 2 jours) génère déjà de vrais
articles de blog par IA (Mistral, ancré sur l'actualité IA via TechCrunch/ActuIA), génère une
image de couverture, publie dans une base Notion existante **"RSS SlapIA"**
(`328b2071-3b6f-8036-afa7-dcd8a9051eca`, déjà 60 articles depuis fin mars), et envoie une
newsletter aux abonnés (base `NOTION_RSS_SUBSCRIBER_DATABASE_ID`, déjà présente côté admin mais
jamais reliée à rien côté public). Le site actuel n'a aucune page publique pour ces articles.

Ce chantier couvre uniquement l'affichage public des articles (liste + lecture). Le flux RSS et
l'abonnement/désabonnement email sont traités dans un second chantier séparé.

## Schéma Notion réel (ne pas recréer de base)

Base "RSS SlapIA" — propriétés existantes, utilisées telles quelles :
- `Titre` (title)
- `Publication Date` (date)
- `Image` (files)
- `Extrait ` (rich_text — **espace en trop dans le nom**, confirmé en direct ; on code autour de
  ce nom exact plutôt que de demander un renommage, car le workflow n8n en production qui publie
  les articles dépend de ce nom précis et un renommage mal coordonné casserait la publication
  automatique)
- Pas de propriété "Contenu" — le texte de l'article est écrit dans le **corps de la page**, en
  blocs `paragraph` Notion. Les titres de section (`## ...`) et les hashtags (`#IA #PME`) sont du
  texte brut à l'intérieur de ces blocs, pas de vrais blocs Notion dédiés — c'est le format
  produit par le pipeline n8n actuel.

## Décisions actées avec l'utilisateur

- Pas de nouvelle base Notion, pas de propriété "Publié ?" (bloquerait l'affichage des nouveaux
  articles générés automatiquement tant qu'elle n'est pas cochée manuellement — casserait
  l'automatisation). Possibilité future d'une case "Masquer ?" optionnelle, décochée par défaut,
  pour cacher un article raté sans le supprimer — pas demandée pour l'instant, à ajouter plus
  tard si besoin.
- Vraies pages par article (`/blog/<slug>`), pas un overlay comme l'ancien site — meilleur SEO et
  partage direct.
- Le slug doit être calculé en PHP avec **exactement le même algorithme** que celui déjà utilisé
  dans le template d'email n8n (normalisation, suppression des accents, minuscule, tirets), pour
  que les liens restent cohérents.
- L'utilisateur mettra à jour le template d'email n8n (nœud "Send Newsletter", 2 endroits : bouton
  principal + les 2 mini-cartes "à lire aussi") pour pointer vers `/blog/<slug>` au lieu de
  `/blog#<slug>` — fourni séparément, hors code PHP. Les emails déjà envoyés avant ce changement
  garderont l'ancien lien (retombera sur la liste des articles, pas un souci bloquant).

## Fonctionnalités

### 1. Page liste `/blog`
- Grille de cartes (image, titre, extrait, date), triée par date de publication décroissante,
  design cohérent avec le reste du site (pas Bootstrap comme l'ancien site — même système de
  design que le reste : tokens CSS existants, cartes façon `.contact-card`/`.review-item`).
- Cache fichier 1h (même pattern que `includes/reviews.php`) pour éviter de solliciter l'API
  Notion à chaque chargement.

### 2. Page article `/blog/<slug>`
- Résout le slug vers l'article correspondant (liste mise en cache, slug calculé à la volée pour
  chaque article et comparé).
- Affiche titre, image de couverture, date, contenu.
- Rendu du contenu : pour chaque bloc `paragraph`, détecte les préfixes markdown `# `/`## `/`### `
  (espace obligatoire après le(s) `#`, pour ne pas confondre avec une ligne de hashtags) et les
  transforme en `<h1>`/`<h2>`/`<h3>` ; détecte une ligne composée uniquement de hashtags
  (`#IA #PME ...`, pas d'espace après le `#`) et l'affiche comme des pastilles ; sinon paragraphe
  normal. Gère aussi, en bonus et sans effort supplémentaire notable, les vrais types de blocs
  Notion (titres, listes, citation, image, séparateur) au cas où un article serait un jour
  édité/complété à la main dans Notion.
- 404 si aucun article ne correspond au slug.

### 3. Génération du slug (PHP, doit produire les mêmes valeurs que le JS du template n8n)
Transformation : normalisation Unicode + suppression des accents (translittération), minuscule,
tout ce qui n'est pas alphanumérique devient un tiret, tirets multiples fusionnés, tirets de
bord supprimés — même résultat que la fonction JS `slugify()` déjà utilisée dans l'email.

## Architecture

- Crée `includes/notion-blog.php` :
  - `listBlogArticles(int $limit = 100): array` — liste triée, avec slug calculé, mise en cache
    fichier (1h).
  - `getBlogArticleBySlug(string $slug): ?array` — trouve l'article correspondant dans la liste
    mise en cache, puis récupère et rend le contenu complet.
  - Fonction de rendu des blocs Notion → HTML (paragraph avec détection markdown + hashtags,
    plus les types de blocs natifs listés ci-dessus).
- Crée `blog.php` (racine, comme `contact.php`) — page liste.
- Crée `blog-article.php` (racine) — page article, lue via `$_GET['slug']`.
- Étend `.htaccess` : nouvelle règle `RewriteRule ^blog/([a-z0-9-]+)$ blog-article.php?slug=$1
  [L,QSA]` pour les URLs `/blog/<slug>`. `/blog` seul fonctionne déjà nativement puisque
  `blog.php` existe physiquement à la racine.
- Crée `assets/css/blog.css` — styles dédiés, cohérents avec le système de design existant.
- Ajoute un lien "Blog" dans la navigation (`includes/header.php`), absent actuellement.
- Nouvelles clés i18n `blog.*` dans `lang/fr.php`, `lang/en.php`, `lang/de.php`.

## Sécurité

- Lecture seule côté PHP — aucune écriture vers Notion dans ce chantier (le pipeline n8n reste le
  seul écrivain).
- `slug` issu de l'URL est validé par la regex de la règle `.htaccess` (`[a-z0-9-]+`) avant même
  d'atteindre le PHP ; re-vérifié côté PHP par comparaison stricte avec les slugs calculés
  (jamais utilisé directement dans une requête Notion).
- Contenu Notion affiché en `innerHTML`/sortie HTML : le texte brut des blocs passe par
  `htmlspecialchars()` avant toute transformation markdown-légère, pour éviter tout risque
  d'injection si un texte généré contenait accidentellement des caractères HTML.

## Gestion des erreurs

- Échec de lecture Notion (API indisponible) → page liste affiche un état vide gracieux (pas
  d'erreur brute), même pattern que `includes/reviews.php`.
- Slug sans correspondance → redirection vers `/404`.

## Hors scope (chantier séparé)

- Flux RSS (`/rss.xml`).
- Formulaire d'abonnement / désabonnement email public.
- Modification du workflow n8n lui-même (faite par l'utilisateur séparément, instructions
  fournies à part).
