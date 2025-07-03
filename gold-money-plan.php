<?php
/**
 * Plugin Name: Gold Money Plan
 * Description: Custom EMI-based investment plan using WooCommerce variable products.
 * Version: 2.0.2
 * Author: Your Name
 * Text Domain: gmp
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Dynamically fetch plugin version from header
$plugin_data = get_file_data( __FILE__, [ 'Version' => 'Version' ] );
define( 'GMP_PLUGIN_VERSION', $plugin_data['Version'] );

define( 'GMP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GMP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

register_activation_hook( __FILE__, [ 'GMP_Init', 'on_activate' ] );

add_action( 'plugins_loaded', [ 'GMP_Init', 'init' ] );
