<?php

function gmp_get_interest_data_for_subscription($subscription) {
    $data = [];

    $user_id = $subscription->get_user_id();

    foreach ($subscription->get_items() as $item) {
        $variation_id = $item->get_variation_id();
        $product_id   = $item->get_product_id();
        $product      = wc_get_product($variation_id ?: $product_id);
        $variation_id = $variation_id ?: $product_id;

        $history = get_user_meta($user_id, "gmp_subscription_history_{$variation_id}", true);
        $base_rate = floatval(get_option("gmp_interest_base_{$variation_id}", 0));

        if (!$history || !is_array($history)) continue;

        foreach ($history as $index => $entry) {
            $rate = $base_rate;
            $emi  = floatval($entry['amount']);
            $interest = round(($emi * $rate / 100), 2);
            $emi_with_interest = $emi + $interest;

            $data[] = [
                'index'      => $index + 1,
                'date'       => $entry['date'],
                'order_id'   => $entry['order_id'],
                'emi'        => wc_price($emi),
                'interest'   => wc_price($interest) . " ({$rate}%)",
                'total'      => wc_price($emi_with_interest),
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
        echo "<td><a href='" . esc_url(admin_url("post.php?post={$row['order_id']}&action=edit")) . "'>#{$row['order_id']}</a></td>";
        echo "<td>{$row['emi']}</td>";
        echo "<td>{$row['interest']}</td>";
        echo "<td>{$row['total']}</td>";
        echo '</tr>';
    }
    echo '</tbody></table>';
}
