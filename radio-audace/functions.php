<?php
/**
 * Thème Radio Audace.
 *
 * Le thème affiche des articles écrits dans RadioManager : il ne suppose aucune
 * intervention dans l'éditeur WordPress. Tout ce qui dépend d'un plugin
 * (grille, antenne, lecteur) se dégrade proprement si le plugin est absent.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Version : toujours identique à l'en-tête de style.css (release.sh le vérifie).
define( 'RADIO_AUDACE_THEME_VERSION', '0.2.4' );

require get_template_directory() . '/inc/reglages.php';   // Supports, scripts, coordonnées de la station.
require get_template_directory() . '/inc/familles.php';   // Rubriques, émissions, replays.
require get_template_directory() . '/inc/contenu.php';    // Nettoyage du HTML reçu de RadioManager et de Divi.
require get_template_directory() . '/inc/antenne.php';    // Ce qui passe à l'antenne, grille.
require get_template_directory() . '/inc/gabarits.php';   // Fragments d'affichage partagés.
require get_template_directory() . '/inc/miseajour.php';  // Mises à jour depuis les releases GitHub.
