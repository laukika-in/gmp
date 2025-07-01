<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GMP_Renewal {

	public static function init() {
		// 1) Record every order for GMP Plans
		add_action( 'woocommerce_checkout_order_processed', [ __CLASS__, 'record_subscription_renewal' ], 10, 1 );

		// 2) If it was an extension payment, record it (after the base recording)
		add_action( 'woocommerce_checkout_order_processed', [ __CLASS__, 'record_extension_payment' ], 20, 1 );

		// 3) Prevent adding a duplicate active subscription to the cart
		add_filter( 'woocommerce_add_to_cart_validation', [ __CLASS__, 'prevent_duplicate_subscription' ], 20, 6 ); 
		    add_filter( 'wcs_view_subscription_actions', [ __CLASS__, 'maybe_add_extension_renew_action' ], 20, 2 );


	}

 public static function maybe_add_extension_renew_action( $actions, $subscription ) {
    if ( ! is_user_logged_in() ) {
      return $actions;
    }

    // Grab the first (and only) line item
    $items = $subscription->get_items();
    if ( empty( $items ) ) {
      return $actions;
    }
    $item = reset( $items );

    // Determine variation + product
    $variation_id = $item->get_variation_id() ?: $item->get_product_id();
    $product      = wc_get_product( $variation_id );
    if ( ! $product || ! $product->is_type( 'subscription_variation' ) ) {
      return $actions;
    }

    // 1) Lock-period instalment count
    $lock_count = intval( $product->get_meta( '_subscription_length' ) ); 
    if ( $lock_count <= 0 ) {
      return $actions;
    }

    // 2) Extension instalments allowed
    $ext_count = intval( get_post_meta( $variation_id, '_gmp_extension_months', true ) );
    if ( $ext_count <= 0 ) {
      return $actions;
    }

    // 3) How many instalments the user has already paid?
    $paid_count = self::get_total_renewals( get_current_user_id(), $variation_id );

    // If they've finished the lock-period but not yet used all extension instalments,
    // re-add the "Renew Now" button pointing at the early-renewal URL.
    if ( $paid_count >= $lock_count && $paid_count < ( $lock_count + $ext_count ) ) {
      $actions['subscription_renewal_early'] = [
        'url'  => wcs_get_early_renewal_url( $subscription ),
        'name' => __( 'Renew now', 'gold-money-plan' ),
      ];
    }

    return $actions;
  }


	public static function record_subscription_renewal( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->get_items() ) {
			return;
		}
		$user_id = $order->get_user_id();
		if ( ! $user_id ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			if ( ! has_term( 'gmp-plan', 'product_cat', $product_id ) ) {
				continue;
			}

			$variation_id = $item->get_variation_id() ?: $product_id;
			$meta_key     = "gmp_subscription_history_{$variation_id}";
			$history      = get_user_meta( $user_id, $meta_key, true ) ?: [];

			$unit_price = $item->get_total() / max( 1, $item->get_quantity() );
			$history[]  = [
				'date'     => current_time( 'mysql' ),
				'amount'   => $unit_price,
				'order_id' => $order_id,
			];

			update_user_meta( $user_id, $meta_key, $history );
		}
	}

	public static function record_extension_payment( $order_id ) {
		if ( empty( $_GET['gmp_extension'] ) ) {
			return;
		}
		$sub_id = intval( $_GET['gmp_extension'] );
		$subscription = wcs_get_subscription( $sub_id );
		if ( ! $subscription ) {
			return;
		}

		$user_id = $subscription->get_user_id();
		$used    = intval( get_user_meta( $user_id, "_gmp_extension_used_{$sub_id}", true ) );
		update_user_meta( $user_id, "_gmp_extension_used_{$sub_id}", $used + 1 );
	}

	public static function prevent_duplicate_subscription( $passed, $product_id, $quantity, $variation_id = 0, $variation_data = [], $cart_item_data = [] ) {
		if ( ! is_user_logged_in() ) {
			return $passed;
		}

		$check_id = $variation_id ?: $product_id;
		$product  = wc_get_product( $check_id );
		if ( ! $product || ! $product->is_type( 'subscription_variation' ) ) {
			return $passed;
		}

		if (
			! empty( $_GET['resubscribe'] ) ||
			! empty( $_REQUEST['subscription_reactivate'] ) ||
			! empty( $cart_item_data['subscription_renewal'] ) ||
			! empty( $cart_item_data['subscription_resubscribe'] )
		) {
			return $passed;
		}

		$existing = self::get_active_subscription_for_user( get_current_user_id(), $check_id );
		if ( $existing ) {
			wc_add_notice( __( 'You already have an active subscription for this EMI plan. Please do not repurchase.', 'gold-money-plan' ), 'error' );
			return false;
		}

		return $passed;
	}

	public static function get_total_renewals( $user_id, $variation_id ) {
		$history = get_user_meta( $user_id, "gmp_subscription_history_{$variation_id}", true );
		return is_array( $history ) ? count( $history ) : 0;
	}

	private static function get_active_subscription_for_user( $user_id, $variation_id ) {
		if ( ! function_exists( 'wcs_get_users_subscriptions' ) ) {
			return false;
		}
		$subs = wcs_get_users_subscriptions( $user_id );
		foreach ( $subs as $sub ) {
			if ( ! in_array( $sub->get_status(), [ 'active', 'on-hold' ], true ) ) {
				continue;
			}
			foreach ( $sub->get_items() as $item ) {
				if ( $item->get_variation_id() === $variation_id ) {
					return $sub;
				}
			}
		}
		return false;
	}
}

GMP_Renewal::init();
