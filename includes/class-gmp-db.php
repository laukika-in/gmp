<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_DB {

    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $cycles_table     = $wpdb->prefix . 'gmp_cycles';
        $installments_table = $wpdb->prefix . 'gmp_installments';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql1 = "CREATE TABLE {$cycles_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            variation_id BIGINT UNSIGNED NOT NULL,
            start_date DATE NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            total_months INT NOT NULL,
            lock_months INT NOT NULL,
            extension_months INT NOT NULL,
            base_interest FLOAT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            INDEX (user_id),
            INDEX (status)
        ) $charset_collate;";

        $sql2 = "CREATE TABLE {$installments_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            cycle_id BIGINT UNSIGNED NOT NULL,
            month_number INT NOT NULL,
            due_date DATE NOT NULL,
            emi_amount FLOAT NOT NULL,
            interest_rate FLOAT NOT NULL,
            total_with_interest FLOAT NOT NULL,
            is_paid TINYINT(1) DEFAULT 0,
            order_id BIGINT UNSIGNED DEFAULT NULL,
            paid_date DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            INDEX (cycle_id),
            INDEX (is_paid),
            INDEX (order_id)
        ) $charset_collate;";

        dbDelta( $sql1 );
        dbDelta( $sql2 );
    }
}
