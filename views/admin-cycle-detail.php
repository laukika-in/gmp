<?php
$cycle_id = absint( $_GET['cycle_id'] ?? 0 );
if ( ! $cycle_id ) {
    echo '<p>Missing cycle ID.</p>';
    return;
}

global $wpdb;
$cycle = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}gmp_cycles WHERE id = %d", $cycle_id
) );

if ( ! $cycle ) {
    echo '<p>Invalid cycle.</p>';
    return;
}

$installments = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}gmp_installments WHERE cycle_id = %d ORDER BY month_number ASC",
    $cycle_id
) );

$product = wc_get_product( $cycle->variation_id );
$parent  = wc_get_product( $product ? $product->get_parent_id() : 0 );
$label   = $parent ? $parent->get_name() : '';
$thumb   = $parent ? $parent->get_image('thumbnail', [ 'style' => 'width:50px; border-radius:4px;' ]) : '';

wp_enqueue_style('gmp-admin-style', GMP_PLUGIN_URL . 'assets/css/gmp-admin.css', [], GMP_PLUGIN_VERSION);

echo '<div class="wrap">';
echo '<h2>Cycle #' . esc_html( $cycle->id ) . '</h2>';
echo '<div class="gmp-flex gmp-cycle-header">';
echo $thumb;
echo '<div><strong>' . esc_html($label) . '</strong><br>';
echo 'Status: <span class="badge badge-' . esc_attr($cycle->status) . '">' . ucfirst($cycle->status) . '</span></div>';
echo '</div>';

echo '<table class="wp-list-table widefat fixed striped gmp-admin-table"><thead><tr>
    <th>#</th><th>Due</th><th>EMI</th><th>Interest</th><th>Total</th><th>Status</th><th>Paid On</th><th>Order</th>
</tr></thead><tbody>';

foreach ( $installments as $ins ) {
    echo '<tr>';
    echo '<td>' . $ins->month_number . '</td>';
    echo '<td>' . date_i18n('j M Y', strtotime($ins->due_date)) . '</td>';
    echo '<td>' . wc_price($ins->emi_amount) . '</td>';
    echo '<td>' . esc_html($ins->interest_rate) . '%</td>';
    echo '<td>' . wc_price($ins->total_with_interest) . '</td>';
    echo '<td>' . ($ins->is_paid ? 'Paid' : 'Pending') . '</td>';
    echo '<td>' . ($ins->paid_date ? date_i18n('j M Y', strtotime($ins->paid_date)) : '—') . '</td>';
    echo '<td>' . ($ins->order_id ? '<a href="' . esc_url( get_edit_post_link($ins->order_id) ) . '">#' . $ins->order_id . '</a>' : '—') . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div>';
