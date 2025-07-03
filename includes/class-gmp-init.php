<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Init {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_category' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );

        // Shortcode to list subscriptions (frontend)
        add_shortcode( 'gmp_my_subscriptions', [ 'GMP_Frontend', 'render_my_subscriptions' ] );
    }

    public static function register_category() {
        if ( ! term_exists( 'gmp-plan', 'product_cat' ) ) {
            wp_insert_term( 'GMP Plan', 'product_cat', [ 'slug' => 'gmp-plan' ] );
        }
    }

    public static function enqueue_assets() {
        wp_enqueue_style( 'gmp-style', plugins_url( '../assets/gmp-style.css', __FILE__ ), [], '1.0' );
    }
}
