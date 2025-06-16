<?php

class GMP_Discount {
    public static function init() {
        add_action('woocommerce_cart_calculate_fees', [__CLASS__, 'apply_gmp_discount']);
    }

    public static function apply_gmp_discount($cart) {
        if (is_admin() || !is_user_logged_in() || is_ajax()) {
            return;
        }

        $user_id = get_current_user_id();
        $plan = get_user_meta($user_id, 'gmp_plan', true);

        if (empty($plan['balance']) || !$plan['redeemed']) {
            return;
        }

        $balance = $plan['balance'];
        $total = $cart->get_total('edit');

        $discount = min($total, $balance);
        if ($discount > 0) {
            $cart->add_fee('GMP Balance Discount', -$discount);
            $plan['balance'] -= $discount;
            update_user_meta($user_id, 'gmp_plan', $plan);
        }
    }
}
