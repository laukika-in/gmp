<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GMP_Renewal {

	/**
	 * Hook everything exactly once.
	 */
	public static function init() {
		// 1) Record every order for GMP Plans
		add_action( 'woocommerce_checkout_order_processed', [ __CLASS__, 'record_subscription_renewal' ], 10, 1 );

		// 2) If it was an extension payment, record it (after the base recording)
		add_action( 'woocommerce_checkout_order_processed', [ __CLASS__, 'record_extension_payment' ], 20, 1 );

		// 3) Prevent adding a duplicate active subscription to the cart
		add_filter( 'woocommerce_add_to_cart_validation', [ __CLASS__, 'prevent_duplicate_subscription' ], 20, 6 );

		// 4) Show “Renew Now” in My Account → Subscriptions list
		add_filter( 'woocommerce_my_account_my_subscriptions_actions', [ __CLASS__, 'add_extension_button' ], 10, 2 );

		// 5) And also on the single-subscription view
		add_filter( 'wcs_view_subscription_actions', [ __CLASS__, 'add_extension_button' ], 10, 2 );
	}

	/**
	 * 1) Record each GMP‐Plan item in the order as a renewal in user meta.
	 */
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
			// only GMP plans
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

	/**
	 * 2) Record an extension payment if the URL had ?gmp_extension={sub_id}
	 */
	public static function record_extension_payment( $order_id ) {
		if ( empty( $_GET['gmp_extension'] ) ) {
			return;
		}
		$sub_id      = intval( $_GET['gmp_extension'] );
		$subscription = wcs_get_subscription( $sub_id );
		if ( ! $subscription ) {
			return;
		}
		$user_id = $subscription->get_user_id();
		$used    = intval( get_user_meta( $user_id, "_gmp_extension_used_{$sub_id}", true ) );
		update_user_meta( $user_id, "_gmp_extension_used_{$sub_id}", $used + 1 );
	}

	/**
	 * 3) Block “Add to cart” if an active subscription already exists
	 */
	public static function prevent_duplicate_subscription( $passed, $product_id, $quantity, $variation_id = 0, $variation_data = [], $cart_item_data = [] ) {
		if ( ! is_user_logged_in() ) {
			return $passed;
		}

		$check_id = $variation_id ?: $product_id;
		$product  = wc_get_product( $check_id );
		if ( ! $product || ! $product->is_type( 'subscription_variation' ) ) {
			return $passed;
		}

		// allow real renewals/resubscribes
		if ( ! empty( $_GET['resubscribe'] )
		  || ! empty( $_REQUEST['subscription_reactivate'] )
		  || ! empty( $cart_item_data['subscription_renewal'] )
		  || ! empty( $cart_item_data['subscription_resubscribe'] )
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

	/**
	 * 4 & 5) Inject “Renew Now” button until extension slots run out.
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

		// product’s extension settings
		$enabled    = get_post_meta( $variation_id, '_gmp_enable_extension', true ) === 'yes';
		$max_ext    = intval( get_post_meta( $variation_id, '_gmp_extension_months', true ) );
		$used       = intval( get_user_meta( $user_id, "_gmp_extension_used_{$sub_id}", true ) );
		$paid       = self::get_total_renewals( $user_id, $variation_id );
		$base_length = intval( $subscription->get_meta( '_subscription_length', true ) );

		// show if they’ve done the base term and still have slots, even if “active”
		if (
			$enabled
			&& in_array( $subscription->get_status(), [ 'active', 'expired' ], true )
			&& $paid >= $base_length
			&& $used < $max_ext
		) {
			$resub_url = wcs_get_users_resubscribe_url( $subscription );
			$resub_url = add_query_arg( 'gmp_extension', $sub_id, $resub_url );

			$actions['gmp_extension'] = [
				'url'  => $resub_url,
				'name' => __( 'Renew Now', 'gold-money-plan' ),
			];
		}

		return $actions;
	}

	/**
	 * How many base EMI payments have they done?
	 */
	public static function get_total_renewals( $user_id, $variation_id ) {
		$history = get_user_meta( $user_id, "gmp_subscription_history_{$variation_id}", true );
		return is_array( $history ) ? count( $history ) : 0;
	}

	/**
	 * Find any active / on-hold subscription for that variation
	 */
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

// fire it up
GMP_Renewal::init();
