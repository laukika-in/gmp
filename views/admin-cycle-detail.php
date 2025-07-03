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

echo '<div class="wrap"><h2>Cycle #' . esc_html( $cycle->id ) . ' Details</h2>';
echo '<p>Status: <strong>' . esc_html( ucfirst( $cycle->status ) ) . '</strong></p>';

echo '<table class="widefat"><thead><tr>
    <th>#</th><th>Due</th><th>EMI</th><th>Interest</th><th>Total</th><th>Status</th><th>Order</th>
</tr></thead><tbody>';
$total_paid = 0;
foreach ( $installments as $ins ) {
    echo '<tr>';
    echo '<td>' . $ins->month_number . '</td>';
    echo '<td>' . $ins->due_date . '</td>';
    echo '<td>' . wc_price( $ins->emi_amount ) . '</td>';
    echo '<td>' . $ins->interest_rate . '%</td>';
    echo '<td>' . wc_price( $ins->total_with_interest ) . '</td>';
    echo '<td>' . ( $ins->is_paid ? 'Paid' : 'Pending' ) . '</td>';
    echo '<td>' . ( $ins->order_id ? '<a href="' . esc_url( get_edit_post_link( $ins->order_id ) ) . '">#' . $ins->order_id . '</a>' : '-' ) . '</td>';
    echo '</tr>';
    
}
echo '</tbody>';

echo '<tfoot><tr>';
echo '<td colspan="4"><strong>Total</strong></td>';
echo '<td colspan="2"><strong>' . wc_price( $total_paid ) . '</strong></td>';
echo '</tr></tfoot></div>';
