<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Init {

    public static function init() {
        self::load_dependencies();
        self::init_hooks();
    }

    private static function load_dependencies() {
        require_once GMP_EMI_PATH . 'includes/admin/class-gmp-admin-menu.php';
        require_once GMP_EMI_PATH . 'includes/admin/class-gmp-admin-screens.php';
        require_once GMP_EMI_PATH . 'includes/class-gmp-checkout-handler.php';
        require_once GMP_EMI_PATH . 'includes/class-gmp-interest-settings.php';
        require_once GMP_EMI_PATH . 'includes/class-gmp-schedule-tracker.php';
        require_once GMP_EMI_PATH . 'includes/class-gmp-customer-dashboard.php';
    }

    private static function init_hooks() {
        add_action( 'init', ['GMP_Schedule_Tracker', 'register_post_type'] );
        add_action( 'woocommerce_order_status_completed', ['GMP_Checkout_Handler', 'handle_order_payment'] );
    }

}
