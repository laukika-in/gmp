<?php
// File: includes/class-gmp-renewal.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GMP_Renewal {

    public static function init() {
        // 1) Log every GMP plan order as a renewal
        add_action( 'woocommerce_checkout_order_processed',
            [ __CLASS__, 'record_subscription_renewal' ], 10, 1
        );

        // 2) If this order was an extension, mark that too
        add_action( 'woocommerce_checkout_order_processed',
            [ __CLASS__, 'record_extension_payment' ], 20, 1
        );

        // 3) Block add-to-cart of an EMI plan if they already have an active subscription for it
        add_filter( 'woocommerce_add_to_cart_validation',
            [ __CLASS__, 'prevent_duplicate_subscription' ], 20, 6
        );

        // 4) Insert “Pay Extension EMI” button into both
        //    a) the subscriptions table
           add_filter( 'woocommerce_my_account_my_subscriptions_actions', [ __CLASS__, 'add_extension_button' ], 10, 2 );

        //    b) the single subscription view
            add_filter( 'wcs_view_subscription_actions',               [ __CLASS__, 'add_extension_button' ], 10, 2 );

    }

    /**
     * 1) Record each GMP plan item in user meta.
     */
    public static function record_subscription_renewal( $order_id ) {
        $order   = wc_get_order( $order_id );
        $user_id = $order ? $order->get_user_id() : 0;
        if ( ! $order || ! $user_id ) {
            return;
        }

        foreach ( $order->get_items() as $item ) {
            $prod_id = $item->get_product_id();
            if ( ! has_term( 'gmp-plan', 'product_cat', $prod_id ) ) {
                continue;
            }
            $var_id  = $item->get_variation_id() ?: $prod_id;
            $meta    = "gmp_subscription_history_{$var_id}";
            $history = get_user_meta( $user_id, $meta, true ) ?: [];

            $unit = $item->get_total() / max(1, $item->get_quantity());
            $history[] = [
                'date'     => current_time( 'mysql' ),
                'amount'   => $unit,
                'order_id' => $order_id,
            ];

            update_user_meta( $user_id, $meta, $history );
        }
    }

    /**
     * 2) If this checkout URL had ?gmp_extension=SUB_ID, count it as an extension payment.
     */
    public static function record_extension_payment( $order_id ) {
        if ( empty( $_GET['gmp_extension'] ) ) {
            return;
        }
        $sub_id = intval( $_GET['gmp_extension'] );
        if ( ! class_exists( 'WC_Subscription' ) ) {
            return;
        }
        $sub = wcs_get_subscription( $sub_id );
        if ( ! $sub ) {
            return;
        }
        $uid  = $sub->get_user_id();
        $used = intval( get_user_meta( $uid, "_gmp_extension_used_{$sub_id}", true ) );
        update_user_meta( $uid, "_gmp_extension_used_{$sub_id}", $used + 1 );
    }

    /**
     * 3) Don’t let them add a variation if they already have an active/on-hold sub for it.
     */
    public static function prevent_duplicate_subscription( $passed, $product_id, $quantity, $variation_id = 0, $variation = [], $cart_item_data = [] ) {
        if ( ! is_user_logged_in() ) {
            return $passed;
        }
        $check_id = $variation_id ?: $product_id;
        $prod     = wc_get_product( $check_id );
        if ( ! $prod || ! $prod->is_type( 'subscription_variation' ) ) {
            return $passed;
        }
        // allow true renewals/resubscribes
        if ( ! empty( $_GET['resubscribe'] )
          || ! empty( $_REQUEST['subscription_reactivate'] )
          || ! empty( $cart_item_data['subscription_renewal'] )
          || ! empty( $cart_item_data['subscription_resubscribe'] ) ) {
            return $passed;
        }
        $existing = self::get_active_subscription_for_user( get_current_user_id(), $check_id );
        if ( $existing ) {
            wc_add_notice( __( 'You already have an active subscription for this EMI plan. Please do not repurchase.', 'gmp' ), 'error' );
            return false;
        }
        return $passed;
    }

    /**
     * Helper: find a user’s active or on-hold subscription for a given variation.
     */
    public static function get_active_subscription_for_user( $user_id, $variation_id ) {
        if ( ! function_exists( 'wcs_get_users_subscriptions' ) ) {
            return false;
        }
        $subs = wcs_get_users_subscriptions( $user_id );
        foreach ( $subs as $sub ) {
            if ( in_array( $sub->get_status(), [ 'active', 'on-hold' ], true ) ) {
                foreach ( $sub->get_items() as $item ) {
                    if ( $item->get_variation_id() === $variation_id ) {
                        return $sub;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Utility: how many renewals have they already logged?
     */
    public static function get_total_renewals( $user_id, $variation_id ) {
        $hist = get_user_meta( $user_id, "gmp_subscription_history_{$variation_id}", true );
        return is_array( $hist ) ? count( $hist ) : 0;
    }

    /**
     * 4) Add an “Extension EMI” button when they’ve exhausted base EMIs
     *    but still have extension slots left.
     */
 
public static function add_extension_button( $actions, $subscription ) {
    if ( ! $subscription instanceof WC_Subscription ) {
        return $actions;
    }

    $user_id      = $subscription->get_user_id();
    $sub_id       = $subscription->get_id();
    $items        = $subscription->get_items();
    $first_item   = reset( $items );
    $variation_id = $first_item ? $first_item->get_variation_id() : 0;

    // extension settings on the product
    $enabled   = get_post_meta( $variation_id, '_gmp_enable_extension', true ) === 'yes';
    $max_ext   = intval( get_post_meta( $variation_id, '_gmp_extension_months', true ) );
    $used      = intval( get_user_meta( $user_id, "_gmp_extension_used_{$sub_id}", true ) );
    $paid      = self::get_total_renewals( $user_id, $variation_id );

    // how many terms they originally subscribed for
    $base_length = intval( $subscription->get_meta( '_subscription_length', true ) );

    // Must be extension-eligible (active or expired), base term done, and slots left
    if (
        $enabled
        && in_array( $subscription->get_status(), [ 'active', 'expired' ], true )
        && $paid >= $base_length
        && $used < $max_ext
    ) {
        // build core resubscribe URL + our flag
        $resub_url = wcs_get_users_resubscribe_url( $subscription );
        $resub_url = add_query_arg( 'gmp_extension', $sub_id, $resub_url );

        $actions['gmp_extension'] = [
            'url'  => $resub_url,
            'name' => __( 'Renew Now', 'gold-money-plan' ),
        ];
    }

    return $actions;
}

}

// finally hook it up
GMP_Renewal::init();
