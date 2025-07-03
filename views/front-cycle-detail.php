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

$product     = wc_get_product( $cycle->variation_id );
$parent      = wc_get_product( $product ? $product->get_parent_id() : 0 );
$parent_name = $parent ? $parent->get_name() : '';

$attributes = [];
foreach ( $product->get_variation_attributes() as $key => $val ) {
    $taxonomy = str_replace( 'attribute_', '', $key );
    $term = get_term_by( 'slug', $val, $taxonomy );
    $attributes[] = $term ? $term->name : ucfirst( $val );
}
$variation_label = implode( ', ', $attributes );
$label = $parent_name . ( $variation_label ? ' - ' . $variation_label : '' );

$link = add_query_arg( 'gmp_pay_now', $product->get_id(), home_url() );

?>

<h3>Product: <?php echo esc_html( $label ); ?></h3>

<?php

if ( $cycle->status === 'active' ) {
    echo '<p><a href="' . esc_url( $link ) . '" class="button">Pay Now</a></p>';
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
echo '</table>';

$total_emi = 0;
$total_interest = 0;

foreach ( $installments as $ins ) {
    if ( $ins->is_paid ) {
        $total_emi      += floatval( $ins->emi_amount );
        $total_interest += floatval( $ins->total_with_interest ) - floatval( $ins->emi_amount );
    }
}

$total_received = $total_emi + $total_interest;

echo '<br><h4>Summary</h4>';
echo '<table class="woocommerce-table shop_table"><thead><tr>
    <th>Description</th><th>Amount</th>
</tr></thead><tbody>';

echo '<tr><td>Total EMI Paid (Principal)</td><td>' . wc_price( $total_emi ) . '</td></tr>';
echo '<tr><td>Total Returns Received (Interest)</td><td>' . wc_price( $total_interest ) . '</td></tr>';
echo '<tr><td><strong>Total Paid</strong></td><td><strong>' . wc_price( $total_received ) . '</strong></td></tr>';

echo '</tbody></table>';