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
         add_action(
        'woocommerce_admin_order_data_after_order_details',
        [ __CLASS__, 'render_interest_table_admin' ]
    );

    // Frontend: show interest table on the “View Subscription” page
    add_action(
        'woocommerce_subscription_details_table',
        [ __CLASS__, 'render_interest_table_frontend' ],
        30
    );

        // Interest Snapshots
        add_action('woocommerce_checkout_create_order_line_item', [__CLASS__, 'store_interest_snapshot'], 10, 4);
add_action(
  'woocommerce_subscriptions_renewal_order_created',
  [ __CLASS__, 'snapshot_interest_on_scheduled_renewal' ],
  10,
  2
);
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

/**
 * Snapshot the interest rate and amount on each order line
 */
 
public static function store_interest_snapshot( $item, $cart_item_key, $values, $order ) {
    $product_id = $item->get_product_id();
    if ( ! has_term( 'gmp-plan', 'product_cat', $product_id ) ) {
        return;
    }

    // 1) Fetch saved interest settings
    $settings      = get_option( 'gmp_interest_settings', [] );
    $interest_data = $settings[ $product_id ] ?? [ 'base' => 0, 'ext' => [] ];
    $base_pct      = floatval( $interest_data['base'] );
    $ext_pcts      = (array) $interest_data['ext'];

    // Determine the actual subscription product (variation or parent)
    $variation_id      = $item->get_variation_id() ?: $product_id;
    $subscription_prod = wc_get_product( $variation_id );

    // 2) Correctly get the lock-period length
    if ( method_exists( $subscription_prod, 'get_length' ) ) {
        $lock_period = intval( $subscription_prod->get_length() );
    } else {
        $lock_period = intval( $subscription_prod->get_meta( '_subscription_length', true ) );
    }

    // 3) How many extension instalments you allowed?
    $extension_count = intval( get_post_meta( $variation_id, '_gmp_extension_months', true ) );

    // 4) Count how many payments have already been made
    $user_id           = $order->get_user_id() ?: get_current_user_id();
    $history_key       = "gmp_subscription_history_{$variation_id}";
    $history           = get_user_meta( $user_id, $history_key, true );
    $paid_count        = is_array( $history ) ? count( $history ) : 0;
    $instalment_number = $paid_count + 1;

    // 5) Pick the correct rate
    if ( $instalment_number <= $lock_period ) {
        // lock-period instalment
        $apply_pct = $base_pct;
    } else {
        // extension-period instalment
        $ext_index = $instalment_number - $lock_period;
        if ( isset( $ext_pcts[ $ext_index ] ) && $ext_index <= $extension_count ) {
            $apply_pct = floatval( $ext_pcts[ $ext_index ] );
        } else {
            $apply_pct = $base_pct;
        }
    }

    // 6) Compute unit EMI & interest amount
    $qty        = max( 1, $item->get_quantity() );
    $unit_price = $item->get_total() / $qty;
    $int_amt    = round( $unit_price * ( $apply_pct / 100 ), 2 );

    // 7) Snapshot it
    $item->add_meta_data( '_gmp_interest_percent',  $apply_pct, true );
    $item->add_meta_data( '_gmp_interest_amount',   $int_amt,   true );
    $item->add_meta_data( '_gmp_instalment_number', $instalment_number, true );
}
public static function snapshot_interest_on_scheduled_renewal( $renewal_order, $subscription ) {
  foreach ( $renewal_order->get_items() as $item ) {
    // re-use your existing logic
    self::store_interest_snapshot( $item, null, null, $renewal_order );
  }
  // make sure the meta gets saved
  $renewal_order->save();
}
     /**
     * Fired on the admin order page.
     * Looks up the first subscription linked to this order,
     * then renders the same table we use in the frontend.
     */
    public static function render_interest_table_admin( $order ) {
        if ( ! function_exists( 'wcs_get_subscriptions_for_order' ) ) {
            return;
        }

        // grab the subscription tied to this order
        $subscriptions = wcs_get_subscriptions_for_order( $order );
        if ( empty( $subscriptions ) ) {
            return;
        }
        $subscription = reset( $subscriptions );

        echo '<h3>Gold Plan EMI + Interest Summary</h3>';
        self::output_interest_table( $subscription );
    }

    /**
     * Fired in the “View Subscription” page under My Account.
     * $subscription is a WC_Subscription instance.
     */
    public static function render_interest_table_frontend( $subscription ) {
        if ( ! $subscription instanceof WC_Subscription ) {
            return;
        }
        echo '<h3>Gold Plan EMI + Interest Summary</h3>';
        self::output_interest_table( $subscription );
    }

    /**
     * Shared table–builder.  Loops parent‐related orders, pulls out
     * our stored _gmp_interest_percent and _gmp_interest_amount line-item metadata,
     * and displays them alongside the base EMI.
     */
    protected static function output_interest_table( $subscription ) {
        // get only the parent/renewal orders
        $related_orders = $subscription->get_related_orders( [ 'parent' ] );
        if ( empty( $related_orders ) ) {
            echo '<p>No EMI records found.</p>';
            return;
        }

        echo '<table class="shop_table shop_table_responsive"><thead><tr>';
        echo '<th>Order</th><th>Date</th><th>Product</th><th>Base EMI</th><th>Interest %</th><th>Total (EMI+Interest)</th>';
        echo '</tr></thead><tbody>';

        foreach ( $related_orders as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                continue;
            }

            foreach ( $order->get_items() as $item ) {
                $qty             = max( 1, $item->get_quantity() );
                $base            = $item->get_total() / $qty;
                $percent         = (float) $item->get_meta( '_gmp_interest_percent' );
                $interest_amount = (float) $item->get_meta( '_gmp_interest_amount' );
                $total           = $base + $interest_amount;

                echo '<tr>';
                echo '<td><a href="' . esc_url( get_edit_post_link( $order->get_id() ) ) . '">#' 
                     . esc_html( $order->get_order_number() ) . '</a></td>';
                echo '<td>' . esc_html( $order->get_date_created()->date( 'Y-m-d' ) ) . '</td>';
                echo '<td>' . esc_html( $item->get_name() ) . '</td>';
                echo '<td>' . wc_price( $base ) . '</td>';
                echo '<td>' . number_format_i18n( $percent, 2 ) . '%</td>';
                echo '<td>' . wc_price( $total ) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
    }
}
