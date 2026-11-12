<?php
/**
 * Page « Écouter » : un lien à envoyer (WhatsApp, SMS, réseaux) qui ouvre un poste de radio plein écran.
 *
 * Adresse : /ecouter (filtre « ra_ecoute_chemin »). C'est une adresse du thème, pas une page
 * WordPress : rien à créer dans l'administration, aucune règle de réécriture à enregistrer.
 * Gabarit : ecouter.php. Styles et script propres à la page : assets/css/ecouter.css, assets/js/ecouter.js.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Chemin de la page, sans barres (« ecouter »). */
function ra_ecoute_chemin(): string {
    return trim( (string) apply_filters( 'ra_ecoute_chemin', 'ecouter' ), '/' );
}

/** Adresse complète de la page « Écouter ». */
function ra_url_ecoute(): string {
    return home_url( user_trailingslashit( ra_ecoute_chemin() ) );
}

/** Vrai quand la page demandée est la page « Écouter ». */
function ra_est_ecoute( ?bool $definir = null ): bool {
    static $oui = false;
    if ( null !== $definir ) {
        $oui = $definir;
    }
    return $oui;
}

/**
 * WordPress ne connaît pas cette adresse : il s'apprêtait à répondre « page introuvable ».
 * On remplace sa demande par une requête minimale (un article, sans décompte), et le
 * gabarit ecouter.php prend la suite. L'adresse est réservée au thème : une page
 * WordPress qui porterait le même identifiant ne serait plus affichée.
 */
add_action( 'parse_request', function ( WP $wp ) {
    $chemin = ra_ecoute_chemin();
    if ( '' === $chemin || is_admin() || trim( (string) $wp->request, '/' ) !== $chemin ) {
        return;
    }
    ra_est_ecoute( true );
    $wp->query_vars = array(
        'posts_per_page'      => 1,
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    );
} );

// WordPress ne doit pas « corriger » l'adresse en redirigeant vers l'accueil.
add_filter( 'redirect_canonical', function ( $redirection ) {
    return ra_est_ecoute() ? false : $redirection;
} );

add_filter( 'template_include', function ( string $gabarit ): string {
    if ( ra_est_ecoute() ) {
        $ecoute = locate_template( 'ecouter.php' );
        if ( $ecoute ) {
            return $ecoute;
        }
    }
    return $gabarit;
}, 30 );

/** Titre de la page (onglet du navigateur, fiche de partage). */
function ra_ecoute_titre(): string {
    $station = ra_station();
    return 'Écouter ' . $station['nom'] . ' ' . $station['frequence'] . ' en direct';
}

add_filter( 'pre_get_document_title', function ( $titre ) {
    return ra_est_ecoute() ? ra_ecoute_titre() : $titre;
}, 99 );

add_filter( 'body_class', function ( array $classes ): array {
    if ( ! ra_est_ecoute() ) {
        return $classes;
    }
    $classes   = array_values( array_diff( $classes, array( 'home', 'blog' ) ) );
    $classes[] = 'ecoute';
    return $classes;
}, 20 );

add_action( 'wp_enqueue_scripts', function () {
    if ( ! ra_est_ecoute() ) {
        return;
    }
    $v   = RADIO_AUDACE_THEME_VERSION;
    $uri = get_template_directory_uri();
    wp_enqueue_style( 'radio-audace-ecouter', $uri . '/assets/css/ecouter.css', array( 'radio-audace' ), $v );
    wp_enqueue_script( 'radio-audace-ecouter', $uri . '/assets/js/ecouter.js', array( 'radio-audace' ), $v, array( 'in_footer' => true, 'strategy' => 'defer' ) );
}, 20 );

add_action( 'wp_head', function () {
    if ( ra_est_ecoute() ) {
        echo '<link rel="canonical" href="' . esc_url( ra_url_ecoute() ) . '">' . "\n";
        echo '<meta name="theme-color" content="#0A1628">' . "\n";
    }
}, 2 );

// Fiche de partage : on voit la fiche avant la page, elle montre donc le poste allumé.
add_filter( 'ra_fiche_partage', function ( array $fiche ): array {
    if ( ! ra_est_ecoute() ) {
        return $fiche;
    }
    $fiche['titre']       = ra_ecoute_titre();
    $fiche['description'] = "Le direct de Douala, où que vous soyez. Un appui et le poste s'allume.";
    $fiche['image']       = get_template_directory_uri() . '/assets/img/partage-ecouter.jpg';
    $fiche['largeur']     = 1200;
    $fiche['hauteur']     = 630;
    $fiche['url']         = ra_url_ecoute();
    $fiche['type']        = 'website';
    return $fiche;
} );
