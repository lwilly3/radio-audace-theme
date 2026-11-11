<?php
/**
 * Ce qui passe à l'antenne, et la grille des programmes.
 *
 * Deux sources, toutes deux pilotées par RadioManager :
 *  - la GRILLE (ce qui est prévu) : option « audace_grille » du plugin audace-analytics ;
 *  - l'ANTENNE (ce qui passe réellement) : l'API publique « now-playing », qui renvoie
 *    l'émission dont le conducteur est en ligne et la piste diffusée par RadioDJ.
 *
 * Règle : une émission n'est annoncée « en direct » que si l'API le dit. Sinon on
 * affiche la musique en cours, et à défaut « Radio Audace » sans rien affirmer.
 * (La rediffusion n'est pas encore signalée par RadioManager : elle n'est pas gérée.)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** « 13:30 » → « 13 h 30 », « 15:00 » → « 15 h ». */
function ra_heure( string $hhmm ): string {
    if ( ! preg_match( '~^(\d{1,2}):(\d{2})~', $hhmm, $m ) ) {
        return $hhmm;
    }
    return (int) $m[1] . "\u{00A0}h" . ( '00' === $m[2] ? '' : "\u{00A0}" . $m[2] );
}

/** Nom ramené à une clé de comparaison (« Le Train d'Audace » = « train d’audace »). */
function ra_cle_nom( string $nom ): string {
    $nom = mb_strtolower( remove_accents( html_entity_decode( $nom, ENT_QUOTES, 'UTF-8' ) ) );
    $nom = preg_replace( '~^(le|la|les|l)\b~', '', trim( preg_replace( '~[^a-z0-9]+~', ' ', $nom ) ) );
    return str_replace( array( ' ', 'infos', 'droits' ), array( '', 'info', 'droit' ), (string) $nom );
}

/** Entrées de la grille (tableau vide si le plugin est absent ou la grille en préparation). */
function ra_grille(): array {
    $reglages = ra_grille_reglages();
    if ( 'relance' === $reglages['mode'] ) {
        return array();
    }
    $grille = get_option( 'audace_grille' );
    return is_array( $grille ) ? array_values( array_filter( $grille, 'is_array' ) ) : array();
}

function ra_grille_reglages(): array {
    $r = get_option( 'audace_grille_settings' );
    return wp_parse_args( is_array( $r ) ? $r : array(), array(
        'mode'    => 'normal',
        'titre'   => 'La grille',
        'message' => '',
    ) );
}

/** Entrée de grille correspondant à un nom d'émission, ou null. */
function ra_grille_pour( string $nom ): ?array {
    $cle = ra_cle_nom( $nom );
    foreach ( ra_grille() as $e ) {
        if ( ! empty( $e['nom'] ) && ra_cle_nom( $e['nom'] ) === $cle ) {
            return $e;
        }
    }
    return null;
}

/** Créneaux d'un jour (1 = lundi … 7 = dimanche), dans l'ordre. */
function ra_grille_jour( int $jour ): array {
    $creneaux = array_filter( ra_grille(), function ( $e ) use ( $jour ) {
        return in_array( $jour, array_map( 'intval', (array) ( $e['jours'] ?? array() ) ), true ) && ! empty( $e['debut'] );
    } );
    usort( $creneaux, function ( $a, $b ) {
        return strcmp( $a['debut'], $b['debut'] );
    } );
    return $creneaux;
}

/** « Chaque samedi », « Du lundi au vendredi »… d'après les jours d'une entrée de grille. */
function ra_jours_texte( array $jours ): string {
    $noms  = array( 1 => 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche' );
    $jours = array_values( array_unique( array_map( 'intval', $jours ) ) );
    sort( $jours );
    if ( 7 === count( $jours ) ) {
        return 'Tous les jours';
    }
    if ( array( 1, 2, 3, 4, 5 ) === $jours ) {
        return 'Du lundi au vendredi';
    }
    if ( 1 === count( $jours ) && isset( $noms[ $jours[0] ] ) ) {
        return 'Chaque ' . $noms[ $jours[0] ];
    }
    return ra_majuscule( implode( ', ', array_map( function ( $j ) use ( $noms ) {
        return $noms[ $j ] ?? '';
    }, $jours ) ) );
}

/** Les prochains créneaux prévus (aujourd'hui puis demain), pour « À suivre ». */
function ra_a_suivre( int $combien = 4 ): array {
    $maintenant = current_datetime();
    $suite      = array();
    for ( $d = 0; $d < 2 && count( $suite ) < $combien; $d++ ) {
        $jour = $maintenant->modify( "+$d day" );
        foreach ( ra_grille_jour( (int) $jour->format( 'N' ) ) as $e ) {
            if ( 0 === $d && $e['debut'] <= $maintenant->format( 'H:i' ) ) {
                continue;
            }
            $suite[] = array(
                'heure'  => ra_heure( $e['debut'] ),
                'titre'  => $e['nom'],
                'detail' => trim( ( 1 === $d ? 'Demain' : '' ) . ( ! empty( $e['qui'] ) ? ( 1 === $d ? ' · ' : '' ) . 'avec ' . $e['qui'] : '' ) ),
            );
            if ( count( $suite ) >= $combien ) {
                break;
            }
        }
    }
    return $suite;
}

/** Réponse brute de l'API « now-playing », gardée 30 secondes. Null si l'API ne répond pas. */
function ra_now_playing(): ?array {
    $cache = get_transient( 'ra_now_playing' );
    if ( is_array( $cache ) ) {
        return $cache['ok'] ? $cache['data'] : null;
    }
    $station  = ra_station();
    $reponse  = wp_remote_get( rtrim( $station['api'], '/' ) . '/public/now-playing', array( 'timeout' => 4 ) );
    $data     = null;
    if ( ! is_wp_error( $reponse ) && 200 === wp_remote_retrieve_response_code( $reponse ) ) {
        $json = json_decode( wp_remote_retrieve_body( $reponse ), true );
        $data = is_array( $json ) ? $json : null;
    }
    set_transient( 'ra_now_playing', array( 'ok' => null !== $data, 'data' => $data ), 30 );
    return $data;
}

/**
 * État de l'antenne, prêt à afficher.
 *
 * @return array{type: string, label: string, titre: string, detail: string, desc: string, suite: array}
 */
function ra_antenne(): array {
    $station = ra_station();
    $freq    = $station['frequence'];
    $suite   = ra_a_suivre();
    $np      = ra_now_playing();

    $etat = array(
        'type'   => 'inconnu',
        'label'  => 'En direct · ' . $freq,
        'titre'  => $station['nom'],
        'detail' => "Bien plus que l'info",
        'desc'   => '',
        'suite'  => $suite,
    );
    if ( ! $np ) {
        return apply_filters( 'ra_antenne', $etat, $np );
    }

    $emission = $np['current_show'] ?? null;
    $piste    = $np['current_track'] ?? null;

    if ( is_array( $emission ) && ! empty( $emission['title'] ) ) {
        $grille  = ra_grille_pour( (string) $emission['title'] );
        $details = array();
        if ( ! empty( $emission['presenters'][0]['name'] ) ) {
            $details[] = 'avec ' . $emission['presenters'][0]['name'];
        }
        if ( $grille && ! empty( $grille['fin'] ) ) {
            $details[] = "jusqu'à " . ra_heure( $grille['fin'] );
        }
        $etat['type']   = 'emission';
        $etat['titre']  = (string) $emission['title'];
        $etat['detail'] = ra_majuscule( implode( ' · ', $details ) );
        $etat['desc']   = ! empty( $emission['current_segment']['title'] )
            ? 'En ce moment : ' . $emission['current_segment']['title']
            : (string) ( $grille['description'] ?? '' );
        return apply_filters( 'ra_antenne', $etat, $np );
    }

    if ( is_array( $piste ) && ! empty( $piste['title'] ) ) {
        $artiste = trim( (string) ( $piste['artist'] ?? '' ) );
        if ( '' === $artiste || 0 === strcasecmp( $artiste, 'Unknown Artist' ) ) {
            $artiste = '';
        }
        $etat['type']   = 'musique';
        $etat['label']  = 'En ce moment · ' . $freq;
        $etat['titre']  = (string) $piste['title'];
        $etat['detail'] = ( $artiste ? $artiste . ' · ' : '' ) . 'Musique';
        if ( ! empty( $np['next_show']['title'] ) ) {
            $etat['desc'] = 'Prochaine émission : ' . $np['next_show']['title'] . '.';
        } elseif ( $suite ) {
            $etat['desc'] = 'Prochaine émission : ' . $suite[0]['titre'] . ', à ' . $suite[0]['heure'] . '.';
        }
    }
    return apply_filters( 'ra_antenne', $etat, $np );
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'radio-audace/v1', '/antenne', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () {
            $reponse = rest_ensure_response( ra_antenne() );
            $reponse->header( 'Cache-Control', 'public, max-age=30' );
            return $reponse;
        },
    ) );
} );
