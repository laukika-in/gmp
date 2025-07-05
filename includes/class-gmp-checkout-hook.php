<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Checkout_Hook {

    public static function init() {
        // Step 1: Run validation before order is placed
        add_action( 'woocommerce_check_cart_items', [ __CLASS__, 'validate_before_checkout' ] );

        // Step 2: Process EMI tracking after order
        add_action( 'woocommerce_checkout_order_processed', [ __CLASS__, 'maybe_track_emi_purchase' ], 20, 1 );

        add_filter( 'woocommerce_add_to_cart_validation', [ __CLASS__, 'block_add_to_cart_if_hold' ], 10, 6 );

    }

    // ✅ Block checkout if any GMP cycle is on hold
    public static function validate_before_checkout() {
        if ( is_admin() || ! is_user_logged_in() ) return;

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product_id = $cart_item['product_id'];

            if ( ! GMP_Product_Helper::is_gmp_product( $product_id ) ) continue;

            $user_id = get_current_user_id();
            global $wpdb;
            $has_hold = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}gmp_cycles 
                 WHERE user_id = %d AND status = 'hold'", 
                $user_id
            ) );

            if ( $has_hold ) {
                wc_add_notice( __( "You cannot purchase a new Gold EMI Plan while one of your plans is on hold. Please reach out our support to resume or cancel it.", "gmp" ), 'error' );

// Redirect back to cart to show the message clearly
if ( is_checkout() ) {
    wp_safe_redirect( wc_get_cart_url() );
    exit;
}
break;

            }
        }
    }

    // ✅ After order placed, create cycle or mark payment
    public static function maybe_track_emi_purchase( $order_id ) {
        $order = wc_get_order( $order_id );
        $user_id = $order->get_user_id();

        foreach ( $order->get_items() as $item ) {
            $product_id   = $item->get_product_id();
            $variation_id = $item->get_variation_id();

            if ( ! GMP_Product_Helper::is_gmp_product( $product_id ) ) {
                continue;
            }

            global $wpdb;

            // 🔒 Prevent new cycle creation if any cycle is on hold
            $has_hold = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}gmp_cycles 
                 WHERE user_id = %d AND status = 'hold'", 
                $user_id
            ) );

            if ( $has_hold ) {
                continue; // Do not create or update
            }

            // ✅ Check if active cycle already exists
            $cycle = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}gmp_cycles
                 WHERE user_id = %d AND variation_id = %d AND status = 'active'
                 ORDER BY id DESC LIMIT 1",
                $user_id, $variation_id
            ) );

            if ( $cycle ) {
                GMP_EMI_Cycle::mark_installment_paid( $cycle->id, $order_id );
            } else {
                // ✅ Fetch interest settings
                $settings = GMP_Settings::get_interest_for_product( $product_id );

                $cycle_id = GMP_EMI_Cycle::create_cycle(
                    $user_id,
                    $product_id,
                    $variation_id,
                    $settings['lock_months'],
                    $settings['extension_months'],
                    $settings['base_interest'],
                    $settings['extension_interest'] ?? []
                );

                if ( $cycle_id ) {
                    GMP_EMI_Cycle::mark_installment_paid( $cycle_id, $order_id );
                }
            }
        }
    }
    public static function block_add_to_cart_if_hold( $passed, $product_id, $quantity, $variation_id = 0, $variation = [], $cart_item_data = [] ) {
    if ( ! is_user_logged_in() ) return $passed;

    $variation_id = $variation_id ?: $product_id;

    if ( ! GMP_Product_Helper::is_gmp_product( $product_id ) ) {
        return $passed;
    }

    $user_id = get_current_user_id();
    global $wpdb;

    $cycle = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}gmp_cycles
         WHERE user_id = %d AND variation_id = %d AND status = 'hold'
         ORDER BY id DESC LIMIT 1",
        $user_id, $variation_id
    ) );

    if ( $cycle ) {
        wc_add_notice( __( "You cannot repurchase this plan while it’s on hold. Please resume or close the plan first.", "gmp" ), 'error' );
        return false;
    }

    return $passed;
}

}
