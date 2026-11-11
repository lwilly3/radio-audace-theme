<?php
/**
 * Nettoyage du HTML reçu de RadioManager (et des anciens articles Divi).
 *
 * Rien de tout cela ne modifie la base : les corrections sont faites à
 * l'affichage, pour que les articles restent tels que RadioManager les envoie.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Retire les shortcodes Divi et les restes de carrousel, qui s'afficheraient en clair sans Divi. */
function ra_sans_shortcodes( string $html ): string {
    $html = preg_replace( '~\[/?et_pb_[^\]]*\]~', '', $html );
    $html = preg_replace( '~\[/?carousel_slide[^\]]*\]~u', '', $html );
    return (string) $html;
}

/**
 * Titre lisible : caractères « gras » Unicode ramenés à des lettres normales,
 * émojis et caractères de remplacement retirés.
 */
function ra_titre_propre( string $titre ): string {
    $titre = html_entity_decode( $titre, ENT_QUOTES, 'UTF-8' );
    if ( class_exists( 'Normalizer' ) ) {
        $n = Normalizer::normalize( $titre, Normalizer::FORM_KC );
        if ( is_string( $n ) ) {
            $titre = $n;
        }
    }
    $titre = preg_replace( '~[\x{FFFC}\x{FFFD}\x{200B}-\x{200D}\x{FE0F}]~u', '', $titre );
    $titre = preg_replace( '~[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]~u', '', $titre );
    $titre = preg_replace( '~\s+~u', ' ', (string) $titre );
    return ra_majuscule( trim( (string) $titre ) );
}

/** Vrai si le titre est saisi tout en capitales : le thème le réduit au lieu de le réécrire. */
function ra_titre_en_capitales( string $titre ): bool {
    $lettres = preg_replace( '~[^\p{L}]~u', '', $titre );
    if ( mb_strlen( $lettres ) < 16 ) {
        return false;
    }
    $capitales = preg_replace( '~[^\p{Lu}]~u', '', $lettres );
    return mb_strlen( $capitales ) / mb_strlen( $lettres ) > 0.8;
}

add_filter( 'the_title', function ( $titre, $id = 0 ) {
    if ( is_admin() || ! $id || 'post' !== get_post_type( $id ) ) {
        return $titre;
    }
    return esc_html( ra_titre_propre( (string) $titre ) );
}, 10, 2 );

/** Espaces insécables de la typographie française, hors balises. */
function ra_insecables( string $html ): string {
    $morceaux = preg_split( '~(<[^>]+>)~', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
    $ignore   = 0;
    foreach ( $morceaux as $i => $m ) {
        if ( '' === $m ) {
            continue;
        }
        if ( '<' === $m[0] ) {
            if ( preg_match( '~^<(pre|code|script|style|textarea)\b~i', $m ) ) {
                $ignore++;
            } elseif ( preg_match( '~^</(pre|code|script|style|textarea)\b~i', $m ) ) {
                $ignore = max( 0, $ignore - 1 );
            }
            continue;
        }
        if ( $ignore ) {
            continue;
        }
        $m = preg_replace( '~ ([!?;])~u', "\u{202F}$1", $m );
        $m = preg_replace( '~ ([:»%])~u', "\u{00A0}$1", $m );
        $m = preg_replace( '~« ~u', "«\u{00A0}", $m );
        $m = preg_replace( '~(\d) (h|min|FCFA|milliards?|millions?|ans|morts|jours|%)(?![\p{L}])~u', "$1\u{00A0}$2", $m );
        $morceaux[ $i ] = $m;
    }
    return implode( '', $morceaux );
}

/**
 * Corps d'article.
 *  - shortcodes Divi retirés ;
 *  - h1 du corps (doublon du titre) rétrogradé en h2 ;
 *  - balises vides retirées ;
 *  - citation d'une phrase transformée en exergue ;
 *  - tableau rendu défilable sur téléphone ;
 *  - espaces insécables.
 */
function ra_corps( string $html ): string {
    $html = ra_sans_shortcodes( $html );
    $html = preg_replace( '~<(/?)h1\b~i', '<$1h2', $html );
    // Balises vides (plusieurs passes : un vide peut en révéler un autre).
    for ( $i = 0; $i < 3; $i++ ) {
        $html = preg_replace( '~<(p|h[2-6]|li|blockquote|div|span|strong|em)\b[^>]*>(?:\s|&nbsp;|<br\s*/?>)*</\1>~iu', '', $html );
    }
    $html = preg_replace_callback( '~<blockquote\b([^>]*)>(.*?)</blockquote>~is', function ( $m ) {
        $texte = trim( wp_strip_all_tags( $m[2] ) );
        $court = mb_strlen( $texte ) <= 150 && substr_count( $m[2], '<p' ) <= 1 && false === stripos( $m[2], '<cite' );
        if ( $court && false === strpos( $m[1], 'class=' ) ) {
            return '<blockquote class="exergue"' . $m[1] . '>' . $m[2] . '</blockquote>';
        }
        return $m[0];
    }, $html );
    $html = preg_replace( '~(<table\b.*?</table>)~is', '<div class="tableau">$1</div>', $html );
    return ra_insecables( (string) $html );
}

add_filter( 'the_content', function ( $html ) {
    if ( is_admin() || is_feed() ) {
        return $html;
    }
    return ra_corps( (string) $html );
}, 20 );

// Sans Divi, ses shortcodes ne doivent apparaître nulle part (flux, extraits automatiques).
add_filter( 'the_content', 'ra_sans_shortcodes', 1 );
add_filter( 'get_the_excerpt', 'ra_sans_shortcodes', 1 );

/** Retire le surtitre en capitales séparées par des barres (« ÉNERGIE | CAMEROUN | ÉCONOMIE — Radio Audace »). */
function ra_sans_surtitre( string $texte ): string {
    $mot    = '[\p{Lu}\d\'’&-]{2,}';
    $groupe = $mot . '(?:\s+' . $mot . ')*';
    $texte  = preg_replace( '~^\s*(?:' . $groupe . '\s*\|\s*)+' . $groupe . '\s*(?:[—–-]\s*Radio Audace\s*)?~u', '', $texte );
    $texte  = preg_replace( '~^\s*[—–-]\s*Radio Audace\s*~u', '', (string) $texte );
    return (string) $texte;
}

/**
 * Chapeau d'un article : l'extrait, débarrassé de ce que RadioManager y laisse
 * (surtitre « ÉNERGIE | CAMEROUN | ÉCONOMIE — Radio Audace », reste de Divi).
 * Si l'extrait est vide ou inutilisable, on prend le début du texte.
 */
function ra_chapeau( $post = null, int $mots = 0 ): string {
    $post = get_post( $post );
    if ( ! $post ) {
        return '';
    }
    $extrait = trim( wp_strip_all_tags( ra_sans_shortcodes( $post->post_excerpt ) ) );
    if ( '' === $extrait ) {
        $texte = ra_sans_shortcodes( $post->post_content );
        $texte = preg_replace( '~<h[1-6]\b.*?</h[1-6]>~is', ' ', $texte ); // Pas d'intertitre collé au texte.
        $texte = preg_replace( '~https?://\S+~', '', wp_strip_all_tags( $texte ) );
        $extrait = wp_trim_words( trim( $texte ), 34, '…' );
    }
    $extrait = html_entity_decode( $extrait, ENT_QUOTES, 'UTF-8' );
    $extrait = trim( preg_replace( '~\s+~u', ' ', ra_sans_surtitre( $extrait ) ) );
    if ( $mots ) {
        $extrait = wp_trim_words( $extrait, $mots, '…' );
    }
    return $extrait;
}

/** Temps de lecture, en minutes. */
function ra_temps_lecture( $post = null ): int {
    $post = get_post( $post );
    if ( ! $post ) {
        return 1;
    }
    $mots = str_word_count( remove_accents( wp_strip_all_tags( ra_sans_shortcodes( $post->post_content ) ) ) );
    return max( 1, (int) round( $mots / 200 ) );
}

/** Texte d'un replay : le contenu sans la vidéo ni les adresses nues. */
function ra_texte_replay( $post = null ): string {
    $post = get_post( $post );
    if ( ! $post ) {
        return '';
    }
    $html = ra_sans_shortcodes( $post->post_content );
    $html = preg_replace( '~<iframe\b.*?</iframe>~is', '', $html );
    $html = preg_replace( '~<figure\b[^>]*wp-block-embed.*?</figure>~is', '', $html );
    $html = preg_replace( '~^\s*https?://\S+\s*$~m', '', $html );
    if ( '' === trim( wp_strip_all_tags( (string) $html ) ) ) {
        return '';
    }
    return ra_corps( wpautop( trim( (string) $html ) ) );
}
