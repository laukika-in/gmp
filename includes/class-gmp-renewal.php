<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GMP_Renewal {

    /**
     * Single init to hook WooCommerce.
     */
    public static function init() {
        // Record every order for GMP plans
        add_action( 'woocommerce_checkout_order_processed',
            [ __CLASS__, 'record_subscription_renewal' ], 10, 1
        );

        // Block duplicate active subscriptions in cart
        add_filter( 'woocommerce_add_to_cart_validation',
            [ __CLASS__, 'prevent_duplicate_subscription' ], 20, 6
        );

        // 2) After payment, if it was an extension, record it
        add_action( 'woocommerce_checkout_order_processed', [ __CLASS__, 'record_extension_payment' ], 20, 1 );

        // 3) Add “Pay Extension EMI” button on the My Account subscriptions list
        add_filter( 'woocommerce_my_account_my_subscriptions_actions', [ __CLASS__, 'add_extension_button' ], 10, 2 );

        // 4) And also on the single-subscription view
        add_filter( 'wcs_view_subscription_actions', [ __CLASS__, 'add_extension_button' ], 10, 2 );
   
    }
/**
     * Record each GMP‐Plan item in the order as a renewal in user meta.
     */
    public static function record_subscription_renewal( $order_id ) {
        $order   = wc_get_order( $order_id );
        $user_id = $order ? $order->get_user_id() : 0;
        if ( ! $user_id ) return;

        foreach ( $order->get_items() as $item ) {
            $prod_id = $item->get_variation_id() ?: $item->get_product_id();
            if ( ! has_term( 'gmp-plan', 'product_cat', $prod_id ) ) continue;

            $history   = get_user_meta( $user_id, "gmp_subscription_history_{$prod_id}", true ) ?: [];
            $unit      = floatval( $item->get_total() ) / max( 1, $item->get_quantity() );
            $history[] = [
                'date'     => current_time( 'mysql' ),
                'amount'   => $unit,
                'order_id' => $order_id,
            ];
            update_user_meta( $user_id, "gmp_subscription_history_{$prod_id}", $history );
        }
    }

    /**
     * If ?gmp_extension={sub_id} was in the checkout URL, this was an extension payment.
     * Record one more “used” extension slot.
     */
    public static function record_extension_payment( $order_id ) {
        if ( empty( $_GET['gmp_extension'] ) ) {
            return;
        }
        $sub_id      = intval( $_GET['gmp_extension'] );
        $subscription = wcs_get_subscription( $sub_id );
        if ( ! $subscription ) return;

        $user_id = $subscription->get_user_id();
        $used    = intval( get_user_meta( $user_id, "_gmp_extension_used_{$sub_id}", true ) );
        update_user_meta( $user_id, "_gmp_extension_used_{$sub_id}", $used + 1 );
    }

    /**
     * Show a “Pay Extension EMI” button if:
     *  - they’ve already paid all the base EMIs (get_payment_count >= lock_period)
     *  - AND they still have unused extension slots.
     */
    public static function add_extension_button( $actions, $subscription ) {
        if ( ! is_a( $subscription, 'WC_Subscription' ) ) {
            return $actions;
        }

        $user_id        = get_current_user_id();
        $paid_count     = $subscription->get_payment_count();
        $subscription_id = $subscription->get_id();

        // look at any one GMP item in the sub
        foreach ( $subscription->get_items() as $item ) {
            $prod_id    = $item->get_variation_id() ?: $item->get_product_id();
            if ( ! has_term( 'gmp-plan', 'product_cat', $prod_id ) ) {
                continue;
            }

            $lock       = intval( get_post_meta( $prod_id, '_gmp_lock_period', true ) );
            $ext_max    = intval( get_post_meta( $prod_id, '_gmp_extension_months', true ) );
            $ext_used   = intval( get_user_meta( $user_id, "_gmp_extension_used_{$subscription_id}", true ) );

            if ( $paid_count >= $lock && $ext_used < $ext_max ) {
                $actions['gmp_extend'] = [
                    'url'  => add_query_arg( 'gmp_extension', $subscription_id, wc_get_checkout_url() ),
                    'name' => __( 'Pay Extension EMI', 'gold-money-plan' ),
                ];
            }

            // we only need one button
            break;
        }

        return $actions;
    }

    /**
     * Utility: How many base‐EMI renewals have they done?
     */
    public static function get_total_renewals( $user_id, $product_id ) {
        $history = get_user_meta( $user_id, "gmp_subscription_history_{$product_id}", true );
        return is_array( $history ) ? count( $history ) : 0;
    }
}

// initialize it exactly once
GMP_Renewal::init();
