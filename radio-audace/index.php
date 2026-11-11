<?php
/**
 * Listes génériques : tous les articles, résultats de recherche, archives par date ou par auteur.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

if ( is_search() ) {
    $titre = 'Recherche';
    $sous  = get_search_query() ? sprintf( '%d résultat(s) pour « %s »', (int) $GLOBALS['wp_query']->found_posts, get_search_query() ) : '';
} elseif ( is_home() ) {
    $titre = 'Tous les articles';
    $sous  = 'Du plus récent au plus ancien.';
} else {
    $titre = wp_strip_all_tags( get_the_archive_title() );
    $sous  = '';
}
?>
  <div class="cadre">
    <header class="rubrique-tete">
      <h1><?php echo esc_html( $titre ); ?></h1>
      <?php if ( $sous ) : ?>
        <p><?php echo esc_html( $sous ); ?></p>
      <?php endif; ?>
      <?php if ( is_search() ) : ?>
        <form class="champ-recherche recherche-page" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
          <input type="search" name="s" aria-label="Rechercher" value="<?php echo esc_attr( get_search_query() ); ?>"><button type="submit">Rechercher</button>
        </form>
      <?php endif; ?>
    </header>
  </div>

  <section class="section section-serree">
    <div class="cadre">
      <?php if ( have_posts() ) : ?>
      <div class="grille trois">
        <?php
        while ( have_posts() ) {
            the_post();
            if ( 'post' === get_post_type() ) {
                ra_carte( get_post() );
            } else {
                echo '<a class="carte sans-image apparait" href="' . esc_url( get_permalink() ) . '"><span class="rubrique">Page</span><h3>' . esc_html( get_the_title() ) . '</h3></a>';
            }
        }
        ?>
      </div>
            <?php ra_pagination(); ?>
      <?php else : ?>
      <p class="note-fin">Aucun résultat. Essayez un autre mot, ou parcourez les rubriques depuis le menu.</p>
      <?php endif; ?>
    </div>
  </section>

<?php
get_footer();
