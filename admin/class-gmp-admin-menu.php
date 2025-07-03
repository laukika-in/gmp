<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Admin_Menu {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
    }

    public static function register_menu() {
        add_submenu_page(
            'woocommerce',
            'GMP EMI Cycles',
            'GMP EMI Cycles',
            'manage_woocommerce',
            'gmp-cycles',
            [ 'GMP_Admin_List', 'render_page' ]
        );

        add_submenu_page(
            null, // hidden from sidebar
            'Cycle Detail',
            'Cycle Detail',
            'manage_woocommerce',
            'gmp-cycle-detail',
            [ 'GMP_Admin_Detail', 'render_page' ]
        );
    }
}
