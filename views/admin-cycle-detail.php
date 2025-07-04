
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

$user = get_userdata( $cycle->user_id );
$product = wc_get_product( $cycle->variation_id );
$parent = wc_get_product( $product ? $product->get_parent_id() : 0 );

$parent_name = $parent ? $parent->get_name() : '';
$thumb = $parent ? $parent->get_image( 'woocommerce_thumbnail' ) : '';
$variation_label = implode( ', ', array_map(function($val, $key) {
    $taxonomy = str_replace( 'attribute_', '', $key );
    $term = get_term_by( 'slug', $val, $taxonomy );
    return $term ? $term->name : ucfirst( $val );
}, $product->get_variation_attributes(), array_keys($product->get_variation_attributes())));

echo '<div class="wrap">';
echo '<h1 class="gmp-toolbar"><a href="' . admin_url( 'admin.php?page=gmp-cycles' ) . '" class="button">← Back to List</a></h1>';

echo '<div class="gmp-meta-section"><div class="gmp-col">';
echo '<h2>User Details</h2>';
echo '<p><strong>Name:</strong> ' . esc_html($user->display_name) . '</p>';
echo '<p><strong>Email:</strong> ' . esc_html($user->user_email) . '</p>';
echo '<p><strong>User ID:</strong> ' . intval($user->ID) . '</p>';
echo '</div><div class="gmp-col">';
echo '<h2>Product Details</h2>';
echo '<div style="display:flex;align-items:center;gap:15px;">';
echo '<div>' . $thumb . '</div>';
echo '<div>';
echo '<p><strong>' . esc_html( $parent_name ) . '</strong></p>';
echo '<p>' . esc_html( $variation_label ) . '</p>';
echo '</div></div>';
echo '</div></div>';

// Installments Table
echo '<table class="wp-list-table widefat fixed striped"><thead><tr>
    <th style="width:50px;">#</th><th>Due</th><th>EMI</th><th>Interest</th><th>Total</th><th>Status</th><th>Paid On</th><th>Order</th>
</tr></thead><tbody>';

$total_emi = 0; $total_interest = 0;

foreach ( $installments as $ins ) {
    echo '<tr>';
    echo '<td>' . $ins->month_number . '</td>';
    echo '<td>' . date_i18n('j M Y', strtotime($ins->due_date)) . '</td>';
    echo '<td>' . wc_price( $ins->emi_amount ) . '</td>';
    echo '<td>' . $ins->interest_rate . '%</td>';
    echo '<td>' . wc_price( $ins->total_with_interest ) . '</td>';

    if ( $ins->is_paid ) {
        $paid_on = $ins->paid_date ? date_i18n('j M Y', strtotime($ins->paid_date)) : '—';
        $order_link = $ins->order_id ? '<a href="' . esc_url( get_edit_post_link($ins->order_id) ) . '">#' . $ins->order_id . '</a>' : '—';
        echo '<td style="color:green;"><strong>Paid</strong></td>';
        echo '<td>' . $paid_on . '</td>';
        echo '<td>' . $order_link . '</td>';
        $total_emi += floatval( $ins->emi_amount );
        $total_interest += floatval( $ins->total_with_interest ) - floatval( $ins->emi_amount );
    } else {
        echo '<td style="color:#999;">Pending</td><td>—</td><td>—</td>';
    }
    echo '</tr>';
}

echo '</tbody></table>';

// Summary + Actions
$total_paid = $total_emi + $total_interest;
$end_date = date_i18n( 'j M Y', strtotime( "+".($cycle->total_months - 1)." months", strtotime( $cycle->start_date ) ) );

echo '<div class="gmp-summary-actions">';
echo '<div class="gmp-col">';
echo '<h3>Summary</h3>';
echo '<table class="widefat"><tbody>';
echo '<tr><td>Total EMI Paid</td><td>' . wc_price( $total_emi ) . '</td></tr>';
echo '<tr><td>Total Interest</td><td>' . wc_price( $total_interest ) . '</td></tr>';
echo '<tr><td><strong>Total Received</strong></td><td><strong>' . wc_price( $total_paid ) . '</strong></td></tr>';
echo '<tr><td>Duration</td><td>' . date_i18n('j M Y', strtotime($cycle->start_date)) . ' - ' . $end_date . '</td></tr>';
echo '</tbody></table>';
echo '</div><div class="gmp-col">';
echo '<h3>Actions</h3>';
echo '<p><a href="#" class="button">Mark as Closed</a></p>';
echo '<p><a href="#" class="button">Cancel Cycle</a></p>';
echo '<p><a href="#" class="button">Stop Future EMIs</a></p>';
echo '</div></div></div>';
