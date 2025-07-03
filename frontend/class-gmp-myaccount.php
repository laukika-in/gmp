<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_MyAccount {

    public static function init() {
        // Register endpoint after WordPress loads
        add_action( 'init', [ __CLASS__, 'add_account_endpoint' ] );

        add_filter( 'woocommerce_account_menu_items', [ __CLASS__, 'add_menu_item' ] );
        add_action( 'woocommerce_account_gmp-cycles_endpoint', [ __CLASS__, 'render_cycle_list' ] );

        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    public static function add_account_endpoint() {
           add_rewrite_endpoint( 'gmp-cycles', EP_ROOT | EP_PAGES | EP_PERMALINK );

    }

    public static function add_menu_item( $items ) {
        $items['gmp-cycles'] = 'My EMI Cycles';
        return $items;
    }

    public static function render_cycle_list() {
        echo '<div style="padding:1em;background:#fff3cd;color:#856404;border:1px solid #ffeeba;">[Debug] Rendered gmp-cycles endpoint</div>';
        include GMP_PLUGIN_DIR . 'views/front-cycle-list.php';
    }

    public static function enqueue_assets() {
        if ( is_account_page() && is_user_logged_in() && is_wc_endpoint_url( 'gmp-cycles' ) ) {
            wp_enqueue_style( 'gmp-style', GMP_PLUGIN_URL . 'assets/css/gmp-style.css', [], GMP_PLUGIN_VERSION );
            wp_enqueue_script( 'gmp-style', GMP_PLUGIN_URL . 'assets/js/gmp-style.js', [ 'jquery' ], GMP_PLUGIN_VERSION, true );
        }
    }
    
}
