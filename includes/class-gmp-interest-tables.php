<?php
if (!defined('ABSPATH')) exit;

/**
 * Displays the interest table for related orders in frontend and admin.
 */

function gmp_get_interest_data_for_subscription($subscription) {
    if (!$subscription || !$subscription instanceof WC_Subscription) return [];

    $related_orders = wcs_get_related_orders($subscription, ['parent']);
    $settings = get_option('gmp_interest_settings', []);

    $data = [];

    foreach ($related_orders as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;

        foreach ($order->get_items() as $item) {
            $product_id   = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $base_price   = $item->get_total() / max(1, $item->get_quantity());
            $item_name    = $item->get_name();

            $setting_key = $product_id; // Interest is set at product level
            $int_rate = floatval($settings[$setting_key]['base'] ?? 0);
            $emi_with_interest = $base_price + ($base_price * $int_rate / 100);

            $data[] = [
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'order_date' => $order->get_date_created()->date('Y-m-d'),
                'product_name' => $item_name,
                'base_emi' => $base_price,
                'interest_percent' => $int_rate,
                'emi_with_interest' => $emi_with_interest
            ];
        }
    }

    return $data;
}

function gmp_render_interest_table($subscription) {
    $rows = gmp_get_interest_data_for_subscription($subscription);

    if (empty($rows)) return;

    echo '<h3>Gold Plan EMI + Interest Summary</h3>';
    echo '<table class="shop_table shop_table_responsive">';
    echo '<thead><tr><th>Order</th><th>Date</th><th>Product</th><th>Base EMI</th><th>Interest %</th><th>Total (EMI + Interest)</th></tr></thead>';
    echo '<tbody>';

    foreach ($rows as $r) {
        echo '<tr>';
        echo '<td><a href="' . esc_url(get_edit_post_link($r['order_id'])) . '" target="_blank">' . esc_html($r['order_number']) . '</a></td>';
        echo '<td>' . esc_html($r['order_date']) . '</td>';
        echo '<td>' . esc_html($r['product_name']) . '</td>';
        echo '<td>₹' . number_format($r['base_emi'], 2) . '</td>';
        echo '<td>' . number_format($r['interest_percent'], 2) . '%</td>';
        echo '<td>₹' . number_format($r['emi_with_interest'], 2) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

function gmp_frontend_related_orders_interest_table($subscription) {
    gmp_render_interest_table($subscription);
}

function gmp_admin_related_orders_interest_table($order) {
    if (!$order || !wcs_order_contains_subscription($order)) return;

    // Get parent subscription from order
    $subscriptions = wcs_get_subscriptions_for_order($order);
    if (empty($subscriptions)) return;

    $subscription = reset($subscriptions);
    gmp_render_interest_table($subscription);
}
