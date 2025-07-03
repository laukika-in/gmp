<?php
if (!defined('ABSPATH')) exit;

class GMP_DB {
    public static function init() {
        register_activation_hook(GMP_PLUGIN_FILE, [__CLASS__, 'create_table']);
    }

    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'gmp_emi_cycles';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            variation_id BIGINT UNSIGNED NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY user_product (user_id, product_id, variation_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
