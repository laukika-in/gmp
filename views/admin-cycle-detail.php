<?php
$cycle_id = absint( $_GET['cycle_id'] ?? 0 );
if ( ! $cycle_id ) return;

global $wpdb;
$cycle = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}gmp_cycles WHERE id = %d", $cycle_id
) );

if ( ! $cycle ) {
    echo '<div class="wrap"><h2>Invalid Cycle</h2></div>';
    return;
}

$installments = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}gmp_installments WHERE cycle_id = %d ORDER BY month_number ASC", $cycle_id
) );

$user = get_userdata( $cycle->user_id );
$product = wc_get_product( $cycle->variation_id );
$parent = wc_get_product( $product ? $product->get_parent_id() : 0 );
$thumb = $parent ? $parent->get_image( 'woocommerce_thumbnail', ['class' => 'gmp-thumb-admin'] ) : '';
$price = $product ? wc_price( $product->get_price() ) : '';

echo '<div class="wrap gmp-admin-wrap">';
echo '<a href="' . admin_url( 'admin.php?page=gmp-cycles' ) . '" class="button">&larr; Back to List</a>';
echo '<h2>Cycle #' . esc_html( $cycle->id ) . ' Details</h2>';

echo '<div class="gmp-two-col">';
echo '<div class="gmp-col"><h3>User Details</h3>';
echo '<p><strong>Name:</strong> ' . esc_html( $user->display_name ) . '</p>';
echo '<p><strong>Email:</strong> ' . esc_html( $user->user_email ) . '</p>';
echo '<p><strong>User ID:</strong> ' . esc_html( $user->ID ) . '</p>';
echo '</div>';

echo '<div class="gmp-col"><h3>Product Details</h3>';
echo $thumb;
echo '<p><strong>' . esc_html( $parent->get_name() ) . '</strong></p>';
echo '<p>' . esc_html( $price ) . '</p>';
echo '</div>';
echo '</div>';

echo '<br><table class="widefat"><thead><tr>
    <th>#</th><th>Due</th><th>EMI</th><th>Interest</th><th>Total</th><th>Status</th><th>Paid On</th><th>Order</th>
</tr></thead><tbody>';

foreach ( $installments as $ins ) {
    echo '<tr>';
    echo '<td>' . $ins->month_number . '</td>';
    echo '<td>' . date_i18n( 'j M Y', strtotime( $ins->due_date ) ) . '</td>';
    echo '<td>' . wc_price( $ins->emi_amount ) . '</td>';
    echo '<td>' . $ins->interest_rate . '%</td>';
    echo '<td>' . wc_price( $ins->total_with_interest ) . '</td>';
    echo '<td>' . ( $ins->is_paid ? 'Paid' : 'Pending' ) . '</td>';
    echo '<td>' . ( $ins->paid_date ? date_i18n( 'j M Y', strtotime( $ins->paid_date ) ) : '—' ) . '</td>';
    echo '<td>' . ( $ins->order_id ? '<a href="' . esc_url( get_edit_post_link( $ins->order_id ) ) . '">#' . $ins->order_id . '</a>' : '-' ) . '</td>';
    echo '</tr>';
}
echo '</tbody></table>';

// Summary Block
$total_emi = $total_interest = 0;
foreach ( $installments as $ins ) {
    if ( $ins->is_paid ) {
        $total_emi += floatval( $ins->emi_amount );
        $total_interest += floatval( $ins->total_with_interest ) - floatval( $ins->emi_amount );
    }
}
$total_received = $total_emi + $total_interest;
$start = date_i18n( 'j M Y', strtotime( $cycle->start_date ) );
$end = date_i18n( 'j M Y', strtotime( "+".($cycle->total_months - 1)." months", strtotime( $cycle->start_date ) ) );

echo '<div class="gmp-two-col">';
echo '<div class="gmp-col">';
echo '<h3>Summary</h3>';
echo '<table class="gmp-summary-table"><tbody>';
echo '<tr><td>Total EMI Paid</td><td>' . wc_price( $total_emi ) . '</td></tr>';
echo '<tr><td>Total Interest</td><td>' . wc_price( $total_interest ) . '</td></tr>';
echo '<tr><td><strong>Total Received</strong></td><td><strong>' . wc_price( $total_received ) . '</strong></td></tr>';
echo '<tr><td>Duration</td><td>' . esc_html( $start . ' - ' . $end ) . '</td></tr>';
echo '</tbody></table>';
echo '</div>';

echo '<div class="gmp-col">';
echo '<h3>Actions</h3>';
echo '<div class="gmp-admin-actions">';
echo '<a href="#" class="button gmp-action-btn" data-cycle-id="' . esc_attr($cycle->id) . '" data-action="close">Mark as Closed</a><br><br>';
echo '<a href="#" class="button gmp-action-btn" data-cycle-id="' . esc_attr($cycle->id) . '" data-action="cancel">Cancel Cycle</a><br><br>';
echo '<a href="#" class="button gmp-action-btn" data-cycle-id="' . esc_attr($cycle->id) . '" data-action="stop">Stop Future EMIs</a>';

echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>';
