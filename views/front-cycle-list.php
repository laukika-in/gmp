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

echo '<table class="woocommerce-table shop_table gmp-list-table">';
echo '<thead><tr>
    <th>Product</th>
    <th>Start</th>
    <th>End</th>
    <th>Status</th>
    <th>Months</th>
    <th>Action</th>
</tr></thead><tbody>';


foreach ( $cycles as $cycle ) {
$product = wc_get_product( $cycle->variation_id );
$start   = date_i18n( 'j M Y', strtotime( $cycle->start_date ) );
$end     = date_i18n( 'j M Y', strtotime( "+".($cycle->total_months - 1)." months", strtotime( $cycle->start_date ) ) );
$url = add_query_arg( 'view', $cycle->id, wc_get_account_endpoint_url( 'gold-money-plan' ) );

$status_badge = $cycle->status === 'closed' 
    ? '<span style="background:#d4edda; color:#155724; padding:3px 8px; border-radius:4px;">Closed</span>'
    : '<span style="background:#fff3cd; color:#856404; padding:3px 8px; border-radius:4px;">Active</span>';

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

    $thumb = $parent ? $parent->get_image( 'thumbnail', [ 'style' => 'width:40px; height:auto; border-radius:4px;' ] ) : '';

    // URL with variation preselected
    $variation_attrs = $product->get_attributes();
    $query_args = [];
    foreach ( $variation_attrs as $attr_name => $attr_value ) {
        $taxonomy = str_replace( 'attribute_', '', $attr_name );
        $query_args[ 'attribute_' . sanitize_title( $taxonomy ) ] = $attr_value;
    }
    $product_url = add_query_arg( $query_args, get_permalink( $parent->get_id() ) );

$row_class = $cycle->status === 'closed' ? 'gmp-row-closed' : 'gmp-row-active';
echo '<tr class="' . esc_attr( $row_class ) . '">';

    echo '<td style="display:flex; align-items:center; gap:10px;">' . $thumb . '<a href="' . esc_url($product_url) . '">' . esc_html( $label ) . '</a></td>';
    echo '<td>' . esc_html( $start ) . '</td>';
    echo '<td>' . esc_html( $end ) . '</td>';
    echo '<td>' . $status_badge . '</td>';
    echo '<td>' . intval( $cycle->total_months ) . '</td>';
echo '<td><a href="' . esc_url( $url ) . '" class="button button-small">View</a></td>';

    echo '</tr>';
} else {
    echo '<tr><td colspan="6">Invalid product</td></tr>';
}

}
echo '</tbody></table>';
}
if ( isset( $_GET['view'] ) ) {
    include GMP_PLUGIN_DIR . 'views/front-cycle-detail.php';
}
