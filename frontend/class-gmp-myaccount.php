<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_MyAccount {

    public static function init() {
        add_shortcode( 'gmp_emi_cycles', [ __CLASS__, 'render_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    // ✅ Enqueue CSS/JS only when shortcode is used
    public static function enqueue_assets() {
            if ( is_account_page() && is_user_logged_in() ) {

            wp_enqueue_style( 'gmp-style', GMP_PLUGIN_URL . 'assets/css/gmp-style.css', [], GMP_PLUGIN_VERSION );
            wp_enqueue_script( 'gmp-style', GMP_PLUGIN_URL . 'assets/js/gmp-style.js', [ 'jquery' ], GMP_PLUGIN_VERSION, true );

              wp_enqueue_style( 'fancybox', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.css' );
        wp_enqueue_script( 'fancybox', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.umd.js', [], null, true );
 
        }
    }

    // ✅ Renders content via shortcode
    public static function render_shortcode( $atts = [], $content = null ) {
        ob_start();

        echo '<div class="gmp-emi-cycles">';
        include GMP_PLUGIN_DIR . 'views/front-cycle-list.php';
        echo '</div>';

        return ob_get_clean();
    }
}