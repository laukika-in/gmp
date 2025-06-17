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
        $user_id = get_current_user_id();
        $pan_url     = get_user_meta($user_id, 'gmp_pan_url', true);
        $aadhar_url  = get_user_meta($user_id, 'gmp_aadhar_url', true);
        $nom_url     = get_user_meta($user_id, 'gmp_nominee_aadhar_url', true);
        $nom_name  = get_user_meta($user_id, 'gmp_nominee_name', true);
        $nom_phone = get_user_meta($user_id, 'gmp_nominee_phone', true);

        echo '<div id="gmp_additional_fields"><h3>' . __('Gold Money Plan Details') . '</h3>';

        // PAN Upload
        if ($pan_url) {
            echo '<p class="form-row form-row-wide"><label>PAN Already Uploaded:</label> <a href="' . esc_url($pan_url) . '" target="_blank">View</a>';
            echo '<input type="hidden" name="gmp_pan_url" value="' . esc_attr($pan_url) . '"></p>';
        } else {
            echo '<p class="form-row form-row-wide">
                <label for="gmp_pan">Upload PAN Card <span class="required">*</span></label>
                <input type="file" id="gmp_pan_upload" accept=".jpg,.jpeg,.png,.pdf" required>
                <input type="hidden" name="gmp_pan_url" id="gmp_pan_url">
            </p>';
        }

        // Aadhar Upload
        if ($aadhar_url) {
            echo '<p class="form-row form-row-wide"><label>Aadhar Already Uploaded:</label> <a href="' . esc_url($aadhar_url) . '" target="_blank">View</a>';
            echo '<input type="hidden" name="gmp_aadhar_url" value="' . esc_attr($aadhar_url) . '"></p>';
        } else {
            echo '<p class="form-row form-row-wide">
                <label for="gmp_aadhar">Upload Aadhar Card <span class="required">*</span></label>
                <input type="file" id="gmp_aadhar_upload" accept=".jpg,.jpeg,.png,.pdf" required>
                <input type="hidden" name="gmp_aadhar_url" id="gmp_aadhar_url">
            </p>';
        }

        woocommerce_form_field('gmp_nominee_name', [
            'type'     => 'text',
            'required' => true,
            'label'    => 'Nominee Name'
        ], $nom_name);

        woocommerce_form_field('gmp_nominee_phone', [
            'type'     => 'text',
            'required' => true,
            'label'    => 'Nominee Phone Number'
        ], $nom_phone);

            // Nominee Aadhar
        if ($nom_url) {
            echo '<p class="form-row form-row-wide"><label>Nominee Aadhar Already Uploaded:</label> <a href="' . esc_url($nom_url) . '" target="_blank">View</a>';
            echo '<input type="hidden" name="gmp_nominee_aadhar_url" value="' . esc_attr($nom_url) . '"></p>';
        } else {
            echo '<p class="form-row form-row-wide">
                <label for="gmp_nominee_aadhar">Upload Nominee Aadhar <span class="required">*</span></label>
                <input type="file" id="gmp_nominee_aadhar_upload" accept=".jpg,.jpeg,.png,.pdf" required>
                <input type="hidden" name="gmp_nominee_aadhar_url" id="gmp_nominee_aadhar_url">
            </p>';
        }

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
        update_user_meta($user_id, 'gmp_pan_url', esc_url($_POST['gmp_pan_url']));
        update_user_meta($user_id, 'gmp_aadhar_url', esc_url($_POST['gmp_aadhar_url']));
        update_user_meta($user_id, 'gmp_nominee_aadhar_url', esc_url($_POST['gmp_nominee_aadhar_url']));

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
        
}
add_action('woocommerce_product_options_general_product_data', 'gmp_add_extension_fields');
add_action('woocommerce_process_product_meta', 'gmp_save_extension_fields');

function gmp_add_extension_fields() {
    global $product_object;

    echo '<div class="options_group">';
    
    woocommerce_wp_checkbox([
        'id' => '_gmp_enable_extension',
        'label' => __('Enable Extension Period', 'gmp'),
        'description' => __('Allow paying beyond subscription expiry.'),
        'desc_tip' => true,
    ]);

    woocommerce_wp_text_input([
        'id' => '_gmp_extension_months',
        'label' => __('Number of Extension Months', 'gmp'),
        'type' => 'number',
        'custom_attributes' => [
            'min' => '0',
            'step' => '1'
        ],
        'description' => __('Number of months the user can optionally pay after subscription ends.'),
        'desc_tip' => true,
    ]);

    echo '</div>';

    // Add JS to hide/show field based on checkbox
    ?>
    <script>
        jQuery(function($) {
            function toggleField() {
                if ($('#_gmp_enable_extension').is(':checked')) {
                    $('#_gmp_extension_months').closest('.form-field').show();
                } else {
                    $('#_gmp_extension_months').closest('.form-field').hide();
                }
            }
            toggleField();
            $('#_gmp_enable_extension').on('change', toggleField);
        });
    </script>
    <?php
}

function gmp_save_extension_fields($post_id) {
    $enabled = isset($_POST['_gmp_enable_extension']) ? 'yes' : 'no';
    update_post_meta($post_id, '_gmp_enable_extension', $enabled);

    if (isset($_POST['_gmp_extension_months'])) {
        update_post_meta($post_id, '_gmp_extension_months', sanitize_text_field($_POST['_gmp_extension_months']));
    }
}

add_action('woocommerce_checkout_order_processed', 'gmp_record_subscription_renewal');

function gmp_record_subscription_renewal($order_id) {
    $order = wc_get_order($order_id);
    if (!$order || !$order->get_items()) return;

    $user_id = $order->get_user_id();
    if (!$user_id) return;

    foreach ($order->get_items() as $item) {
        $product_id   = $item->get_product_id();
        $variation_id = $item->get_variation_id();
        $key          = $variation_id ?: $product_id;

        $quantity     = $item->get_quantity(); // In case someone buys more than one
        $unit_price   = $item->get_total() / max($quantity, 1);

        // Get past renewal data
        $meta_key     = "gmp_subscription_history_{$key}";
        $history      = get_user_meta($user_id, $meta_key, true) ?: [];

        $history[] = [
            'date'     => current_time('Y-m-d H:i:s'),
            'amount'   => $unit_price,
            'order_id' => $order_id,
        ];

        update_user_meta($user_id, $meta_key, $history);
    }
}
