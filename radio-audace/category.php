<?php
/**
 * Catégorie. Trois cas :
 *  - la catégorie mère « émissions » : la liste des émissions ;
 *  - une émission : une page de programme (visuel, horaire, vidéos, articles) ;
 *  - une rubrique : une liste d'articles, strictement par date.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$terme   = get_queried_object();
$nom     = ra_nom_categorie( $terme );
$station = ra_station();

if ( (int) $terme->term_id === ra_id_emissions() ) :
    ?>
  <div class="cadre">
    <header class="rubrique-tete programme">
      <h1>Les émissions</h1>
      <p>Tous les rendez-vous de l'antenne, avec leurs vidéos et leurs sujets.</p>
    </header>
  </div>
  <section class="section section-serree">
    <div class="cadre">
      <div class="grille quatre emissions-liste">
        <?php
        foreach ( ra_emissions() as $emission ) {
            ra_carte_emission( $emission );
        }
        ?>
      </div>
    </div>
  </section>
    <?php

elseif ( ra_est_emission( $terme ) ) :
    $grille   = ra_grille_pour( $nom );
    $visuel   = ra_visuel_emission( $terme );
    $accroche = ra_description_emission( $terme );
    $videos   = get_posts( array( 'cat' => $terme->term_id, 'numberposts' => 12, 'meta_key' => '_ra_replay', 'meta_value' => '1' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
    $articles = ra_articles( array( 'cat' => $terme->term_id, 'numberposts' => 9 ) );
    ?>
  <!-- Page d'émission : chaque information absente de RadioManager disparaît simplement. -->
  <section class="emission-tete">
    <div class="cadre<?php echo $visuel ? '' : ' sans-visuel'; ?>">
      <div>
        <span class="rubrique">Émission</span>
        <h1><?php echo esc_html( $nom ); ?></h1>
        <?php if ( $accroche ) : ?>
          <p class="accroche"><?php echo esc_html( $accroche ); ?></p>
        <?php endif; ?>
        <?php if ( $grille ) : ?>
        <dl class="fiche">
          <div><dt>Diffusion</dt><dd><?php echo esc_html( ra_jours_texte( (array) $grille['jours'] ) . ', ' . ra_heure( $grille['debut'] ) ); ?></dd></div>
            <?php
            $presentateurs = array_filter( array_column( (array) ( $grille['presentateurs'] ?? array() ), 'nom' ) );
            if ( ! $presentateurs && ! empty( $grille['qui'] ) ) {
                $presentateurs = array( $grille['qui'] );
            }
            ?>
            <?php if ( $presentateurs ) : ?>
          <div><dt>Présentation</dt><dd><?php echo esc_html( implode( ', ', $presentateurs ) ); ?></dd></div>
            <?php endif; ?>
        </dl>
        <?php endif; ?>
        <div class="boutons">
          <?php ra_bouton_direct(); ?>
          <a class="bouton clair" href="<?php echo esc_url( ra_tel( $station['telephones'][0] ) ); ?>">Appeler l'antenne</a>
        </div>
      </div>
      <?php if ( $visuel ) : ?>
        <figure class="vignette"><img src="<?php echo esc_url( $visuel ); ?>" alt="<?php echo esc_attr( "Visuel de l'émission " . $nom ); ?>" width="1280" height="720" fetchpriority="high"></figure>
      <?php endif; ?>
    </div>
  </section>

    <?php if ( $articles ) : ?>
  <section class="section">
    <div class="cadre">
      <div class="section-tete"><div><h2>Les sujets de l'émission</h2></div></div>
      <div class="grille trois">
        <?php
        foreach ( $articles as $article ) {
            ra_carte( $article );
        }
        ?>
      </div>
    </div>
  </section>
    <?php endif; ?>

    <?php if ( $videos ) : ?>
  <section class="section<?php echo $articles ? ' section-suite' : ''; ?>">
    <div class="cadre">
      <div class="section-tete"><div><h2>Archives vidéo</h2><p>Les émissions filmées, à revoir.</p></div></div>
      <div class="grille quatre">
        <?php
        foreach ( $videos as $video ) {
            ra_carte( $video, array( 'surtitre' => false ) );
        }
        ?>
      </div>
    </div>
  </section>
    <?php endif; ?>

    <?php ra_bloc_emissions( 'Les autres émissions', "Tous les rendez-vous de l'antenne.", (int) $terme->term_id ); ?>
    <?php

else :
    $enfants = get_terms( array( 'taxonomy' => 'category', 'parent' => $terme->term_id, 'hide_empty' => true ) );
    $enfants = is_wp_error( $enfants ) ? array() : $enfants;
    $parent  = $terme->parent ? get_term( $terme->parent, 'category' ) : null;
    $total   = (int) $GLOBALS['wp_query']->found_posts;
    $desc    = wp_strip_all_tags( term_description( $terme ) );
    ?>
  <div class="cadre">
    <header class="rubrique-tete <?php echo esc_attr( ra_classe_rubrique( $terme ) ); ?>">
      <h1><?php echo esc_html( $nom ); ?></h1>
      <?php if ( $desc && mb_strlen( $desc ) > 12 ) : ?>
        <p><?php echo esc_html( $desc ); ?></p>
      <?php endif; ?>
      <?php if ( $enfants || ( $parent instanceof WP_Term && ! in_array( $parent->slug, ra_categories_ignorees(), true ) ) ) : ?>
      <nav class="puces" aria-label="Sous-rubriques">
        <?php if ( $parent instanceof WP_Term && ! in_array( $parent->slug, ra_categories_ignorees(), true ) ) : ?>
          <a href="<?php echo esc_url( get_category_link( $parent ) ); ?>">‹ <?php echo esc_html( ra_nom_categorie( $parent ) ); ?></a>
        <?php endif; ?>
        <?php foreach ( $enfants as $enfant ) : ?>
          <a href="<?php echo esc_url( get_category_link( $enfant ) ); ?>"><?php echo esc_html( ra_nom_categorie( $enfant ) ); ?></a>
        <?php endforeach; ?>
      </nav>
      <?php endif; ?>
    </header>
  </div>

  <section class="section section-serree">
    <div class="cadre">
      <?php if ( have_posts() ) : ?>
      <div class="grille <?php echo $total <= 2 ? 'deux' : 'trois'; ?>">
        <?php
        while ( have_posts() ) {
            the_post();
            ra_carte( get_post(), array( 'extrait' => $total <= 2 ) );
        }
        ?>
      </div>
      <p class="note-fin"><?php echo esc_html( 1 === $total ? '1 article dans cette rubrique.' : $total . ' articles dans cette rubrique.' ); ?></p>
            <?php ra_pagination(); ?>
      <?php else : ?>
      <p class="note-fin">Aucun article dans cette rubrique pour le moment.</p>
      <?php endif; ?>
    </div>
  </section>

    <?php
    // Rubrique presque vide : la suite est clairement séparée, jamais mélangée.
    if ( $total < 4 ) :
        $ids     = wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' );
        $ailleurs = ra_articles( array( 'numberposts' => 3, 'post__not_in' => $ids ) );
        if ( $ailleurs ) :
            ?>
  <section class="section papier">
    <div class="cadre">
      <div class="section-tete"><h2>Ailleurs sur <?php echo esc_html( $station['nom'] ); ?></h2></div>
      <div class="grille trois">
            <?php
            foreach ( $ailleurs as $article ) {
                ra_carte( $article );
            }
            ?>
      </div>
    </div>
  </section>
            <?php
        endif;
    endif;
endif;

get_footer();
