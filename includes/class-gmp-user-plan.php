<?php

class GMP_User_Plan {
    public static function init() {
        // Hook into WooCommerce order completion
        add_action('woocommerce_order_status_completed', [__CLASS__, 'record_emi_payment']);
    }

    public static function record_emi_payment($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (has_term('gmp-plan', 'product_cat', $product->get_id())) {
                $emi = $item->get_total();
                $plan = get_user_meta($user_id, 'gmp_plan', true);
                if (!$plan) {
                    $plan = [
                        'emi' => $emi,
                        'duration' => 7, // default to 7, or read from product meta
                        'paid_months' => [],
                        'locked' => false,
                        'redeemed' => false,
                        'balance' => 0,
                    ];
                }

                $month = count($plan['paid_months']) + 1;
                $plan['paid_months'][] = ['month' => $month, 'amount' => $emi];
                $payments = get_user_meta($user_id, "gmp_payments_{$plan_id}", true) ?: [];
                $payments[] = [
                    'month' => $current_month,
                    'amount' => $emi_amount,
                    'date' => current_time('Y-m-d'),
                ];
                update_user_meta($user_id, "gmp_payments_{$plan_id}", $payments);

                update_user_meta($user_id, 'gmp_plan', $plan);
            }
        }
    }
}
