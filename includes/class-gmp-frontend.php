<?php
if (!defined('ABSPATH')) exit;

class GMP_Frontend {
    public static function init() {
        add_action('woocommerce_account_gmp-subscriptions_endpoint', [__CLASS__, 'render']);
        add_filter('woocommerce_account_menu_items', [__CLASS__, 'menu']);
    }

    public static function menu($items) {
        $items['gmp-subscriptions'] = 'Gold EMI';
        return $items;
    }

    public static function render() {
        echo '<h2>Your Gold Money Plan Subscriptions</h2>';

        $user_id = get_current_user_id();
        global $wpdb;

        $cycles = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}gmp_cycles WHERE user_id = %d ORDER BY id DESC
        ", $user_id));

        if (!$cycles) {
            echo '<p>No subscriptions found.</p>';
            return;
        }

        foreach ($cycles as $cycle) {
            $variation = wc_get_product($cycle->variation_id);
            $name = $variation ? $variation->get_name() : 'Unknown';
            echo "<h3>{$name}</h3><p>Start: {$cycle->start_date} | End: {$cycle->end_date}</p>";
            echo '<table class="shop_table"><thead><tr>
                    <th>#</th><th>Order</th><th>Date</th><th>Amount</th><th>Interest %</th><th>Interest ₹</th>
                  </tr></thead><tbody>';

            $payments = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}gmp_emi_payments WHERE cycle_id = %d ORDER BY instalment_no ASC
            ", $cycle->id));

            foreach ($payments as $pay) {
                $order = wc_get_order($pay->order_id);
                $num = $order ? $order->get_order_number() : $pay->order_id;
                echo "<tr><td>{$pay->instalment_no}</td><td>#{$num}</td><td>{$pay->paid_date}</td><td>₹{$pay->amount}</td><td>{$pay->interest_pct}%</td><td>₹{$pay->interest_amt}</td></tr>";
            }

            echo '</tbody></table><hr>';
        }
    }
}
