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
    echo '<h3>Gold Money Plans</h3>';
    echo '<p>No EMI cycles found. Choose a plan below to get started.</p>';

    $args = [
        'post_type' => 'product',
        'posts_per_page' => 12,
        'product_cat' => 'gmp-plan',
        'post_status' => 'publish'
    ];
    $loop = new WP_Query( $args );

    if ( $loop->have_posts() ) {
        echo '<div class="gmp-product-grid" style="display:flex; flex-wrap:wrap; gap:20px;">';

        while ( $loop->have_posts() ) {
            $loop->the_post();
            $product = wc_get_product( get_the_ID() );
            if ( ! $product ) continue;

            echo '<div class="gmp-grid-item" style="width:200px; border:1px solid #ddd; padding:10px; border-radius:6px;">';
            echo '<a href="' . get_permalink() . '">' . $product->get_image( 'woocommerce_thumbnail' ) . '</a>';
            echo '<h4 style="font-size:16px;">' . $product->get_name() . '</h4>';
            echo '<p style="margin:4px 0;">' . $product->get_price_html() . '</p>';
            echo '<a href="' . get_permalink() . '" class="button">View Plans</a>';
            echo '</div>';
        }

        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>No GMP plans available right now.</p>';
    }

    return;
}


echo '<table class="woocommerce-table gmp-list-table ux-table table table-striped table-hover">';
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

$status_badge = match ($cycle->status) {
    'active' => '<span class="badge-active">Active</span>',
    'closed' => '<span class="badge-closed">Closed</span>',
    'hold' => '<span class="badge-hold">On Hold</span>',
    'cancelled' => '<span class="badge-cancelled">Cancelled</span>',
    default => '<span>' . ucfirst($cycle->status) . '</span>',
};


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

   $thumb = $parent ? $parent->get_image( 'woocommerce_thumbnail', [ 'style' => 'width: 40px; height: auto; border-radius: 4px;' ] ) : '';


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

echo '<td data-label="Product">';
echo '<div class="gmp-product-flex" style="display:flex; align-items:center; gap:10px;">';
echo $thumb;
echo '<div style="line-height:1.4;">';
echo '<a href="' . esc_url($product_url) . '" style="font-weight:bold;">' . esc_html( $parent_name ) . '</a><br>';
echo '<small style="color:#666;">' . esc_html( $variation_label ) . '</small>';
echo '</div></div></td>';


    echo '<td data-label="Start">' . esc_html( $start ) . '</td>';
    echo '<td data-label="End">' . esc_html( $end ) . '</td>';
    echo '<td data-label="Status">' . $status_badge . '</td>';
    echo '<td data-label="Months">' . intval( $cycle->total_months ) . '</td>';
echo '<td data-label="Action"><a href="' . esc_url( $url ) . '" class="button button-small">View</a></td>';

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
