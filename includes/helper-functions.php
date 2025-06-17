<?php
add_action('wp_ajax_gmp_upload_file', 'gmp_handle_upload');
add_action('wp_ajax_nopriv_gmp_upload_file', 'gmp_handle_upload');

function gmp_handle_upload() {
    check_ajax_referer('gmp_upload_nonce', 'nonce');

    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    $upload = wp_handle_upload($_FILES['file'], ['test_form' => false]);
    if (isset($upload['url'])) {
        wp_send_json_success(['url' => esc_url($upload['url'])]);
    } else {
        wp_send_json_error(['message' => $upload['error'] ?? 'Unknown error']);
    }
}
function gmp_calculate_interest($product_id, $month_number) {
    $settings = get_option('gmp_interest_settings', []);
    if (!isset($settings[$product_id])) return 0;

    $base = $settings[$product_id]['base'] ?? 0;
    $ext = $settings[$product_id]['ext'] ?? [];

    // Before extension
    if ($month_number <= 10) return $base;

    // After extension (e.g. 11th, 12th...)
    return $ext[$month_number - 10] ?? 0;
}
