<?php
/**
 * Fragments d'affichage partagés par les gabarits.
 * Chaque fonction écrit directement son HTML (échappé).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ra_icone( string $nom ): string {
    $icones = array(
        'jouer'    => '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M2.5.8v12.4L13 7z"/></svg>',
        'loupe'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.8-3.8"/></svg>',
        'menu'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
        'croix'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>',
        'chevg'    => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M10 3 5 8l5 5"/></svg>',
        'chevd'    => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="m6 3 5 5-5 5"/></svg>',
        'whatsapp' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 1.800a8.200 8.200 0 1 1-4.200 15.300l-.3-.2-3 .8.8-2.900-.2-.3A8.200 8.200 0 0 1 12 3.800zm-3.200 4c-.2 0-.5 0-.7.300-.3.300-1 1-1 2.300s1 2.700 1.100 2.900c.2.2 2 3.100 4.900 4.300 2.400 1 2.900.8 3.400.7.500 0 1.700-.7 1.900-1.300.2-.7.2-1.200.2-1.300-.1-.2-.3-.2-.6-.4l-1.900-.9c-.3-.1-.5-.2-.7.100l-.9 1.100c-.2.2-.3.2-.6.100-.3-.2-1.200-.5-2.300-1.400-.8-.8-1.400-1.700-1.600-2-.2-.3 0-.5.100-.6l.5-.5.3-.5c.1-.2 0-.4 0-.5l-.9-2c-.2-.5-.4-.4-.6-.4z"/></svg>',
        'facebook' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.500 21v-7.500H16l.4-3h-2.900V8.600c0-.9.3-1.500 1.500-1.500h1.500V4.400c-.3 0-1.200-.1-2.200-.1-2.200 0-3.700 1.300-3.700 3.800v2.400H8v3h2.600V21z"/></svg>',
        'x'        => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.200 4h2.800l-6.100 7 7.100 9h-5.600l-4.400-5.700L6 20H3.200l6.500-7.500L2.900 4h5.700l4 5.200zm-1 14.300h1.500L7.800 5.600H6.200z"/></svg>',
        'lien'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.600 13.400a1 1 0 0 1 0-1.400l3-3a1 1 0 1 1 1.400 1.400l-3 3a1 1 0 0 1-1.400 0zM7 17a4 4 0 0 1 0-5.700l2-2 1.400 1.400-2 2A2 2 0 0 0 11.300 15.600l2-2 1.400 1.400-2 2A4 4 0 0 1 7 17zm6.600-6.600-1.400-1.400 2-2A4 4 0 0 1 19.900 12.700l-2 2-1.400-1.400 2-2A2 2 0 0 0 15.600 8.400z"/></svg>',
    );
    return $icones[ $nom ] ?? '';
}

/** Menu personnalisé rendu comme une simple suite de liens (même HTML que le menu automatique). */
class RA_Menu_Plat extends Walker_Nav_Menu {
    public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
        $actif   = in_array( 'current-menu-item', (array) $data_object->classes, true ) || in_array( 'current-menu-parent', (array) $data_object->classes, true );
        $output .= sprintf( '<a href="%s"%s>%s</a>', esc_url( $data_object->url ), $actif ? ' aria-current="page"' : '', esc_html( $data_object->title ) );
    }
    public function end_el( &$output, $data_object, $depth = 0, $args = null ) {}
    public function start_lvl( &$output, $depth = 0, $args = null ) {}
    public function end_lvl( &$output, $depth = 0, $args = null ) {}
}

/** Rapport largeur / hauteur de l'image à la une (0 s'il n'y en a pas). */
function ra_ratio_image( $post = null ): float {
    $id = get_post_thumbnail_id( $post );
    if ( ! $id ) {
        return 0.0;
    }
    $meta = wp_get_attachment_metadata( $id );
    if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
        return 1.5;
    }
    return round( $meta['width'] / $meta['height'], 3 );
}

/** Surtitre d'un article : rubrique (et sous-rubrique), ou émission pour un replay. */
function ra_surtitre( $post = null, bool $liens = false ): void {
    $f      = ra_familles( $post );
    $termes = ra_est_replay( $post ) && $f['emission'] ? array( $f['emission'] ) : array_filter( array( $f['rubrique'], $f['sous'] ) );
    if ( ! $termes && $f['emission'] ) {
        $termes = array( $f['emission'] );
    }
    if ( ! $termes ) {
        return;
    }
    $morceaux = array();
    foreach ( $termes as $terme ) {
        $nom        = esc_html( ra_nom_categorie( $terme ) );
        $morceaux[] = $liens ? '<a href="' . esc_url( get_category_link( $terme ) ) . '">' . $nom . '</a>' : $nom;
    }
    if ( ra_est_replay( $post ) && ! $liens ) {
        $morceaux = array_slice( $morceaux, 0, 1 );
    }
    printf(
        '<span class="rubrique %s">%s</span>',
        esc_attr( ra_classe_rubrique( reset( $termes ) ) ),
        implode( ' · ', $morceaux ) // phpcs:ignore WordPress.Security.EscapeOutput -- déjà échappé.
    );
}

function ra_date( $post = null ): void {
    $post = get_post( $post );
    if ( ra_est_replay( $post ) ) {
        $infos = ra_replay_infos( $post );
        printf( '<time class="date" datetime="%s">%s</time>', esc_attr( $infos['date'] ), esc_html( ra_date_longue( $infos['date'] ) ) );
        return;
    }
    printf( '<time class="date" datetime="%s">%s</time>', esc_attr( get_the_date( 'Y-m-d', $post ) ), esc_html( ra_date_longue( get_the_date( 'Y-m-d', $post ) ) ) );
}

function ra_titre( $post = null ): string {
    $post = get_post( $post );
    return ra_est_replay( $post ) ? ra_replay_infos( $post )['titre'] : ra_titre_propre( $post->post_title );
}

/**
 * Carte d'article.
 * - avec image : vignette ; image portrait ou carrée montrée entière sur un fond flouté ;
 * - sans image : carte typographique avec le chapeau ;
 * - replay : vignette de la vidéo et pastille « Vidéo ».
 *
 * @param array{extrait?: bool, surtitre?: bool} $args
 */
function ra_carte( $post, array $args = array() ): void {
    $post = get_post( $post );
    if ( ! $post ) {
        return;
    }
    $args   = wp_parse_args( $args, array( 'extrait' => false, 'surtitre' => true ) );
    $replay = ra_est_replay( $post );
    $ratio  = ra_ratio_image( $post );
    $yt     = $replay ? ra_youtube_id( $post ) : '';
    $classe = 'carte apparait' . ( $replay ? ' video' : '' ) . ( ! $ratio && ! $yt ? ' sans-image' : '' );

    echo '<a class="' . esc_attr( $classe ) . '" href="' . esc_url( get_permalink( $post ) ) . '">';
    if ( $yt ) {
        echo '<figure class="vignette"><img src="' . esc_url( ra_vignette_youtube( $yt ) ) . '" alt="" loading="lazy" width="320" height="180">';
        echo '<span class="jouer-badge">' . ra_icone( 'jouer' ) . 'Vidéo</span></figure>'; // phpcs:ignore WordPress.Security.EscapeOutput
    } elseif ( $ratio ) {
        if ( $ratio < 1.15 ) { // Portrait ou carré : on ne recadre pas.
            echo '<figure class="vignette entiere" style="--img:url(' . esc_url( get_the_post_thumbnail_url( $post, 'medium' ) ) . ')">';
        } else {
            echo '<figure class="vignette">';
        }
        echo get_the_post_thumbnail( $post, 'ra-carte', array( 'alt' => '', 'loading' => 'lazy', 'sizes' => '(max-width: 600px) 100vw, (max-width: 900px) 50vw, 400px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
        echo '</figure>';
    }
    if ( $args['surtitre'] ) {
        ra_surtitre( $post );
    }
    $titre = ra_titre( $post );
    echo '<h3' . ( ra_titre_en_capitales( $titre ) ? ' class="capitales"' : '' ) . '>' . esc_html( $titre ) . '</h3>'; // Titre saisi en capitales : réduit, pas réécrit.
    if ( ! $replay && ( $args['extrait'] || ! $ratio ) ) {
        echo '<p>' . esc_html( ra_chapeau( $post ) ) . '</p>';
    }
    ra_date( $post );
    echo '</a>';
}

/** Ligne compacte (listes par rubrique de l'accueil). */
function ra_ligne( $post ): void {
    $post  = get_post( $post );
    $image = has_post_thumbnail( $post );
    echo '<a class="ligne' . ( $image ? '' : ' sans-image' ) . '" href="' . esc_url( get_permalink( $post ) ) . '"><div>';
    $titre = ra_titre( $post );
    echo '<h3' . ( ra_titre_en_capitales( $titre ) ? ' class="capitales"' : '' ) . '>' . esc_html( $titre ) . '</h3>';
    ra_date( $post );
    echo '</div>';
    if ( $image ) {
        echo '<figure class="vignette">' . get_the_post_thumbnail( $post, 'medium', array( 'alt' => '', 'loading' => 'lazy' ) ) . '</figure>'; // phpcs:ignore WordPress.Security.EscapeOutput
    }
    echo '</a>';
}

/** Boutons de partage : WhatsApp d'abord. */
function ra_partage( bool $bas = false ): void {
    $url   = rawurlencode( get_permalink() );
    $titre = rawurlencode( ra_titre() );
    echo '<div class="partage' . ( $bas ? ' bas' : '' ) . '"' . ( $bas ? '' : ' aria-label="Partager"' ) . '>';
    if ( $bas ) {
        echo '<span>Partager</span>';
    }
    $liens = array(
        'whatsapp' => array( 'https://wa.me/?text=' . $titre . '%20' . $url, 'Partager sur WhatsApp' ),
        'facebook' => array( 'https://www.facebook.com/sharer/sharer.php?u=' . $url, 'Partager sur Facebook' ),
        'x'        => array( 'https://x.com/intent/post?url=' . $url . '&text=' . $titre, 'Partager sur X' ),
    );
    foreach ( $liens as $icone => $lien ) {
        printf( '<a href="%s" target="_blank" rel="noopener" aria-label="%s">%s</a>', esc_url( $lien[0] ), esc_attr( $lien[1] ), ra_icone( $icone ) ); // phpcs:ignore WordPress.Security.EscapeOutput
    }
    echo '<span class="copie" role="status"></span><button type="button" aria-label="Copier le lien" data-copier>' . ra_icone( 'lien' ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput
    echo '</div>';
}

/** Bouton d'écoute du direct (son libellé et son icône sont tenus à jour par theme.js). */
function ra_bouton_direct( string $classe = 'lecture' ): void {
    echo '<button class="' . esc_attr( $classe ) . '" type="button" data-direct-btn><span class="ico">' . ra_icone( 'jouer' ) . '</span><span class="libelle">Écouter le direct</span></button>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

/** Lien vers la page de la grille, s'il en existe une. */
function ra_url_grille(): string {
    static $url = null;
    if ( null === $url ) {
        $url = '';
        foreach ( apply_filters( 'ra_slugs_grille', array( 'grille-des-emissions', 'grille' ) ) as $slug ) {
            $page = get_page_by_path( $slug );
            if ( $page && 'publish' === $page->post_status ) {
                $url = get_permalink( $page );
                break;
            }
        }
    }
    return $url;
}

/** Grand bloc « À l'antenne » de l'accueil. */
function ra_bloc_antenne(): void {
    $a = ra_antenne();
    echo '<aside class="antenne" id="antenne" aria-label="À l\'antenne" data-antenne>';
    echo '<div class="bloc"><span class="etiquette"><span class="pastille"></span><span data-a="label">' . esc_html( $a['label'] ) . '</span></span>';
    echo '<h2 data-a="titre">' . esc_html( $a['titre'] ) . '</h2>';
    echo '<p class="quoi" data-a="detail">' . esc_html( $a['detail'] ) . '</p>';
    echo '<p class="desc" data-a="desc"' . ( $a['desc'] ? '' : ' hidden' ) . '>' . esc_html( $a['desc'] ) . '</p></div>';
    echo '<div class="commandes">';
    ra_bouton_direct();
    echo '<div class="onde" aria-hidden="true">' . str_repeat( '<i></i>', 10 ) . '</div></div>';
    if ( $a['suite'] ) {
        echo '<ul class="suite"><li class="titre">À suivre</li>';
        foreach ( $a['suite'] as $s ) {
            echo '<li><time>' . esc_html( $s['heure'] ) . '</time><div><b>' . esc_html( $s['titre'] ) . '</b>' . ( $s['detail'] ? '<span>' . esc_html( $s['detail'] ) . '</span>' : '' ) . '</div></li>';
        }
        echo '</ul>';
    } else {
        // Pas de grille (nouvelle saison en préparation) : le bas du bloc présente les émissions au lieu de rester vide.
        $emissions = array_slice( ra_emissions(), 0, 4 );
        if ( $emissions ) {
            echo '<ul class="suite sans-heure"><li class="titre">Les émissions</li>';
            foreach ( $emissions as $emission ) {
                $desc = ra_description_emission( $emission );
                echo '<li><a href="' . esc_url( get_category_link( $emission ) ) . '"><b>' . esc_html( ra_nom_categorie( $emission ) ) . '</b>' . ( $desc ? '<span>' . esc_html( wp_trim_words( $desc, 9, '…' ) ) . '</span>' : '' ) . '</a></li>';
            }
            echo '</ul>';
            if ( ra_id_emissions() ) {
                echo '<a class="vers-grille" href="' . esc_url( get_category_link( ra_id_emissions() ) ) . '">Toutes les émissions</a>';
            }
        }
    }
    if ( ra_url_grille() ) {
        echo '<a class="vers-grille" href="' . esc_url( ra_url_grille() ) . '">Toute la grille</a>';
    }
    echo '</aside>';
}

/** Bandeau du direct en fin d'article. */
function ra_bandeau_direct(): void {
    $a = ra_antenne();
    echo '<aside class="bandeau-direct" aria-label="Le direct" data-antenne><div class="t">';
    echo '<span class="etiquette"><span class="pastille"></span><span data-a="label">' . esc_html( $a['label'] ) . '</span></span>';
    echo '<b data-a="titre">' . esc_html( $a['titre'] ) . '</b>';
    echo '<span class="h"><span data-a="detail">' . esc_html( $a['detail'] ) . '</span> · le son continue pendant votre lecture</span></div>';
    ra_bouton_direct();
    echo '</aside>';
}

/** Carte d'émission (carrousel). */
function ra_carte_emission( WP_Term $terme ): void {
    $nom    = ra_nom_categorie( $terme );
    $grille = ra_grille_pour( $nom );
    $visuel = ra_visuel_emission( $terme );
    $desc   = ra_description_emission( $terme );
    echo '<a class="emission" href="' . esc_url( get_category_link( $terme ) ) . '">';
    if ( $visuel ) {
        echo '<figure class="vignette"><img src="' . esc_url( $visuel ) . '" alt="" loading="lazy" width="320" height="180"></figure>';
    } else {
        echo '<div class="vignette sans-visuel" aria-hidden="true">' . esc_html( $nom ) . '</div>';
    }
    echo '<div class="txt">';
    if ( $grille ) {
        echo '<span class="quand">' . esc_html( ra_jours_texte( (array) $grille['jours'] ) . ' · ' . ra_heure( $grille['debut'] ) ) . '</span>';
    }
    echo '<h3>' . esc_html( $nom ) . '</h3>';
    if ( $desc ) {
        echo '<p>' . esc_html( wp_trim_words( $desc, 22, '…' ) ) . '</p>';
    }
    echo '</div></a>';
}

/** Carrousel des émissions. */
function ra_bloc_emissions( string $titre, string $sous = '', int $sauf = 0 ): void {
    $emissions = array_filter( ra_emissions(), function ( $t ) use ( $sauf ) {
        return (int) $t->term_id !== $sauf;
    } );
    if ( ! $emissions ) {
        return;
    }
    echo '<section class="section papier" id="emissions"><div class="cadre"><div class="section-tete"><div><h2>' . esc_html( $titre ) . '</h2>';
    if ( $sous ) {
        echo '<p>' . esc_html( $sous ) . '</p>';
    }
    echo '</div><div class="droite">';
    if ( ra_url_grille() ) {
        echo '<a class="lien" href="' . esc_url( ra_url_grille() ) . '">La grille complète</a>';
    }
    echo '<div class="fleches"><button type="button" aria-label="Émissions précédentes">' . ra_icone( 'chevg' ) . '</button><button type="button" aria-label="Émissions suivantes">' . ra_icone( 'chevd' ) . '</button></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
    echo '</div></div><div class="defile"><div class="emissions">';
    foreach ( $emissions as $terme ) {
        ra_carte_emission( $terme );
    }
    echo '</div></div></div></section>';
}

/** Rappel compact de l'émission d'un article ou d'un replay. */
function ra_rappel_emission( WP_Term $terme, string $prefixe ): void {
    $nom    = ra_nom_categorie( $terme );
    $grille = ra_grille_pour( $nom );
    $visuel = ra_visuel_emission( $terme );
    $station = ra_station();
    echo '<a class="rappel-emission" href="' . esc_url( get_category_link( $terme ) ) . '">';
    if ( $visuel ) {
        echo '<img src="' . esc_url( $visuel ) . '" alt="" width="320" height="180" loading="lazy">';
    }
    echo '<span class="t">' . esc_html( $prefixe ) . '<b>' . esc_html( $nom ) . '</b>';
    if ( $grille ) { // L'horaire n'est affiché que si la grille le fournit.
        echo esc_html( ra_jours_texte( (array) $grille['jours'] ) . ' à ' . ra_heure( $grille['debut'] ) . ' sur ' . $station['frequence'] );
    }
    echo '</span><span class="fl" aria-hidden="true">›</span></a>';
}

/** Pagination des listes. */
function ra_pagination(): void {
    $liens = paginate_links( array( 'type' => 'array', 'prev_text' => '‹ Précédent', 'next_text' => 'Suivant ›', 'mid_size' => 1 ) );
    if ( ! $liens ) {
        return;
    }
    echo '<nav class="pagination" aria-label="Pages">' . implode( '', $liens ) . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

/** Requête d'articles écrits (hors replays), les plus récents d'abord. */
function ra_articles( array $args = array() ): array {
    return get_posts( wp_parse_args( $args, array(
        'post_type'           => 'post',
        'numberposts'         => 6,
        'ignore_sticky_posts' => true,
        'meta_query'          => ra_meta_articles(), // phpcs:ignore WordPress.DB.SlowDBQuery
        'suppress_filters'    => false,
    ) ) );
}
