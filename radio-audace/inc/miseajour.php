<?php
/**
 * Mise à jour du thème depuis les releases GitHub, dans l'écran natif de WordPress
 * (Tableau de bord › Mises à jour, ou Apparence › Thèmes).
 *
 * Fonctionnement :
 *  - une release GitHub « vX.Y.Z » plus récente que la version installée est proposée ;
 *  - le paquet installé est l'asset « radio-audace.zip » de la release (racine : radio-audace/) ;
 *  - la réponse de GitHub est gardée 12 heures ; un lien « Vérifier maintenant » la rafraîchit.
 *
 * Le dépôt doit être PUBLIC : un dépôt privé répond 404 au site.
 * Publication d'une version : ./release.sh à la racine du dépôt.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RA_Mise_A_Jour {

    const DEPOT = 'lwilly3/radio-audace-theme';
    const ASSET = 'radio-audace.zip';
    const CACHE = 'ra_github_release';
    const DUREE = 12 * HOUR_IN_SECONDS;

    /** Dossier du thème (« radio-audace »). */
    private string $slug;

    /** Dernière erreur rencontrée en interrogeant GitHub (vide si aucune). */
    private string $erreur = '';

    public function __construct() {
        $this->slug = get_template();

        add_filter( 'pre_set_site_transient_update_themes', array( $this, 'verifier' ) );
        add_action( 'upgrader_process_complete', array( $this, 'apres_mise_a_jour' ), 10, 2 );
        add_action( 'admin_init', array( $this, 'verification_manuelle' ) );
        add_action( 'admin_notices', array( $this, 'avis' ) );
    }

    private static function depot(): string {
        return (string) apply_filters( 'ra_depot_github', self::DEPOT );
    }

    private static function version( string $tag ): string {
        return ltrim( $tag, 'vV' );
    }

    /** Dernière release publiée, ou null si GitHub ne répond pas comme attendu. */
    private function release(): ?array {
        $cache = get_transient( self::CACHE );
        if ( is_array( $cache ) ) {
            return $cache;
        }
        $release = $this->release_publique();
        if ( $release ) {
            set_transient( self::CACHE, $release, self::DUREE );
            return $release;
        }
        $premiere_erreur = $this->erreur;

        // Repli : l'API de GitHub, qui donne aussi les notes de version mais refuse souvent (HTTP 403)
        // les hébergements mutualisés, limités à 60 requêtes par heure pour toute l'adresse IP.
        $reponse = wp_remote_get( 'https://api.github.com/repos/' . self::depot() . '/releases/latest', array(
            'timeout' => 10,
            'headers' => array(
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; theme-radio-audace',
            ),
        ) );
        if ( is_wp_error( $reponse ) ) {
            $this->erreur = $reponse->get_error_message();
            return null;
        }
        $code = (int) wp_remote_retrieve_response_code( $reponse );
        if ( 200 !== $code ) {
            // 404 : dépôt privé ou aucune release ; 403 : trop de requêtes.
            $this->erreur = $premiere_erreur . ' ; API : HTTP ' . $code;
            return null;
        }
        $corps = json_decode( wp_remote_retrieve_body( $reponse ), true );
        if ( ! is_array( $corps ) || empty( $corps['tag_name'] ) ) {
            $this->erreur = 'réponse GitHub illisible';
            return null;
        }
        // On ne garde que l'utile (le transient reste petit).
        $release = array(
            'tag_name'     => (string) $corps['tag_name'],
            'html_url'     => (string) ( $corps['html_url'] ?? '' ),
            'body'         => (string) ( $corps['body'] ?? '' ),
            'published_at' => (string) ( $corps['published_at'] ?? '' ),
            'paquet'       => '',
        );
        foreach ( (array) ( $corps['assets'] ?? array() ) as $asset ) {
            if ( self::ASSET === ( $asset['name'] ?? '' ) ) {
                $release['paquet'] = (string) $asset['browser_download_url'];
            }
        }
        set_transient( self::CACHE, $release, self::DUREE );
        return $release;
    }

    /**
     * Dernière release lue par l'adresse publique de GitHub, sans passer par l'API.
     *
     * « /releases/latest » redirige vers « /releases/tag/vX.Y.Z » : le numéro de version est
     * dans l'adresse de redirection, et l'adresse du paquet s'en déduit. Cette voie n'a pas
     * de quota par adresse IP, contrairement à l'API.
     */
    private function release_publique(): ?array {
        $base    = 'https://github.com/' . self::depot() . '/releases';
        $reponse = wp_remote_head( $base . '/latest', array(
            'timeout'     => 10,
            'redirection' => 0,
            'headers'     => array( 'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; theme-radio-audace' ),
        ) );
        if ( is_wp_error( $reponse ) ) {
            $this->erreur = $reponse->get_error_message();
            return null;
        }
        $code   = (int) wp_remote_retrieve_response_code( $reponse );
        $cible  = (string) wp_remote_retrieve_header( $reponse, 'location' );
        if ( ! in_array( $code, array( 301, 302, 303, 307, 308 ), true ) || ! preg_match( '~/releases/tag/(v?\d+\.\d+\.\d+)$~', $cible, $m ) ) {
            // 404 : dépôt privé. Redirection vers « /releases » : aucune release publiée.
            $this->erreur = 'HTTP ' . $code . ( $cible ? ' (aucune release publiée)' : '' );
            return null;
        }
        $this->erreur = '';
        return array(
            'tag_name'     => $m[1],
            'html_url'     => $base . '/tag/' . $m[1],
            'body'         => '',
            'published_at' => '',
            'paquet'       => $base . '/download/' . $m[1] . '/' . self::ASSET,
        );
    }

    /** Annonce la nouvelle version à WordPress. */
    public function verifier( $transient ) {
        if ( ! is_object( $transient ) ) {
            return $transient;
        }
        $release = $this->release();
        if ( ! $release ) {
            return $transient;
        }
        $distante = self::version( $release['tag_name'] );
        $element  = array(
            'theme'        => $this->slug,
            'new_version'  => $distante,
            'url'          => $release['html_url'],
            'package'      => $release['paquet'],
            'requires'     => '6.5',
            'requires_php' => '8.0',
        );
        // Sans l'asset attendu, on ne propose rien : le ZIP automatique de GitHub n'a pas la bonne racine.
        if ( $release['paquet'] && version_compare( $distante, RADIO_AUDACE_THEME_VERSION, '>' ) ) {
            $transient->response[ $this->slug ] = $element;
        } else {
            unset( $transient->response[ $this->slug ] );
            $transient->no_update[ $this->slug ] = $element;
        }
        return $transient;
    }

    public function apres_mise_a_jour( $upgrader, $options ): void {
        if ( 'update' === ( $options['action'] ?? '' ) && 'theme' === ( $options['type'] ?? '' ) && in_array( $this->slug, (array) ( $options['themes'] ?? array() ), true ) ) {
            delete_transient( self::CACHE );
        }
    }

    /** Adresse signée qui force une vérification immédiate. */
    public static function url_verification(): string {
        return wp_nonce_url( add_query_arg( 'ra_verifier_maj', '1', admin_url( 'themes.php' ) ), 'ra_verifier_maj' );
    }

    public function verification_manuelle(): void {
        if ( empty( $_GET['ra_verifier_maj'] ) || ! current_user_can( 'update_themes' ) ) {
            return;
        }
        check_admin_referer( 'ra_verifier_maj' );
        delete_transient( self::CACHE );
        delete_site_transient( 'update_themes' );
        wp_update_themes();

        $release  = $this->release();
        $resultat = array( 'ra_maj_verifiee' => '1' );
        if ( ! $release ) {
            $resultat['ra_maj_erreur'] = rawurlencode( $this->erreur ? $this->erreur : 'inconnue' );
        } else {
            $resultat['ra_maj_distante'] = rawurlencode( self::version( $release['tag_name'] ) );
            if ( ! $release['paquet'] ) {
                $resultat['ra_maj_erreur'] = rawurlencode( 'la release ne contient pas ' . self::ASSET );
            }
        }
        wp_safe_redirect( add_query_arg( $resultat, admin_url( 'themes.php' ) ) );
        exit;
    }

    /** Sur Apparence › Thèmes : version installée, lien de vérification, résultat. */
    public function avis(): void {
        $ecran = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $ecran || 'themes' !== $ecran->id || ! current_user_can( 'update_themes' ) ) {
            return;
        }
        $lien = '<a href="' . esc_url( self::url_verification() ) . '">Vérifier les mises à jour maintenant</a>';

        // phpcs:disable WordPress.Security.NonceVerification -- simple affichage d'un résultat.
        if ( empty( $_GET['ra_maj_verifiee'] ) ) {
            echo '<div class="notice notice-info"><p>Thème Radio Audace ' . esc_html( RADIO_AUDACE_THEME_VERSION ) . ' — mises à jour depuis GitHub (' . esc_html( self::depot() ) . '). ' . $lien . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
            return;
        }
        $erreur   = isset( $_GET['ra_maj_erreur'] ) ? sanitize_text_field( wp_unslash( $_GET['ra_maj_erreur'] ) ) : '';
        $distante = isset( $_GET['ra_maj_distante'] ) ? sanitize_text_field( wp_unslash( $_GET['ra_maj_distante'] ) ) : '';
        // phpcs:enable

        if ( '' !== $erreur ) {
            $aide = 'HTTP 404' === $erreur ? ' Le dépôt est peut-être privé, ou n\'a encore aucune release.' : '';
            printf(
                '<div class="notice notice-error is-dismissible"><p>Thème Radio Audace : la vérification a échoué (dépôt %1$s, erreur : %2$s).%3$s</p></div>',
                esc_html( self::depot() ),
                esc_html( $erreur ),
                esc_html( $aide )
            );
        } elseif ( version_compare( $distante, RADIO_AUDACE_THEME_VERSION, '>' ) ) {
            $maj = wp_nonce_url( admin_url( 'update.php?action=upgrade-theme&theme=' . rawurlencode( $this->slug ) ), 'upgrade-theme_' . $this->slug );
            printf(
                '<div class="notice notice-warning is-dismissible"><p>Thème Radio Audace : la version %1$s est disponible (version installée : %2$s). <a href="%3$s"><strong>Mettre à jour maintenant</strong></a></p></div>',
                esc_html( $distante ),
                esc_html( RADIO_AUDACE_THEME_VERSION ),
                esc_url( $maj )
            );
        } else {
            printf(
                '<div class="notice notice-success is-dismissible"><p>Le thème Radio Audace est à jour (version %s).</p></div>',
                esc_html( RADIO_AUDACE_THEME_VERSION )
            );
        }
    }
}

new RA_Mise_A_Jour();
