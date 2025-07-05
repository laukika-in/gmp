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

$user    = get_userdata( $cycle->user_id );
$product = wc_get_product( $cycle->variation_id );
$parent  = wc_get_product( $product ? $product->get_parent_id() : 0 );

$thumb  = $parent ? $parent->get_image( 'woocommerce_thumbnail', ['class' => 'gmp-thumb-admin'] ) : '';
$price  = $product ? wc_price( $product->get_price() ) : '';
$edit_product_link = $parent ? get_edit_post_link( $parent->get_id() ) : '#';

$edit_user_link = $user ? admin_url( 'user-edit.php?user_id=' . $user->ID ) : '#';

echo '<div class="wrap gmp-admin-wrap">';
echo '<a href="' . admin_url( 'admin.php?page=gmp-cycles' ) . '" class="button">&larr; Back to List</a>';
echo '<h2>Cycle #' . esc_html( $cycle->id ) . ' Details</h2>';
$status_colors = [
    'active' => '#ffc107',
    'closed' => '#28a745',
    'cancelled' => '#dc3545'
];
$color = $status_colors[ $cycle->status ] ?? '#6c757d';

echo '<p><strong>Status:</strong> <span style="padding:4px 8px; border-radius:4px; background:' . esc_attr($color) . '; color:#fff;">' . ucfirst( $cycle->status ) . '</span></p>';

// === Two Column Metaboxes ===
echo '<div class="gmp-two-col">';

// User box
echo '<div class="gmp-col"><h3>User Details</h3>';
if ( $user ) {
    echo '<p><strong>Name:</strong> <a href="' . esc_url( $edit_user_link ) . '" target="_blank">' . esc_html( $user->display_name ) . '</a></p>';
    echo '<p><strong>Email:</strong> <a href="mailto:' . esc_attr( $user->user_email ) . '">' . esc_html( $user->user_email ) . '</a></p>';
    echo '<p><strong>User ID:</strong> ' . esc_html( $user->ID ) . '</p>';
} else {
    echo '<p>User not found</p>';
}
echo '</div>';

// Product box
echo '<div class="gmp-col"><h3>Product Details</h3>';
echo '<div class="gmp-product-info">';
$product_link = $parent ? get_permalink( $parent->get_id() ) : '#';
echo '<a href="' . esc_url( $product_link ) . '" target="_blank">' . $thumb . '</a>';

echo '<div>';
echo '<p><a href="' . esc_url( $edit_product_link ) . '" target="_blank"><strong>' . esc_html( $parent ? $parent->get_name() : 'N/A' ) . '</strong></a></p>';


if ( $product ) {
    $attrs = [];
    foreach ( $product->get_variation_attributes() as $key => $val ) {
        $taxonomy = str_replace( 'attribute_', '', $key );
        $term = get_term_by( 'slug', $val, $taxonomy );
        $attrs[] = $term ? $term->name : ucfirst( $val );
    }
  echo '<p><strong>EMI Plan:</strong> ' . esc_html( implode( ', ', $attrs ) ) . '</p>';
echo '<p><strong>Payable Installment:</strong> ' . $price . '</p>';

}
echo '</div></div>';
echo '</div>';

echo '</div>'; // gmp-two-col

// === Installments Table ===
echo '<br><table class="widefat"><thead><tr>
    <th style="width:50px;">#</th><th>Due</th><th>EMI</th><th>Interest</th><th>Total</th><th>Status</th><th>Paid On</th><th>Order</th>
</tr></thead><tbody>';

foreach ( $installments as $ins ) {
     $row_class = $ins->is_paid ? 'gmp-paid-row' : 'gmp-pending-row';
    echo '<tr class="' . esc_attr($row_class) . '">';
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

// === Summary + Actions ===
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

echo '<div class="gmp-two-col" style="margin-top:30px;">';

// Summary
echo '<div class="gmp-col"><h3>Summary</h3>';
echo '<table class="gmp-summary-table"><tbody>';
echo '<tr><td>Total EMI Paid</td><td>' . wc_price( $total_emi ) . '</td></tr>';
echo '<tr><td>Total Interest</td><td>' . wc_price( $total_interest ) . '</td></tr>';
echo '<tr><td><strong>Total Received</strong></td><td><strong>' . wc_price( $total_received ) . '</strong></td></tr>';
echo '<tr><td>Duration</td><td>' . esc_html( $start . ' - ' . $end ) . '</td></tr>';
echo '</tbody></table>';
echo '</div>';

// Actions
echo '<div class="gmp-col"><h3>Actions</h3>';
echo '<div class="gmp-admin-actions">';
if ( $cycle->status === 'active' ) {
    echo '<a href="#" class="button gmp-action-btn gmp-btn-close" data-cycle-id="' . esc_attr($cycle->id) . '" data-action="close">Mark as Closed</a>';
echo '<a href="#" class="button gmp-action-btn gmp-btn-cancel" data-cycle-id="' . esc_attr($cycle->id) . '" data-action="cancel">Cancel Cycle</a>';
echo '<a href="#" class="button gmp-action-btn gmp-btn-hold" data-cycle-id="' . esc_attr($cycle->id) . '" data-action="hold">Hold Future Payments</a>';

} elseif ( $cycle->status === 'hold' ) {
    echo '<a href="#" class="button gmp-action-btn gmp-btn-resume " data-cycle-id="' . esc_attr($cycle->id) . '" data-action="resume">Resume Payments</a>';
}else {
    echo '<p><em>This cycle is not active. Actions unavailable.</em></p>';
}

echo '</div></div>';

echo '</div>'; // gmp-two-col

echo '</div>'; // wrap
