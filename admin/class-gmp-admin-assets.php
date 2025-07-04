<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Admin_Assets {
    public static function init() {
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
    }

    public static function enqueue_styles() {
        // Only enqueue on our plugin's admin pages
        $screen = get_current_screen();
        if ( isset( $screen->id ) && strpos( $screen->id, 'gmp' ) !== false ) {
            wp_enqueue_style(
                'gmp-admin-css',
                GMP_PLUGIN_URL . 'assets/css/gmp-admin.css',
                [],
                GMP_PLUGIN_VERSION
            );
        }
    }
}
