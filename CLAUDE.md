# CLAUDE.md — Thème Radio Audace

Toujours répondre en français, avec les accents.

## Projet

Thème WordPress **autonome et sur mesure** pour radioaudace.com, destiné à
remplacer Extra (Elegant Themes). Décision de l'utilisateur du 2026-11-11 :
un thème professionnel, d'inspiration Apple (espace, typographie soignée,
grandes images, animations discrètes), qui garde l'ambiance d'un site
d'information radio, versionné ici et mis à jour sur le site via GitHub.

**État : projet créé, aucune ligne de thème écrite.** Le dossier
`radio-audace/` ne contient qu'un squelette minimal.

**Prochaine étape convenue : maquette de l'accueil et de la page d'article**
(HTML statique dans `design/`), à faire valider avant d'écrire le thème.

## Exigences de l'utilisateur

- Le thème doit être **adapté aux articles radio** (c'est le cœur du besoin).
- Les articles sont **écrits dans RadioManager**, qui les pousse vers
  WordPress. Le thème doit donc bien afficher du HTML simple sans aucune
  intervention dans l'éditeur WordPress.
- Mises à jour gérées par GitHub, comme le plugin Radio Audace Player.

## Ce que contient le site (mesuré sur la prod le 2026-11-11, 104 articles)

| Période | Articles | Construits avec Divi | Contenu type |
|---|---|---|---|
| 2026 | 29 | 1 | Texte propre : `p`, `h2`, `strong`, `blockquote`, `ul` |
| 2025 | 2 | 2 | — |
| 2021-2022 | 69 | 59 | Une vidéo YouTube d'émission, peu de texte |

- Articles récents : tous ont un extrait, 28 sur 30 une image à la une,
  aucune étiquette, aucun audio ni vidéo intégré.
- 13 articles récents contiennent un `h1` dans le corps (doublon du titre) :
  le thème doit le rétrograder en `h2`.
- 61 articles Divi à convertir (sans Divi, les shortcodes `et_pb_*`
  s'afficheraient en clair). Presque tous suivent le même modèle (vidéo
  YouTube), donc une conversion automatique est réaliste.
- Catégories : mélange de **rubriques** (sport 23, économie 10, infos 10,
  politique, éducation, culture, société) et d'**émissions** (Train d'Audace,
  Audace Infos Midi Magazine, Femmes d'Audace, Parlons Droit, Santé Magazine,
  Club Audace, Intima, Bold Generation, Les Petits Audacieux…). Le thème doit
  traiter ces deux familles différemment.
- Pages : À propos, contacts, invités, Newsletter, lecteur (VMPlayer).
- À vérifier : le canal exact par lequel RadioManager publie les articles
  (ils arrivent avec image, extrait et catégorie).

## Modèle d'article envisagé (à confirmer par la maquette)

- Colonne de lecture étroite, grande image à la une, extrait en chapeau,
  intertitres et citations bien marqués.
- Lien avec l'antenne : bandeau discret « Écouter le direct » et, si l'article
  relève d'une émission, encart avec son nom et son horaire.
- Rubriques affichées comme un média d'information ; émissions comme des pages
  de programme (présentation, animateur, horaire, derniers épisodes).
- Articles vidéo (replays) : présentation dédiée, vidéo en tête.

## Projets liés (à lire avant de concevoir)

| Projet | Emplacement | Rôle pour ce thème |
|---|---|---|
| Chantier « L'Onde » | `~/App/radioaudace-website` | Refonte précédente **sur Extra**, en place sur le staging depuis août 2026. Contient la direction artistique validée (`docs/DESIGN.md`, maquettes `design/`), le logo SVG, l'inventaire du site (`docs/SITE.md`), les runbooks (`docs/OPERATIONS.md`) et le mu-plugin `wp/mu-plugins/onde-tweaks.php` |
| Plugin lecteur | `~/App/scripts/scripts-radioManager/wordpress-plugin/radio-audace-player` | Lecteur du direct, shortcodes programme/grille/équipe. Modèle pour l'updater GitHub (`admin/updater.php`) et le script de release (`../release.sh`) |
| Plugin `audace-analytics` | `~/App/audace-analytics-wp` | Grille des programmes et médias d'émissions pilotés par RadioManager |

Points à trancher avec l'utilisateur au moment de la maquette :

- **Identité visuelle** : L'Onde a une palette et une typographie validées
  (encre `#0A1628`, bleu Audace `#1E56E8`, direct `#E8442E`, Archivo Black +
  Inter). L'inspiration Apple demandée ici est plus sobre. Reprendre L'Onde,
  l'adoucir, ou repartir de zéro ?
- **Fonctions du mu-plugin à reprendre dans le thème** : `[audace_direct]`,
  `[audace_articles]` (page Actualités), `[audace_related]`, composants
  d'article (surtitre, contexte, partage), rétrogradation `h1` → `h2`.
  `[audace_grille]` dépend d'`audace-analytics`.

## Environnements et règles

- **Staging** : `https://www.staging.radioaudace.com` (SSH : alias
  `radioaudace-staging`). C'est là que le thème se teste.
- **Prod** : `https://www.radioaudace.com`. **Aucune écriture sans décision
  explicite de l'utilisateur.** La bascule de thème est réversible
  (réactiver Extra restaure l'affichage).
- Hébergement mutualisé OVH : les requêtes `curl` sans User-Agent applicatif
  reçoivent un 403, et une rafale bloquée peut bannir l'IP une quinzaine de
  minutes. Détails dans `~/App/radioaudace-website/CLAUDE.md`.
- Secrets : jamais dans ce dépôt ni dans les sorties.

## Dépôt et mises à jour

- Dépôt GitHub `lwilly3/radio-audace-theme`, **privé** pour l'instant.
- Le site ne peut lire les releases que d'un dépôt **public** (leçon du
  plugin : dépôt privé = HTTP 404). Avant la première release, rendre ce
  dépôt public ou publier dans un dépôt miroir public.
- À écrire le moment venu : un updater de thème (filtre
  `pre_set_site_transient_update_themes`) et un `release.sh`, sur le modèle
  du plugin. L'asset de release doit être un ZIP dont la racine est
  `radio-audace/`.
- La version vit dans l'en-tête de `radio-audace/style.css` ; tenir
  `CHANGELOG.md` à jour.

## Arborescence

```text
radio-audace/   # le thème (ce dossier devient le ZIP installable)
design/         # maquettes HTML statiques
docs/           # documentation de conception
```

## Commits

Messages conventionnels en français. Auteur configuré localement avec
l'adresse noreply GitHub (le dépôt deviendra public) : ne pas la changer.
