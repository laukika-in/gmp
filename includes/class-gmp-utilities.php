<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Utilities {

    public static function is_gmp_product( $product_id ) {
        return has_term( 'gmp-plan', 'product_cat', $product_id );
    }

    public static function get_interest_settings( $product_id ) {
        $settings = get_option( 'gmp_interest_settings', [] );
        $config = isset( $settings[$product_id] ) ? $settings[$product_id] : [ 'base' => 0, 'ext' => [] ];
        $base = floatval( $config['base'] );
        $ext = is_array( $config['ext'] ) ? $config['ext'] : explode(',', $config['ext']);
        $ext = array_map( 'floatval', $ext );
        return [ 'base' => $base, 'ext' => $ext ];
    }

    public static function get_lock_and_extension( $variation_id ) {
        $lock = intval( get_post_meta( $variation_id, '_gmp_lock_period', true ) );
        $ext  = intval( get_post_meta( $variation_id, '_gmp_extension_months', true ) );
        return [ 'lock' => $lock, 'ext' => $ext ];
    }

    public static function get_schedule_range( $start_date, $lock, $ext ) {
        $dates = [];
        $current = new DateTime( $start_date );
        $total = $lock + $ext;
        for ( $i = 0; $i < $total; $i++ ) {
            $dates[] = $current->format( 'Y-m-d' );
            $current->modify( '+1 month' );
        }
        return $dates;
    }

    public static function format_money( $amount ) {
        return wc_price( $amount );
    }

}
