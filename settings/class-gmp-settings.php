<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Settings {

    /**
     * Return base + extension interest for a product
     */
    public static function get_interest_for_product( $product_id ) {
        $lock_months       = (int) get_post_meta( $product_id, '_gmp_lock_months', true );
        $extension_months  = (int) get_post_meta( $product_id, '_gmp_extension_months', true );
        $base_interest     = (float) get_post_meta( $product_id, '_gmp_base_interest', true );

        // Store extension interest as serialized array
        $extension_interest_raw = get_post_meta( $product_id, '_gmp_extension_interest', true );
        $extension_interest = is_array( $extension_interest_raw )
            ? $extension_interest_raw
            : maybe_unserialize( $extension_interest_raw );

        return [
            'lock_months'        => $lock_months,
            'extension_months'   => $extension_months,
            'base_interest'      => $base_interest,
            'extension_interest' => $extension_interest ?: [],
        ];
    }
}
