<?php
defined('ABSPATH') || exit;

$user_id = get_current_user_id();
$customer_orders = wc_get_orders([
    'customer_id' => $user_id,
    'limit'       => -1,
    'status'      => ['completed', 'processing'],
]);

echo '<h2>Your Gold Money Plans</h2>';

if (empty($customer_orders)) {
    echo '<p>You have not purchased any Gold Money Plan yet.</p>';
    return;
}

echo '<table class="shop_table shop_table_responsive my_account_orders">';
echo '<thead><tr>
    <th>Order ID</th>
    <th>Plan</th>
    <th>EMI Amount</th>
    <th>Start Date</th>
    <th>Status</th>
    <th>Action</th>
</tr></thead><tbody>';

foreach ($customer_orders as $order) {
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if (!$product) continue;

        if (has_term('gmp-plan', 'product_cat', $product->get_id())) {
            $order_id = $order->get_id();
            $plan_name = $product->get_name();
            $emi_amount = wc_price($item->get_total());
            $start_date = $order->get_date_created() ? $order->get_date_created()->date('Y-m-d') : '-';
            $status = ucfirst($order->get_status());
           $detail_url = wc_get_account_endpoint_url('gmp') . $order_id;

            echo "<tr>
                <td>#{$order_id}</td>
                <td>{$plan_name}</td>
                <td>{$emi_amount}</td>
                <td>{$start_date}</td>
                <td>{$status}</td>
                <td><a class='button' href='{$detail_url}'>View</a></td>
            </tr>";
        }
    }
}

echo '</tbody></table>';
