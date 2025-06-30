<?php

class GMP_WooCommerce {

    public static function init() {
        add_action('init', [__CLASS__, 'register_category']);
        add_action('template_redirect', [__CLASS__, 'require_login_for_gmp']);
        add_action('template_redirect', [__CLASS__, 'force_enctype']);

        // Custom Checkout Fields
        add_action('woocommerce_after_order_notes', [__CLASS__, 'add_custom_checkout_fields']);
        add_action('woocommerce_checkout_process', [__CLASS__, 'validate_custom_checkout_fields']);
        add_action('woocommerce_checkout_update_order_meta', [__CLASS__, 'save_order_meta']);
        add_action('woocommerce_checkout_update_user_meta', [__CLASS__, 'save_user_meta']);

        // Admin Meta Display
        add_action('woocommerce_admin_order_data_after_order_details', [__CLASS__, 'display_admin_order_meta']);

        // Interest Tables (Frontend + Admin)
        add_action('woocommerce_admin_order_data_after_order_details', ['GMP_Interest_Table', 'render_admin']);
        add_action('woocommerce_subscription_details_table', ['GMP_Interest_Table', 'render_frontend'], 20);

        // Interest Snapshots
        add_action('woocommerce_checkout_create_order_line_item', [__CLASS__, 'store_interest_snapshot'], 10, 4);

        // Frontend + Admin Related Order Columns
        add_filter('wcs_related_orders_table_row', ['GMP_Interest_Meta', 'add_admin_column'], 10, 3);
        add_filter('wcs_my_subscriptions_related_orders_column_gmp_interest', ['GMP_Interest_Meta', 'get_column']);
        add_filter('wcs_my_subscriptions_related_orders_columns', function ($columns) {
            $columns['gmp_interest'] = __('Interest (₹)', 'gmp');
            return $columns;
        });
        add_filter('wcs_related_orders_table_header', function ($headers) {
            $headers['gmp_interest'] = __('Interest (₹)', 'gmp');
            return $headers;
        });

        // Product Extension Fields
        add_action('woocommerce_product_options_general_product_data', ['GMP_Product_Fields', 'add']);
        add_action('woocommerce_process_product_meta', ['GMP_Product_Fields', 'save']);

        // Renewal Logging
        add_action('woocommerce_checkout_order_processed', ['GMP_Renewal', 'record']);
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
                    wp_redirect(wc_get_page_permalink('myaccount') . '?redirect_to=' . urlencode(wc_get_checkout_url()));
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
        $nom_name    = get_user_meta($user_id, 'gmp_nominee_name', true);
        $nom_phone   = get_user_meta($user_id, 'gmp_nominee_phone', true);

        echo '<div id="gmp_additional_fields"><h3>' . __('Gold Money Plan Details') . '</h3>';

        // PAN
        if ($pan_url) {
            echo '<p><label>PAN Already Uploaded:</label> <a href="' . esc_url($pan_url) . '" target="_blank">View</a></p>';
            echo '<input type="hidden" name="gmp_pan_url" value="' . esc_attr($pan_url) . '">';
        } else {
            echo '<p><label>Upload PAN Card <span class="required">*</span></label><input type="file" id="gmp_pan_upload" accept=".jpg,.jpeg,.png,.pdf" required><input type="hidden" name="gmp_pan_url" id="gmp_pan_url"></p>';
        }

        // Aadhar
        if ($aadhar_url) {
            echo '<p><label>Aadhar Already Uploaded:</label> <a href="' . esc_url($aadhar_url) . '" target="_blank">View</a></p>';
            echo '<input type="hidden" name="gmp_aadhar_url" value="' . esc_attr($aadhar_url) . '">';
        } else {
            echo '<p><label>Upload Aadhar Card <span class="required">*</span></label><input type="file" id="gmp_aadhar_upload" accept=".jpg,.jpeg,.png,.pdf" required><input type="hidden" name="gmp_aadhar_url" id="gmp_aadhar_url"></p>';
        }

        woocommerce_form_field('gmp_nominee_name', ['type' => 'text', 'required' => true, 'label' => 'Nominee Name'], $nom_name);
        woocommerce_form_field('gmp_nominee_phone', ['type' => 'text', 'required' => true, 'label' => 'Nominee Phone Number'], $nom_phone);

        // Nominee Aadhar
        if ($nom_url) {
            echo '<p><label>Nominee Aadhar Already Uploaded:</label> <a href="' . esc_url($nom_url) . '" target="_blank">View</a></p>';
            echo '<input type="hidden" name="gmp_nominee_aadhar_url" value="' . esc_attr($nom_url) . '">';
        } else {
            echo '<p><label>Upload Nominee Aadhar <span class="required">*</span></label><input type="file" id="gmp_nominee_aadhar_upload" accept=".jpg,.jpeg,.png,.pdf" required><input type="hidden" name="gmp_nominee_aadhar_url" id="gmp_nominee_aadhar_url"></p>';
        }

        echo '</div>';
    }

    public static function validate_custom_checkout_fields() {
        if (!self::cart_has_gmp()) return;
        $required_fields = [
            'gmp_nominee_name' => 'nominee name',
            'gmp_nominee_phone' => 'nominee phone',
            'gmp_pan_url' => 'PAN card',
            'gmp_aadhar_url' => 'Aadhar card',
            'gmp_nominee_aadhar_url' => 'nominee Aadhar',
        ];

        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) wc_add_notice("Please upload/enter your {$label}.", 'error');
        }
    }

    public static function save_order_meta($order_id) {
        if (!self::cart_has_gmp()) return;

        $fields = [
            'gmp_pan_url', 'gmp_aadhar_url', 'gmp_nominee_aadhar_url',
            'gmp_nominee_name', 'gmp_nominee_phone'
        ];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($order_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public static function save_user_meta($user_id) {
        if (!self::cart_has_gmp()) return;

        $fields = [
            'gmp_pan_url', 'gmp_aadhar_url', 'gmp_nominee_aadhar_url',
            'gmp_nominee_name', 'gmp_nominee_phone'
        ];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public static function display_admin_order_meta($order) {
        $labels = [
            'gmp_pan' => 'PAN Card',
            'gmp_aadhar' => 'Aadhar Card',
            'gmp_nominee_aadhar' => 'Nominee Aadhar',
        ];

        echo '<p><strong>Gold Money Plan Documents</strong></p><ul>';
        foreach ($labels as $key => $label) {
            $url = get_post_meta($order->get_id(), $key, true);
            if ($url) {
                echo "<li><strong>{$label}:</strong> <a href='" . esc_url($url) . "' target='_blank'>View</a></li>";
            }
        }
        echo '</ul>';
    }

    public static function store_interest_snapshot($item, $cart_item_key, $values, $order) {
        $product = $values['data'];
        $product_id = $product->get_id();
        $variation_id = $product->get_variation_id() ?: $product_id;

        if (!has_term('gmp-plan', 'product_cat', $product_id)) return;

        $settings = get_option('gmp_interest_settings', []);
        $interest_data = $settings[$variation_id] ?? $settings[$product_id] ?? ['base' => 0, 'ext' => []];

        $user_id = get_current_user_id();
        $order_count = GMP_Renewal::get_total_renewals($user_id, $variation_id);

        $base_interest = floatval($interest_data['base']);
        $extra_interest = $interest_data['ext'][$order_count + 1] ?? 0;

        $unit_price = $item->get_total() / max($item->get_quantity(), 1);
        $total_interest = round($unit_price * ($base_interest + $extra_interest) / 100, 2);

        $item->add_meta_data('_gmp_interest_percent', $base_interest + $extra_interest, true);
        $item->add_meta_data('_gmp_interest_amount', $total_interest, true);
    }
}
