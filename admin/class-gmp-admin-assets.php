<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Admin_Assets {

    public static function init() {
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action('wp_ajax_gmp_admin_cycle_action', [__CLASS__, 'handle_action']);

    }

    public static function enqueue_assets( $hook ) {
        if ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'gmp' ) !== false ) {
            wp_enqueue_style(
                'gmp-admin',
                GMP_PLUGIN_URL . 'assets/css/gmp-admin.css',
                [],
                GMP_PLUGIN_VERSION
            );
            wp_enqueue_script(
                'gmp-admin',
                GMP_PLUGIN_URL . 'assets/js/gmp-admin.js',
                [ 'jquery' ],
                GMP_PLUGIN_VERSION,
                true
            );
        }
    }
    public static function handle_action() {
    check_ajax_referer('gmp_admin_action');

    $cycle_id = absint($_POST['cycle_id'] ?? 0);
    $action   = sanitize_key($_POST['do_action'] ?? '');

    global $wpdb;
    $cycle_table = $wpdb->prefix . 'gmp_cycles';
    $install_table = $wpdb->prefix . 'gmp_installments';

    if (!in_array($action, ['close', 'cancel', 'stop'])) {
        wp_send_json_error('Invalid action');
    }

    if (! $cycle_id) {
        wp_send_json_error('Missing cycle ID');
    }

    switch ($action) {
        case 'close':
            $wpdb->update($cycle_table, ['status' => 'closed'], ['id' => $cycle_id]);
            break;

        case 'cancel':
            $wpdb->update($cycle_table, ['status' => 'cancelled'], ['id' => $cycle_id]);
            $wpdb->delete($install_table, ['cycle_id' => $cycle_id, 'is_paid' => 0]);
            break;

        case 'stop':
            $wpdb->query("UPDATE $install_table SET is_skipped = 1 WHERE cycle_id = $cycle_id AND is_paid = 0");
            break;
    }

    wp_send_json_success();
}

}
