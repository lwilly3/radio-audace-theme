<?php
/**
 * Fil « Audace en continu » : des titres d'information horodatés sur l'accueil.
 *
 * Les titres vivent dans RadioManager (module Social, écran « Info en continu »).
 * Le navigateur n'appelle jamais RadioManager : il lit la route relais du thème.
 *
 *   RadioManager  GET /social/live/feed  (clé partagée, en-tête X-Audace-Secret)
 *        │
 *   ra_continu()  copie de 60 secondes, dernière copie connue pendant 10 minutes si l'API ne répond pas
 *        │
 *   GET  radio-audace/v1/continu        relais public, lu par assets/js/theme.js
 *   POST radio-audace/v1/continu/purge  vide la copie ; appelé par RadioManager après chaque changement
 *
 * Mise en service — dans wp-config.php :
 *
 *   define( 'RA_CONTINU_CLE', '…' );   // la même valeur que SOCIAL_LIVE_FEED_SECRET côté API
 *   define( 'RA_CONTINU_API', '…' );   // facultatif : une autre API que celle de la station (site de test)
 *
 * Sans clé, rien ne se passe : aucun appel sortant, et l'accueil garde sa disposition par défaut.
 * C'est aussi le cas quand le fil est désactivé dans RadioManager, vide, trop ancien, ou quand
 * l'API est injoignable sans copie récente. Le gabarit par défaut (front-page.php) n'est pas modifié :
 * la disposition avec le fil est un second rendu, choisi au chargement de la page.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const RA_CONTINU_COPIE    = 60;   // Durée de la copie du flux, en secondes.
const RA_CONTINU_SECOURS  = 600;  // Âge maximal de la dernière copie connue servie quand l'API ne répond pas.
const RA_CONTINU_VISIBLES = 8;    // Le flux fournit au plus 8 titres.

/** Clé partagée avec RadioManager (chaîne vide : fonction hors service). */
function ra_continu_cle(): string {
    $cle = defined( 'RA_CONTINU_CLE' ) ? (string) RA_CONTINU_CLE : '';
    return trim( (string) apply_filters( 'ra_continu_cle', $cle ) );
}

/** Adresse de l'API qui sert le flux. */
function ra_continu_api(): string {
    $station = ra_station();
    $api     = defined( 'RA_CONTINU_API' ) && RA_CONTINU_API ? (string) RA_CONTINU_API : (string) $station['api'];
    return rtrim( $api, '/' );
}

/** Classe de couleur d'une rubrique du fil (les mêmes teintes que les rubriques du site). */
function ra_continu_classe( string $rubrique ): string {
    $cle = sanitize_title( $rubrique );
    foreach ( array( 'sport', 'economie', 'societe', 'politique', 'culture', 'education' ) as $connue ) {
        if ( str_starts_with( $cle, $connue ) ) {
            return $connue;
        }
    }
    return 'infos';
}

/**
 * Un titre du flux, prêt à afficher. Les heures sont données dans le fuseau du Cameroun.
 *
 * « jour » est vide pour un titre du jour ; sinon « Hier » ou la date : un « 22 h 40 » de la
 * veille ne doit pas passer pour un titre du jour.
 */
function ra_continu_titre( array $brut ): ?array {
    $titre = trim( wp_strip_all_tags( (string) ( $brut['title'] ?? '' ) ) );
    if ( '' === $titre || empty( $brut['id'] ) ) {
        return null;
    }
    $fuseau = new DateTimeZone( 'Africa/Douala' );
    $heure  = '';
    $iso    = '';
    $jour   = '';
    try {
        $quand = new DateTimeImmutable( (string) ( $brut['published_at'] ?? '' ) );
        $quand = $quand->setTimezone( $fuseau );
        $iso   = $quand->format( 'c' );
        $heure = ra_heure( $quand->format( 'H:i' ) );
        $ecart = (int) ( new DateTimeImmutable( 'today', $fuseau ) )->diff( $quand->setTime( 0, 0 ) )->format( '%r%a' );
        if ( -1 === $ecart ) {
            $jour = 'Hier';
        } elseif ( 0 !== $ecart ) {
            $jour = wp_date( 'j F', $quand->getTimestamp(), $fuseau );
        }
    } catch ( Exception $e ) {
        $heure = '';
    }
    $etiquette = (string) ( $brut['label'] ?? '' );
    $rubrique  = trim( wp_strip_all_tags( (string) ( $brut['category'] ?? '' ) ) );
    return array(
        'id'        => (int) $brut['id'],
        'titre'     => $titre,
        'rubrique'  => $rubrique,
        'classe'    => ra_continu_classe( $rubrique ),
        'etiquette' => in_array( $etiquette, array( 'urgent', 'rectificatif' ), true ) ? $etiquette : '',
        'heure'     => $heure,
        'iso'       => $iso,
        'jour'      => $jour,
    );
}

/**
 * Le fil, tel que le relais le sert.
 *
 * @return array{enabled: bool, active: bool, epingle: ?array, titres: array, dernier: string}
 */
function ra_continu_mettre_en_forme( array $flux ): array {
    $titres = array();
    foreach ( array_slice( (array) ( $flux['items'] ?? array() ), 0, RA_CONTINU_VISIBLES ) as $brut ) {
        $titre = is_array( $brut ) ? ra_continu_titre( $brut ) : null;
        if ( $titre ) {
            $titres[] = $titre;
        }
    }
    $epingle = is_array( $flux['pinned'] ?? null ) ? ra_continu_titre( $flux['pinned'] ) : null;
    return array(
        'enabled' => ! empty( $flux['enabled'] ),
        'active'  => ! empty( $flux['active'] ) && ( $titres || $epingle ) ? true : false,
        'epingle' => $epingle,
        'titres'  => $titres,
        'dernier' => $titres ? $titres[0]['heure'] : '',
    );
}

/** Fil hors service : l'accueil garde sa disposition par défaut. */
function ra_continu_vide(): array {
    return array( 'enabled' => false, 'active' => false, 'epingle' => null, 'titres' => array(), 'dernier' => '' );
}

/**
 * Lit le flux : la copie de 60 secondes si elle existe, sinon l'API.
 * API injoignable : la dernière copie connue pendant 10 minutes, puis plus rien.
 */
function ra_continu(): array {
    $cle = ra_continu_cle();
    if ( '' === $cle ) {
        return ra_continu_vide();
    }
    $copie = get_transient( 'ra_continu' );
    if ( is_array( $copie ) && isset( $copie['flux'] ) ) {
        return ra_continu_mettre_en_forme( (array) $copie['flux'] );
    }

    $reponse = wp_remote_get( ra_continu_api() . '/social/live/feed', array(
        'timeout' => 4,
        'headers' => array( 'X-Audace-Secret' => $cle, 'Accept' => 'application/json' ),
    ) );
    $flux = null;
    if ( ! is_wp_error( $reponse ) && 200 === wp_remote_retrieve_response_code( $reponse ) ) {
        $json = json_decode( wp_remote_retrieve_body( $reponse ), true );
        $flux = is_array( $json ) && isset( $json['items'] ) ? $json : null;
    }

    if ( null !== $flux ) {
        set_transient( 'ra_continu', array( 'flux' => $flux ), RA_CONTINU_COPIE );
        update_option( 'ra_continu_secours', array( 'flux' => $flux, 'a' => time() ), false );
        return ra_continu_mettre_en_forme( $flux );
    }

    // Panne : on ne rappelle pas l'API à chaque visite, et on sert la dernière copie tant qu'elle est récente.
    $secours = get_option( 'ra_continu_secours' );
    $recent  = is_array( $secours ) && isset( $secours['flux'], $secours['a'] ) && ( time() - (int) $secours['a'] ) <= RA_CONTINU_SECOURS;
    $flux    = $recent ? (array) $secours['flux'] : array( 'enabled' => false, 'active' => false, 'items' => array() );
    set_transient( 'ra_continu', array( 'flux' => $flux ), 30 );
    return ra_continu_mettre_en_forme( $flux );
}

/** Oublie toutes les copies : un titre retiré ne doit pas revenir par la copie de secours. */
function ra_continu_purger(): void {
    delete_transient( 'ra_continu' );
    delete_option( 'ra_continu_secours' );
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'radio-audace/v1', '/continu', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () {
            $reponse = rest_ensure_response( ra_continu() );
            // Pas de copie dans le navigateur : un titre retiré doit disparaître à la relecture suivante.
            $reponse->header( 'Cache-Control', 'no-store' );
            return $reponse;
        },
    ) );
    register_rest_route( 'radio-audace/v1', '/continu/purge', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true', // Le contrôle est la clé partagée, vérifiée ci-dessous.
        'callback'            => function ( WP_REST_Request $requete ) {
            $cle = ra_continu_cle();
            if ( '' === $cle ) {
                return new WP_Error( 'ra_continu_hors_service', 'Clé du fil non configurée.', array( 'status' => 503 ) );
            }
            if ( ! hash_equals( $cle, (string) $requete->get_header( 'x_audace_secret' ) ) ) {
                return new WP_Error( 'ra_continu_cle', 'Clé du fil invalide.', array( 'status' => 401 ) );
            }
            ra_continu_purger();
            return rest_ensure_response( array( 'purge' => true ) );
        },
    ) );
} );

/* ------------------------------------------------------------ Affichage */

/** Étiquettes et rubrique d'un titre. Jamais de rouge : il est réservé au direct. */
function ra_continu_meta( array $titre, bool $epingle = false ): string {
    $html = '<div class="meta">';
    if ( $epingle ) {
        $html .= '<span class="pastille-fil epingle">Épinglé</span>';
    }
    if ( 'urgent' === $titre['etiquette'] ) {
        $html .= '<span class="pastille-fil urgent">Urgent</span>';
    } elseif ( 'rectificatif' === $titre['etiquette'] ) {
        $html .= '<span class="pastille-fil rectificatif">Rectificatif</span>';
    }
    if ( '' !== $titre['rubrique'] ) {
        $html .= '<span class="rubrique ' . esc_attr( $titre['classe'] ) . '">' . esc_html( $titre['rubrique'] ) . '</span>';
    }
    return $html . '</div>';
}

/**
 * Le bloc du fil. Rien n'est cliquable : ce sont des titres, pas des articles.
 * Le même balisage est reproduit par assets/js/theme.js quand un titre arrive.
 */
function ra_bloc_continu( array $fil ): void {
    echo '<aside class="continu" id="continu" aria-labelledby="continu-titre" data-fil>';
    echo '<div class="continu-tete"><h2 id="continu-titre">Audace en continu</h2>';
    echo '<span class="maj" data-fil-maj' . ( $fil['dernier'] ? '' : ' hidden' ) . '>Dernier titre à ' . esc_html( $fil['dernier'] ) . '</span></div>';

    // Titre épinglé : hors de la frise, sans heure, pour ne pas casser l'ordre des heures.
    $epingle = $fil['epingle'];
    echo '<div class="epingle-fil" data-fil-epingle' . ( $epingle ? ' data-id="' . (int) $epingle['id'] . '"' : ' hidden' ) . '>';
    if ( $epingle ) {
        echo ra_continu_meta( $epingle, true ) . '<p>' . esc_html( $epingle['titre'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
    }
    echo '</div>';

    // Lecteurs d'écran : seuls les titres ajoutés sont annoncés (aria-relevant="additions").
    echo '<ol class="fil" data-fil-liste aria-live="polite" aria-relevant="additions">';
    $jour = '';
    foreach ( $fil['titres'] as $titre ) {
        if ( '' !== $titre['jour'] && $titre['jour'] !== $jour ) {
            echo '<li class="jour">' . esc_html( $titre['jour'] ) . '</li>';
        }
        $jour = $titre['jour'];
        echo '<li data-id="' . (int) $titre['id'] . '" data-jour="' . esc_attr( $titre['jour'] ) . '">';
        echo '<time datetime="' . esc_attr( $titre['iso'] ) . '">' . esc_html( $titre['heure'] ) . '</time>';
        echo '<div>' . ra_continu_meta( $titre ) . '<p>' . esc_html( $titre['titre'] ) . '</p></div></li>'; // phpcs:ignore WordPress.Security.EscapeOutput
    }
    echo '</ol>';
    echo '<button class="plus" type="button" data-fil-plus aria-expanded="false"' . ( count( $fil['titres'] ) > 3 ? '' : ' hidden' ) . '>Voir plus de titres</button>';
    echo '</aside>';
}

/**
 * L'antenne en bandeau, au-dessus de la une et du fil.
 *
 * Grille désactivée : ce qui passe réellement, le bouton d'écoute, un lien vers les émissions.
 * Grille active : en plus, la prochaine émission, annoncée « Prévu à… » (la grille ne garantit
 * pas l'antenne), et le lien vers la grille. Un seul lien dans tous les cas.
 */
function ra_bandeau_antenne_continu(): void {
    $a = ra_antenne();
    echo '<aside class="antenne" id="antenne" aria-label="À l\'antenne" data-antenne>';
    echo '<div class="bloc"><span class="etiquette"><span class="pastille"></span><span data-a="label">' . esc_html( $a['label'] ) . '</span></span>';
    echo '<h2 data-a="titre">' . esc_html( $a['titre'] ) . '</h2>';
    echo '<p class="quoi" data-a="detail">' . esc_html( $a['detail'] ) . '</p></div>';
    $prochaine = $a['suite'][0] ?? null;
    if ( $prochaine ) {
        echo '<p class="ensuite"><span>Prévu à ' . esc_html( $prochaine['heure'] ) . '</span><b>' . esc_html( $prochaine['titre'] ) . '</b></p>';
    }
    echo '<div class="commandes">';
    ra_bouton_direct();
    echo '</div>';
    if ( $prochaine && ra_url_grille() ) {
        echo '<a class="vers-grille" href="' . esc_url( ra_url_grille() ) . '">Toute la grille</a>';
    } elseif ( ra_id_emissions() ) {
        echo '<a class="vers-grille" href="' . esc_url( get_category_link( ra_id_emissions() ) ) . '">Les émissions</a>';
    }
    echo '</aside>';
}
