<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Admin_Assets {
    public static function init() {
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
    }

    public static function enqueue() {
        $screen = get_current_screen();
        if ( strpos( $screen->id, 'gmp' ) !== false ) {
            wp_enqueue_style( 'gmp-admin-style', GMP_PLUGIN_URL . 'assets/css/gmp-admin.css', [], GMP_PLUGIN_VERSION );
            wp_enqueue_script( 'gmp-admin-script', GMP_PLUGIN_URL . 'assets/js/gmp-admin.js', [ 'jquery' ], GMP_PLUGIN_VERSION, true );

            wp_localize_script( 'gmp-admin-script', 'GMP_Admin_Actions', [
                'nonce' => wp_create_nonce( 'gmp_admin_cycle_action' )
            ] );
        }
    }
}