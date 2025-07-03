<?php
class GMP_Cycle_Controller {

    public static function init() {
        add_action('woocommerce_thankyou', [__CLASS__, 'maybe_record_emi_payment'], 10, 1);
    }

    public static function maybe_record_emi_payment($order_id) {
        $order = wc_get_order($order_id);
        if (!$order || $order->get_status() !== 'processing') return;

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if (!has_term('gmp-plan', 'product_cat', $product_id)) continue;

            $variation_id = $item->get_variation_id();
            $user_id = $order->get_user_id();

            GMP_Cycle_Model::record_payment($user_id, $variation_id, $order_id);
        }
    }
}
