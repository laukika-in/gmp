<?php
class GMP_Interest_Tables {

function gmp_get_interest_data_for_subscription($subscription) {
    $data = [];

    foreach ($subscription->get_related_orders('all') as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;

        foreach ($order->get_items() as $item) {
            $emi      = floatval($item->get_total()) / max(1, $item->get_quantity());
            $interest = floatval($item->get_meta('_gmp_interest_amount'));
            $percent  = floatval($item->get_meta('_gmp_interest_percent'));

            $data[] = [
                'index'      => count($data) + 1,
                'date'       => $order->get_date_created()->date('Y-m-d'),
                'order_id'   => $order->get_id(),
                'order_no'   => $order->get_order_number(),
                'order_url'  => $order->get_view_order_url(),
                'emi'        => wc_price($emi),
                'interest'   => wc_price($interest) . " ({$percent}%)",
                'total'      => wc_price($emi + $interest),
            ];
        }
    }

    return $data;
}


function gmp_admin_related_orders_interest_table($order) {
    if (!wcs_order_contains_subscription($order)) return;

    $subs = wcs_get_subscriptions_for_order($order);
    foreach ($subs as $sub) {
        gmp_render_interest_table($sub, 'admin');
    }
}

function gmp_frontend_related_orders_interest_table($subscription) {
    gmp_render_interest_table($subscription, 'frontend');
}

function gmp_render_interest_table($subscription, $context = 'admin') {
    $interest_rows = gmp_get_interest_data_for_subscription($subscription);
    if (empty($interest_rows)) return;

    echo '<h3>Interest & EMI Details</h3>';
    echo '<table class="shop_table shop_table_responsive">';
    echo '<thead><tr><th>#</th><th>Date</th><th>Order</th><th>Base EMI</th><th>Interest</th><th>EMI + Interest</th></tr></thead>';
    echo '<tbody>';
    foreach ($interest_rows as $row) {
        echo '<tr>';
        echo "<td>{$row['index']}</td>";
        echo "<td>{$row['date']}</td>";
        $link = ($context === 'frontend')
    ? esc_url($row['order_url'])
    : esc_url(admin_url("post.php?post={$row['order_id']}&action=edit"));

echo "<td><a href='{$link}'>#{$row['order_no']}</a></td>";
        echo "<td>{$row['emi']}</td>";
        echo "<td>{$row['interest']}</td>";
        echo "<td>{$row['total']}</td>";
        echo '</tr>';
    }
    echo '</tbody></table>';
}
}