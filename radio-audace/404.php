<?php
/**
 * Page introuvable.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
$recents = ra_articles( array( 'numberposts' => 3 ) );
?>
  <div class="cadre">
    <header class="rubrique-tete">
      <h1>Page introuvable</h1>
      <p>Cette page n'existe pas ou a été déplacée. Vous pouvez lancer une recherche ou revenir à l'accueil.</p>
      <form class="champ-recherche recherche-page" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <input type="search" name="s" aria-label="Rechercher" placeholder="Rechercher un sujet, une émission…"><button type="submit">Rechercher</button>
      </form>
    </header>
  </div>
  <?php if ( $recents ) : ?>
  <section class="section section-serree">
    <div class="cadre">
      <div class="section-tete"><h2>Derniers articles</h2><a class="lien" href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></div>
      <div class="grille trois">
        <?php
        foreach ( $recents as $article ) {
            ra_carte( $article );
        }
        ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
<?php
get_footer();
