<?php

class GMP_WooCommerce {
    public static function init() {
        // Ensure 'gmp-plan' category exists
        add_action('init', [__CLASS__, 'register_category']);
    }

    public static function register_category() {
        if (!term_exists('gmp-plan', 'product_cat')) {
            wp_insert_term('GMP Plan', 'product_cat', ['slug' => 'gmp-plan']);
        }
    }
}
