<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Admin_Assets {
    public static function init() {
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
    }

    public static function enqueue( $hook ) {
        // Load CSS only for GMP pages
        if ( isset($_GET['page']) && strpos($_GET['page'], 'gmp-cycle') === 0 ) {
            wp_enqueue_style( 'gmp-admin', GMP_PLUGIN_URL . 'assets/css/gmp-admin.css', [], GMP_PLUGIN_VERSION );
        }
    }
}

