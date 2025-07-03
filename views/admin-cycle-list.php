<?php
global $wpdb;
$cycles = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gmp_cycles ORDER BY id DESC LIMIT 100" );

echo '<div class="wrap"><h1>GMP EMI Cycles</h1>';
echo '<table class="wp-list-table widefat fixed striped"><thead><tr>
    <th>ID</th><th>User</th><th>Product</th><th>Start Date</th><th>Status</th><th>Actions</th>
</tr></thead><tbody>';

foreach ( $cycles as $cycle ) {
    $user = get_userdata( $cycle->user_id );
    $product = wc_get_product( $cycle->variation_id );
    $url = admin_url( 'admin.php?page=gmp-cycle-detail&cycle_id=' . $cycle->id );

    echo '<tr>';
    echo '<td>' . $cycle->id . '</td>';
    echo '<td>' . esc_html( $user ? $user->display_name : 'N/A' ) . '</td>';
    echo '<td>' . esc_html( $product ? $product->get_name() : 'N/A' ) . '</td>';
    echo '<td>' . $cycle->start_date . '</td>';
    echo '<td>' . ucfirst( $cycle->status ) . '</td>';
    echo '<td><a href="' . esc_url( $url ) . '">View</a></td>';
    echo '</tr>';
}
echo '</tbody></table></div>';
