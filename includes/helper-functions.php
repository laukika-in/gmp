<?php

function gmp_get_current_plan($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    return get_user_meta($user_id, 'gmp_plan', true);
}
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
