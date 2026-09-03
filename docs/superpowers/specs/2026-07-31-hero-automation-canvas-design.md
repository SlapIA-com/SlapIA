# Hero — Canvas d'automatisation vivant — Design

Date : 2026-07-31
Statut : Validé par l'utilisateur (choisi via companion visuel parmi 3 directions).

## Contexte

Retour utilisateur sur le site en général : "trop statique et terne", malgré une mise en forme
correcte. Diagnostic précisé : manque de mouvement/interactivité, mise en page trop classique,
absence d'élément signature mémorable. Décision : traiter d'abord la page d'accueil comme terrain
d'expérimentation (impact maximal, itération rapide), en commençant par le hero uniquement — pas
de restructuration du reste de la page dans ce tour.

## Direction retenue

Parmi 3 pistes présentées (canvas d'automatisation animé façon n8n/Make, terminal IA en direct
qui "streame", bento asymétrique + interactions magnétiques), l'utilisateur a choisi le **canvas
d'automatisation vivant** — jugé le plus mémorable et le plus fidèle au métier réel de SlapIA
(formation à l'automatisation IA/n8n/Make).

## Description

Le fond du hero de `index.php` devient un graphe de nœuds animé, en SVG, suggérant un flux
d'automatisation qui tourne en direct :

- **~8-10 nœuds** (cercles), positionnés pour suggérer un flux gauche → droite, reliés par des
  lignes.
- **Séquence d'entrée** (une fois, au chargement de la page) : les nœuds apparaissent
  progressivement, puis les lignes se "dessinent" entre eux.
- **Boucle ambiante** (continue après la séquence d'entrée) : de petits points lumineux voyagent
  le long des lignes (flux de données), les nœuds pulsent doucement à tour de rôle.
- **Réactivité au curseur** : léger parallax de l'ensemble du graphe vers la position de la souris
  (quelques pixels, discret).
- **Accessibilité** : respecte `prefers-reduced-motion: reduce` — dans ce cas, le graphe s'affiche
  dans son état final (nœuds + lignes visibles) sans aucune animation ni boucle.
- **Mobile** : la couche reste présente mais atténuée (moins de nœuds visibles, opacité réduite)
  pour ne pas nuire à la lisibilité du texte sur petit écran.

## Architecture

- **Nouveau fichier** : `assets/js/hero-canvas.js` — génère le SVG (nœuds + lignes) et pilote les
  animations (séquence d'entrée, boucle, parallax). Vanilla JS, pas de librairie externe
  (cohérent avec le reste du site, pas de nouvelle dépendance).
- **`index.php`** : ajout d'un conteneur `<div class="hero-canvas" aria-hidden="true"></div>` en
  premier enfant de `.hero` (avant `.hero__grid`), que `hero-canvas.js` peuple au chargement.
- **`assets/css/style.css`** : nouvelles règles pour positionner la couche en absolu derrière
  `.hero__grid` (qui passe en `z-index` supérieur), styles des nœuds/lignes, et le bloc
  `@media (prefers-reduced-motion: reduce)` qui coupe les animations JS (le JS lit
  `matchMedia('(prefers-reduced-motion: reduce)')` avant de lancer boucle/parallax).
- Couleurs : réutilise les tokens de marque déjà définis (`--signal`, `--signal-pink`, `--forest`)
  — pas de nouvelles valeurs codées en dur.

## Gestion des erreurs

- Si `hero-canvas.js` échoue à s'exécuter (JS désactivé, erreur), `.hero-canvas` reste un conteneur
  vide — le hero reste pleinement lisible et fonctionnel sans lui (dégradation silencieuse, pas de
  dépendance fonctionnelle du contenu réel sur cette couche décorative).

## Hors scope

- Aucune restructuration des sections sous le hero (stats, méthode, etc.) — pourra faire l'objet
  d'un tour de design ultérieur si cette direction convainc.
- Aucun changement sur les autres pages du site.
