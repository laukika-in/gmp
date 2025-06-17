<?php
// This hook checks if the same subscription product is being re-purchased and treats it as a renewal
add_action('woocommerce_checkout_create_order_line_item', 'gmp_handle_subscription_renewal_logic', 10, 4);
function gmp_handle_subscription_renewal_logic($item, $cart_item_key, $values, $order) {
    $product = $values['data'];
    if (!$product->is_type('subscription') && !$product->is_type('variable-subscription')) return;

    $user_id = get_current_user_id();
    $product_id = $product->get_id();
    $variation_id = $values['variation_id'] ?? 0;

    $key = 'gmp_manual_subscription_' . $product_id;
    if ($variation_id) {
        $key .= '_' . $variation_id;
    }

    $history = get_user_meta($user_id, $key, true) ?: [];

    $history[] = [
        'order_id' => $order->get_id(),
        'amount' => $item->get_total(),
        'date' => current_time('mysql'),
    ];

    update_user_meta($user_id, $key, $history);
}
?>
