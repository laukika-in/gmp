<?php
$cycle_id = absint( $_GET['view'] ?? 0 );
if ( ! $cycle_id ) return;

global $wpdb;
$cycle = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}gmp_cycles WHERE id = %d AND user_id = %d",
    $cycle_id, get_current_user_id()
) );

if ( ! $cycle ) {
    echo '<p>Invalid cycle.</p>';
    return;
}

$installments = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}gmp_installments WHERE cycle_id = %d ORDER BY month_number ASC",
    $cycle_id
) );

echo '<h4>EMI Installments for Cycle #' . esc_html( $cycle->id ) . '</h4>';
echo '<table class="woocommerce-table shop_table"><thead><tr>
    <th>#</th><th>Due Date</th><th>EMI</th><th>Interest</th><th>Total</th><th>Status</th>
</tr></thead><tbody>';
$total_paid = 0;

foreach ( $installments as $ins ) {
    echo '<tr>';
    echo '<td>' . $ins->month_number . '</td>';
    echo '<td>' . esc_html( $ins->due_date ) . '</td>';
    echo '<td>' . wc_price( $ins->emi_amount ) . '</td>';
    echo '<td>' . $ins->interest_rate . '%</td>';
    echo '<td>' . wc_price( $ins->total_with_interest ) . '</td>';
   if ( $ins->is_paid ) {
    $order_link = $ins->order_id ? '<br><a href="' . esc_url( wc_get_endpoint_url( 'view-order', $ins->order_id, wc_get_page_permalink( 'myaccount' ) ) ) . '">View Order</a>' : '';
    echo '<td>Paid' . $order_link . '</td>';
} else {
    echo '<td>Pending</td>';
}

    echo '</tr>';
       if ( $ins->is_paid ) {
        $total_paid += floatval( $ins->total_with_interest );
    }
}
echo '</tbody>';
echo '<tfoot><tr>';
echo '<td colspan="4"><strong>Total Paid</strong></td>';
echo '<td colspan="2"><strong>' . wc_price( $total_paid ) . '</strong></td>';
echo '</tr></tfoot>';
echo '</table></div>';
