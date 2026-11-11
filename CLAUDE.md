# CLAUDE.md — Thème Radio Audace

Toujours répondre en français, avec les accents.

## Projet

Thème WordPress **autonome et sur mesure** pour radioaudace.com, destiné à
remplacer Extra (Elegant Themes). Décision de l'utilisateur du 2026-11-11 :
un thème professionnel, d'inspiration Apple (espace, typographie soignée,
grandes images, animations discrètes), qui garde l'ambiance d'un site
d'information radio, versionné ici et mis à jour sur le site via GitHub.

**État (2026-11-11) : maquettes en troisième passe (deux analyses critiques
traitées), en attente de validation.** **Thème écrit dans `radio-audace/` le 2026-11-11. L'utilisateur a installé
et activé lui-même la version 0.1.0 sur la PROD ce jour-là** (archive
`dist/radio-audace-0.1.0.zip`, téléversée à la main). Constat sur la prod,
en lecture seule : article, replay, page d'émission et pied conformes à la
maquette, aucune erreur de script ; mais l'accueil affichait la liste « Tous
les articles ». Cause : sur ce site, `is_front_page()` est faux à la racine
(réglages de lecture hérités d'Extra), donc WordPress ignorait
`front-page.php`. Corrigé dans la 0.1.1 (`ra_est_accueil()` et filtre
`template_include` dans `inc/reglages.php`), qui ajoute aussi les
présentations par défaut des émissions et la réduction des titres en
capitales dans les listes. **La 0.1.1 (`dist/radio-audace-0.1.1.zip`) n'est
pas encore installée ni vérifiée en ligne.** Le staging n'a pas servi (SSH
expiré le 2026-11-11) ; aucun WordPress local.

Ce que fait le thème (détail dans les en-têtes de fichiers) :

- `inc/reglages.php` : coordonnées de la station (`ra_station()`, filtrable),
  styles, scripts ; coupe à l'affichage le lecteur flottant et le Pjax du
  plugin Radio Audace Player (le thème a les siens).
- `inc/familles.php` : rubriques contre émissions (enfants de la catégorie
  `emissions`), noms propres des catégories, replays (métadonnée
  `_ra_replay`, calculée à l'enregistrement et une fois pour l'existant).
- `inc/contenu.php` : nettoyage à l'affichage, sans toucher la base
  (shortcodes Divi, `h1` → `h2`, balises vides, exergue, tableau, espaces
  insécables, chapeau, titres en caractères Unicode gras).
- `inc/antenne.php` : `ra_antenne()` (émission en ligne, musique, ou rien de
  connu ; rediffusion volontairement non gérée, décision de l'utilisateur),
  grille, route REST `radio-audace/v1/antenne`.
- Gabarits : `front-page`, `single` (article et replay), `category`
  (émissions, émission, rubrique), `page-grille-des-emissions`, `index`
  (liste, recherche), `page`, `404`.
- `assets/js/theme.js` : lecteur, antenne rafraîchie chaque minute,
  navigation sans rechargement active seulement pendant l'écoute (repli sur
  une navigation normale au moindre doute), statistiques d'écoute envoyées
  au plugin lecteur.
- Restent à écrire : l'updater GitHub et `release.sh`.

- Point d'entrée : `design/index.html` (liste des pages, changements, données
  simulées, décisions attendues). Pages : `accueil`, `article`,
  `article-cas-difficiles`, `replay`, `emission`, `rubrique`, `grille`. Feuille commune
  `design/maquette.css` ; `design/maquette.js` injecte l'en-tête, le pied et
  le lecteur du direct dans toutes les pages. Contenus réels du site
  (`design/assets/`, dont les vignettes YouTube `yt-*.jpg`).
- Direction, **à confirmer** : « L'Onde adoucie » — palette L'Onde, titres en
  Archivo 700, texte en Inter, fond blanc, rubriques en surtitres colorés,
  rouge réservé au direct, une seule teinte pour les émissions.
- La deuxième version répond à une analyse critique de la première (rendue
  sur onze tailles d'écran). Principes retenus : l'antenne sur le premier
  écran de l'accueil ; en-tête sur une seule rangée, menu en tiroir sous
  1140 px ; bouton du direct qui joue le flux sans quitter la page, lecteur
  persistant en bas d'écran avec états (connexion, écoute, pause, erreur) ;
  dans l'article, cadre d'image au format exact de l'image, hauteur
  plafonnée (une image portrait a donc un cadre plus étroit) ; dans les
  listes, image portrait entière sur un fond flouté d'elle-même ; lecteur
  avec délai d'attente de 12 s ; aucune cible tactile sous 44 px.
- Règles d'affichage posées par la maquette : la une est le dernier article
  ayant une image (un article épinglé passe devant) ; les articles sont
  listés strictement par date ; une colonne de rubrique n'est jamais
  complétée par une autre rubrique ; un titre tout en capitales est réduit,
  pas réécrit ; une citation de moins de 150 caractères devient une exergue ;
  les titres de replays (« femmes d’audace du 28-12-2021 ») sont réécrits en
  « Émission du 28 décembre 2021 ».

**Décisions attendues de l'utilisateur** (détaillées dans `design/index.html`) :

- Logo : le logo d'origine dessine « AUDACE » en lettres blanches à ombre
  bleue, illisible en petit sur fond clair (ce n'est pas un défaut d'export).
  La maquette utilise une **proposition de version simplifiée**,
  `design/assets/logo-radio-audace-simplifie.svg` : « RADIO AUDACE » en
  lettres pleines (Arial Black resserré à 85 %), « 106.8 FM » repris des
  tracés d'origine. À valider ou à faire reprendre par un graphiste ;
  l'original (`logo-radio-audace.svg`) n'est pas modifié.
- Lecteur persistant : navigation sans rechargement pour que le son ne
  s'interrompe pas d'une page à l'autre (recommandé ; conditionne
  l'architecture du thème).
- Liens Facebook et WhatsApp de l'antenne : à fournir (YouTube connu :
  `https://www.youtube.com/@radioaudace`).
- « Parlons Droit ! » ou « Parlons Droits ! » : le visuel de l'émission porte
  un « s », la catégorie du site non.

**Prochaine étape : recueillir l'avis de l'utilisateur sur ces maquettes**, puis écrire le thème.

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
- Constats faits en préparant la maquette (à traiter dans le thème) :
  plusieurs titres sont saisis tout en capitales ; les extraits automatiques
  commencent par un surtitre (« ÉNERGIE | CAMEROUN | ÉCONOMIE ») ou par le
  premier intertitre collé au texte ; les images à la une ont des formats
  très variés (paysage, carré 447 px, portrait 730×1280) et contiennent
  parfois du texte ; la ponctuation française n'a pas d'espaces insécables.
- Constats supplémentaires (deuxième maquette) : le corps des articles
  contient des `h2` et des `p` vides ; « Que dit la loi » est une catégorie
  (id 67), pas une étiquette ; les extraits des replays valent
  « [carousel_slide id=’560′] » (reste de Divi, à ignorer) ; certains titres
  de 2022 sont écrits en caractères Unicode gras et émojis ; les vignettes
  YouTube des épisodes portent souvent le visuel de l'émission et le nom de
  l'animateur ; flux du direct : `https://stream.radio.audace.ovh/stream.mp3`.
- Grille : sur le staging (2026-11-11), la page « Grille des émissions » est
  en mode « relance » (« La nouvelle grille arrive prochainement ») ; les
  horaires des maquettes sont donc simulés. Le thème doit gérer ce mode :
  blocs « À l'antenne » réduits au bouton d'écoute. Modèle de la grille dans
  `audace-analytics` (`includes/class-aa-program.php`, option
  `audace_grille`) : nom, qui, jours, début, fin, description, image, logo,
  replays, présentateurs (nom, rôle, photo).
- Visuels d'émission : aucun sur le site. Les visuels officiels de la saison
  2021 (avec le nom des présentateurs) sont les vignettes des vidéos YouTube ;
  la maquette les réutilise (`design/assets/yt-*.jpg`,
  `visuel-femmes-daudace.jpg`). Second numéro de l'antenne relevé sur le
  staging : (+237) 6 58 31 14 55.
- **Ce qui passe réellement à l'antenne** (demande de l'utilisateur du
  2026-11-11) : la grille dit ce qui est prévu, mais l'antenne peut diffuser
  autre chose (musique, rediffusion). Le lecteur et les blocs « À l'antenne »
  doivent afficher la réalité, pilotée par RadioManager : une émission
  n'apparaît « en direct » que si son conducteur est en ligne ; sinon le
  lecteur affiche les métadonnées de la musique ou de la rediffusion.
  - Existant, à réutiliser : le plugin Radio Audace Player v3 interroge
    `https://api.cloud.audace.ovh/public/now-playing` (sans authentification,
    relais AJAX avec cache de 30 s) qui renvoie `current_show` (émission dont
    le conducteur est en cours, sinon `null`), `next_show` et `current_track`
    (artiste, titre, album, durée, `track_type`, `started_at`, poussés par
    RadioDJ). Relevé le 2026-11-11 : `current_show: null`, une piste musicale.
  - Manques constatés : aucune notion de rediffusion (`track_type` est `null`) ;
    le shortcode programme du plugin affiche « Aucune émission en cours » sans
    la piste quand il n'y a pas d'émission ; la grille d'`audace-analytics`
    (prévu) et le « now playing » du plugin lecteur (réel) sont deux sources
    séparées.
  - L'utilisateur envisage un plugin dédié pour gérer cela (lecteur et
    grille). À décider : nouveau plugin, ou extension du plugin lecteur.
  - Maquette : quatre cas (`?antenne=emission|musique|rediffusion|inconnu`),
    listés dans `design/index.html`.
- Lecteur : le thème doit piloter le lecteur du plugin Radio Audace Player,
  pas en créer un second ; la navigation sans rechargement est une couche
  ajoutée à un thème qui fonctionne sans elle.
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

- Dépôt GitHub `lwilly3/radio-audace-theme`, **public** depuis le 2026-11-11
  (ne jamais y mettre de secret ni d'adresse personnelle).
- Le site ne peut lire les releases que d'un dépôt **public** (leçon du
  plugin : dépôt privé = HTTP 404). Avant la première release, rendre ce
  dépôt public ou publier dans un dépôt miroir public.
- Updater : `radio-audace/inc/miseajour.php` (filtre
  `pre_set_site_transient_update_themes`, cache de 12 h, lien « Vérifier les
  mises à jour maintenant » sur Apparence › Thèmes). Il lit la dernière
  release de ce dépôt et installe son asset `radio-audace.zip` (racine
  `radio-audace/`). Écrit le 2026-11-11, logique essayée hors WordPress,
  jamais essayé sur le site.
- Publier une version : incrémenter la version dans `radio-audace/style.css`
  ET dans `radio-audace/functions.php` (`RADIO_AUDACE_THEME_VERSION`), ajouter
  la section `## [X.Y.Z]` dans `CHANGELOG.md`, commiter, pousser sur `main`,
  puis `./release.sh` (`./release.sh --essai` vérifie tout sans publier). Le
  script refuse de publier si le dépôt est privé, si `main` n'est pas poussée
  ou si la release existe.
- Le site est en 0.1.0, qui n'a pas l'updater : la première version qui le
  contient (0.2.0, `dist/radio-audace-0.2.0.zip`) doit être téléversée à la
  main une dernière fois.
- Fait le 2026-11-11, avec l'accord de l'utilisateur : dépôt rendu public,
  release `v0.2.0` publiée (asset `radio-audace.zip` téléchargeable sans
  authentification, vérifié). Reste à vérifier sur le site une fois la 0.2.0
  installée : l'accueil, puis une vraie mise à jour (la prochaine release).

## Arborescence

```text
radio-audace/   # le thème (ce dossier devient le ZIP installable)
design/         # maquettes HTML statiques
docs/           # documentation de conception
```

## Commits

Messages conventionnels en français. Auteur configuré localement avec
l'adresse noreply GitHub (le dépôt deviendra public) : ne pas la changer.
