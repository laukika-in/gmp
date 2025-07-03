<?php
if (!defined('ABSPATH')) exit;

class GMP_Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
    }

    public static function menu() {
        add_submenu_page(
            'woocommerce',
            'GMP Subscriptions',
            'GMP Subscriptions',
            'manage_woocommerce',
            'gmp-subscriptions',
            [__CLASS__, 'render']
        );
    }

    public static function render() {
        global $wpdb;
        $cycles = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gmp_cycles ORDER BY id DESC");

        echo '<div class="wrap"><h1>GMP Subscriptions</h1>';

        if (!$cycles) {
            echo '<p>No EMI records found.</p>';
            return;
        }

        foreach ($cycles as $cycle) {
            $user = get_userdata($cycle->user_id);
            $variation = wc_get_product($cycle->variation_id);
            $name = $variation ? $variation->get_name() : 'Unknown';
            echo "<h2>{$name} - {$user->display_name}</h2><p>Start: {$cycle->start_date} | End: {$cycle->end_date}</p>";

            echo '<table class="widefat"><thead><tr>
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

        echo '</div>';
    }
}
