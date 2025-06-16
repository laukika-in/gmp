<?php

class GMP_WooCommerce {
    public static function init() {
        add_action('init', [__CLASS__, 'register_category']);

        // Restrict GMP product checkout to logged-in users
        add_action('template_redirect', [__CLASS__, 'require_login_for_gmp']);

        // Add and handle custom checkout fields
        add_action('woocommerce_after_order_notes', [__CLASS__, 'add_custom_checkout_fields']);
        add_action('woocommerce_checkout_process', [__CLASS__, 'validate_custom_checkout_fields']);
        add_action('woocommerce_checkout_update_order_meta', [__CLASS__, 'save_order_meta']);
        add_action('woocommerce_checkout_update_user_meta', [__CLASS__, 'save_user_meta']);
    }

    public static function register_category() {
        if (!term_exists('gmp-plan', 'product_cat')) {
            wp_insert_term('GMP Plan', 'product_cat', ['slug' => 'gmp-plan']);
        }
    }

    public static function require_login_for_gmp() {
        if (is_checkout() && WC()->cart) {
            foreach (WC()->cart->get_cart_contents() as $item) {
                if (has_term('gmp-plan', 'product_cat', $item['product_id']) && !is_user_logged_in()) {
                    wp_redirect(wp_login_url(wc_get_checkout_url()));
                    exit;
                }
            }
        }
    }

    public static function cart_has_gmp() {
        if (!WC()->cart) return false;

        foreach (WC()->cart->get_cart_contents() as $item) {
            if (has_term('gmp-plan', 'product_cat', $item['product_id'])) {
                return true;
            }
        }
        return false;
    }

    public static function add_custom_checkout_fields($checkout) {
        if (!self::cart_has_gmp()) return;

        echo '<div id="gmp_additional_fields"><h3>' . __('Gold Money Plan Details') . '</h3>';

        woocommerce_form_field('gmp_pan', [
            'type'     => 'file',
            'required' => true,
            'label'    => 'Upload PAN Card'
        ], '');

        woocommerce_form_field('gmp_aadhar', [
            'type'     => 'file',
            'required' => true,
            'label'    => 'Upload Aadhar Card'
        ], '');

        woocommerce_form_field('gmp_nominee_name', [
            'type'     => 'text',
            'required' => true,
            'label'    => 'Nominee Name'
        ], '');

        woocommerce_form_field('gmp_nominee_phone', [
            'type'     => 'text',
            'required' => true,
            'label'    => 'Nominee Phone Number'
        ], '');

        woocommerce_form_field('gmp_nominee_aadhar', [
            'type'     => 'file',
            'required' => true,
            'label'    => 'Upload Nominee Aadhar Card'
        ], '');

        echo '</div>';
    }

    public static function validate_custom_checkout_fields() {
        if (!self::cart_has_gmp()) return;

        if (empty($_FILES['gmp_pan']['name'])) wc_add_notice('Please upload your PAN card.', 'error');
        if (empty($_FILES['gmp_aadhar']['name'])) wc_add_notice('Please upload your Aadhar card.', 'error');
        if (empty($_POST['gmp_nominee_name'])) wc_add_notice('Please enter nominee name.', 'error');
        if (empty($_POST['gmp_nominee_phone'])) wc_add_notice('Please enter nominee phone.', 'error');
        if (empty($_FILES['gmp_nominee_aadhar']['name'])) wc_add_notice('Please upload nominee Aadhar.', 'error');
    }

    public static function save_order_meta($order_id) {
        if (!self::cart_has_gmp()) return;

        $uploads = [];

        // Upload files
        foreach (['gmp_pan', 'gmp_aadhar', 'gmp_nominee_aadhar'] as $field) {
            if (!empty($_FILES[$field]['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                $uploaded = wp_handle_upload($_FILES[$field], ['test_form' => false]);
                if (!isset($uploaded['error'])) {
                    $uploads[$field] = esc_url($uploaded['url']);
                }
            }
        }

        update_post_meta($order_id, 'gmp_pan', $uploads['gmp_pan'] ?? '');
        update_post_meta($order_id, 'gmp_aadhar', $uploads['gmp_aadhar'] ?? '');
        update_post_meta($order_id, 'gmp_nominee_name', sanitize_text_field($_POST['gmp_nominee_name']));
        update_post_meta($order_id, 'gmp_nominee_phone', sanitize_text_field($_POST['gmp_nominee_phone']));
        update_post_meta($order_id, 'gmp_nominee_aadhar', $uploads['gmp_nominee_aadhar'] ?? '');
    }

    public static function save_user_meta($user_id) {
        if (!self::cart_has_gmp()) return;

        update_user_meta($user_id, 'gmp_nominee_name', sanitize_text_field($_POST['gmp_nominee_name']));
        update_user_meta($user_id, 'gmp_nominee_phone', sanitize_text_field($_POST['gmp_nominee_phone']));
        // File uploads are only saved in order meta for security reasons
    }
}
