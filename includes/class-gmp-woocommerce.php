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
        add_action('template_redirect', [__CLASS__, 'force_enctype']);


    }
public static function force_enctype() {
    if (is_checkout()) {
        ob_start(function ($content) {
            return str_replace(
                '<form method="post" class="checkout',
                '<form method="post" enctype="multipart/form-data" class="checkout',
                $content
            );
        });
    }
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

    echo '<div id="gmp_additional_fields"><h3>Gold Money Plan Details</h3>';

    echo '<p class="form-row form-row-wide">
        <label for="gmp_pan">Upload PAN Card <span class="required">*</span></label>
        <input type="file" id="gmp_pan_upload" accept=".jpg,.jpeg,.png,.pdf" required>
        <input type="hidden" name="gmp_pan_url" id="gmp_pan_url">
    </p>';

    echo '<p class="form-row form-row-wide">
        <label for="gmp_aadhar">Upload Aadhar Card <span class="required">*</span></label>
        <input type="file" id="gmp_aadhar_upload" accept=".jpg,.jpeg,.png,.pdf" required>
        <input type="hidden" name="gmp_aadhar_url" id="gmp_aadhar_url">
    </p>';

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

    echo '<p class="form-row form-row-wide">
        <label for="gmp_nominee_aadhar">Upload Nominee Aadhar <span class="required">*</span></label>
        <input type="file" id="gmp_nominee_aadhar_upload" accept=".jpg,.jpeg,.png,.pdf" required>
        <input type="hidden" name="gmp_nominee_aadhar_url" id="gmp_nominee_aadhar_url">
    </p>';

    echo '</div>';
}

    public static function validate_custom_checkout_fields() {
        if (!self::cart_has_gmp()) return;

        
        if (empty($_POST['gmp_nominee_name'])) wc_add_notice('Please enter nominee name.', 'error');
        if (empty($_POST['gmp_nominee_phone'])) wc_add_notice('Please enter nominee phone.', 'error');
         if (empty($_POST['gmp_pan_url'])) wc_add_notice('Please upload your PAN card.', 'error');
if (empty($_POST['gmp_aadhar_url'])) wc_add_notice('Please upload your Aadhar card.', 'error');
if (empty($_POST['gmp_nominee_aadhar_url'])) wc_add_notice('Please upload nominee Aadhar.', 'error');


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

        update_post_meta($order_id, 'gmp_pan', esc_url($_POST['gmp_pan_url']));
update_post_meta($order_id, 'gmp_aadhar', esc_url($_POST['gmp_aadhar_url']));
update_post_meta($order_id, 'gmp_nominee_aadhar', esc_url($_POST['gmp_nominee_aadhar_url']));

        update_post_meta($order_id, 'gmp_nominee_name', sanitize_text_field($_POST['gmp_nominee_name']));
        update_post_meta($order_id, 'gmp_nominee_phone', sanitize_text_field($_POST['gmp_nominee_phone'])); 
    }

    public static function save_user_meta($user_id) {
        if (!self::cart_has_gmp()) return;

        update_user_meta($user_id, 'gmp_nominee_name', sanitize_text_field($_POST['gmp_nominee_name']));
        update_user_meta($user_id, 'gmp_nominee_phone', sanitize_text_field($_POST['gmp_nominee_phone']));
        // File uploads are only saved in order meta for security reasons
    }
    
}
// Hook to display uploaded files in admin order page
add_action('woocommerce_admin_order_data_after_order_details', 'gmp_show_uploaded_files_in_admin');

function gmp_show_uploaded_files_in_admin($order) {
    $order_id = $order->get_id();

    $pan     = get_post_meta($order_id, 'gmp_pan', true);
    $aadhar  = get_post_meta($order_id, 'gmp_aadhar', true);
    $nom_aadhar = get_post_meta($order_id, 'gmp_nominee_aadhar', true);

    if (!$pan && !$aadhar && !$nom_aadhar) return;

    echo '<div class="order_data_column">';
    echo '<h4>' . esc_html__('GMP Documents', 'gold-money-plan') . '</h4>';
    echo '<ul>';

    if ($pan) {
        echo '<li><strong>PAN Card:</strong> <a href="' . esc_url($pan) . '" target="_blank">View / Download</a></li>';
    }

    if ($aadhar) {
        echo '<li><strong>Aadhar Card:</strong> <a href="' . esc_url($aadhar) . '" target="_blank">View / Download</a></li>';
    }

    if ($nom_aadhar) {
        echo '<li><strong>Nominee Aadhar:</strong> <a href="' . esc_url($nom_aadhar) . '" target="_blank">View / Download</a></li>';
    }

    echo '</ul>';
    echo '</div>';
}
