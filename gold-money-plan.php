<?php
/**
 * Plugin Name: Gold Money Plan
 * Description: Custom EMI-based gold investment plugin for WooCommerce.
 * Version: 2.0.28
 * Author: Your Name
 * Text Domain: gmp
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$plugin_data = get_file_data( __FILE__, [ 'Version' => 'Version' ] );
define( 'GMP_PLUGIN_VERSION', $plugin_data['Version'] );
define( 'GMP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GMP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ✅ MUST load this before using GMP_Init in register_activation_hook
require_once GMP_PLUGIN_DIR . 'includes/class-gmp-init.php';
require_once GMP_PLUGIN_DIR . 'includes/class-gmp-db.php'; // this is called inside on_activate()

// ✅ Register activation hook only AFTER class is loaded
register_activation_hook( __FILE__, [ 'GMP_Init', 'on_activate' ] );

// ✅ Normal plugin init
add_action( 'plugins_loaded', [ 'GMP_Init', 'init' ] );
