<?php
/**
 * Article. Deux présentations :
 *  - l'article écrit (cas courant) : colonne de lecture, image au format réel ;
 *  - le replay : la vidéo d'abord, sur fond encre.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while ( have_posts() ) :
    the_post();
    $f      = ra_familles();
    $replay = ra_est_replay();
    $yt     = $replay ? ra_youtube_id() : '';

    if ( $replay && $yt ) :
        $infos = ra_replay_infos();
        ?>
<article>
  <div class="replay-scene">
    <div class="cadre">
      <!-- Rien n'est chargé de YouTube avant le clic. -->
      <button class="video" type="button" data-youtube="<?php echo esc_attr( $yt ); ?>" aria-label="<?php echo esc_attr( 'Lire la vidéo : ' . $infos['titre'] ); ?>">
        <img src="<?php echo esc_url( ra_vignette_youtube( $yt ) ); ?>" data-grande="<?php echo esc_url( ra_vignette_youtube( $yt, true ) ); ?>" alt="" width="1280" height="720" fetchpriority="high">
        <span class="gros-bouton"><?php echo ra_icone( 'jouer' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
      </button>
      <?php if ( $f['emission'] ) : ?>
        <span class="rubrique"><a href="<?php echo esc_url( get_category_link( $f['emission'] ) ); ?>"><?php echo esc_html( ra_nom_categorie( $f['emission'] ) ); ?></a> · Replay</span>
      <?php else : ?>
        <span class="rubrique">Replay</span>
      <?php endif; ?>
      <h1><?php echo esc_html( $infos['titre'] ); ?></h1>
      <div class="signature">
        <span class="meta">Émission du <time datetime="<?php echo esc_attr( $infos['date'] ); ?>"><?php echo esc_html( ra_date_longue( $infos['date'] ) ); ?></time></span>
        <?php ra_partage(); ?>
      </div>
    </div>
  </div>

        <?php if ( $f['emission'] ) : ?>
  <div class="hors-corps replay-rappel">
            <?php ra_rappel_emission( $f['emission'], "Un épisode de l'émission" ); ?>
  </div>
        <?php endif; ?>

        <?php $texte = ra_texte_replay(); ?>
        <?php if ( $texte ) : ?>
  <div class="corps"><?php echo $texte; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
        <?php endif; ?>
</article>

        <?php
        $autres = $f['emission'] ? get_posts( array(
            'cat'          => $f['emission']->term_id,
            'numberposts'  => 3,
            'post__not_in' => array( get_the_ID() ),
            'meta_key'     => '_ra_replay', // phpcs:ignore WordPress.DB.SlowDBQuery
            'meta_value'   => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
        ) ) : array();
        ?>
        <?php if ( $autres ) : ?>
<section class="section">
  <div class="cadre">
    <div class="section-tete"><h2>Autres vidéos de l'émission</h2><a class="lien" href="<?php echo esc_url( get_category_link( $f['emission'] ) ); ?>"><?php echo esc_html( ra_nom_categorie( $f['emission'] ) ); ?></a></div>
    <div class="grille trois">
            <?php
            foreach ( $autres as $autre ) {
                ra_carte( $autre, array( 'surtitre' => false ) );
            }
            ?>
    </div>
  </div>
</section>
        <?php endif; ?>

    <?php else : ?>
        <?php
        $titre = ra_titre_propre( get_post()->post_title );
        $ratio = ra_ratio_image();
        ?>
<div class="progression" aria-hidden="true"></div>
<article>
  <header class="article-tete">
        <?php ra_surtitre( null, true ); ?>
    <h1<?php echo ra_titre_en_capitales( $titre ) ? ' class="capitales"' : ''; ?>><?php echo esc_html( $titre ); ?></h1>
        <?php $chapeau = ra_chapeau(); ?>
        <?php if ( $chapeau ) : ?>
    <p class="chapeau"><?php echo esc_html( $chapeau ); ?></p>
        <?php endif; ?>
    <div class="signature">
      <span class="meta"><time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"><?php echo esc_html( ra_date_longue( get_the_date( 'Y-m-d' ) ) ); ?></time> · <?php echo esc_html( ra_temps_lecture() ); ?> min de lecture</span>
        <?php ra_partage(); ?>
    </div>
        <?php
        if ( $f['emission'] ) { // Seulement si l'article relève d'une émission.
            ra_rappel_emission( $f['emission'], "Un sujet de l'émission" );
        }
        ?>
  </header>

        <?php if ( $ratio ) : ?>
  <!-- Le cadre épouse le format réel de l'image : aucun recadrage. -->
  <figure class="article-image">
    <div class="vignette" style="--r:<?php echo esc_attr( $ratio ); ?>"><?php the_post_thumbnail( 'ra-une', array( 'fetchpriority' => 'high', 'loading' => 'eager', 'sizes' => '(max-width: 860px) 100vw, 820px' ) ); ?></div>
            <?php $legende = wp_get_attachment_caption( get_post_thumbnail_id() ); ?>
            <?php if ( $legende ) : ?>
    <figcaption><?php echo esc_html( $legende ); ?></figcaption>
            <?php endif; ?>
  </figure>
        <?php endif; ?>

  <div class="corps"><?php the_content(); ?></div>

  <div class="hors-corps">
        <?php ra_bandeau_direct(); ?>
        <?php ra_partage( true ); ?>
  </div>
</article>

        <?php
        // À lire aussi : d'abord la même rubrique, puis les plus récents.
        $suggeres = array();
        if ( $f['rubrique'] ) {
            $suggeres = ra_articles( array( 'cat' => $f['rubrique']->term_id, 'numberposts' => 3, 'post__not_in' => array( get_the_ID() ) ) );
        }
        if ( count( $suggeres ) < 3 ) {
            $exclus   = array_merge( array( get_the_ID() ), wp_list_pluck( $suggeres, 'ID' ) );
            $suggeres = array_merge( $suggeres, ra_articles( array( 'numberposts' => 3 - count( $suggeres ), 'post__not_in' => $exclus ) ) );
        }
        ?>
        <?php if ( $suggeres ) : ?>
<section class="section">
  <div class="cadre">
    <div class="section-tete"><h2>À lire aussi</h2></div>
    <div class="grille trois">
            <?php
            foreach ( $suggeres as $suggere ) {
                ra_carte( $suggere );
            }
            ?>
    </div>
  </div>
</section>
        <?php endif; ?>
    <?php endif; ?>
    <?php
endwhile;

get_footer();
