<?php

class GMP_Settings {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_settings_menu']);
    }

    public static function add_settings_menu() {
        add_menu_page(
            'GMP Interests', 
            'GMP Interests', 
            'manage_options',
            'gmp-interest-settings',
            [__CLASS__, 'render_settings_page'],
            'dashicons-money-alt',
            56
        );
    }

    public static function render_settings_page() {
        // ✅ Move Save Handler Up Here
        if (isset($_POST['gmp_interest_submit']) && check_admin_referer('gmp_interest_save')) {
            $result = [];
            foreach ($_POST['products'] as $pid) {
                $pid = intval($pid);
                $result[$pid] = [
                    'base' => floatval($_POST['base_interest'][$pid] ?? 0),
                    'ext' => [],
                ];
                if (!empty($_POST['ext_interest'][$pid])) {
                    foreach ($_POST['ext_interest'][$pid] as $m => $v) {
                        $result[$pid]['ext'][intval($m)] = floatval($v);
                    }
                }
            }
            update_option('gmp_interest_settings', $result);
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }

        $saved = get_option('gmp_interest_settings', []);
        $products = wc_get_products(['type' => 'variable-subscription', 'limit' => -1]);

        echo '<div class="wrap"><h1>Gold Plan Interest Settings</h1>';
        echo '<form method="post" action="">';
        wp_nonce_field('gmp_interest_save');

        echo '<table class="widefat"><thead><tr><th>Product</th><th>Base Interest (%)</th><th>Extension Interests</th></tr></thead><tbody>';

        foreach ($products as $product) {
            $id = $product->get_id();
            $title = $product->get_name();
            $enabled = get_post_meta($id, '_gmp_enable_extension', true) === 'yes';
            $months = intval(get_post_meta($id, '_gmp_extension_months', true));
            $data = $saved[$id] ?? ['base' => '', 'ext' => []];

            echo "<tr><td><strong>{$title}</strong><input type='hidden' name='products[]' value='{$id}'></td>";
            echo "<td><input type='number' name='base_interest[{$id}]' value='" . esc_attr($data['base']) . "' step='0.1' min='0'></td>";

            echo "<td>";
            if ($enabled && $months > 0) {
                for ($m = 1; $m <= $months; $m++) {
                    $val = $data['ext'][$m] ?? '';
                    $variations = $product->get_children();
                    $first_variation_id = $variations ? $variations[0] : 0;
                    $unit = get_post_meta($first_variation_id, '_subscription_period', true); // day/week/month/year
                    $unit_label = ucfirst($unit); // Capitalize it
                    echo "<label style='display:inline-block; width:90px;'>{$unit_label} {$m}:</label> 
                          <input type='number' name='ext_interest[{$id}][{$m}]' value='" . esc_attr($val) . "' step='0.1' min='0' style='width:70px;'><br>";
                }
            } else {
                echo '—';
            }
            echo "</td></tr>";
        }

        echo '</tbody></table><br>';
        echo '<input type="submit" class="button-primary" name="gmp_interest_submit" value="Save Settings">';
        echo '</form></div>';
    }
}
