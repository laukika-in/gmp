<?php
if (!defined('ABSPATH')) exit;

class GMP_Interest_Settings {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'save']);
    }

    public static function menu() {
        add_submenu_page(
            'woocommerce',
            'GMP Interest Settings',
            'GMP Interest',
            'manage_woocommerce',
            'gmp-interest',
            [__CLASS__, 'render']
        );
    }

    public static function save() {
        if (isset($_POST['gmp_interest_settings'])) {
            check_admin_referer('gmp_interest_save');
            update_option('gmp_interest_settings', $_POST['gmp_interest_settings']);
        }
    }

    public static function render() {
        $products = wc_get_products(['type' => 'variable', 'limit' => -1]);
        $settings = get_option('gmp_interest_settings', []);

        echo '<div class="wrap"><h1>GMP Interest Settings</h1><form method="post">';
        wp_nonce_field('gmp_interest_save');

        foreach ($products as $product) {
            $pid = $product->get_id();
            $base = $settings[$pid]['base'] ?? '';
            $ext = $settings[$pid]['ext'] ?? [];

            echo "<h2>{$product->get_name()}</h2>";
            echo "<p><label>Base Interest (%): <input name='gmp_interest_settings[{$pid}][base]' value='{$base}'></label></p>";

            echo "<p><label>Extension Interest (% comma separated): <input name='gmp_interest_settings[{$pid}][ext]' value='" . esc_attr(implode(',', $ext)) . "'></label></p>";
        }

        submit_button('Save Settings');
        echo '</form></div>';
    }
}
