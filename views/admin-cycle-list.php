<?php
global $wpdb;
$cycles = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gmp_cycles ORDER BY id DESC LIMIT 100" );

echo '<div class="wrap"><h1>GMP EMI Cycles</h1>';
echo '<table class="wp-list-table widefat fixed striped gmp-admin-table"><thead><tr>
    <th>ID</th><th>User</th><th>Product</th><th>Start</th><th>Status</th><th>Action</th>
</tr></thead><tbody>';

foreach ( $cycles as $cycle ) {
    $user    = get_userdata( $cycle->user_id );
    $product = wc_get_product( $cycle->variation_id );
    $parent  = $product ? wc_get_product( $product->get_parent_id() ) : null;
    $thumb   = $parent ? $parent->get_image( 'thumbnail', ['style' => 'width:40px;height:auto;border-radius:4px'] ) : '';
    $url     = admin_url( 'admin.php?page=gmp-cycle-detail&cycle_id=' . $cycle->id );

    $attributes = [];
    if ( $product ) {
        foreach ( $product->get_variation_attributes() as $key => $val ) {
            $taxonomy = str_replace( 'attribute_', '', $key );
            $term = get_term_by( 'slug', $val, $taxonomy );
            $attributes[] = $term ? $term->name : ucfirst( $val );
        }
    }

    $variation = implode(', ', $attributes);
    $label     = $parent ? $parent->get_name() : '';
    $status    = ucfirst($cycle->status);

    echo '<tr>';
    echo '<td>' . esc_html($cycle->id) . '</td>';
    echo '<td style="min-width:120px;">' . esc_html($user ? $user->display_name : 'N/A') . '</td>';
    echo '<td>';
    echo '<div class="gmp-admin-product">';
    echo $thumb;
    echo '<div><strong>' . esc_html($label) . '</strong><br>';
    echo '<small>' . esc_html($variation) . '</small></div>';
    echo '</div></td>';
    echo '<td>' . date_i18n( 'j M Y', strtotime($cycle->start_date) ) . '</td>';
    echo '<td><span class="gmp-status gmp-status-' . esc_attr(strtolower($status)) . '">' . esc_html($status) . '</span></td>';
    echo '<td><a href="' . esc_url($url) . '" class="button button-small">View</a></td>';
    echo '</tr>';
}
echo '</tbody></table></div>';
