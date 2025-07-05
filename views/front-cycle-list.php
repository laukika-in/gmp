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

// =======================
// 🟡 CASE 1: NO CYCLES YET
// =======================
if ( empty( $cycles ) ) {
    $args = [
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'tax_query'      => [[
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => 'gmp-plan',
        ]],
    ];
    $products = wc_get_products( $args );

    foreach ( $products as $product ) {
        if ( ! $product->is_type( 'variable' ) ) continue;

        $variations = $product->get_children();
        $prices = array_map(function($vid) {
            $v = wc_get_product($vid);
            return $v ? floatval($v->get_price()) : 0;
        }, $variations);

        $min_price = min($prices);
        $max_price = max($prices);

        // Lock period from meta (or fallback)
        $lock_months = get_post_meta( $product->get_id(), '_gmp_lock_months', true ) ?: 10;

        $per_month = wc_price($min_price) . ($min_price != $max_price ? ' – ' . wc_price($max_price) : '');
        $total_pay = wc_price($min_price * $lock_months) . ($min_price != $max_price ? ' – ' . wc_price($max_price * $lock_months) : '');

        $product_url = get_permalink( $product->get_id() );

        echo '<div class="gmp-plan-card">';
        echo '<div class="gmp-card-left">';
        echo '<span class="gmp-price-big">' . wc_price($min_price) . '</span><br>';
        echo '<small>Per Month</small>';
        echo '</div>';
        echo '<div class="gmp-card-right">';
        echo '<h3>' . esc_html( $product->get_name() ) . '</h3>';
        echo '<p><strong>Plan Type:</strong> Amount</p>';
        echo '<p><strong>Plan Duration:</strong> ' . intval($lock_months) . ' Months</p>';
        echo '<p><strong>You Pay Per Month:</strong> ' . $per_month . '</p>';
        echo '<p><strong>Total Amount You Pay:</strong> ' . $total_pay . '</p>';
        echo '<label><input type="checkbox" id="terms-' . esc_attr($product->get_id()) . '"> Terms & Conditions</label>';
        echo '<a href="#" class="button gmp-buy-now" data-url="' . esc_url($product_url) . '" data-terms="#terms-' . esc_attr($product->get_id()) . '">Buy Now</a>';
        echo '</div></div>';
    }
}

// =======================
// ✅ CASE 2: HAS EMI CYCLES
// =======================

echo '<h3>My EMI Cycles</h3>';
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
    if ( ! $product ) continue;

    $start = date_i18n( 'j M Y', strtotime( $cycle->start_date ) );
    $end = date_i18n( 'j M Y', strtotime( "+".($cycle->total_months - 1)." months", strtotime( $cycle->start_date ) ) );
    $url = add_query_arg( 'view', $cycle->id, wc_get_account_endpoint_url( 'gold-money-plan' ) );

    $parent = wc_get_product( $product->get_parent_id() );
    $parent_name = $parent ? $parent->get_name() : '';
    $thumb = $parent ? $parent->get_image( 'woocommerce_thumbnail', [ 'style' => 'width: 40px; height: auto; border-radius: 4px;' ] ) : '';

    $attributes = [];
    foreach ( $product->get_variation_attributes() as $key => $val ) {
        $taxonomy = str_replace( 'attribute_', '', $key );
        $term = get_term_by( 'slug', $val, $taxonomy );
        $attributes[] = $term ? $term->name : ucfirst( $val );
    }
    $variation_label = implode( ', ', $attributes );

    $status_badge = match ($cycle->status) {
        'active' => '<span class="badge-active">Active</span>',
        'closed' => '<span class="badge-closed">Closed</span>',
        'hold' => '<span class="badge-hold">On Hold</span>',
        'cancelled' => '<span class="badge-cancelled">Cancelled</span>',
        default => '<span>' . ucfirst($cycle->status) . '</span>',
    };

    $row_class = $cycle->status === 'closed' ? 'gmp-row-closed' : 'gmp-row-active';

    echo '<tr class="' . esc_attr( $row_class ) . '">';
    echo '<td data-label="Product">';
    echo '<div class="gmp-product-flex" style="display:flex; align-items:center; gap:10px;">';
    echo $thumb;
    echo '<div style="line-height:1.4;">';
    echo '<a href="' . esc_url( get_permalink( $parent->get_id() ) ) . '" style="font-weight:bold;">' . esc_html( $parent_name ) . '</a><br>';
    echo '<small style="color:#666;">' . esc_html( $variation_label ) . '</small>';
    echo '</div></div></td>';

    echo '<td>' . esc_html( $start ) . '</td>';
    echo '<td>' . esc_html( $end ) . '</td>';
    echo '<td>' . $status_badge . '</td>';
    echo '<td>' . intval( $cycle->total_months ) . '</td>';
    echo '<td><a href="' . esc_url( $url ) . '" class="button button-small">View</a></td>';
    echo '</tr>';
}

echo '</tbody></table>';
?>
