<?php

class GMP_Init {
    public static function init() {
        // Load text domain
        load_plugin_textdomain('gold-money-plan', false, dirname(plugin_basename(__FILE__)) . '/../languages');

        // Enqueue assets
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);

        // Init hooks 
        GMP_WooCommerce::init();
        add_action('template_redirect', function () {
    if (is_checkout()) {
        ob_start(function ($content) {
            // Ensure we only replace the first instance of the form tag
            return preg_replace(
                '/<form([^>]*class="[^"]*checkout[^"]*"[^>]*)>/i',
                '<form$1 enctype="multipart/form-data">',
                $content,
                1
            );
        });
    }
});

    }

   public static function enqueue_assets() {
    wp_enqueue_style('gmp-style', GMP_PLUGIN_URL . 'assets/css/style.css', [], GMP_VERSION);
    wp_enqueue_script('gmp-script', GMP_PLUGIN_URL . 'assets/js/script.js', ['jquery'], GMP_VERSION, true);

    wp_localize_script('gmp-script', 'gmp_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('gmp_upload_nonce'),
    ]);
}

}
 