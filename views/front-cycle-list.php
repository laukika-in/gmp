<?php
if ( ! is_user_logged_in() ) {
    echo '<p>You must be logged in to view your EMI cycles.</p>';
    return;
}

global $wpdb;
$user_id = get_current_user_id();
$cycles_table = $wpdb->prefix . 'gmp_cycles';

$cycles = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM $cycles_table WHERE user_id = %d ORDER BY id DESC", $user_id
) );

echo '<h3>My EMI Cycles</h3>';
if ( empty( $cycles ) ) {
    echo '<p>No EMI cycles found.</p>';
    return;
}

echo '<table class="woocommerce-table woocommerce-table--orders shop_table shop_table_responsive">';
echo '<thead><tr>
    <th>Product</th>
    <th>Start Date</th>
    <th>Status</th>
    <th>Months</th>
    <th>Action</th>
</tr></thead><tbody>';

foreach ( $cycles as $cycle ) {
    $product = wc_get_product( $cycle->variation_id );
    $url = wc_get_account_endpoint_url( 'gmp-cycles' ) . '?view=' . $cycle->id;

    echo '<tr>';
    echo '<td>' . esc_html( $product ? $product->get_name() : 'N/A' ) . '</td>';
    echo '<td>' . esc_html( $cycle->start_date ) . '</td>';
    echo '<td>' . esc_html( ucfirst( $cycle->status ) ) . '</td>';
    echo '<td>' . intval( $cycle->total_months ) . '</td>';
    echo '<td><a href="' . esc_url( $url ) . '">View</a></td>';
    echo '</tr>';
}
echo '</tbody></table>';

if ( isset( $_GET['view'] ) ) {
    include GMP_PLUGIN_DIR . 'views/front-cycle-detail.php';
}
