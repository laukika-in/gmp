<?php
if (!defined('ABSPATH')) exit;

class GMP_Tracker {
    public static function init() {
        add_action('woocommerce_thankyou', [__CLASS__, 'maybe_record_emi'], 10, 1);
    }

    public static function maybe_record_emi($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if (!has_term('gmp-plan', 'product_cat', $product_id)) continue;

            $variation_id = $item->get_variation_id() ?: $product_id;
            $user_id = $order->get_user_id();
            $amount = $item->get_total();

            global $wpdb;
            $cycle_table = "{$wpdb->prefix}gmp_cycles";
            $payment_table = "{$wpdb->prefix}gmp_emi_payments";

            // Get or create current cycle
            $cycle_id = $wpdb->get_var($wpdb->prepare("
                SELECT id FROM $cycle_table
                WHERE user_id = %d AND variation_id = %d
                ORDER BY id DESC LIMIT 1
            ", $user_id, $variation_id));

            if (!$cycle_id || self::is_cycle_complete($cycle_id, $variation_id)) {
                $lock = intval(get_post_meta($variation_id, '_gmp_lock_period', true));
                $ext  = intval(get_post_meta($variation_id, '_gmp_extension_months', true));
                $start = current_time('mysql');
                $end   = date('Y-m-d', strtotime("+{$lock} months +{$ext} months", strtotime($start)));
                $wpdb->insert($cycle_table, [
                    'user_id'      => $user_id,
                    'variation_id' => $variation_id,
                    'start_date'   => $start,
                    'end_date'     => $end,
                ]);
                $cycle_id = $wpdb->insert_id;
            }

            // Get settings
            $settings = get_option('gmp_interest_settings', []);
            $cfg = $settings[$product_id] ?? ['base' => 0, 'ext' => []];
            $base = floatval($cfg['base']);
            $exts = array_values(array_map('floatval', $cfg['ext'] ?? []));

            // Count existing EMIs
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $payment_table WHERE cycle_id = %d", $cycle_id));
            $instalment = $count + 1;

            // Determine interest rate
            $lock = intval(get_post_meta($variation_id, '_gmp_lock_period', true));
            $pct = $instalment <= $lock ? $base : ($exts[$instalment - $lock - 1] ?? $base);
            $interest_amt = round($amount * ($pct / 100), 2);

            // Record payment
            $wpdb->insert($payment_table, [
                'cycle_id'     => $cycle_id,
                'order_id'     => $order_id,
                'paid_date'    => current_time('mysql'),
                'amount'       => $amount,
                'interest_pct' => $pct,
                'interest_amt' => $interest_amt,
                'instalment_no'=> $instalment,
            ]);
        }
    }

    public static function is_cycle_complete($cycle_id, $variation_id) {
        global $wpdb;
        $lock = intval(get_post_meta($variation_id, '_gmp_lock_period', true));
        $ext  = intval(get_post_meta($variation_id, '_gmp_extension_months', true));
        $max  = $lock + $ext;
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}gmp_emi_payments
            WHERE cycle_id = %d
        ", $cycle_id));
        return $count >= $max;
    }
}
