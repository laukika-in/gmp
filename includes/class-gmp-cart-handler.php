<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Cart_Handler {

    public static function init() {
        add_action( 'template_redirect', [ __CLASS__, 'maybe_clear_cart_for_gmp_checkout' ] );
    }

    public static function maybe_clear_cart_for_gmp_checkout() {
        // Only act if:
        // 1. ?add-to-cart is present
        // 2. We're on the checkout page
        // 3. Referrer was the EMI detail page (with ?view= in URL)
        if (
            isset( $_GET['add-to-cart'] ) &&
            is_checkout() &&
            isset( $_SERVER['HTTP_REFERER'] ) &&
            strpos( $_SERVER['HTTP_REFERER'], 'view=' ) !== false &&
            strpos( $_SERVER['HTTP_REFERER'], 'gold-money-plan' ) !== false
        ) {
            WC()->cart->empty_cart();
        }
    }
}
