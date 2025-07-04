<?php
$cycle_id = absint($_GET['cycle_id'] ?? 0);
if (!$cycle_id) {
    echo '<p>Missing cycle ID.</p>';
    return;
}

global $wpdb;
$cycle = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}gmp_cycles WHERE id = %d", $cycle_id
));
if (!$cycle) {
    echo '<p>Invalid cycle.</p>';
    return;
}

$installments = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}gmp_installments WHERE cycle_id = %d ORDER BY month_number ASC",
    $cycle_id
));

$product = wc_get_product($cycle->variation_id);
$parent  = $product ? wc_get_product($product->get_parent_id()) : null;
$thumb   = $parent ? $parent->get_image('thumbnail') : '';
$label   = $parent ? $parent->get_name() : 'N/A';

$attributes = [];
foreach ($product->get_variation_attributes() as $key => $val) {
    $taxonomy = str_replace('attribute_', '', $key);
    $term     = get_term_by('slug', $val, $taxonomy);
    $attributes[] = $term ? $term->name : ucfirst($val);
}
$variation = implode(', ', $attributes);

echo '<div class="wrap">';
echo '<div class="gmp-admin-header">';
echo '<div class="gmp-thumb">' . $thumb . '</div>';
echo '<div>';
echo '<h2>' . esc_html($label) . '</h2>';
echo '<p><small>' . esc_html($variation) . '</small></p>';
echo '<p>Status: <strong>' . ucfirst($cycle->status) . '</strong></p>';
echo '</div>';
echo '</div>';

echo '<table class="widefat gmp-admin-detail"><thead><tr>
    <th style="width:40px;">#</th>
    <th>Due</th>
    <th>EMI</th>
    <th>Interest</th>
    <th>Total</th>
    <th>Status</th>
    <th>Paid On</th>
    <th>Order</th>
</tr></thead><tbody>';

foreach ($installments as $ins) {
    echo '<tr>';
    echo '<td>' . $ins->month_number . '</td>';
    echo '<td>' . date_i18n('j M Y', strtotime($ins->due_date)) . '</td>';
    echo '<td>' . wc_price($ins->emi_amount) . '</td>';
    echo '<td>' . $ins->interest_rate . '%</td>';
    echo '<td>' . wc_price($ins->total_with_interest) . '</td>';
    echo '<td>' . ($ins->is_paid ? 'Paid' : 'Pending') . '</td>';
    echo '<td>' . ($ins->paid_date ? date_i18n('j M Y', strtotime($ins->paid_date)) : '—') . '</td>';
    echo '<td>' . ($ins->order_id ? '<a href="' . esc_url(get_edit_post_link($ins->order_id)) . '">#' . $ins->order_id . '</a>' : '—') . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div>';
