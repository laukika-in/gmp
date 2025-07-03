<?php
/**
 * Plugin Name: Gold Money Plan
 * Description: Custom EMI-based investment plugin using WooCommerce product variations.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: gold-money-plan
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GMP_EMI_VERSION', '1.0' );
define( 'GMP_EMI_PATH', plugin_dir_path( __FILE__ ) );
define( 'GMP_EMI_URL', plugin_dir_url( __FILE__ ) );

require_once GMP_EMI_PATH . 'includes/class-gmp-init.php';

add_action( 'plugins_loaded', ['GMP_Init', 'init'] );
