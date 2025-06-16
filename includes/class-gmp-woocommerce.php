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
   add_action('woocommerce_before_checkout_form', function () {
    ob_start(function ($content) {
        return str_replace(
            '<form method="post" class="checkout',
            '<form method="post" enctype="multipart/form-data" class="checkout',
            $content
        );
    });
});

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
                // Redirect to My Account page instead of wp-login.php
                $login_page = wc_get_page_permalink('myaccount');
                wp_redirect($login_page . '?redirect_to=' . urlencode(wc_get_checkout_url()));
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

    // File upload: PAN
    echo '<p class="form-row form-row-wide">
        <label for="gmp_pan">Upload PAN Card <span class="required">*</span></label>
        <input type="file" name="gmp_pan" id="gmp_pan" accept=".jpg,.jpeg,.png,.pdf" required>
    </p>';

    // File upload: Aadhar
    echo '<p class="form-row form-row-wide">
        <label for="gmp_aadhar">Upload Aadhar Card <span class="required">*</span></label>
        <input type="file" name="gmp_aadhar" id="gmp_aadhar" accept=".jpg,.jpeg,.png,.pdf" required>
    </p>';

    // Text fields
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

    // File upload: Nominee Aadhar
    echo '<p class="form-row form-row-wide">
        <label for="gmp_nominee_aadhar">Upload Nominee Aadhar <span class="required">*</span></label>
        <input type="file" name="gmp_nominee_aadhar" id="gmp_nominee_aadhar" accept=".jpg,.jpeg,.png,.pdf" required>
    </p>';

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
