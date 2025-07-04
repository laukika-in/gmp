<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Admin_Actions {
    public static function init() {
        add_action( 'wp_ajax_gmp_admin_cycle_action', [ __CLASS__, 'handle_action' ] );
    }

    public static function handle_action() {
         if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    check_ajax_referer('gmp_cycle_action');

    $cycle_id = absint($_POST['cycle_id']);
    $action = sanitize_text_field($_POST['type']);

    global $wpdb;
    $table = $wpdb->prefix . 'gmp_cycles';
    $installments_table = $wpdb->prefix . 'gmp_installments';

    $cycle = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $cycle_id));
    if (!$cycle) wp_send_json_error(['message' => 'Cycle not found.']);

    switch ($action) {
        case 'close':
            $wpdb->update($table, ['status' => 'closed'], ['id' => $cycle_id]);
            break;

        case 'cancel':
            $wpdb->update($table, ['status' => 'cancelled'], ['id' => $cycle_id]);
            $wpdb->delete($installments_table, ['cycle_id' => $cycle_id, 'is_paid' => 0]);
            break;

        case 'hold':
    $wpdb->update($table, ['status' => 'hold'], ['id' => $cycle_id]);
    break;

case 'resume':
    $wpdb->update($table, ['status' => 'active'], ['id' => $cycle_id]);
    break;


        default:
            wp_send_json_error(['message' => 'Invalid action.']);
    }

    wp_send_json_success(['message' => ucfirst($action) . ' successful.']);
    }
}