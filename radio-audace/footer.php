<?php
/**
 * Pied de page et lecteur persistant du direct.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$station   = ra_station();
$emissions = array_slice( ra_emissions(), 0, 4 );
$mere      = ra_id_emissions();
$antenne   = ra_antenne();
$pages     = array();
foreach ( array( 'about-us' => 'À propos', 'contacts' => 'Contacts', 'invites' => 'Invités', 'newsletter' => 'Newsletter' ) as $slug => $nom ) {
    $page = get_page_by_path( $slug );
    if ( $page && 'publish' === $page->post_status ) {
        $pages[ $nom ] = get_permalink( $page );
    }
}
$legal = array();
foreach ( array( 'mentions-legales' => 'Mentions légales', 'confidentialite' => 'Confidentialité' ) as $slug => $nom ) {
    $page = get_page_by_path( $slug );
    if ( $page && 'publish' === $page->post_status ) {
        $legal[ $nom ] = get_permalink( $page );
    }
}
if ( ! isset( $legal['Confidentialité'] ) && get_privacy_policy_url() ) {
    $legal['Confidentialité'] = get_privacy_policy_url();
}
?>
</main>

<footer class="pied">
  <div class="cadre">
    <div class="colonnes">
      <div>
        <a class="marque" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $station['nom'] . ' ' . $station['frequence'] . ', accueil' ); ?>">
          <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo.svg' ); ?>" alt="" width="170" height="42" loading="lazy">
        </a>
        <address>
          <?php echo esc_html( $station['adresse'][0] ); ?><br><span class="l2"><?php echo esc_html( $station['adresse'][1] ); ?></span>
          <span class="coord">
            <?php foreach ( $station['telephones'] as $tel ) : ?>
              <a href="<?php echo esc_url( ra_tel( $tel ) ); ?>"><?php echo esc_html( $tel ); ?></a>
            <?php endforeach; ?>
            <a href="<?php echo esc_url( 'mailto:' . $station['courriel'] ); ?>"><?php echo esc_html( $station['courriel'] ); ?></a>
          </span>
        </address>
        <?php $reseaux = array_filter( $station['reseaux'] ); ?>
        <?php if ( $reseaux ) : ?>
          <div class="reseaux" aria-label="Réseaux sociaux">
            <?php foreach ( $reseaux as $nom => $url ) : ?>
              <a href="<?php echo esc_url( $url ); ?>" rel="noopener"><?php echo esc_html( $nom ); ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if ( $emissions ) : ?>
      <nav aria-labelledby="p-emi"><h2 id="p-emi">Émissions</h2><ul>
        <?php foreach ( $emissions as $e ) : ?>
          <li><a href="<?php echo esc_url( get_category_link( $e ) ); ?>"><?php echo esc_html( ra_nom_categorie( $e ) ); ?></a></li>
        <?php endforeach; ?>
        <?php if ( $mere ) : ?><li><a href="<?php echo esc_url( get_category_link( $mere ) ); ?>">Toutes les émissions</a></li><?php endif; ?>
        <?php if ( ra_url_grille() ) : ?><li><a href="<?php echo esc_url( ra_url_grille() ); ?>">Grille des programmes</a></li><?php endif; ?>
        <li><a href="<?php echo esc_url( ra_url_ecoute() ); ?>">Le poste : écouter en ligne</a></li>
      </ul></nav>
      <?php endif; ?>

      <nav aria-labelledby="p-rub"><h2 id="p-rub">Rubriques</h2><ul>
        <?php foreach ( ra_rubriques() as $r ) : ?>
          <li><a href="<?php echo esc_url( get_category_link( $r ) ); ?>"><?php echo esc_html( ra_nom_categorie( $r ) ); ?></a></li>
        <?php endforeach; ?>
      </ul></nav>

      <?php if ( $pages ) : ?>
      <nav aria-labelledby="p-ra"><h2 id="p-ra"><?php echo esc_html( $station['nom'] ); ?></h2><ul>
        <?php foreach ( $pages as $nom => $url ) : ?>
          <li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $nom ); ?></a></li>
        <?php endforeach; ?>
      </ul></nav>
      <?php endif; ?>
    </div>
    <div class="bas">
      <span>© <?php echo esc_html( wp_date( 'Y' ) . ' ' . $station['editeur'] ); ?> · Douala</span>
      <?php if ( $legal ) : ?>
      <nav aria-label="Informations légales">
        <?php foreach ( $legal as $nom => $url ) : ?>
          <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $nom ); ?></a>
        <?php endforeach; ?>
      </nav>
      <?php endif; ?>
    </div>
  </div>
</footer>

<aside class="lecteur" aria-label="Lecteur du direct" data-antenne>
  <div class="cadre">
    <button class="jouer" type="button" data-direct-btn><span class="ico"><?php echo ra_icone( 'jouer' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="sr libelle">Écouter le direct</span></button>
    <div class="txt">
      <div class="etat"><span class="pastille"></span><span data-etat></span></div>
      <div class="quoi"><span class="t" data-a="titre"><?php echo esc_html( $antenne['titre'] ); ?></span> <span class="d">· <span data-a="detail"><?php echo esc_html( $antenne['detail'] ); ?></span></span></div>
    </div>
    <div class="onde" aria-hidden="true"><?php echo str_repeat( '<i></i>', 7 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
    <button class="fermer" type="button" aria-label="Fermer le lecteur" data-direct-fermer><?php echo ra_icone( 'croix' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
  </div>
</aside>

<?php wp_footer(); ?>
</body>
</html>
