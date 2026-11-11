<?php
/**
 * En-tête : une seule rangée à toutes les largeurs, menu en tiroir sous 1140 px.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$station   = ra_station();
$mere      = ra_id_emissions();
$courant   = get_queried_object();
$terme_cou = $courant instanceof WP_Term ? $courant : null;
if ( is_singular( 'post' ) ) {
    $f         = ra_familles();
    $terme_cou = ra_est_replay() && $f['emission'] ? $f['emission'] : ( $f['rubrique'] ?? $f['emission'] );
}
$dans_emissions = $terme_cou && ( ra_est_emission( $terme_cou ) || (int) $terme_cou->term_id === $mere );
$url_grille     = ra_url_grille();
?><!doctype html>
<html <?php language_attributes(); ?> data-direct="arret">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="evitement" href="#contenu">Aller au contenu</a>

<header class="entete">
  <div class="cadre">
    <button class="icone-btn burger" type="button" aria-expanded="false" aria-controls="menu" aria-label="Menu"><?php echo ra_icone( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
    <a class="marque" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $station['nom'] . ' ' . $station['frequence'] . ', accueil' ); ?>">
      <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo.svg' ); ?>" alt="" width="138" height="34">
    </a>
    <nav class="menu" id="menu" aria-label="Navigation principale">
      <form class="champ-recherche" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <input type="search" name="s" placeholder="Rechercher…" aria-label="Rechercher" value="<?php echo esc_attr( get_search_query() ); ?>"><button type="submit">OK</button>
      </form>
      <?php
      if ( has_nav_menu( 'principal' ) ) {
          wp_nav_menu( array( 'theme_location' => 'principal', 'container' => false, 'items_wrap' => '%3$s', 'depth' => 1, 'walker' => new RA_Menu_Plat() ) );
      } else {
          // Menu automatique : l'antenne d'abord (émissions, grille), puis les rubriques qui ont des articles.
          if ( $mere ) {
              printf( '<a class="radio" href="%s"%s>Émissions</a>', esc_url( get_category_link( $mere ) ), $dans_emissions ? ' aria-current="page"' : '' );
          }
          if ( $url_grille ) {
              printf( '<a class="radio" href="%s"%s>Grille</a>', esc_url( $url_grille ), is_page() && get_permalink() === $url_grille ? ' aria-current="page"' : '' );
          }
          echo '<span class="sep" aria-hidden="true"></span>';
          foreach ( ra_rubriques() as $rubrique ) {
              $actif = $terme_cou && ! $dans_emissions && ( (int) $terme_cou->term_id === (int) $rubrique->term_id || (int) $terme_cou->parent === (int) $rubrique->term_id );
              printf( '<a href="%s"%s>%s</a>', esc_url( get_category_link( $rubrique ) ), $actif ? ' aria-current="page"' : '', esc_html( ra_nom_categorie( $rubrique ) ) );
          }
      }
      ?>
    </nav>
    <div class="outils">
      <button class="icone-btn chercher" type="button" aria-expanded="false" aria-controls="recherche" aria-label="Rechercher"><?php echo ra_icone( 'loupe' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
      <button class="direct-btn" type="button" data-direct-btn><span class="ico"><?php echo ra_icone( 'jouer' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="libelle long">Écouter le direct</span><span class="libelle court">Direct</span></button>
    </div>
  </div>
  <div class="barre-recherche" id="recherche" hidden>
    <div class="cadre">
      <form class="champ-recherche" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <input type="search" name="s" placeholder="Rechercher un sujet, une émission…" aria-label="Rechercher" value="<?php echo esc_attr( get_search_query() ); ?>"><button type="submit">Rechercher</button>
      </form>
    </div>
  </div>
</header>

<main id="contenu">
