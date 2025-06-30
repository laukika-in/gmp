<?php
// File: includes/class-gmp-renewal.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GMP_Renewal {

    /**
     * Hook into WooCommerce so we record every GMP order
     * and block duplicate subscriptions in cart.
     */
    public static function init() {
        // Record every checkout order that contains a GMP PLAN item
        add_action( 'woocommerce_checkout_order_processed',
            [ __CLASS__, 'record_subscription_renewal' ], 10, 1
        );

        // Prevent adding a duplicate active subscription to cart
        add_filter( 'woocommerce_add_to_cart_validation',
            [ __CLASS__, 'prevent_duplicate_subscription' ], 20, 6
        );
    }

    /**
     * Record each order as a “renewal” in user meta history.
     */
    public static function record_subscription_renewal( $order_id ) {
        $order   = wc_get_order( $order_id );
        $user_id = $order ? $order->get_user_id() : 0;
        if ( ! $user_id ) {
            return;
        }

        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            // only GMP‐PLAN products
            if ( ! has_term( 'gmp-plan', 'product_cat', $product_id ) ) {
                continue;
            }

            $key      = $product_id;
            $meta_key = "gmp_subscription_history_{$key}";

            $qty        = max( 1, $item->get_quantity() );
            $unit_price = $item->get_total() / $qty;
            $history    = get_user_meta( $user_id, $meta_key, true ) ?: [];

            $history[] = [
                'date'     => current_time( 'Y-m-d H:i:s' ),
                'amount'   => $unit_price,
                'order_id' => $order_id,
            ];

            update_user_meta( $user_id, $meta_key, $history );
        }
    }

    /**
     * Return how many times the user has paid this plan (for interest calcs).
     */
    public static function get_total_renewals( $user_id, $product_id ) {
        $history = get_user_meta( $user_id, "gmp_subscription_history_{$product_id}", true );
        return is_array( $history ) ? count( $history ) : 0;
    }

    /**
     * Block adding to cart if an active subscription for this variation exists.
     */
    public static function prevent_duplicate_subscription( $passed, $product_id, $quantity, $variation_id = 0, $variation = [], $cart_item_data = [] ) {
        if ( ! is_user_logged_in() ) {
            return $passed;
        }

        // Only block actual subscription variations
        $check_id = $variation_id ?: $product_id;
        if ( ! function_exists( 'wcs_get_users_subscriptions' ) ) {
            return $passed;
        }

        // skip internal renewal/resubscribe flags
        if ( ! empty( $_GET['resubscribe'] ) || ! empty( $_REQUEST['subscription_reactivate'] ) ) {
            return $passed;
        }

        // find any active subs for this variation
        $subs = wcs_get_users_subscriptions( get_current_user_id() );
        foreach ( $subs as $sub ) {
            foreach ( $sub->get_items() as $item ) {
                if ( $item->get_variation_id() === $check_id && $sub->has_status( [ 'active', 'on-hold' ] ) ) {
                    wc_add_notice( __( 'You already have an active subscription for this EMI plan. Please do not repurchase.', 'gmp' ), 'error' );
                    return false;
                }
            }
        }

        return $passed;
    }
}

// initialize
GMP_Renewal::init();
