<?php
global $wpdb;
$cycles = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gmp_cycles ORDER BY id DESC LIMIT 100");

wp_enqueue_style('gmp-admin-style', GMP_PLUGIN_URL . 'assets/css/gmp-admin.css', [], GMP_PLUGIN_VERSION);

echo '<div class="wrap"><h1>GMP EMI Cycles</h1>';
echo '<table class="wp-list-table widefat fixed striped gmp-admin-table"><thead><tr>
    <th>ID</th><th>User</th><th>Product</th><th>Start</th><th>End</th><th>Status</th><th>Actions</th>
</tr></thead><tbody>';

foreach ( $cycles as $cycle ) {
    $user    = get_userdata( $cycle->user_id );
    $product = wc_get_product( $cycle->variation_id );
    $url     = admin_url( 'admin.php?page=gmp-cycle-detail&cycle_id=' . $cycle->id );

    $parent = wc_get_product( $product ? $product->get_parent_id() : 0 );
    $parent_name = $parent ? $parent->get_name() : '';
    $variation_name = $product ? implode(', ', array_map(function($k, $v) {
        $taxonomy = str_replace('attribute_', '', $k);
        $term = get_term_by('slug', $v, $taxonomy);
        return $term ? $term->name : $v;
    }, array_keys($product->get_variation_attributes()), $product->get_variation_attributes())) : '';

    $thumb = $parent ? $parent->get_image('thumbnail', [ 'style' => 'width: 40px; height:auto; border-radius:4px;' ]) : '';
    $start = date_i18n( 'j M Y', strtotime( $cycle->start_date ) );
    $end   = date_i18n( 'j M Y', strtotime( "+".($cycle->total_months - 1)." months", strtotime( $cycle->start_date ) ) );

    $badge = $cycle->status === 'closed'
        ? '<span class="badge badge-closed">Closed</span>'
        : '<span class="badge badge-active">Active</span>';

    echo '<tr>';
    echo '<td>' . $cycle->id . '</td>';
    echo '<td>' . esc_html( $user ? $user->display_name : '—' ) . '</td>';
    echo '<td><div class="gmp-flex">' . $thumb . '<div><strong>' . esc_html($parent_name) . '</strong><br><small>' . esc_html($variation_name) . '</small></div></div></td>';
    echo '<td>' . $start . '</td>';
    echo '<td>' . $end . '</td>';
    echo '<td>' . $badge . '</td>';
    echo '<td><a href="' . esc_url($url) . '" class="button">View</a></td>';
    echo '</tr>';
}
echo '</tbody></table></div>';
