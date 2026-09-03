# Menu utilisateur dans le header — Design

Date : 2026-07-31
Statut : Validé par l'utilisateur.

## Contexte

Le header affiche actuellement, pour un utilisateur connecté, deux boutons séparés :
"Administration" (si admin) et "Mon espace"/"Connexion" (`includes/header.php`). Il n'existe
aujourd'hui **aucun lien de déconnexion** nulle part dans la navigation.

## Décision

Remplacer les deux boutons par un seul point d'entrée, dont le contenu dépend du rôle Notion
(`particulier`/`entreprise` → "Mon espace" ; `admin` → "Administration") :

- **Desktop, connecté** : photo de profil Notion (ronde, via `api/notion-avatar.php?id=<user_id>`,
  déjà utilisé côté admin) cliquable → menu déroulant avec : nom de l'utilisateur, lien
  tableau de bord (admin ou client selon le rôle), lien de déconnexion.
- **Desktop, déconnecté** : bouton "Connexion" inchangé.
- **Mobile, connecté** : pas de dropdown — deux liens empilés (tableau de bord, déconnexion),
  cohérent avec le menu plein écran déjà en place.
- **Mobile, déconnecté** : lien "Connexion" inchangé.

## Implémentation

- `includes/header.php` : remplace les deux blocs conditionnels existants (desktop lignes
  ~100-104, mobile lignes ~131-135) par la logique ci-dessus, dans les deux emplacements
  (nav desktop et menu mobile).
- `assets/css/style.css` : nouvelles classes `.user-menu`, `.user-menu__trigger`,
  `.user-menu__avatar`, `.user-menu__dropdown`, `.user-menu__name` — réutilisent les variables
  de couleur/police déjà définies (`--ink`, `--line`, `--paper`, `--font-mono`, etc.), pas de
  nouvelles valeurs codées en dur.
- `assets/js/main.js` : petit gestionnaire de clic pour ouvrir/fermer le dropdown, plus
  fermeture au clic extérieur — même esprit que le toggle du menu mobile déjà présent dans ce
  fichier.
- `lang/fr.php`, `lang/en.php`, `lang/de.php` : nouvelle clé `nav.logout` (traduite dans les
  trois langues), ajoutée dans le tableau `nav` déjà existant.
- Déconnexion : réutilise `api/auth-logout.php` (existant, sous-projet auth) — aucune
  modification de cet endpoint.

## Hors scope

- Aucune autre modification du contenu ou du style de la navigation.
- Pas de gestion de rôles supplémentaires — uniquement les 3 valeurs déjà connues
  (`particulier`, `entreprise`, `admin`).
