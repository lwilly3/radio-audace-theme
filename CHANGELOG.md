# Changelog

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/) et le
[Semantic Versioning](https://semver.org/lang/fr/).

Chaque section `## [X.Y.Z]` sert de notes à la release GitHub du même numéro
(`./release.sh`).

## [Non publié]

## [0.5.0] - 2026-11-24

### Ajouté
- Fil « Audace en continu » sur l'accueil : des titres d'information horodatés, saisis dans RadioManager (module Social, écran « Info en continu »). Quand le fil est actif, l'antenne passe en bandeau, la une reste à gauche et le fil s'affiche à droite (sous la une sur téléphone). Les titres se mettent à jour seuls toutes les 90 secondes ; un titre retiré disparaît aussitôt
- Sans clé configurée, ou quand le fil est désactivé, vide ou trop ancien, l'accueil reste exactement celui d'avant
- Mise en service : ajouter `define( 'RA_CONTINU_CLE', '…' );` dans `wp-config.php`, avec la même valeur que `SOCIAL_LIVE_FEED_SECRET` côté RadioManager

## [0.4.1] - 2026-11-24

### Corrigé
- Le son ne s'interrompt plus d'une page à l'autre quand on est connecté à WordPress : la navigation sans rechargement était réservée aux visiteurs non connectés. La barre d'administration suit désormais la page affichée

## [0.4.0] - 2026-11-12

### Ajouté
- Page « Écouter » à l'adresse `/ecouter` : un poste de radio plein écran, à envoyer par lien (WhatsApp, SMS, réseaux). Un appui lance le direct ; la vitre du cadran s'éclaire, le voyant s'allume, et l'on peut jouer avec le bouton d'accord. Rien à créer dans WordPress : l'adresse est fournie par le thème
- Fiche de partage propre à cette page : elle montre le poste allumé
- Pied de page : lien « Le poste : écouter en ligne »

## [0.3.0] - 2026-11-11

### Ajouté
- Fiche de partage (WhatsApp, Facebook, X) sur l'accueil, les émissions, les rubriques, les replays et les pages : titre, description et image. Le thème complète ce qu'une extension produit déjà pour les articles, sans le doubler
- Image de partage par défaut et icône du site (tant qu'aucune icône n'est choisie dans WordPress)

### Corrigé
- Description des articles partagés : le surtitre « ÉNERGIE | CAMEROUN | ÉCONOMIE » n'apparaît plus en tête

## [0.2.4] - 2026-11-11

### Ajouté
- Pied de page : lien vers le compte X de la radio

## [0.2.3] - 2026-11-11

### Ajouté
- Pied de page : lien vers la page Facebook de la radio

## [0.2.2] - 2026-11-11

### Corrigé
- Accueil : sans grille des programmes, le bloc « À l'antenne » restait à moitié vide. Il présente maintenant les principales émissions, avec un lien vers toutes les émissions

## [0.2.1] - 2026-11-11

### Ajouté
- Aperçu du thème dans Apparence › Thèmes

### Corrigé
- Mise à jour depuis WordPress : la vérification échouait avec « HTTP 403 ». Le thème lit désormais l'adresse publique des releases GitHub au lieu de l'API, limitée à 60 requêtes par heure pour toute l'adresse IP de l'hébergement mutualisé
- Accueil : la une est de nouveau le dernier article ayant une image. Presque tous les articles récents du site étant épinglés, un article sans image prenait la place

## [0.2.0] - 2026-11-11

### Ajouté
- Mise à jour du thème depuis WordPress : une release GitHub plus récente est proposée dans Tableau de bord › Mises à jour, avec un lien « Vérifier les mises à jour maintenant » sur Apparence › Thèmes
- Script `release.sh` : vérifie les versions, le journal et la syntaxe, construit `radio-audace.zip` et publie la release
- Présentation par défaut de sept émissions, tant que RadioManager n'en fournit pas

### Corrigé
- Accueil : le site affichait la liste « Tous les articles » au lieu de la page d'accueil (antenne, une, émissions)
- Titres saisis tout en capitales : réduits dans les listes et à la une
- Bandeau du direct : un titre de chanson long tient sur une ligne

## [0.1.0] - 2026-11-11

### Ajouté
- Première version du thème : accueil, article, replay vidéo, pages d'émission et de rubrique, grille, recherche, 404
- Lecteur du direct persistant, avec navigation sans rechargement pendant l'écoute
- Antenne réelle (émission en ligne ou musique) lue depuis RadioManager
- Nettoyage à l'affichage du HTML reçu de RadioManager et des anciens articles Divi
- Polices et logo simplifié hébergés dans le thème
- Maquettes statiques dans `design/` (sommaire : `design/index.html`) : accueil, article, article « cas difficiles », replay, émission, rubrique, grille
