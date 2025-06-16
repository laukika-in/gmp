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
        add_action('woocommerce_admin_order_data_after_order_details', [__CLASS__, 'display_admin_order_meta']);
        add_action('init', [__CLASS__, 'add_my_account_endpoint']);
        add_filter('query_vars', [__CLASS__, 'add_query_vars']);
        add_filter('woocommerce_account_menu_items', [__CLASS__, 'add_gmp_menu']);
        add_action('woocommerce_account_gmp_endpoint', [__CLASS__, 'render_gmp_page']);
        add_action('woocommerce_add_to_cart', [__CLASS__, 'track_emi_cart_add']);
add_action('woocommerce_checkout_order_processed', [__CLASS__, 'record_emi_payment']);
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
    public static function display_admin_order_meta($order) {
    $fields = [
        'gmp_pan' => 'PAN Card',
        'gmp_aadhar' => 'Aadhar Card',
        'gmp_nominee_aadhar' => 'Nominee Aadhar',
    ];

    echo '<p class="form-field form-field-wide" style=" margin: 12px 0px; font-weight: 600; color: #333; font-size: 105%; ">Gold Money Plan Documents</p><ul>';
    foreach ($fields as $key => $label) {
        $url = get_post_meta($order->get_id(), $key, true);
        if ($url) {
            echo "<li><strong>{$label}:</strong> <a href='" . esc_url($url) . "' target='_blank'>View</a></li>";
        }
    }
    echo '</ul>';
    }
    public static function add_my_account_endpoint() {
        add_rewrite_endpoint('gmp', EP_ROOT | EP_PAGES);
    }

    public static function add_query_vars($vars) {
        $vars[] = 'gmp';
        return $vars;
    }

    public static function add_gmp_menu($items) {
        $logout = $items['customer-logout'];
        unset($items['customer-logout']);
        $items['gmp'] = 'Gold Money Plan';
        $items['customer-logout'] = $logout;
        return $items;
    }

    public static function render_gmp_page() {
        $order_id = get_query_var('gmp');

        if (is_numeric($order_id)) {
            include GMP_PLUGIN_PATH . 'templates/gmp-detail.php';
        } else {
            include GMP_PLUGIN_PATH . 'templates/gmp-dashboard.php';
        }
    }
public static function track_emi_cart_add($cart_item_key) {
    if (!empty($_GET['gmp_emi_payment']) && is_numeric($_GET['gmp_emi_payment'])) {
        WC()->cart->cart_contents[$cart_item_key]['gmp_emi_payment'] = intval($_GET['gmp_emi_payment']);
    }
}

public static function record_emi_payment($order_id) {
    $order = wc_get_order($order_id);
    foreach ($order->get_items() as $item) {
        $meta = $item->get_meta_data();
        foreach ($meta as $m) {
            if ($m->key === 'gmp_emi_payment') {
                $original_order = intval($m->value);
                $user_id = $order->get_user_id();
                $payments = get_user_meta($user_id, "gmp_payments_{$original_order}", true) ?: [];

                $payments[] = [
                    'month' => 'EMI ' . (count($payments) + 1),
                    'amount' => $item->get_total(),
                    'date' => current_time('Y-m-d'),
                ];
                update_user_meta($user_id, "gmp_payments_{$original_order}", $payments);
            }
        }
    }
}
}
