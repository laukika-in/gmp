<?php

class GMP_Init {
    public static function init() {
        // Load text domain
        load_plugin_textdomain('gold-money-plan', false, dirname(plugin_basename(__FILE__)) . '/../languages');

        // Enqueue assets
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);

        // Init hooks
        GMP_Plans::init();
        GMP_User_Plan::init();
        GMP_Admin_UI::init();
        GMP_WooCommerce::init();
        GMP_Redeem::init();
        GMP_Discount::init();
    }

    public static function enqueue_assets() {
        wp_enqueue_style('gmp-style', GMP_PLUGIN_URL . 'assets/css/style.css', [], GMP_VERSION);
        wp_enqueue_script('gmp-script', GMP_PLUGIN_URL . 'assets/js/script.js', ['jquery'], GMP_VERSION, true);
    }
}
add_shortcode('gmp_dashboard', function () {
    ob_start();
    include GMP_PLUGIN_PATH . 'templates/dashboard.php';
    return ob_get_clean();
});
