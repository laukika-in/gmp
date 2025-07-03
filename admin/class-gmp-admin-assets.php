<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Admin_Assets {

    public static function init() {
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    public static function enqueue_assets( $hook ) {
        // Restrict to only GMP admin pages
        if ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'gmp' ) !== false ) {
            wp_enqueue_style( 'gmp-admin', GMP_PLUGIN_URL . 'assets/css/gmp-admin.css', [], GMP_PLUGIN_VERSION );
            wp_enqueue_script( 'gmp-admin', GMP_PLUGIN_URL . 'assets/js/gmp-admin.js', [ 'jquery' ], GMP_PLUGIN_VERSION, true );
        }
    }
}
