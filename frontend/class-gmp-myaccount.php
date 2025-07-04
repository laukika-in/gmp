<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_MyAccount {

    public static function init() {
        add_shortcode( 'gmp_emi_cycles', [ __CLASS__, 'render_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    // ✅ Enqueue CSS/JS only when shortcode is used
    public static function enqueue_assets() {
        if ( is_user_logged_in() && is_account_page() && has_shortcode( get_post()->post_content, 'gmp_emi_cycles' ) ) {
            wp_enqueue_style( 'gmp-style', GMP_PLUGIN_URL . 'assets/css/gmp-style.css', [], GMP_PLUGIN_VERSION );
            wp_enqueue_script( 'gmp-style', GMP_PLUGIN_URL . 'assets/js/gmp-style.js', [ 'jquery' ], GMP_PLUGIN_VERSION, true );
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