<?php
/**
 * Supports du thème, feuilles de style, scripts et coordonnées de la station.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'responsive-embeds' );
    add_image_size( 'ra-carte', 800, 0 );
    add_image_size( 'ra-une', 1600, 0 );
    register_nav_menus( array(
        'principal' => 'Menu principal (laisser vide pour le menu automatique)',
    ) );
} );

/**
 * Coordonnées et réglages de la station. Un seul endroit, filtrable.
 * Les liens laissés vides ne sont pas affichés (pas de lien mort).
 */
function ra_station(): array {
    $rap = get_option( 'rap_options', array() ); // Réglages du plugin Radio Audace Player, s'il est installé.
    return apply_filters( 'ra_station', array(
        'nom'        => 'Radio Audace',
        'frequence'  => '106.8 FM',
        'accroche'   => "Bien plus que l'info. Le direct, les émissions et l'actualité du Cameroun, à Douala et partout en ligne.",
        'adresse'    => array( 'Rue Tokoto, Bonapriso', 'BP 1797 Douala, Cameroun' ),
        'telephones' => array( '(+237) 223 42 09 49', '(+237) 6 58 31 14 55' ),
        'courriel'   => 'contact@audacemediagroup.com',
        'reseaux'    => array(
            'WhatsApp' => '',
            'Facebook' => '',
            'YouTube'  => 'https://www.youtube.com/@radioaudace',
        ),
        'flux'       => ! empty( $rap['stream_url'] ) ? $rap['stream_url'] : 'https://stream.radio.audace.ovh/stream.mp3',
        'api'        => ! empty( $rap['api_url'] ) ? $rap['api_url'] : 'https://api.cloud.audace.ovh',
        'editeur'    => 'Audace Media Group',
    ) );
}

/** Numéro de téléphone au format d'un lien tel:. */
function ra_tel( string $numero ): string {
    return 'tel:+' . preg_replace( '/\D/', '', $numero );
}

add_action( 'wp_enqueue_scripts', function () {
    $v   = RADIO_AUDACE_THEME_VERSION;
    $uri = get_template_directory_uri();
    wp_enqueue_style( 'radio-audace-polices', $uri . '/assets/css/polices.css', array(), $v );
    wp_enqueue_style( 'radio-audace', $uri . '/assets/css/theme.css', array( 'radio-audace-polices' ), $v );
    wp_enqueue_script( 'radio-audace', $uri . '/assets/js/theme.js', array(), $v, array( 'in_footer' => true, 'strategy' => 'defer' ) );

    $station = ra_station();
    wp_localize_script( 'radio-audace', 'RadioAudace', array(
        'flux'       => $station['flux'],
        'antenne'    => ra_antenne(),
        'antenneUrl' => rest_url( 'radio-audace/v1/antenne' ),
        // Statistiques d'écoute : envoyées au plugin lecteur s'il est actif (même tableau de bord RadioManager).
        'stats'      => function_exists( 'rap_ajax_analytics' ) ? admin_url( 'admin-ajax.php' ) : '',
        'accueil'    => home_url( '/' ),
    ) );
} );

// Les blocs Gutenberg ne servent pas : les articles arrivent en HTML simple.
add_action( 'wp_enqueue_scripts', function () {
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'global-styles' );
    wp_dequeue_style( 'classic-theme-styles' );
}, 100 );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

// Préchargement de la police de texte : évite un saut de mise en page au premier affichage.
add_action( 'wp_head', function () {
    printf(
        '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
        esc_url( get_template_directory_uri() . '/assets/fonts/inter-latin.woff2' )
    );
}, 1 );

/**
 * Le thème porte son propre lecteur (bas d'écran) et sa propre navigation sans
 * rechargement. On coupe donc, à l'affichage seulement, le lecteur flottant et
 * la navigation Pjax du plugin Radio Audace Player : deux lecteurs sur la même
 * page joueraient deux fois le flux. Les réglages enregistrés ne sont pas modifiés.
 */
add_filter( 'option_rap_options', function ( $options ) {
    if ( is_admin() || ! is_array( $options ) ) {
        return $options;
    }
    $options['floating_enabled']    = false;
    $options['persistent_playback'] = false;
    return $options;
} );

// Même coupure quand le plugin n'a jamais enregistré de réglages (il applique alors ses valeurs par défaut).
add_action( 'wp', function () {
    remove_action( 'wp_footer', 'rap_render_floating_player', 99 );
} );

add_filter( 'body_class', function ( array $classes ): array {
    $classes[] = 'ra';
    return $classes;
} );

/**
 * Vrai sur la racine du site, première page.
 *
 * Sur radioaudace.com, les réglages de lecture hérités d'Extra font que WordPress
 * ne reconnaît pas la racine comme « page d'accueil » (is_front_page() est faux) :
 * il choisirait le gabarit de liste. On s'appuie donc sur l'adresse demandée.
 */
function ra_est_accueil(): bool {
    if ( isset( $_GET['liste'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- « Tous les articles » : la liste, pas l'accueil.
        return false;
    }
    if ( is_front_page() && ! is_paged() ) {
        return true;
    }
    return is_home() && ! is_paged() && ! get_queried_object_id() && ! is_search();
}

add_filter( 'template_include', function ( string $gabarit ): string {
    if ( ra_est_accueil() ) {
        $accueil = locate_template( 'front-page.php' );
        if ( $accueil ) {
            return $accueil;
        }
    }
    return $gabarit;
}, 20 );

add_filter( 'body_class', function ( array $classes ): array {
    if ( ra_est_accueil() && ! in_array( 'home', $classes, true ) ) {
        $classes[] = 'home';
    }
    return $classes;
} );

// Listes en grille de trois : douze cartes par page.
add_action( 'pre_get_posts', function ( WP_Query $requete ) {
    if ( ! is_admin() && $requete->is_main_query() && ( $requete->is_archive() || $requete->is_home() || $requete->is_search() ) ) {
        $requete->set( 'posts_per_page', 12 );
    }
} );

add_filter( 'excerpt_length', function () {
    return 34;
} );
add_filter( 'excerpt_more', function () {
    return '…';
} );
