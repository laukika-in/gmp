<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Init {

    public static function init() {
        // Load Core
        require_once GMP_PLUGIN_DIR . 'includes/class-gmp-db.php';
        require_once GMP_PLUGIN_DIR . 'includes/class-gmp-product-helper.php';
        require_once GMP_PLUGIN_DIR . 'includes/class-gmp-emicycle.php';
        require_once GMP_PLUGIN_DIR . 'includes/class-gmp-checkout-hook.php';
        require_once GMP_PLUGIN_DIR . 'settings/class-gmp-settings.php';

        // Admin
        if ( is_admin() ) {
            require_once GMP_PLUGIN_DIR . 'admin/class-gmp-admin-assets.php';
            require_once GMP_PLUGIN_DIR . 'admin/class-gmp-admin-menu.php';
            require_once GMP_PLUGIN_DIR . 'admin/class-gmp-admin-list.php';
            require_once GMP_PLUGIN_DIR . 'admin/class-gmp-admin-detail.php';
            require_once GMP_PLUGIN_DIR . 'includes/class-gmp-product-meta.php';
            require_once GMP_PLUGIN_DIR . 'admin/class-gmp-settings-page.php';

            GMP_Admin_Assets::init();
            GMP_Admin_Menu::init();
            GMP_Product_Meta::init();
            GMP_Settings_Page::init();
        }

        // Frontend
        if ( ! is_admin() ) {
            require_once GMP_PLUGIN_DIR . 'frontend/class-gmp-myaccount.php';
            GMP_MyAccount::init();
        }

        // Common Hooks
        GMP_Checkout_Hook::init();
    }

    public static function on_activate() {
        // ✅ Create tables
        GMP_DB::create_tables();
if ( isset($_GET['show_rules']) ) {
        global $wp_rewrite;
        echo '<pre>';
        print_r( $wp_rewrite->wp_rewrite_rules() );
        echo '</pre>';
        exit;
    }
         add_rewrite_endpoint( 'gmp-cycles', EP_ROOT | EP_PAGES | EP_PERMALINK );

    // Force one-time flush
    flush_rewrite_rules();

    // Set option to skip next time
    update_option( 'gmp_rewrite_flushed', true );
    }
}
