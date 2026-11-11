<?php
/**
 * Accueil : l'antenne et la une sur le premier écran, puis les articles par date,
 * les émissions et les rubriques. Rien n'est à régler dans WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$station = ra_station();

// La une : un article épinglé s'il y en a un, sinon le dernier article ayant une image.
$une      = null;
$epingles = array_filter( array_map( 'intval', (array) get_option( 'sticky_posts' ) ) );
if ( $epingles ) {
    $trouve = ra_articles( array( 'post__in' => $epingles, 'numberposts' => 1 ) );
    $une    = $trouve[0] ?? null;
}
if ( ! $une ) {
    $trouve = ra_articles( array( 'numberposts' => 1, 'meta_query' => array( 'relation' => 'AND', ra_meta_articles(), array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
    $une    = $trouve[0] ?? null;
}
if ( ! $une ) {
    $trouve = ra_articles( array( 'numberposts' => 1 ) );
    $une    = $trouve[0] ?? null;
}
$montres = $une ? array( $une->ID ) : array();

// Les articles suivants, strictement par date.
$decryptages = ra_articles( array( 'numberposts' => 6, 'post__not_in' => $montres ) );
$montres     = array_merge( $montres, wp_list_pluck( $decryptages, 'ID' ) );
?>

  <h1 class="sr"><?php echo esc_html( $station['nom'] . ' ' . $station['frequence'] . " — le direct et l'actualité du Cameroun" ); ?></h1>

  <section class="ouverture cadre" aria-label="À la une et à l'antenne">
    <?php if ( $une ) : ?>
    <a class="une" href="<?php echo esc_url( get_permalink( $une ) ); ?>">
      <?php if ( has_post_thumbnail( $une ) ) : ?>
        <figure class="vignette"><?php echo get_the_post_thumbnail( $une, 'ra-une', array( 'fetchpriority' => 'high', 'loading' => 'eager', 'sizes' => '(max-width: 760px) 100vw, 60vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></figure>
      <?php endif; ?>
      <?php ra_surtitre( $une ); ?>
      <h2 class="titre<?php echo ra_titre_en_capitales( ra_titre( $une ) ) ? ' capitales' : ''; ?>"><?php echo esc_html( ra_titre( $une ) ); ?></h2>
      <p class="chapeau"><?php echo esc_html( ra_chapeau( $une ) ); ?></p>
      <?php ra_date( $une ); ?>
    </a>
    <?php endif; ?>
    <?php ra_bloc_antenne(); ?>
  </section>

  <?php if ( $decryptages ) : ?>
  <section class="section">
    <div class="cadre">
      <div class="section-tete">
        <div><h2>Nos décryptages</h2><p>Les sujets que la rédaction prend le temps d'expliquer.</p></div>
        <?php $tous = get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : add_query_arg( 'liste', '1', home_url( '/' ) ); ?>
        <a class="lien" href="<?php echo esc_url( $tous ); ?>">Tous les articles</a>
      </div>
      <div class="grille trois">
        <?php
        foreach ( $decryptages as $article ) {
            ra_carte( $article );
        }
        ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php ra_bloc_emissions( 'Les émissions', "Chaque rendez-vous de l'antenne et ses vidéos." ); ?>

  <?php
  // Une colonne par rubrique ayant au moins deux articles non affichés plus haut ; jamais complétée par une autre rubrique.
  $colonnes = array();
  foreach ( ra_rubriques() as $rubrique ) {
      // category__in : la rubrique elle-même, sans ses sous-rubriques (« Infos » ne doit pas tout absorber).
      $articles = ra_articles( array( 'category__in' => array( $rubrique->term_id ), 'numberposts' => 3, 'post__not_in' => $montres ) );
      if ( count( $articles ) >= 2 ) {
          $colonnes[] = array( $rubrique, $articles );
          $montres    = array_merge( $montres, wp_list_pluck( $articles, 'ID' ) );
      }
      if ( 2 === count( $colonnes ) ) {
          break;
      }
  }
  ?>
  <?php if ( $colonnes ) : ?>
  <section class="section">
    <div class="cadre duo">
      <?php foreach ( $colonnes as $colonne ) : ?>
      <div class="colonne <?php echo esc_attr( ra_classe_rubrique( $colonne[0] ) ); ?>">
        <h2 class="tete"><?php echo esc_html( ra_nom_categorie( $colonne[0] ) ); ?> <a class="lien" href="<?php echo esc_url( get_category_link( $colonne[0] ) ); ?>">Tout voir</a></h2>
        <?php
        foreach ( $colonne[1] as $article ) {
            ra_ligne( $article );
        }
        ?>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="frequence">
    <div class="cadre apparait">
      <div class="chiffre"><?php echo esc_html( preg_replace( '~\s*FM$~', '', $station['frequence'] ) ); ?><small>FM</small></div>
      <div>
        <p><?php echo esc_html( $station['accroche'] ); ?></p>
        <div class="boutons">
          <a class="bouton" href="<?php echo esc_url( ra_tel( $station['telephones'][0] ) ); ?>">Appeler l'antenne</a>
          <?php $newsletter = get_page_by_path( 'newsletter' ); ?>
          <?php if ( $newsletter && 'publish' === $newsletter->post_status ) : ?>
            <a class="bouton clair" href="<?php echo esc_url( get_permalink( $newsletter ) ); ?>">Recevoir la newsletter</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

<?php
get_footer();
