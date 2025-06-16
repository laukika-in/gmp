<?php
$order_id = get_query_var('gmp');
if (!is_numeric($order_id)) {
    echo '<p>Invalid Plan ID.</p>';
    return;
}

$order = wc_get_order($order_id);
if (!$order || $order->get_user_id() != get_current_user_id()) {
    echo '<p>This plan does not exist or you do not have permission.</p>';
    return;
}

echo "<h2>Gold Money Plan Details (Order #{$order_id})</h2>";

// Show meta info
echo '<ul>';
echo '<li><strong>Plan:</strong> ' . $order->get_items()[0]->get_name() . '</li>';
echo '<li><strong>Start Date:</strong> ' . $order->get_date_created()->format('Y-m-d') . '</li>';
echo '<li><strong>EMI:</strong> ' . wc_price($order->get_total()) . '</li>';
echo '<li><strong>Status:</strong> ' . ucfirst($order->get_status()) . '</li>';
echo '</ul>';

// KYC files
$kyc = [
    'PAN Card' => get_post_meta($order_id, 'gmp_pan', true),
    'Aadhar Card' => get_post_meta($order_id, 'gmp_aadhar', true),
    'Nominee Aadhar' => get_post_meta($order_id, 'gmp_nominee_aadhar', true),
];
echo '<h3>KYC Documents</h3><ul>';
foreach ($kyc as $label => $url) {
    echo "<li>{$label}: <a href='{$url}' target='_blank'>View</a></li>";
}
echo '</ul>';

// Nominee
echo '<h3>Nominee Details</h3>';
echo '<ul>';
echo '<li><strong>Name:</strong> ' . esc_html(get_post_meta($order_id, 'gmp_nominee_name', true)) . '</li>';
echo '<li><strong>Phone:</strong> ' . esc_html(get_post_meta($order_id, 'gmp_nominee_phone', true)) . '</li>';
echo '</ul>';

// EMI Payments
$payments = get_user_meta(get_current_user_id(), "gmp_payments_{$order_id}", true);
echo '<h3>EMI Payment History</h3>';
if (!empty($payments)) {
    echo '<table><tr><th>Month</th><th>Amount</th><th>Date</th></tr>';
    foreach ($payments as $p) {
        echo '<tr><td>' . esc_html($p['month']) . '</td><td>' . wc_price($p['amount']) . '</td><td>' . esc_html($p['date']) . '</td></tr>';
    }
    echo '</table>';
} else {
    echo '<p>No EMI payments yet.</p>';
}

echo '<p><a href="' . wc_get_account_endpoint_url('gmp') . '">&larr; Back to Plans</a></p>';
