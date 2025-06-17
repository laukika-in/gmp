<?php
$order_id = get_query_var('gmp');
$order = wc_get_order($order_id);
if (!$order) {
    echo '<p>Invalid order.</p>';
    return;
}
$user_id = get_current_user_id();
$items = $order->get_items();
$plan_item = null;
foreach ($items as $item) {
    $product = $item->get_product();
    if (has_term('gmp-plan', 'product_cat', $product->get_id())) {
        $plan_item = $item;
        break;
    }
}

if (!$plan_item) {
    echo '<p>No GMP plan found in this order.</p>';
    return;
}

echo '<h2>Gold Money Plan Detail</h2>';
echo '<p><strong>Order ID:</strong> #' . $order_id . '</p>';
echo '<p><strong>Product:</strong> ' . esc_html($plan_item->get_name()) . '</p>';
echo '<p><strong>Purchased on:</strong> ' . $order->get_date_created()->format('Y-m-d') . '</p>';
echo '<p><strong>Amount:</strong> ' . wc_price($plan_item->get_total()) . '</p>';

// Show EMI Payments
$payments = get_user_meta($user_id, "gmp_payments_{$order_id}", true);
echo '<h3>EMI Payment History</h3>';
if ($payments) {
    echo '<table class="shop_table"><thead><tr><th>Month</th><th>Amount</th><th>Date</th></tr></thead><tbody>';
    foreach ($payments as $p) {
        echo '<tr><td>' . esc_html($p['month']) . '</td><td>' . wc_price($p['amount']) . '</td><td>' . esc_html($p['date']) . '</td></tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<p>No EMI payments yet.</p>';
}
