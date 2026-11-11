<?php
/**
 * Thème Radio Audace — squelette minimal, à construire après validation de la maquette.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RADIO_AUDACE_THEME_VERSION', '0.1.0' );

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'radio-audace', get_stylesheet_uri(), array(), RADIO_AUDACE_THEME_VERSION );
} );
