<?php
if (!defined('ABSPATH')) exit;

class GMP_Admin_Dashboard {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
    }

    public static function add_menu() {
        add_submenu_page(
            'woocommerce',
            'Customer Gold Plans',
            'GMP Subscriptions',
            'manage_woocommerce',
            'gmp-subscriptions',
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page() {
        global $wpdb;

        $table = $wpdb->prefix . 'gmp_emi_cycles';
        $plans = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");

        echo '<div class="wrap"><h1>Gold Plan Subscriptions</h1>';

        if (empty($plans)) {
            echo '<p>No GMP subscriptions found.</p></div>';
            return;
        }

        foreach ($plans as $plan) {
            $user = get_userdata($plan->user_id);
            $variation = wc_get_product($plan->variation_id);
            $key = "gmp_subscription_history_{$plan->variation_id}";
            $history = get_user_meta($plan->user_id, $key, true) ?: [];

            echo '<div style="margin-bottom:30px; border:1px solid #ccc; padding:15px;">';
            echo '<h3>User: ' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</h3>';
            echo '<p>Product: ' . esc_html($variation->get_name()) . '</p>';
            echo '<p>Start: ' . esc_html($plan->start_date) . ' | End: ' . esc_html($plan->end_date) . '</p>';

            echo '<table class="widefat"><thead><tr>
                <th>Instalment</th><th>Order</th><th>Date</th><th>Amount</th>
            </tr></thead><tbody>';

            foreach ($history as $i => $entry) {
                echo '<tr>';
                echo '<td>' . ($i + 1) . '</td>';
                echo '<td><a href="' . esc_url(get_edit_post_link($entry['order_id'])) . '">#' . $entry['order_id'] . '</a></td>';
                echo '<td>' . esc_html($entry['date']) . '</td>';
                echo '<td>' . wc_price($entry['amount']) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table></div>';
        }

        echo '</div>';
    }
}
