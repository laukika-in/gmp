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
             add_filter( 'wcs_my_subscriptions_actions',    [ __CLASS__, 'add_list_extension_button' ], 20, 2 );
        // add the “Extend” button on the single-subscription View page
        add_filter( 'wcs_view_subscription_actions',   [ __CLASS__, 'add_detail_extension_button' ], 20, 2 );
        // after checkout, record that this was an extension payment
        add_action( 'woocommerce_checkout_order_processed', [ __CLASS__, 'maybe_record_extension_payment' ], 20, 1 );
   
    }

    /**
     * Called when a checkout order is placed.
     */
    public static function record_subscription_renewal( $order_id ) {
        $order   = wc_get_order( $order_id );
        $user_id = $order ? $order->get_user_id() : 0;
        if ( ! $user_id ) {
            return;
        }

        foreach ( $order->get_items() as $item ) {
            $pid = $item->get_product_id();
            // only GMP Plan products
            if ( ! has_term( 'gmp-plan', 'product_cat', $pid ) ) {
                continue;
            }

            $meta_key = "gmp_subscription_history_{$pid}";
            $qty      = max( 1, $item->get_quantity() );
            $unit     = $item->get_total() / $qty;
            $hist     = get_user_meta( $user_id, $meta_key, true ) ?: [];

            $hist[] = [
                'date'     => current_time( 'Y-m-d H:i:s' ),
                'amount'   => $unit,
                'order_id' => $order_id,
            ];

            update_user_meta( $user_id, $meta_key, $hist );
        }
    }

    /**
     * Helper for interest logic later.
     */
    public static function get_total_renewals( $user_id, $pid ) {
        $hist = get_user_meta( $user_id, "gmp_subscription_history_{$pid}", true );
        return is_array( $hist ) ? count( $hist ) : 0;
    }

    /**
     * Prevent adding a new purchase of the same variation
     * when there’s already an Active/On-Hold subscription.
     */
/**
 * Prevent adding a brand-new subscription of the same plan to cart,
 * but allow true renewal/resubscribe flows.
 */
public static function prevent_duplicate_subscription( $passed, $product_id, $quantity, $variation_id = 0, $variation = [], $cart_item_data = [] ) {
    // must be logged in
    if ( ! is_user_logged_in() ) {
        return $passed;
    }

    // only for GMP subscription variations
    $check_id = $variation_id ?: $product_id;
    $product  = wc_get_product( $check_id );
    if ( ! $product || ! $product->is_type( 'subscription_variation' ) ) {
        return $passed;
    }

    // 1) If WooCommerce Subscriptions is driving a renewal or resubscribe, let it pass
    if ( ! empty( $cart_item_data['subscription_renewal'] )
      || ! empty( $cart_item_data['subscription_resubscribe'] )
      || ! empty( $_GET['resubscribe'] )
      || ! empty( $_REQUEST['subscription_reactivate'] )
      || ( function_exists( 'wcs_cart_is_renewal' ) && wcs_cart_is_renewal() )
    ) {
        return $passed;
    }

    // 2) Now block if there’s still an active/on-hold subscription for this variation
    if ( function_exists( 'wcs_get_users_subscriptions' ) ) {
        $user_id = get_current_user_id();
        $subs    = wcs_get_users_subscriptions( $user_id );
        foreach ( $subs as $sub ) {
            if ( $sub->has_status( [ 'active', 'on-hold' ] ) ) {
                foreach ( $sub->get_items() as $item ) {
                    if ( $item->get_variation_id() === $check_id ) {
                        wc_add_notice( __( 'You already have an active subscription for this EMI plan. Please do not repurchase.', 'gmp' ), 'error' );
                        return false;
                    }
                }
            }
        }
    }

    return $passed;
}
 /**
     * List page: My Account → Subscriptions table
     */
    public static function add_list_extension_button( $actions, $subscription ) {
        return self::maybe_add( $actions, $subscription );
    }

    /**
     * Detail page: My Account → Subscriptions → View
     */
    public static function add_detail_extension_button( $actions, $subscription ) {
        return self::maybe_add( $actions, $subscription );
    }

    /**
     * Shared logic to append our “Pay Extension EMI” if eligible.
     */
    protected static function maybe_add( $actions, $subscription ) {
        // Only expired subscriptions can be extended
        if ( ! $subscription->has_status( 'expired' ) ) {
            return $actions;
        }

        $user_id = $subscription->get_user_id();
        foreach ( $subscription->get_items() as $item ) {
            // support variations too:
            $product_id = $item->get_variation_id() ?: $item->get_product_id();

            // only for our GMP plans
            if ( ! has_term( 'gmp-plan', 'product_cat', $product_id ) ) {
                continue;
            }

            // product-level meta:
            $enabled = get_post_meta( $product_id, '_gmp_enable_extension', true ) === 'yes';
            $max     = intval( get_post_meta( $product_id, '_gmp_extension_months', true ) );
            // how many extensions already used on this subscription?
            $used    = intval( get_user_meta( $user_id, "_gmp_extension_used_{$subscription->get_id()}", true ) );

            if ( $enabled && $used < $max ) {
                // get the built-in resubscribe URL
                $url = wcs_get_users_resubscribe_url( $subscription );
                if ( $url ) {
                    // tack on our flag so we know this is an extension
                    $url = add_query_arg( 'gmp_extension', $subscription->get_id(), $url );

                    $actions['gmp_extend'] = [
                        'name' => __( 'Pay Extension EMI', 'gold-money-plan' ),
                        'url'  => esc_url( $url ),
                    ];
                }
            }
        }

        return $actions;
    }

    /**
     * After checkout, if we see our flag, count it.
     */
    public static function maybe_record_extension_payment( $order_id ) {
        if ( empty( $_GET['gmp_extension'] ) ) {
            return;
        }
        $sub_id  = intval( $_GET['gmp_extension'] );
        $order   = wc_get_order( $order_id );
        $user_id = $order->get_user_id() ?: get_current_user_id();
        $meta    = "_gmp_extension_used_{$sub_id}";
        $used    = intval( get_user_meta( $user_id, $meta, true ) );
        update_user_meta( $user_id, $meta, $used + 1 );
    }
}

// initialize it exactly once
GMP_Renewal::init();
