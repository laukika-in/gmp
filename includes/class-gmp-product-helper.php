<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Product_Helper {

    /**
     * Check if product (or variation) belongs to GMP category
     */
    public static function is_gmp_product( $product_id ) {
        return has_term( 'gmp-plan', 'product_cat', $product_id );
    }

    /**
     * Get parent product ID if variation
     */
    public static function get_parent_product_id( $product_id ) {
        $product = wc_get_product( $product_id );
        return $product && $product->is_type( 'variation' ) ? $product->get_parent_id() : $product_id;
    }

    /**
     * Get variation price
     */
    public static function get_variation_price( $variation_id ) {
        $product = wc_get_product( $variation_id );
        return $product ? floatval( $product->get_price() ) : 0;
    }
}
