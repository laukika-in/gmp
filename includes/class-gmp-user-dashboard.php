<?php
if (!defined('ABSPATH')) exit;

class GMP_User_Dashboard {
    public static function init() {
        add_action('init', [__CLASS__, 'add_endpoint']);
        add_filter('query_vars', [__CLASS__, 'add_query_var']);
        add_filter('woocommerce_account_menu_items', [__CLASS__, 'add_menu_item']);
        add_action('woocommerce_account_gmp-subscriptions_endpoint', [__CLASS__, 'render_page']);
    }

    public static function add_endpoint() {
        add_rewrite_endpoint('gmp-subscriptions', EP_ROOT | EP_PAGES);
    }

    public static function add_query_var($vars) {
        $vars[] = 'gmp-subscriptions';
        return $vars;
    }

    public static function add_menu_item($items) {
        $items['gmp-subscriptions'] = 'My Gold Plans';
        return $items;
    }

    public static function render_page() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>You must be logged in to view this page.</p>';
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gmp_emi_cycles';
        $plans = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY start_date DESC",
            $user_id
        ));

        echo '<h2>My Gold Plans</h2>';
        if (empty($plans)) {
            echo '<p>No active EMI plans found.</p>';
            return;
        }

        foreach ($plans as $plan) {
            $variation = wc_get_product($plan->variation_id);
            $key = "gmp_subscription_history_{$plan->variation_id}";
            $history = get_user_meta($user_id, $key, true) ?: [];

            echo '<div style="margin-bottom: 30px; border:1px solid #ccc; padding:15px;">';
            echo '<h3>' . esc_html($variation->get_name()) . '</h3>';
            echo '<p>Start: ' . esc_html($plan->start_date) . ' | End: ' . esc_html($plan->end_date) . '</p>';

            echo '<table class="shop_table"><thead><tr>
                <th>Instalment</th><th>Order</th><th>Date</th><th>Amount</th>
            </tr></thead><tbody>';

            foreach ($history as $i => $entry) {
                echo '<tr>';
                echo '<td>' . ($i + 1) . '</td>';
                echo '<td><a href="' . esc_url(get_permalink($entry['order_id'])) . '">#' . $entry['order_id'] . '</a></td>';
                echo '<td>' . esc_html($entry['date']) . '</td>';
                echo '<td>' . wc_price($entry['amount']) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table></div>';
        }
    }
}
