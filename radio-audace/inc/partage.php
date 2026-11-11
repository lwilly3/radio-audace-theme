<?php
/**
 * Fiche de partage (WhatsApp, Facebook, X…) et icône du site.
 *
 * Quand un lien est partagé, ces services lisent des balises « Open Graph » dans la page
 * pour afficher un titre, une description et une image. Sur le site, une extension en
 * produit déjà pour les articles, mais rien pour l'accueil, les émissions ni les rubriques.
 *
 * Règle : le thème complète, il ne double pas. Il n'ajoute ses balises que si la page
 * n'en contient pas déjà (quelle que soit l'extension qui les aurait produites).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Texte d'une ligne, sans balises, limité en longueur. */
function ra_texte_court( string $texte, int $max = 200 ): string {
    $texte = trim( preg_replace( '~\s+~u', ' ', wp_strip_all_tags( html_entity_decode( $texte, ENT_QUOTES, 'UTF-8' ) ) ) );
    if ( mb_strlen( $texte ) > $max ) {
        $texte = rtrim( mb_substr( $texte, 0, $max - 1 ), " ,;:.-–" ) . '…';
    }
    return $texte;
}

/**
 * Titre, description, image et adresse de la page courante, pour la fiche de partage.
 *
 * @return array{titre: string, description: string, image: string, largeur: int, hauteur: int, url: string, type: string}
 */
function ra_fiche_partage(): array {
    $station = ra_station();
    $defaut  = get_template_directory_uri() . '/assets/img/partage.png';
    $fiche   = array(
        'titre'       => $station['nom'] . ' ' . $station['frequence'] . " — le direct et l'actualité du Cameroun",
        'description' => $station['accroche'],
        'image'       => $defaut,
        'largeur'     => 1200,
        'hauteur'     => 630,
        'url'         => home_url( '/' ),
        'type'        => 'website',
    );

    if ( is_singular() ) {
        $post               = get_queried_object();
        $fiche['url']       = get_permalink( $post );
        $fiche['type']      = 'post' === $post->post_type ? 'article' : 'website';
        $fiche['titre']     = 'post' === $post->post_type ? ra_titre( $post ) : ra_titre_propre( $post->post_title );
        $chapeau            = ra_chapeau( $post );
        if ( $chapeau ) {
            $fiche['description'] = $chapeau;
        }
        $image = wp_get_attachment_image_src( (int) get_post_thumbnail_id( $post ), 'large' );
        if ( $image ) {
            list( $fiche['image'], $fiche['largeur'], $fiche['hauteur'] ) = $image;
        } elseif ( 'post' === $post->post_type && ra_est_replay( $post ) && ra_youtube_id( $post ) ) {
            $fiche['image']   = 'https://i.ytimg.com/vi/' . rawurlencode( ra_youtube_id( $post ) ) . '/hqdefault.jpg';
            $fiche['largeur'] = 480;
            $fiche['hauteur'] = 360;
        }
    } elseif ( is_category() ) {
        $terme          = get_queried_object();
        $nom            = (int) $terme->term_id === ra_id_emissions() ? 'Les émissions' : ra_nom_categorie( $terme );
        $fiche['url']   = get_category_link( $terme );
        $fiche['titre'] = $nom . ' — ' . $station['nom'] . ' ' . $station['frequence'];
        if ( ra_est_emission( $terme ) ) {
            $desc = ra_description_emission( $terme );
            if ( $desc ) {
                $fiche['description'] = $desc;
            }
        } else {
            $desc = trim( wp_strip_all_tags( term_description( $terme ) ) );
            $fiche['description'] = mb_strlen( $desc ) > 12 ? $desc : $nom . " : les articles et les sujets de la rédaction de " . $station['nom'] . '.';
        }
    } elseif ( is_search() ) {
        $fiche['titre'] = 'Recherche — ' . $station['nom'] . ' ' . $station['frequence'];
        $fiche['url']   = home_url( '/' );
    }

    $fiche['titre']       = ra_texte_court( $fiche['titre'], 110 );
    $fiche['description'] = ra_texte_court( $fiche['description'], 200 );
    return apply_filters( 'ra_fiche_partage', $fiche );
}

/** Balises de la fiche, sans celles que la page contient déjà. */
function ra_balises_partage( string $entete ): string {
    $station = ra_station();
    $f       = ra_fiche_partage();
    $sortie  = '';

    if ( false === stripos( $entete, 'name="description"' ) ) {
        $sortie .= '<meta name="description" content="' . esc_attr( $f['description'] ) . '">' . "\n";
    }
    if ( false === stripos( $entete, 'property="og:title"' ) ) {
        $og = array(
            'og:locale'       => 'fr_FR',
            'og:type'         => $f['type'],
            'og:site_name'    => $station['nom'] . ' ' . $station['frequence'],
            'og:title'        => $f['titre'],
            'og:description'  => $f['description'],
            'og:url'          => $f['url'],
            'og:image'        => $f['image'],
            'og:image:width'  => (string) $f['largeur'],
            'og:image:height' => (string) $f['hauteur'],
        );
        foreach ( $og as $propriete => $valeur ) {
            $sortie .= '<meta property="' . esc_attr( $propriete ) . '" content="' . esc_attr( $valeur ) . '">' . "\n";
        }
    }
    if ( false === stripos( $entete, 'name="twitter:card"' ) ) {
        $sortie .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
        $sortie .= '<meta name="twitter:title" content="' . esc_attr( $f['titre'] ) . '">' . "\n";
        $sortie .= '<meta name="twitter:description" content="' . esc_attr( $f['description'] ) . '">' . "\n";
        $sortie .= '<meta name="twitter:image" content="' . esc_url( $f['image'] ) . '">' . "\n";
    }
    // Icône du site, tant qu'aucune n'est choisie dans Apparence › Personnaliser › Identité du site.
    if ( ! has_site_icon() && false === stripos( $entete, 'rel="icon"' ) ) {
        $icone   = esc_url( get_template_directory_uri() . '/assets/img/icone.png' );
        $sortie .= '<link rel="icon" href="' . $icone . '" sizes="512x512" type="image/png">' . "\n";
        $sortie .= '<link rel="apple-touch-icon" href="' . $icone . '">' . "\n";
    }
    return $sortie ? "\n<!-- Thème Radio Audace : fiche de partage -->\n" . $sortie : '';
}

// On lit l'en-tête produit par WordPress et les extensions, puis on le complète.
add_action( 'wp_head', function () {
    ob_start();
}, -9999 );
add_action( 'wp_head', function () {
    $entete = (string) ob_get_clean();
    echo $entete . ra_balises_partage( $entete ); // phpcs:ignore WordPress.Security.EscapeOutput -- en-tête déjà produit, complété de balises échappées.
}, 9999 );

/**
 * Description de partage des articles : on retire le surtitre que RadioManager place
 * en tête d'extrait (« ÉNERGIE | CAMEROUN | ÉCONOMIE »), quelle que soit l'extension qui la lit.
 */
add_filter( 'get_the_excerpt', function ( $extrait, $post = null ) {
    if ( is_admin() || ! is_string( $extrait ) || '' === $extrait ) {
        return $extrait;
    }
    return ra_sans_surtitre( $extrait );
}, 20, 2 );
