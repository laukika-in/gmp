<?php
if (!defined('ABSPATH')) exit;

class GMP_Order_Handler {
    public static function init() {
        add_action('woocommerce_thankyou', [__CLASS__, 'track_emi_payment']);
    }

    public static function track_emi_payment($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            if (!has_term('gmp-plan', 'product_cat', $product_id)) continue;

            $user_id = $order->get_user_id();
            $amount  = $item->get_total();
            $date    = $order->get_date_paid() ? $order->get_date_paid()->date('Y-m-d') : current_time('Y-m-d');

            // Save to user_meta for now (simpler than full DB)
            $key = "gmp_subscription_history_{$variation_id}";
            $history = get_user_meta($user_id, $key, true) ?: [];
            $history[] = [
                'order_id' => $order_id,
                'amount'   => $amount,
                'date'     => $date,
            ];
            update_user_meta($user_id, $key, $history);

            // Check if this is the first EMI for this variation
            $table = $GLOBALS['wpdb']->prefix . 'gmp_emi_cycles';
            $exists = $GLOBALS['wpdb']->get_var(
                $GLOBALS['wpdb']->prepare("SELECT id FROM $table WHERE user_id = %d AND variation_id = %d", $user_id, $variation_id)
            );

            if (!$exists) {
                $lock = intval(get_post_meta($variation_id, '_gmp_lock_period', true));
                $ext  = intval(get_post_meta($variation_id, '_gmp_extension_months', true));
                $start = $date;
                $end = date('Y-m-d', strtotime("+".($lock+$ext)." months", strtotime($start)));

                $GLOBALS['wpdb']->insert($table, [
                    'user_id' => $user_id,
                    'product_id' => $product_id,
                    'variation_id' => $variation_id,
                    'start_date' => $start,
                    'end_date' => $end,
                ]);
            }
        }
    }
}
