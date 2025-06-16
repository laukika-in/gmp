<?php
$order_id = absint(get_query_var('gmp'));
$order = wc_get_order($order_id);

if (!$order || $order->get_user_id() !== get_current_user_id()) {
    echo '<p>Access denied or plan not found.</p>';
    return;
}

$item = current($order->get_items());
$product = $item->get_product();

echo "<h2>Gold Money Plan Detail</h2>";
echo "<p><strong>Plan:</strong> {$product->get_name()}</p>";
echo "<p><strong>Date:</strong> " . $order->get_date_created()->format('Y-m-d') . "</p>";
echo "<p><strong>EMI:</strong> " . wc_price($item->get_total()) . "</p>";

// EMI History
$payments = get_user_meta(get_current_user_id(), "gmp_payments_{$order_id}", true);

echo "<h3>EMI Payment History</h3>";
if ($payments) {
    echo "<table><tr><th>Month</th><th>Amount</th><th>Date</th></tr>";
    foreach ($payments as $entry) {
        echo "<tr><td>{$entry['month']}</td><td>" . wc_price($entry['amount']) . "</td><td>{$entry['date']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No EMI payments found.</p>";
}
echo '<a href="' . wc_get_account_endpoint_url('gmp') . '">← Back to plans</a>';
// Show Pay EMI Button
$emi_amount = $item->get_total();
$pay_link = add_query_arg([
    'add-to-cart' => $product->get_id(),
    'gmp_emi_payment' => $order_id
], wc_get_cart_url());

echo "<p><a class='button' href='{$pay_link}'>Pay EMI ₹" . wc_price($emi_amount) . "</a></p>";

