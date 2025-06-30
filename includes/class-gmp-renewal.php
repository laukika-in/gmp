<?php
 
class GMP_Renewal {
      public static function init() {
        add_action('woocommerce_checkout_order_processed', [__CLASS__, 'record_subscription_renewal']);
    }
    public static function record($order_id) {
        $order = wc_get_order($order_id);
        if (!$order || !$order->get_items()) return;

        $user_id = $order->get_user_id();
        if (!$user_id) return;

        foreach ($order->get_items() as $item) {
            $product_id   = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $key          = $variation_id ?: $product_id;

            $quantity     = $item->get_quantity();
            $unit_price   = $item->get_total() / max($quantity, 1);

            $meta_key     = "gmp_subscription_history_{$key}";
            $history      = get_user_meta($user_id, $meta_key, true) ?: [];

            $history[] = [
                'date'     => current_time('Y-m-d H:i:s'),
                'amount'   => $unit_price,
                'order_id' => $order_id,
            ];

            update_user_meta($user_id, $meta_key, $history);
        }
    }
}


// Block cart if same variation is already subscribed
add_filter('woocommerce_add_to_cart_validation', function ($passed, $product_id, $quantity, $variation_id = 0, $variation = [], $cart_item_data = []) {
    if (!is_user_logged_in()) return $passed;

    $user_id  = get_current_user_id();
    $check_id = $variation_id ?: $product_id;

    $product = wc_get_product($check_id);
    if (!$product || !$product->is_type('subscription_variation')) return $passed;

    if (
        !empty($_GET['resubscribe']) ||
        !empty($_REQUEST['subscription_reactivate']) ||
        (isset($cart_item_data['subscription_renewal']) && $cart_item_data['subscription_renewal']) ||
        (isset($cart_item_data['subscription_resubscribe']) && $cart_item_data['subscription_resubscribe'])
    ) {
        return $passed;
    }

    $existing_sub = GMP_Renewal::get_active_subscription_for_user($user_id, $check_id);
    if ($existing_sub) {
        wc_add_notice(__('You already have an active subscription for this EMI plan. Please do not repurchase.'), 'error');
        return false;
    }

    return $passed;
}, 10, 6);

// Add "Pay Extension EMI" button in subscription view (if eligible)
add_filter('wcs_view_subscription_actions', function ($actions, $subscription) {
    $user_id = get_current_user_id();

    $processed = [];
    foreach ($subscription->get_items() as $item) {
        $variation_id = $item->get_variation_id();
        $product_id   = $item->get_product_id();
        $product      = wc_get_product($variation_id ?: $product_id);
        $variation_id = $variation_id ?: $product_id;

        // Prevent duplicates if multiple items reference same variation/product
        if (in_array($variation_id, $processed)) continue;
        $processed[] = $variation_id;

        $ext_enabled = get_post_meta($product->get_id(), '_gmp_enable_extension', true);
        $ext_months  = intval(get_post_meta($product->get_id(), '_gmp_extension_months', true));
        $ext_used    = get_user_meta($user_id, "_gmp_extension_used_{$subscription->get_id()}", true) ?: 0;
        $completed = $subscription->get_payment_count();

        $items = $subscription->get_items();
        $total_count = 0;

        foreach ($items as $item) {
            $product = $item->get_product();
            if ($product && $product->is_type('subscription_variation')) {
                $billing_length = $product->get_meta('_subscription_length');
                $total_count = intval($billing_length);
            }
        }


        if (
            $ext_enabled === 'yes' &&
            $ext_used < $ext_months &&
            $subscription->has_status(['expired', 'active']) &&
            $completed >= $total_count
        ) {
            $actions['gmp_extend'] = [
                'url'  => add_query_arg([
                    'gmp_extension_payment' => $subscription->get_id(),
                    'variation_id'          => $variation_id,
                ], wc_get_checkout_url()),
                'name' => 'Pay Extension EMI',
            ];
        }
    }

    return $actions;
}, 10, 2);
