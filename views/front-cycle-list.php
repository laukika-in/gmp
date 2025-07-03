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
if ( ! isset( $_GET['view'] ) ) {

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
   $url = add_query_arg( 'view', $cycle->id, wc_get_account_endpoint_url( 'gold-money-plan' ) );


    echo '<tr>';

    if ( $product ) {
    $parent = wc_get_product( $product->get_parent_id() );
    $parent_name = $parent ? $parent->get_name() : '';
$attributes = [];
foreach ( $product->get_variation_attributes() as $key => $val ) {
    $taxonomy = str_replace( 'attribute_', '', $key );
    $term = get_term_by( 'slug', $val, $taxonomy );
    $attributes[] = $term ? $term->name : ucfirst( $val );
}
$variation_label = implode( ', ', $attributes );
$label = $parent_name . ( $variation_label ? ' - ' . $variation_label : '' );
    $link = add_query_arg( $attributes, get_permalink( $parent->get_id() ) );
    echo '<td><a href="' . esc_url($link) . '">' . esc_html( $label ) . '</a></td>';
} else {
    echo '<td>N/A</td>';
}


    echo '<td>' . esc_html( $cycle->start_date ) . '</td>';
    echo '<td>' . esc_html( ucfirst( $cycle->status ) ) . '</td>';
    echo '<td>' . intval( $cycle->total_months ) . '</td>';
    echo '<td><a href="' . esc_url( $url ) . '">View</a></td>';
    echo '</tr>';
}
echo '</tbody></table>';
}
if ( isset( $_GET['view'] ) ) {
    include GMP_PLUGIN_DIR . 'views/front-cycle-detail.php';
}
