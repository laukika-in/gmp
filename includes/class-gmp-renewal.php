<?php

class GMP_Renewal {

    public static function init() {
        add_action('woocommerce_checkout_order_processed', [__CLASS__, 'record_subscription_renewal']);
    }

    /**
     * Records each GMP plan renewal (i.e., each order placed for a GMP product)
     * Tracks it under user meta keyed by variation ID or product ID.
     */
    public static function record_subscription_renewal($order_id) {
        $order = wc_get_order($order_id);
        if (!$order || !$order->get_items()) return;

        $user_id = $order->get_user_id();
        if (!$user_id) return;

        foreach ($order->get_items() as $item) {
            $product_id   = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $product      = wc_get_product($variation_id ?: $product_id);

            if (!$product || !has_term('gmp-plan', 'product_cat', $product_id)) {
                continue;
            }

            $key = $variation_id ?: $product_id;
            $meta_key = "gmp_subscription_history_{$key}";

            $quantity   = $item->get_quantity();
            $unit_price = $item->get_total() / max($quantity, 1);
            $history    = get_user_meta($user_id, $meta_key, true) ?: [];

            $history[] = [
                'date'     => current_time('mysql'),
                'amount'   => $unit_price,
                'order_id' => $order_id,
            ];

            update_user_meta($user_id, $meta_key, $history);
        }
    }

    /**
     * Gets the total renewal count for a given user and product/variation.
     */
    public static function get_total_renewals($user_id, $product_or_variation_id) {
        $history = get_user_meta($user_id, "gmp_subscription_history_{$product_or_variation_id}", true);
        return is_array($history) ? count($history) : 0;
    }

    /**
     * Gets full renewal history (date, amount, order_id)
     */
    public static function get_renewal_history($user_id, $product_or_variation_id) {
        return get_user_meta($user_id, "gmp_subscription_history_{$product_or_variation_id}", true) ?: [];
    }

    public static function get_active_subscription_for_user($user_id, $variation_id) {
        if (!function_exists('wcs_get_users_subscriptions')) return false;

        $subscriptions = wcs_get_users_subscriptions($user_id);
        foreach ($subscriptions as $sub) {
            foreach ($sub->get_items() as $item) {
                if ($item->get_variation_id() === $variation_id && $sub->has_status(['active', 'on-hold'])) {
                    return $sub;
                }
            }
        }
        return false;
    }
}

// 🟢 This goes OUTSIDE the class:
add_filter('woocommerce_add_to_cart_validation', function ($passed, $product_id, $quantity, $variation_id = 0, $variation = [], $cart_item_data = []) {
    if (!is_user_logged_in()) return $passed;

    $user_id = get_current_user_id();
    $check_id = $variation_id ?: $product_id;

    // Only target subscription variations
    $product = wc_get_product($check_id);
    if (!$product || !$product->is_type('subscription_variation')) return $passed;

    // Check active subscription
    $existing_sub = GMP_Renewal::get_active_subscription_for_user($user_id, $check_id);
    if ($existing_sub) {
        wc_add_notice(__('You already have an active subscription for this EMI plan. Please do not repurchase.'), 'error');
        return false;
    }

    return $passed;
}, 10, 6);
