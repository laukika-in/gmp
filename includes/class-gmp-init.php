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
            require_once GMP_PLUGIN_DIR . 'admin/class-gmp-admin-actions.php';


            GMP_Admin_Assets::init();
            GMP_Admin_Menu::init();
            GMP_Product_Meta::init();
            GMP_Settings_Page::init();
            GMP_Admin_Actions::init();

        }

        // Frontend
        if ( ! is_admin() ) {
            require_once GMP_PLUGIN_DIR . 'frontend/class-gmp-myaccount.php';
            require_once GMP_PLUGIN_DIR . 'includes/class-gmp-cart-handler.php';
            require_once GMP_PLUGIN_DIR . 'includes/class-gmp-pay-now-handler.php';


            GMP_MyAccount::init();
            GMP_Cart_Handler::init();
            GMP_Pay_Now_Handler::init();

        }

        // Common Hooks
        GMP_Checkout_Hook::init();
    }

    public static function on_activate() {
        // ✅ Create tables
        GMP_DB::create_tables(); 
    // Force one-time flush
    flush_rewrite_rules();
 
    }
}
