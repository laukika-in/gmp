<<h2>Your Gold Money Plans</h2>
<table class="shop_table shop_table_responsive">
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Product</th>
            <th>EMI</th>
            <th>Purchased On</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $customer_orders = wc_get_orders(['customer_id' => get_current_user_id()]);
    foreach ($customer_orders as $order) {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (has_term('gmp-plan', 'product_cat', $product->get_id())) {
                echo '<tr>';
                echo '<td>#' . $order->get_id() . '</td>';
                echo '<td>' . esc_html($product->get_name()) . '</td>';
                echo '<td>' . wc_price($item->get_total()) . '</td>';
                echo '<td>' . esc_html($order->get_date_created()->format('Y-m-d')) . '</td>';
                echo '<td>' . ($order->is_paid() ? 'Active' : 'Pending') . '</td>';
                echo '<td><a class="button" href="' . esc_url(wc_get_account_endpoint_url('gmp') . $order->get_id()) . '">View</a></td>';
                echo '</tr>';
            }
        }
    }
    ?>
    </tbody>
</table>
