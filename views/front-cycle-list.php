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
       $thumb = $product->get_image( 'woocommerce_thumbnail', [
    'style' => 'max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);',
    'alt'   => $product->get_name()
] );

echo '<div class="gmp-card-left">';
echo $thumb;
echo '</div>';

        echo '<div class="gmp-card-right">';
        echo '<h3>' . esc_html( $product->get_name() ) . '</h3>';
        echo '<p><strong>Plan Type:</strong> Amount</p>';
        echo '<p><strong>Plan Duration:</strong> ' . intval($lock_months) . ' Months</p>';
        echo '<p><strong>You Pay Per Month:</strong> ' . $per_month . '</p>';
        echo '<p><strong>Total Amount You Pay:</strong> ' . $total_pay . '</p>';
       echo '<label><input type="checkbox" id="terms-' . esc_attr($product->get_id()) . '"> ';
echo '<a href="#" class="gmp-terms-link" data-url="/sample-page/">Terms & Conditions</a></label>';

        echo '<a href="#" class="button gmp-buy-now" data-url="' . esc_url($product_url) . '" data-terms="#terms-' . esc_attr($product->get_id()) . '">Buy Now</a>';
        echo '</div></div>';
    }
}

// =======================
// ✅ CASE 2: HAS EMI CYCLES
// =======================
if ( !empty( $cycles ) ) {
    echo '<h3>My EMI Cycles</h3>';
    echo '<div class="gmp-cycle-grid">';

    foreach ( $cycles as $cycle ) {
        $product = wc_get_product( $cycle->variation_id );
        if ( ! $product ) continue;

        $start = date_i18n( 'j M Y', strtotime( $cycle->start_date ) );
        $end = date_i18n( 'j M Y', strtotime( "+".($cycle->total_months - 1)." months", strtotime( $cycle->start_date ) ) );
        $url = add_query_arg( 'view', $cycle->id, wc_get_account_endpoint_url( 'gold-money-plan' ) );

        $parent = wc_get_product( $product->get_parent_id() );
        $parent_name = $parent ? $parent->get_name() : '';
        $thumb = $parent ? $parent->get_image( 'woocommerce_thumbnail', [ 'style' => 'width: 100%; height: auto; border-radius: 6px;' ] ) : '';

        $attributes = [];
        foreach ( $product->get_variation_attributes() as $key => $val ) {
            $taxonomy = str_replace( 'attribute_', '', $key );
            $term = get_term_by( 'slug', $val, $taxonomy );
            $attributes[] = $term ? $term->name : ucfirst( $val );
        }
        $variation_label = implode( ', ', $attributes );

        $status_badge = match ($cycle->status) {
            'active' => '<span class="badge badge-active">Active</span>',
            'closed' => '<span class="badge badge-closed">Closed</span>',
            'hold' => '<span class="badge badge-hold">On Hold</span>',
            'cancelled' => '<span class="badge badge-cancelled">Cancelled</span>',
            default => '<span class="badge">' . ucfirst($cycle->status) . '</span>',
        };

        echo '<div class="gmp-cycle-card">';
        echo '<div class="gmp-card-thumb">' . $thumb . '</div>';
        echo '<div class="gmp-card-body">';
        echo '<h4><a href="' . esc_url( get_permalink( $parent->get_id() ) ) . '">' . esc_html( $parent_name ) . '</a></h4>';
        echo '<small style="color:#666;">' . esc_html( $variation_label ) . '</small>';
        echo '<p><strong>Status:</strong> ' . $status_badge . '</p>';
        echo '<p><strong>Duration:</strong> ' . esc_html( $start ) . ' – ' . esc_html( $end ) . '</p>';
        echo '<p><strong>Months:</strong> ' . intval( $cycle->total_months ) . '</p>';
        echo '<a href="' . esc_url( $url ) . '" class="button gmp-view-btn">View Details</a>';
        echo '</div></div>';
    }

    echo '</div>'; // close .gmp-cycle-grid
}

?>
