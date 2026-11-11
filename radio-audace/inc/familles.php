<?php
/**
 * Les catégories du site mélangent deux familles :
 *  - les RUBRIQUES (infos, politique, sport…), traitées comme un média d'information ;
 *  - les ÉMISSIONS (Le Train d'Audace, Parlons Droit…), traitées comme des programmes.
 * Une émission est une catégorie enfant de la catégorie « emissions ».
 *
 * Ce fichier porte aussi la notion de REPLAY : un ancien article dont le contenu
 * est une vidéo YouTube d'émission, avec peu ou pas de texte.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Catégories techniques, jamais affichées comme rubrique ni comme émission. */
function ra_categories_ignorees(): array {
    return apply_filters( 'ra_categories_ignorees', array(
        'non-classe', 'uncategorized', 'accueil', 'direct', 'emissions',
        'de-lundi-a-vendredi', 'une-fois-par-semaine',
    ) );
}

/**
 * Noms propres des catégories. Sur le site, ils sont saisis sans accents, tout en
 * minuscules ou tout en capitales (« economie », « SANTE MAGAZINE »).
 */
function ra_noms_categories(): array {
    return apply_filters( 'ra_noms_categories', array(
        'infos'                      => 'Infos',
        'politique-infos'            => 'Politique',
        'economie-infos'             => 'Économie',
        'sport-infos'                => 'Sport',
        'culture'                    => 'Culture',
        'education'                  => 'Éducation',
        'divertissement-infos'       => 'Divertissement',
        'societe'                    => 'Société',
        'que-dit-la-loi'             => 'Que dit la loi',
        'journal'                    => 'Journal',
        'train-daudace'              => "Le Train d'Audace",
        'audace-infos-midi-magazine' => 'Audace Info Midi Magazine',
        'club-audace'                => 'Le Club Audace',
        'parlons-droit'              => 'Parlons Droit !',
        'sante-magazine'             => 'Santé Magazine',
        'femmes-daudace'             => "Femmes d'Audace",
        'bold-generation'            => 'Bold Génération',
        'intima'                     => 'Intima',
        'les-petits-audacieux'       => 'Les Petits Audacieux',
        'on-vous-embarque'           => 'On vous embarque',
        'idees-vertes'               => 'Idées vertes',
        'la-discotheque-de-papa'     => 'La Discothèque de Papa',
        'the-political-tribe'        => 'The Political Tribe',
        'cap-sur-la-can'             => 'Cap sur la CAN',
        'audace-sports-magazine'     => 'Audace Sports Magazine',
    ) );
}

/**
 * Présentation des émissions, utilisée tant que ni la grille de RadioManager ni la
 * description de la catégorie WordPress n'en fournissent une.
 */
function ra_descriptions_emissions(): array {
    return apply_filters( 'ra_descriptions_emissions', array(
        'train-daudace'              => "La matinale d'information : rubriques, invités et la parole aux auditeurs.",
        'audace-infos-midi-magazine' => 'Politique, économie, culture, santé et sports, avec la parole aux experts.',
        'club-audace'                => "La tribune qui décortique l'actualité sportive, scoops et analyses.",
        'parlons-droit'              => 'Droit pénal, civil, matrimonial : des acteurs reconnus répondent à vos questions.',
        'sante-magazine'             => 'Santé et bien-être, avec les spécialistes qui répondent aux préoccupations.',
        'femmes-daudace'             => 'Une tribune de promotion des compétences et du leadership au féminin.',
        'bold-generation'            => 'La vitrine du savoir-faire jeune et du patrimoine culturel camerounais.',
    ) );
}

/** Présentation d'une émission : grille, puis description WordPress, puis texte par défaut. */
function ra_description_emission( WP_Term $terme ): string {
    $grille = ra_grille_pour( ra_nom_categorie( $terme ) );
    if ( $grille && ! empty( $grille['description'] ) ) {
        return (string) $grille['description'];
    }
    $desc = trim( wp_strip_all_tags( term_description( $terme ) ) );
    if ( mb_strlen( $desc ) > 12 ) {
        return $desc;
    }
    return ra_descriptions_emissions()[ $terme->slug ] ?? '';
}

/** Nom d'affichage d'une catégorie. */
function ra_nom_categorie( WP_Term $terme ): string {
    $noms = ra_noms_categories();
    if ( isset( $noms[ $terme->slug ] ) ) {
        return $noms[ $terme->slug ];
    }
    $nom = html_entity_decode( $terme->name, ENT_QUOTES, 'UTF-8' );
    // Nom saisi tout en minuscules ou tout en capitales : une capitale à chaque mot.
    if ( $nom === mb_strtolower( $nom ) || $nom === mb_strtoupper( $nom ) ) {
        $nom = mb_convert_case( mb_strtolower( $nom ), MB_CASE_TITLE, 'UTF-8' );
    }
    return $nom;
}

/** Classe de couleur d'une rubrique (voir theme.css). */
function ra_classe_rubrique( ?WP_Term $terme ): string {
    if ( ! $terme ) {
        return 'infos';
    }
    if ( ra_est_emission( $terme ) ) {
        return 'programme';
    }
    foreach ( array( 'sport', 'economie', 'societe', 'politique', 'culture', 'education' ) as $cle ) {
        if ( str_starts_with( $terme->slug, $cle ) ) {
            return $cle;
        }
    }
    if ( 'que-dit-la-loi' === $terme->slug ) {
        return 'societe';
    }
    return 'infos';
}

/** Identifiant de la catégorie mère des émissions (0 si absente). */
function ra_id_emissions(): int {
    static $id = null;
    if ( null === $id ) {
        $terme = get_term_by( 'slug', apply_filters( 'ra_slug_emissions', 'emissions' ), 'category' );
        $id    = $terme ? (int) $terme->term_id : 0;
    }
    return $id;
}

function ra_est_emission( WP_Term $terme ): bool {
    $mere = ra_id_emissions();
    if ( ! $mere || in_array( $terme->slug, ra_categories_ignorees(), true ) ) {
        return false;
    }
    return (int) $terme->parent === $mere || in_array( $mere, get_ancestors( $terme->term_id, 'category' ), true );
}

/** Toutes les émissions ayant au moins un contenu, les plus fournies d'abord. */
function ra_emissions(): array {
    $mere = ra_id_emissions();
    if ( ! $mere ) {
        return array();
    }
    $termes = get_terms( array(
        'taxonomy'   => 'category',
        'child_of'   => $mere,
        'hide_empty' => true,
        'orderby'    => 'count',
        'order'      => 'DESC',
    ) );
    if ( is_wp_error( $termes ) ) {
        return array();
    }
    return array_values( array_filter( $termes, 'ra_est_emission' ) );
}

/** Les rubriques ayant au moins un article, pour le menu et le pied. */
function ra_rubriques(): array {
    $ordre  = array( 'infos', 'politique-infos', 'economie-infos', 'sport-infos', 'societe', 'culture', 'education' );
    $termes = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => true ) );
    if ( is_wp_error( $termes ) ) {
        return array();
    }
    $rubriques = array();
    foreach ( $termes as $terme ) {
        if ( in_array( $terme->slug, ra_categories_ignorees(), true ) || ra_est_emission( $terme ) ) {
            continue;
        }
        $rang = array_search( $terme->slug, $ordre, true );
        if ( false !== $rang ) { // Le menu ne liste que les rubriques principales, dans un ordre fixe.
            $rubriques[ $rang ] = $terme;
        }
    }
    ksort( $rubriques );
    return array_values( $rubriques );
}

/**
 * Rubrique principale et émission d'un article.
 *
 * @return array{rubrique: ?WP_Term, sous: ?WP_Term, emission: ?WP_Term}
 */
function ra_familles( $post = null ): array {
    $post = get_post( $post );
    $res  = array( 'rubrique' => null, 'sous' => null, 'emission' => null );
    if ( ! $post ) {
        return $res;
    }
    $termes = get_the_category( $post->ID );
    foreach ( $termes as $terme ) {
        if ( in_array( $terme->slug, ra_categories_ignorees(), true ) ) {
            continue;
        }
        if ( ra_est_emission( $terme ) ) {
            $res['emission'] = $res['emission'] ?? $terme;
            continue;
        }
        if ( 'infos' === $terme->slug && $res['rubrique'] ) {
            continue; // « Infos » est la rubrique mère : une rubrique plus précise passe devant.
        }
        if ( 'que-dit-la-loi' === $terme->slug ) {
            $res['sous'] = $terme; // Sous-rubrique : affichée après la rubrique.
            continue;
        }
        if ( ! $res['rubrique'] || 'infos' === $res['rubrique']->slug ) {
            $res['rubrique'] = $terme;
        }
    }
    if ( ! $res['rubrique'] && $res['sous'] ) {
        $res['rubrique'] = $res['sous'];
        $res['sous']     = null;
    }
    return $res;
}

/* ------------------------------------------------------------------ Replays */

/** Identifiant YouTube trouvé dans le contenu brut (y compris dans un shortcode Divi). */
function ra_youtube_id( $post = null ): string {
    $post = get_post( $post );
    if ( ! $post ) {
        return '';
    }
    if ( preg_match( '~(?:youtube(?:-nocookie)?\.com/(?:embed/|watch\?v=|shorts/)|youtu\.be/)([\w-]{11})~', $post->post_content, $m ) ) {
        return $m[1];
    }
    return '';
}

/**
 * Un replay est un article dont le contenu est une vidéo, avec très peu de texte.
 * Le résultat est gardé en métadonnée (« _ra_replay » : 1 ou 0) pour pouvoir
 * filtrer les listes d'articles sans relire chaque contenu.
 */
function ra_calculer_replay( WP_Post $post ): bool {
    if ( ! ra_youtube_id( $post ) ) {
        return false;
    }
    $texte = wp_strip_all_tags( ra_sans_shortcodes( $post->post_content ) );
    $texte = preg_replace( '~https?://\S+~', '', $texte );
    return mb_strlen( trim( $texte ) ) < 400;
}

function ra_est_replay( $post = null ): bool {
    $post = get_post( $post );
    if ( ! $post || 'post' !== $post->post_type ) {
        return false;
    }
    $meta = get_post_meta( $post->ID, '_ra_replay', true );
    if ( '' === $meta ) {
        $meta = ra_calculer_replay( $post ) ? '1' : '0';
        update_post_meta( $post->ID, '_ra_replay', $meta );
    }
    return '1' === $meta;
}

add_action( 'save_post_post', function ( int $id, WP_Post $post ) {
    if ( wp_is_post_revision( $id ) ) {
        return;
    }
    update_post_meta( $id, '_ra_replay', ra_calculer_replay( $post ) ? '1' : '0' );
}, 10, 2 );

/**
 * Premier passage : marque tous les articles existants (une centaine).
 * Relancé si la version du marquage change.
 */
add_action( 'init', function () {
    if ( '1' === get_option( 'ra_replays_marques' ) ) {
        return;
    }
    $ids = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );
    foreach ( $ids as $id ) {
        $post = get_post( $id );
        if ( $post ) {
            update_post_meta( $id, '_ra_replay', ra_calculer_replay( $post ) ? '1' : '0' );
        }
    }
    update_option( 'ra_replays_marques', '1', false );
}, 20 );

/** Condition de requête : seulement les articles écrits (pas les replays). */
function ra_meta_articles(): array {
    return array(
        'relation' => 'OR',
        array( 'key' => '_ra_replay', 'value' => '1', 'compare' => '!=' ),
        array( 'key' => '_ra_replay', 'compare' => 'NOT EXISTS' ),
    );
}

/**
 * Titre et date de diffusion d'un replay.
 * Les titres d'origine ont la forme « femmes d’audace du 28-12-2021 » ou
 * « femmes d’audace du 18-02-2021: Miss Santé Sud-Ouest Cameroun ».
 *
 * @return array{titre: string, date: string} date au format Y-m-d, ou vide.
 */
function ra_replay_infos( $post = null ): array {
    $post  = get_post( $post );
    $titre = ra_titre_propre( $post ? $post->post_title : '' );
    $date  = '';
    if ( preg_match( '~^(.*?)\bdu\s+(\d{1,2})\s*[-–—/. ]\s*(\d{1,2})\s*[-–—/. ]\s*(\d{2,4})\s*[:：–-]?\s*(.*)$~iu', $titre, $m ) ) {
        $annee = (int) $m[4];
        $annee = $annee < 100 ? 2000 + $annee : $annee;
        if ( checkdate( (int) $m[3], (int) $m[2], $annee ) ) {
            $date  = sprintf( '%04d-%02d-%02d', $annee, $m[3], $m[2] );
            $sujet = trim( $m[5] );
            $avant = trim( $m[1], " \t:–-" );
            // « Sujet : émission du… » : le sujet est avant la date.
            if ( '' === $sujet && mb_strlen( $avant ) > 40 ) {
                $sujet = preg_replace( '~\s*[:–-]\s*[^:–-]*$~u', '', $avant );
            }
            $titre = '' !== $sujet
                ? ra_majuscule( $sujet )
                : 'Émission du ' . ra_date_longue( $date );
        }
    }
    if ( ! $date && $post ) {
        $date = get_the_date( 'Y-m-d', $post );
    }
    return array( 'titre' => $titre, 'date' => $date );
}

/** « 2021-12-28 » → « 28 décembre 2021 ». */
function ra_date_longue( string $date ): string {
    $t = strtotime( $date . ' 12:00:00' );
    $s = $t ? date_i18n( 'j F Y', $t ) : $date;
    return preg_replace( '~^1 ~', '1er ', $s );
}

function ra_majuscule( string $texte ): string {
    return mb_strtoupper( mb_substr( $texte, 0, 1 ) ) . mb_substr( $texte, 1 );
}

/** Vignette d'une vidéo YouTube (c'est souvent le visuel officiel de l'émission). */
function ra_vignette_youtube( string $id, bool $grande = false ): string {
    return 'https://i.ytimg.com/vi/' . rawurlencode( $id ) . '/' . ( $grande ? 'maxresdefault' : 'mqdefault' ) . '.jpg';
}

/** Dernier replay d'une émission, pour lui servir de visuel quand la grille n'en fournit pas. */
function ra_visuel_emission( WP_Term $terme ): string {
    $grille = ra_grille_pour( ra_nom_categorie( $terme ) );
    if ( $grille && ! empty( $grille['image'] ) ) {
        return $grille['image'];
    }
    $cle    = 'ra_visuel_' . $terme->term_id;
    $visuel = get_transient( $cle );
    if ( false === $visuel ) {
        $visuel = '';
        $posts  = get_posts( array( 'cat' => $terme->term_id, 'numberposts' => 6, 'meta_key' => '_ra_replay', 'meta_value' => '1' ) );
        foreach ( $posts as $p ) {
            $id = ra_youtube_id( $p );
            if ( $id ) {
                $visuel = ra_vignette_youtube( $id );
                break;
            }
        }
        set_transient( $cle, $visuel, DAY_IN_SECONDS );
    }
    return $visuel;
}
