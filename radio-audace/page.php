<?php
/**
 * Page (À propos, Contacts, Newsletter…) : même colonne de lecture qu'un article.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while ( have_posts() ) :
    the_post();
    ?>
<article>
  <header class="article-tete">
    <h1><?php echo esc_html( ra_titre_propre( get_post()->post_title ) ); ?></h1>
  </header>
  <div class="corps"><?php the_content(); ?></div>
</article>
    <?php
endwhile;

get_footer();
