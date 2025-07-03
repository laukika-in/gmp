<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Pay_Now_Handler {

    public static function init() {
        add_action( 'template_redirect', [ __CLASS__, 'handle_redirect' ] );
    }

    public static function handle_redirect() {
        if ( ! isset( $_GET['gmp_pay_now'] ) ) return;

        $variation_id = absint( $_GET['gmp_pay_now'] );
        $product = wc_get_product( $variation_id );

        if ( ! $product || ! $product->is_type( 'variation' ) ) {
            wp_die( 'Invalid product variation.' );
        }

        // Empty the cart
        WC()->cart->empty_cart();

        // Add variation to cart
        WC()->cart->add_to_cart( $product->get_parent_id(), 1, $variation_id, $product->get_variation_attributes() );

        // Redirect to checkout
        wp_safe_redirect( wc_get_checkout_url() );
        exit;
    }
}
