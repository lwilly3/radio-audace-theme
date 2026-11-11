# Changelog

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/) et le
[Semantic Versioning](https://semver.org/lang/fr/).

Chaque section `## [X.Y.Z]` sert de notes à la release GitHub du même numéro
(`./release.sh`).

## [Non publié]

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
