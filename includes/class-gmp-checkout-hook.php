<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Checkout_Hook {

    public static function init() {
        add_action( 'woocommerce_checkout_order_processed', [ __CLASS__, 'maybe_track_emi_purchase' ], 20, 1 );
    }

    public static function maybe_track_emi_purchase( $order_id ) {
        $order = wc_get_order( $order_id );
        $user_id = $order->get_user_id();

        foreach ( $order->get_items() as $item ) {
            $product_id   = $item->get_product_id();
            $variation_id = $item->get_variation_id();

            if ( ! GMP_Product_Helper::is_gmp_product( $product_id ) ) {
                continue;
            }

            // Check if an active cycle already exists for this user + variation
            global $wpdb;
            $cycle = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}gmp_cycles
                 WHERE user_id = %d AND variation_id = %d AND status = 'active'
                 ORDER BY id DESC LIMIT 1",
                $user_id, $variation_id
            ) );

            if ( $cycle ) {
                GMP_EMI_Cycle::mark_installment_paid( $cycle->id, $order_id );
            } else {
                // Fetch interest settings from settings table (from GMP_Settings)
                $settings = GMP_Settings::get_interest_for_product( $product_id );

                GMP_EMI_Cycle::create_cycle(
                    $user_id,
                    $product_id,
                    $variation_id,
                    $settings['lock_months'],
                    $settings['extension_months'],
                    $settings['base_interest'],
                    $settings['extension_interest'] ?? []
                );

                // Mark first installment as paid
                $cycle_id = $wpdb->insert_id;
                GMP_EMI_Cycle::mark_installment_paid( $cycle_id, $order_id );
            }
        }
    }
}
