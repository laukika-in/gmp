<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Admin_Actions {
    public static function init() {
        add_action( 'wp_ajax_gmp_admin_cycle_action', [ __CLASS__, 'handle_action' ] );
    }

    public static function handle_action() {
        if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'gmp_admin_cycle_action', '', false ) ) {
            wp_send_json_error([ 'message' => 'Unauthorized' ]);
        }

        $cycle_id = absint( $_POST['cycle_id'] ?? 0 );
        $action = sanitize_text_field( $_POST['cycle_action'] ?? '' );

        global $wpdb;
        $cycle = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gmp_cycles WHERE id = %d", $cycle_id
        ) );

        if ( ! $cycle ) {
            wp_send_json_error([ 'message' => 'Cycle not found.' ]);
        }

        switch ( $action ) {
            case 'close':
                $wpdb->update( "{$wpdb->prefix}gmp_cycles", [ 'status' => 'closed' ], [ 'id' => $cycle_id ] );
                break;
            case 'cancel':
                $wpdb->update( "{$wpdb->prefix}gmp_cycles", [ 'status' => 'cancelled' ], [ 'id' => $cycle_id ] );
                $wpdb->update( "{$wpdb->prefix}gmp_installments", [ 'is_paid' => 0 ], [ 'cycle_id' => $cycle_id ] );
                break;
            case 'stop':
                $max_paid = $wpdb->get_var( $wpdb->prepare(
                    "SELECT MAX(month_number) FROM {$wpdb->prefix}gmp_installments WHERE cycle_id = %d AND is_paid = 1", $cycle_id
                ) );
                $wpdb->query( $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}gmp_installments WHERE cycle_id = %d AND month_number > %d",
                    $cycle_id, intval($max_paid)
                ) );
                $wpdb->update( "{$wpdb->prefix}gmp_cycles", [
                    'total_months' => intval($max_paid)
                ], [ 'id' => $cycle_id ] );
                break;
            default:
                wp_send_json_error([ 'message' => 'Unknown action.' ]);
        }

        wp_send_json_success([ 'message' => ucfirst($action) . ' action successful.' ]);
    }
}