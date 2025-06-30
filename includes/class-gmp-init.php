<?php

class GMP_Init {
    public static function init() {
        // Load text domain for translations
        load_plugin_textdomain('gold-money-plan', false, dirname(plugin_basename(__FILE__)) . '/../languages');

        // Enqueue frontend assets
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);

        // Handle multipart/form for file uploads in checkout
        add_action('template_redirect', [__CLASS__, 'force_checkout_enctype']);

        // Init core classes
        GMP_WooCommerce::init();
        GMP_Settings::init();
        GMP_Renewal::init();

        // Init extension fields on product page
        add_action('woocommerce_product_options_general_product_data', ['GMP_Product_Fields', 'add']);
        add_action('woocommerce_process_product_meta', ['GMP_Product_Fields', 'save']);

        // Init interest tracking in related orders table
        add_filter('wcs_related_orders_table_row',       ['GMP_Interest_Meta', 'add_admin_column'], 10, 3);
        add_filter('wcs_my_subscriptions_related_orders_column_gmp_interest', ['GMP_Interest_Meta', 'get_column']);
        add_filter('wcs_my_subscriptions_related_orders_columns', function ($columns) {
            $columns['gmp_interest'] = __('Interest (₹)', 'gold-money-plan');
            return $columns;
        });
        add_filter('wcs_related_orders_table_header', function ($headers) {
            $headers['gmp_interest'] = __('Interest (₹)', 'gold-money-plan');
            return $headers;
        });

        // Log renewals (used for extension interest logic)
        add_action('woocommerce_checkout_order_processed', ['GMP_Renewal', 'record']);
    }

    public static function enqueue_assets() {
        wp_enqueue_style('gmp-style', GMP_PLUGIN_URL . 'assets/css/style.css', [], GMP_VERSION);
        wp_enqueue_script('gmp-script', GMP_PLUGIN_URL . 'assets/js/script.js', ['jquery'], GMP_VERSION, true);

        wp_localize_script('gmp-script', 'gmp_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('gmp_upload_nonce'),
        ]);
    }

    public static function force_checkout_enctype() {
        if (is_checkout()) {
            ob_start(function ($content) {
                return preg_replace(
                    '/<form([^>]*class="[^"]*checkout[^"]*"[^>]*)>/i',
                    '<form$1 enctype="multipart/form-data">',
                    $content,
                    1
                );
            });
        }
    }
}
