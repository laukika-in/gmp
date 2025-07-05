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
    echo '<h3>Start Your Gold EMI Plan</h3>';

    $args = [
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'tax_query'      => [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => ['gmp-plan'],
            ]
        ],
    ];

    $loop = new WP_Query( $args );

    if ( $loop->have_posts() ) {
        echo '<div class="gmp-plans-grid">';

        while ( $loop->have_posts() ) {
            $loop->the_post();
            $product = wc_get_product( get_the_ID() );
            if ( ! $product || ! $product->is_type('variable') ) continue;

            $price = $product->get_variation_price( 'min' );
            $permalink = get_permalink( $product->get_id() );
            $thumbnail = $product->get_image( 'woocommerce_thumbnail', [ 'style' => 'max-width:100%; height:auto;' ] );

            echo '<div class="gmp-plan-card">';
            echo '<div class="gmp-card-left" style="background:#ffd700; padding:20px;">';
            echo $thumbnail;
            echo '<div style="font-size:26px; font-weight:bold; margin-top:10px;">' . wc_price( $price ) . '</div>';
            echo '<small>Per Month</small>';
            echo '</div>';

            echo '<div class="gmp-card-right" style="padding:20px;">';
            echo '<h3>' . esc_html( $product->get_name() ) . '</h3>';
            echo '<p><strong>Plan Type:</strong> Amount</p>';
            echo '<p><strong>Plan Duration:</strong> 10 Months</p>';
            echo '<p><strong>You Pay Per Month:</strong> ' . wc_price( $price ) . '</p>';
            echo '<p><strong>Total Amount You Pay:</strong> ' . wc_price( $price * 10 ) . '</p>';
            echo '<label><input type="checkbox"> Terms & Conditions</label><br>';
            echo '<a href="' . esc_url( $permalink ) . '" class="button gmp-buy-now" style="margin-top:10px;">Buy Now</a>';
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>No GMP Plans found. Please check back later.</p>';
    }

    return; // prevent rest of table from loading
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
