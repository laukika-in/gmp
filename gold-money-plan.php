<?php
/**
 * Plugin Name: Gold Money Plan
 * Description: A WooCommerce-integrated EMI saving and redemption system with interest tracking and purchase discount.
 * Version: 1.0.26
 * Author: Your Name
 * Text Domain: gold-money-plan
 */

if (!defined('ABSPATH')) {
    exit;
} 

// Define constants
define( 'GMP_DIR', plugin_dir_path( __FILE__ ) );
define( 'GMP_URL', plugin_dir_url( __FILE__ ) );
define('GMP_VERSION', '1.0.26');  
// Autoload core files
require_once GMP_DIR . 'includes/class-gmp-init.php';
require_once GMP_DIR . 'includes/class-gmp-product-fields.php';
require_once GMP_DIR . 'includes/class-gmp-interest-settings.php';
require_once GMP_DIR . 'includes/class-gmp-emi-tracker.php';
require_once GMP_DIR . 'includes/class-gmp-order-handler.php';
require_once GMP_DIR . 'includes/class-gmp-admin-ui.php';
require_once GMP_DIR . 'includes/class-gmp-myaccount-ui.php';
require_once GMP_DIR . 'includes/db-schema.php';

// Initialize plugin
GMP_Init::init();
GMP_DB::create_table();
GMP_Product_Fields::init();
GMP_Interest_Settings::init();
GMP_Order_Handler::init();

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('gmp-style', plugins_url('assets/css/gmp-style.css', __FILE__));
});

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style('gmp-admin-style', plugins_url('assets/css/gmp-style.css', __FILE__));
});

 