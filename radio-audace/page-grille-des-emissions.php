<?php
/**
 * Page de la grille (appliquée automatiquement à la page dont le slug est « grille-des-emissions »).
 *
 * Entièrement pilotée par RadioManager via le plugin audace-analytics :
 *  - mode normal : les créneaux du jour choisi (?jour=1…7) ;
 *  - mode « relance » (nouvelle saison en préparation) ou grille vide : le message de RadioManager.
 * Le créneau prévu n'est marqué « En ce moment » que si l'émission est réellement à l'antenne.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$reglages   = ra_grille_reglages();
$grille     = ra_grille();
$maintenant = current_datetime();
$aujourdhui = (int) $maintenant->format( 'N' );
$jour       = isset( $_GET['jour'] ) ? (int) $_GET['jour'] : $aujourdhui; // phpcs:ignore WordPress.Security.NonceVerification
$jour       = $jour >= 1 && $jour <= 7 ? $jour : $aujourdhui;
$antenne    = ra_antenne();
$heure      = $maintenant->format( 'H:i' );
$courts     = array( 1 => 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim' );
$lundi      = $maintenant->modify( '-' . ( $aujourdhui - 1 ) . ' day' );
?>
  <div class="cadre">
    <header class="rubrique-tete programme">
      <h1><?php echo esc_html( 'Grille des émissions' === $reglages['titre'] ? 'La grille' : $reglages['titre'] ); ?></h1>
      <?php if ( $grille ) : ?>
      <p>Les rendez-vous de l'antenne, jour par jour. Heure de Douala.</p>
      <nav class="jours" aria-label="Jour de la semaine">
        <?php for ( $j = 1; $j <= 7; $j++ ) : ?>
          <a href="<?php echo esc_url( add_query_arg( 'jour', $j, get_permalink() ) ); ?>"<?php echo $j === $jour ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $courts[ $j ] ); ?><small><?php echo esc_html( $j === $aujourdhui ? "Aujourd'hui" : $lundi->modify( '+' . ( $j - 1 ) . ' day' )->format( 'j' ) ); ?></small></a>
        <?php endfor; ?>
      </nav>
      <?php endif; ?>
    </header>
  </div>

  <section class="section section-serree">
    <div class="cadre">
      <?php if ( $grille ) : ?>
            <?php $creneaux = ra_grille_jour( $jour ); ?>
            <?php if ( $creneaux ) : ?>
      <ol class="creneaux">
                <?php
                foreach ( $creneaux as $c ) :
                    $prevu_maintenant = $jour === $aujourdhui && $c['debut'] <= $heure && $c['fin'] > $heure;
                    $en_ligne         = $prevu_maintenant && 'emission' === $antenne['type'] && ra_cle_nom( $antenne['titre'] ) === ra_cle_nom( $c['nom'] );
                    $passe            = $jour === $aujourdhui && $c['fin'] <= $heure;
                    $qui              = implode( ', ', array_filter( array_column( (array) ( $c['presentateurs'] ?? array() ), 'nom' ) ) );
                    $qui              = $qui ? $qui : (string) ( $c['qui'] ?? '' );
                    $image            = ! empty( $c['logo'] ) ? $c['logo'] : ( $c['image'] ?? '' );
                    ?>
        <li class="creneau<?php echo $prevu_maintenant ? ' en-cours' : ( $passe ? ' passe' : '' ); ?>">
          <div class="heure"><?php echo esc_html( ra_heure( $c['debut'] ) ); ?><small>→ <?php echo esc_html( ra_heure( $c['fin'] ) ); ?></small></div>
                    <?php if ( $image ) : ?>
          <figure class="vignette"><img src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy" width="320" height="180"></figure>
                    <?php else : ?>
          <div class="vignette sans-visuel" aria-hidden="true"></div>
                    <?php endif; ?>
          <div>
                    <?php if ( $prevu_maintenant ) : ?>
            <span class="etiquette"><?php echo $en_ligne ? '<span class="pastille"></span>En ce moment' : 'Prévu à cette heure'; ?></span>
                    <?php endif; ?>
            <h2><?php echo esc_html( $c['nom'] ); ?></h2>
                    <?php if ( $qui || ! empty( $c['description'] ) ) : ?>
            <p><?php echo esc_html( $qui ? 'Avec ' . $qui : wp_trim_words( $c['description'], 18, '…' ) ); ?></p>
                    <?php endif; ?>
          </div>
                    <?php
                    if ( $prevu_maintenant ) {
                        ra_bouton_direct();
                    }
                    ?>
        </li>
                <?php endforeach; ?>
      </ol>
            <?php else : ?>
      <p class="note-fin">Aucune émission n'est prévue ce jour-là.</p>
            <?php endif; ?>
      <?php else : ?>
      <!-- Nouvelle saison en préparation (ou plugin de grille absent). -->
      <div class="relance">
        <b>La nouvelle grille arrive prochainement</b>
        <p><?php echo esc_html( $reglages['message'] ? $reglages['message'] : 'Radio Audace prépare sa nouvelle saison. En attendant, le direct continue.' ); ?></p>
        <div class="boutons">
            <?php ra_bouton_direct( 'lecture sombre' ); ?>
            <?php if ( ra_id_emissions() ) : ?>
          <a class="bouton contour" href="<?php echo esc_url( get_category_link( ra_id_emissions() ) ); ?>">Voir les émissions</a>
            <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </section>

<?php
get_footer();
