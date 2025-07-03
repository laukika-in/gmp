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

foreach ( $installments as $ins ) {
    echo '<tr>';
    echo '<td>' . $ins->month_number . '</td>';
    echo '<td>' . esc_html( $ins->due_date ) . '</td>';
    echo '<td>' . wc_price( $ins->emi_amount ) . '</td>';
    echo '<td>' . $ins->interest_rate . '%</td>';
    echo '<td>' . wc_price( $ins->total_with_interest ) . '</td>';
    echo '<td>' . ( $ins->is_paid ? 'Paid' : 'Pending' ) . '</td>';
    echo '</tr>';
}
echo '</tbody></table>';
