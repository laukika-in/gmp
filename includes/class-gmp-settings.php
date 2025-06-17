<?php

class GMP_Settings {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_settings_menu']);
    }

    public static function add_settings_menu() {
        add_menu_page(
            'GMP Interest Settings',
            'GMP Interest Settings',
            'manage_options',
            'gmp-interest-settings',
            [__CLASS__, 'render_settings_page'],
            'dashicons-money-alt',
            56
        );
    }

    public static function render_settings_page() {
        // Handle Save
        if (isset($_POST['gmp_interest_submit']) && check_admin_referer('gmp_interest_save')) {
            $old = get_option('gmp_interest_settings', []);
            $result = [];
            $log_entry = [
                'user'      => wp_get_current_user()->user_login,
                'ip'        => $_SERVER['REMOTE_ADDR'],
                'timestamp' => current_time('mysql'),
                'changes'   => []
            ];

            foreach ($_POST['products'] as $pid) {
                $pid = intval($pid);
                $base = floatval($_POST['base_interest'][$pid] ?? 0);
                $exts = $_POST['ext_interest'][$pid] ?? [];

                $result[$pid] = [
                    'base' => $base,
                    'ext'  => []
                ];

                // Log base interest change
                if (!isset($old[$pid]['base']) || $old[$pid]['base'] != $base) {
                    $log_entry['changes'][$pid]['base'] = [
                        'from' => $old[$pid]['base'] ?? '',
                        'to'   => $base
                    ];
                }

                // Log extension interest changes
                foreach ($exts as $m => $v) {
                    $v = floatval($v);
                    $old_val = $old[$pid]['ext'][$m] ?? '';
                    if ($old_val != $v) {
                        $log_entry['changes'][$pid]['ext'][$m] = [
                            'from' => $old_val,
                            'to'   => $v
                        ];
                    }
                    $result[$pid]['ext'][$m] = $v;
                }
            }

            update_option('gmp_interest_settings', $result);

            if (!empty($log_entry['changes'])) {
                $history = get_option('gmp_interest_history', []);
                array_unshift($history, $log_entry); // Add to top
                $history = array_slice($history, 0, 5); // Keep last 5
                update_option('gmp_interest_history', $history);
            }

            echo '<div class="updated"><p>Settings saved.</p></div>';
        }

        // UI Output
        $saved    = get_option('gmp_interest_settings', []);
        $products = wc_get_products(['type' => 'variable-subscription', 'limit' => -1]);

        echo '<div class="wrap"><h1>Gold Plan Interest Settings</h1>';
        echo '<form method="post">';
        wp_nonce_field('gmp_interest_save');

        echo '<table class="widefat"><thead><tr><th>Product</th><th>Base Interest (%)</th><th>Extension Interests</th></tr></thead><tbody>';

        foreach ($products as $product) {
            $id = $product->get_id();
            $title = $product->get_name();
            $enabled = get_post_meta($id, '_gmp_enable_extension', true) === 'yes';
            $months  = intval(get_post_meta($id, '_gmp_extension_months', true));
            $interval = $product->get_meta('_subscription_period') ?: 'month';
            $data = $saved[$id] ?? ['base' => '', 'ext' => []];

            echo "<tr><td><strong>{$title}</strong><br><small>Unit: {$interval}</small><input type='hidden' name='products[]' value='{$id}'></td>";
            echo "<td><input type='number' name='base_interest[{$id}]' value='" . esc_attr($data['base']) . "' step='0.1' min='0'></td>";

            echo "<td>";
            if ($enabled && $months > 0) {
                for ($m = 1; $m <= $months; $m++) {
                    $val = $data['ext'][$m] ?? '';
                    echo "<label>{$interval} {$m}:</label> <input type='number' name='ext_interest[{$id}][{$m}]' value='" . esc_attr($val) . "' step='0.1' min='0'><br>";
                }
            } else {
                echo '—';
            }
            echo "</td></tr>";
        }

        echo '</tbody></table><br>';
        echo '<input type="submit" class="button-primary" name="gmp_interest_submit" value="Save Settings">';
        echo '</form>';

        // Show last 5 changes
        $history = get_option('gmp_interest_history', []);
        if (!empty($history)) {
            echo '<h2>Recent Changes (Last 5)</h2><ul style="list-style: disc; padding-left: 20px;">';
            foreach ($history as $entry) {
                echo '<li><strong>' . esc_html($entry['user']) . '</strong> from IP <code>' . esc_html($entry['ip']) . '</code> updated on <em>' . esc_html($entry['timestamp']) . '</em><ul>';

                foreach ($entry['changes'] as $pid => $change) {
                    $product_title = get_the_title($pid);
                    echo "<li><strong>{$product_title}</strong><ul>";
                    if (isset($change['base'])) {
                        echo "<li>Base Interest: <code>{$change['base']['from']}</code> → <code>{$change['base']['to']}</code></li>";
                    }
                    if (isset($change['ext'])) {
                        foreach ($change['ext'] as $month => $v) {
                            echo "<li>Extension {$month}: <code>{$v['from']}</code> → <code>{$v['to']}</code></li>";
                        }
                    }
                    echo '</ul></li>';
                }

                echo '</ul></li>';
            }
            echo '</ul>';
        }

        echo '</div>';
    }
}
