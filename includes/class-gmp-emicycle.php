<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_EMI_Cycle {

    /**
     * Create a new EMI cycle for a user + product variation
     */
    public static function create_cycle( $user_id, $product_id, $variation_id, $lock_months, $extension_months, $base_interest, $extension_interest ) {
        global $wpdb;
        $cycles_table = $wpdb->prefix . 'gmp_cycles';
        $installments_table = $wpdb->prefix . 'gmp_installments';

        $total_months = $lock_months + $extension_months;
        $emi_amount   = GMP_Product_Helper::get_variation_price( $variation_id );
        $start_date   = current_time( 'Y-m-d' );

        $wpdb->insert( $cycles_table, [
            'user_id'           => $user_id,
            'product_id'        => $product_id,
            'variation_id'      => $variation_id,
            'start_date'        => $start_date,
            'status'            => 'active',
            'total_months'      => $total_months,
            'lock_months'       => $lock_months,
            'extension_months'  => $extension_months,
            'base_interest'     => $base_interest,
        ] );

        $cycle_id = $wpdb->insert_id;

        // Insert installments
        for ( $i = 1; $i <= $total_months; $i++ ) {
            $interest_rate = $i <= $lock_months
                ? $base_interest
                : ( isset( $extension_interest[ $i - $lock_months ] ) ? $extension_interest[ $i - $lock_months ] : $base_interest );

            $interest_amount = ( $interest_rate / 100 ) * $emi_amount;
            $total_due       = $emi_amount + $interest_amount;
            $due_date        = date( 'Y-m-d', strtotime( "+".($i-1)." months", strtotime( $start_date ) ) );

            $wpdb->insert( $installments_table, [
                'cycle_id'            => $cycle_id,
                'month_number'        => $i,
                'due_date'            => $due_date,
                'emi_amount'          => $emi_amount,
                'interest_rate'       => $interest_rate,
                'total_with_interest' => $total_due,
                'is_paid'             => 0,
            ] );
        }

        return $cycle_id;
    }

    /**
     * Mark installment as paid
     */
    public static function mark_installment_paid( $cycle_id, $order_id ) {
        global $wpdb;
        $installments_table = $wpdb->prefix . 'gmp_installments';

        // Get first unpaid installment for this cycle
        $installment = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $installments_table WHERE cycle_id = %d AND is_paid = 0 ORDER BY month_number ASC LIMIT 1",
            $cycle_id
        ) );

        if ( $installment ) {
            $wpdb->update( $installments_table, [
                'is_paid'   => 1,
                'order_id'  => $order_id,
                'paid_date' => current_time( 'mysql' ),
            ], [
                'id' => $installment->id,
            ] );

            self::maybe_close_cycle( $cycle_id );
        }
    }

    /**
     * Close the EMI cycle if all installments are paid
     */
    public static function maybe_close_cycle( $cycle_id ) {
        global $wpdb;
        $installments_table = $wpdb->prefix . 'gmp_installments';
        $cycles_table = $wpdb->prefix . 'gmp_cycles';

        $remaining = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $installments_table WHERE cycle_id = %d AND is_paid = 0",
            $cycle_id
        ) );

        if ( ! $remaining ) {
            $wpdb->update( $cycles_table, [ 'status' => 'closed' ], [ 'id' => $cycle_id ] );
        }
    }
}
