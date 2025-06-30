<?php
/**
 * Plugin Name: Gold Money Plan
 * Description: A WooCommerce-integrated EMI saving and redemption system with interest tracking and purchase discount.
 * Version: 1.0.12
 * Author: Your Name
 * Text Domain: gold-money-plan
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GMP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GMP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GMP_VERSION', '1.0.08');

// Autoload core classes
require_once GMP_PLUGIN_PATH . 'includes/class-gmp-init.php'; 
require_once GMP_PLUGIN_PATH . 'includes/class-gmp-woocommerce.php';
require_once GMP_PLUGIN_PATH . 'includes/helper-functions.php';
require_once GMP_PLUGIN_PATH . 'includes/class-gmp-settings.php';
require_once GMP_PLUGIN_PATH . 'includes/class-gmp-renewal.php';
require_once GMP_PLUGIN_PATH . 'includes/class-gmp-interest-tables.php';

// Initialize
add_action('plugins_loaded', ['GMP_Init', 'init']);