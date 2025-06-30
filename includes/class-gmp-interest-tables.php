<?php
if (!defined('ABSPATH')) exit;

class GMP_Interest_Tables {

    public static function init() {
        add_action('woocommerce_subscription_details_table', [__CLASS__, 'frontend'], 30);
        add_action('woocommerce_admin_order_data_after_order_details', [__CLASS__, 'admin']);
    }

    public static function frontend($subscription) {
        self::render($subscription);
    }

    public static function admin($order) {
        if (!$order || !wcs_order_contains_subscription($order)) return;

        $subscriptions = wcs_get_subscriptions_for_order($order);
        if (empty($subscriptions)) return;

        $subscription = reset($subscriptions);
        self::render($subscription);
    }

    public static function render($subscription) {
        if (!$subscription instanceof WC_Subscription) return;

        $related_orders = wcs_get_related_orders($subscription, ['parent']);
        $settings = get_option('gmp_interest_settings', []);

        echo '<h3>Gold Plan EMI + Interest Summary</h3>';
        echo '<table class="shop_table shop_table_responsive">';
        echo '<thead><tr><th>Order</th><th>Date</th><th>Product</th><th>Base EMI</th><th>Interest %</th><th>Total (EMI + Interest)</th></tr></thead>';
        echo '<tbody>';

        foreach ($related_orders as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) continue;

            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $base_price = $item->get_total() / max(1, $item->get_quantity());
                $item_name = $item->get_name();

                $interest = floatval($settings[$product_id]['base'] ?? 0);
                $total_with_interest = $base_price + ($base_price * $interest / 100);

                echo '<tr>';
                echo '<td><a href="' . esc_url(get_edit_post_link($order_id)) . '" target="_blank">' . esc_html($order->get_order_number()) . '</a></td>';
                echo '<td>' . esc_html($order->get_date_created()->date('Y-m-d')) . '</td>';
                echo '<td>' . esc_html($item_name) . '</td>';
                echo '<td>₹' . number_format($base_price, 2) . '</td>';
                echo '<td>' . number_format($interest, 2) . '%</td>';
                echo '<td>₹' . number_format($total_with_interest, 2) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
    }
}

// Ensure it's only loaded after WooCommerce Subscriptions is ready
add_action('plugins_loaded', function () {
    if (class_exists('WC_Subscription') && function_exists('wcs_get_related_orders')) {
        GMP_Interest_Tables::init();
    }
});
