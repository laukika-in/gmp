<?php
$user_id = get_current_user_id();
$orders = wc_get_orders([
    'customer_id' => $user_id,
    'limit'       => -1,
    'status'      => ['completed', 'processing'],
]);

echo '<h2>Gold Money Plans</h2>';
echo '<table class="shop_table"><tr><th>Order</th><th>Plan</th><th>EMI</th><th>Date</th><th>Action</th></tr>';

foreach ($orders as $order) {
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product && has_term('gmp-plan', 'product_cat', $product->get_id())) {
            $emi = wc_price($item->get_total());
            $date = $order->get_date_created()->format('Y-m-d');
            $link = wc_get_account_endpoint_url('gmp') . $order->get_id();
            echo "<tr>
                <td>#{$order->get_id()}</td>
                <td>{$product->get_name()}</td>
                <td>{$emi}</td>
                <td>{$date}</td>
                <td><a class='button' href='{$link}'>View</a></td>
            </tr>";
        }
    }
}
echo '</table>';
