<?php foreach ( $subscription->get_items() as $item ) :
    $product = $item->get_product();
    $base_emi = $item->get_total() / $item->get_quantity();
    $product_id = $product->get_id();

    $interest_data = get_option('gmp_interest_settings', []);
    $interest_rate = $interest_data[$product_id]['base'] ?? 0;
    $emi_with_interest = $base_emi + ($base_emi * $interest_rate / 100);
?>
<tr>
    <td><?php echo esc_html($product->get_name()); ?></td>
    <td>₹<?php echo number_format($base_emi, 2); ?></td>
    <td><?php echo $interest_rate; ?>%</td>
    <td>₹<?php echo number_format($emi_with_interest, 2); ?></td>
</tr>

<?php endforeach; ?>
