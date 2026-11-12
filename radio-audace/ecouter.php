<?php
/**
 * Page « Écouter » (/ecouter) : un poste de radio plein écran, à envoyer par lien.
 *
 * Page autonome : ni en-tête, ni pied, ni lecteur de bas d'écran. Le bouton central pilote
 * le même lecteur que le reste du site (assets/js/theme.js) ; le poste, lui, est dessiné en
 * CSS (assets/css/ecouter.css) et animé par assets/js/ecouter.js. Sans script, la page
 * reste lisible et propose le flux en lien direct.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$station = ra_station();
$antenne = ra_antenne();
$nom     = $station['nom'] . ' ' . $station['frequence'];
$adresse = ra_url_ecoute();
$whatsapp = 'https://wa.me/?text=' . rawurlencode( 'Écoutez ' . $nom . ' en direct : ' . $adresse );

// Fin de la bande FM, de 102 à 108 MHz (l'échelle va jusqu'à 109 pour laisser de l'air après 108).
$frequence = (float) preg_replace( '~[^0-9.]~', '', str_replace( ',', '.', $station['frequence'] ) );
if ( $frequence < 102 || $frequence > 108 ) {
    $frequence = 106.8;
}
$pct = function ( float $f ): string {
    return rtrim( rtrim( number_format( ( $f - 102 ) / 7 * 100, 3, '.', '' ), '0' ), '.' ) . '%';
};
$marque = strtoupper( remove_accents( trim( str_ireplace( 'radio', '', $station['nom'] ) ) ) );
?><!doctype html>
<html <?php language_attributes(); ?> data-direct="arret">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<main class="pv-scene" id="contenu" data-antenne data-type="<?php echo esc_attr( $antenne['type'] ); ?>">
  <h1 class="sr"><?php echo esc_html( ra_ecoute_titre() ); ?></h1>
  <header class="pv-tete">
    <b><span class="pastille"></span>Douala · <?php echo esc_html( $station['frequence'] ); ?></b>
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Lire l'actualité ›</a>
  </header>

  <div class="pv-milieu">
    <div class="pv-poste" data-station="<?php echo esc_attr( (string) $frequence ); ?>">
      <div class="pv-toile">
        <div class="pv-plaque"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo.svg' ); ?>" alt="<?php echo esc_attr( $nom ); ?>" width="130" height="32"></div>
      </div>

      <div class="pv-vitre">
        <div class="pv-echelle" aria-hidden="true" style="--f:<?php echo esc_attr( (string) $frequence ); ?>">
          <span class="st" style="left:<?php echo esc_attr( $pct( $frequence ) ); ?>"><?php echo esc_html( $marque ); ?></span>
          <?php
          // Un trait tous les 0,2 MHz : la station a le sien, et le chiffre lu sous l'aiguille est le sien.
          for ( $n = 0; $n <= 30; $n++ ) {
              $f    = round( 102 + $n * 0.2, 1 );
              $fort = abs( $f - round( $f ) ) < 0.01;
              printf( '<i%s style="left:%s"></i>', $fort ? ' class="fort"' : '', esc_attr( $pct( $f ) ) );
              // Les repères chiffrés trop proches de la station sont omis : on ne lit qu'un chiffre sous l'aiguille.
              if ( $fort && 0 === ( (int) round( $f ) ) % 2 && abs( $f - $frequence ) > 1.1 ) {
                  printf( '<span class="n" style="left:%s">%d</span>', esc_attr( $pct( $f ) ), (int) round( $f ) );
              }
          }
          ?>
          <span class="n ici" style="left:<?php echo esc_attr( $pct( $frequence ) ); ?>"><?php echo esc_html( rtrim( rtrim( number_format( $frequence, 1, '.', '' ), '0' ), '.' ) ); ?></span>
          <b class="pv-aiguille"></b>
        </div>
        <div class="pv-affiche" aria-live="polite">
          <span class="pv-etiq"><span class="pastille"></span><span data-a="court"><?php echo esc_html( $antenne['court'] ); ?></span></span>
          <h2 data-a="titre"><?php echo esc_html( $antenne['titre'] ); ?></h2>
          <span class="d" data-a="detail"><?php echo esc_html( $antenne['detail'] ); ?></span>
        </div>
      </div>

      <div class="pv-facade">
        <div class="pv-commande" aria-hidden="true">
          <div class="pv-niche"><i class="pv-voyant"></i></div>
          <span>Marche</span>
        </div>
        <div class="pv-commande">
          <button class="pv-marche" type="button" data-direct-btn><span class="ico"><?php echo ra_icone( 'jouer' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="sr libelle">Écouter le direct</span></button>
          <span data-touche aria-hidden="true">Écouter</span>
        </div>
        <?php // L'accord est un jeu facultatif : il n'est pas présenté aux lecteurs d'écran, pour lesquels il ne servirait à rien. ?>
        <div class="pv-commande" aria-hidden="true">
          <div class="pv-niche"><div class="pv-prise" data-accord><div class="pv-rond"></div></div></div>
          <span>Accord</span>
        </div>
      </div>
    </div>

    <p class="pv-ou" role="status" data-ou>Appuyez sur le bouton pour écouter</p>
    <p class="pv-apres" data-a="desc"<?php echo 'musique' === $antenne['type'] && $antenne['desc'] ? '' : ' hidden'; ?>><?php echo esc_html( $antenne['desc'] ); ?></p>
    <noscript><p class="pv-ou"><a href="<?php echo esc_url( $station['flux'] ); ?>">Ouvrir le flux du direct</a></p></noscript>
  </div>

  <footer class="pv-partage">
    <a href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener" aria-label="Envoyer le lien sur WhatsApp"><?php echo ra_icone( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Envoyer</a>
    <button type="button" data-ecoute-copie="<?php echo esc_url( $adresse ); ?>"><?php echo ra_icone( 'lien' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>Copier le lien</span></button>
  </footer>
</main>

<?php wp_footer(); ?>
</body>
</html>
