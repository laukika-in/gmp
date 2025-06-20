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
 
function gmp_get_interest_data($product_id) {
    $settings = get_option('gmp_interest_settings', []);
    return $settings[$product_id] ?? ['base' => 0, 'ext' => []];
}
function gmp_get_total_renewals($user_id, $product_or_variation_id) {
    $history = get_user_meta($user_id, "gmp_subscription_history_{$product_or_variation_id}", true);
    return is_array($history) ? count($history) : 0;
}
