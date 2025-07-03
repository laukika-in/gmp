<?php
class GMP_Cycle_Model {

    public static function record_payment($user_id, $variation_id, $order_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gmp_emi_cycles';

        $existing = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $table 
            WHERE user_id = %d AND variation_id = %d AND status = 'active'
            ORDER BY id DESC LIMIT 1
        ", $user_id, $variation_id));

        if ($existing) {
            // Add payment row
            self::insert_payment($existing->id, $order_id);
        } else {
            // Create new cycle
            $lock = get_post_meta($variation_id, '_gmp_lock_period', true);
            $ext  = get_post_meta($variation_id, '_gmp_extension_months', true);
            $start_date = current_time('mysql');
            $end_date = date('Y-m-d H:i:s', strtotime("+".($lock + $ext)." months"));

            $wpdb->insert($table, [
                'user_id'      => $user_id,
                'variation_id' => $variation_id,
                'start_date'   => $start_date,
                'end_date'     => $end_date,
                'status'       => 'active',
            ]);
            $cycle_id = $wpdb->insert_id;
            self::insert_payment($cycle_id, $order_id);
        }
    }

    public static function insert_payment($cycle_id, $order_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gmp_emi_payments';

        $wpdb->insert($table, [
            'cycle_id' => $cycle_id,
            'order_id' => $order_id,
            'paid_on'  => current_time('mysql')
        ]);
    }

    public static function get_cycles_for_user($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gmp_emi_cycles';

        return $wpdb->get_results($wpdb->prepare("
            SELECT id, variation_id, start_date, end_date, status 
            FROM $table 
            WHERE user_id = %d
            ORDER BY id DESC
        ", $user_id), ARRAY_A);
    }

    public static function get_payments_for_cycle($cycle_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gmp_emi_payments';

        return $wpdb->get_results($wpdb->prepare("
            SELECT order_id, paid_on FROM $table 
            WHERE cycle_id = %d 
            ORDER BY paid_on ASC
        ", $cycle_id), ARRAY_A);
    }
}
