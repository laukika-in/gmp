<?php

class GMP_Admin_UI {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_admin_menu']);
        add_action('admin_menu', [__CLASS__, 'register_admin_detail_page']);
    }

    public static function register_admin_menu() {
        add_menu_page(
            'Gold Money Plan',
            'Gold Money Plan',
            'manage_options',
            'gmp-admin',
            [__CLASS__, 'render_main_page'],
            'dashicons-money-alt',
            56
        );
    }

   public static function render_main_page() {
    echo '<div class="wrap"><h1>Gold Money Plan Purchases</h1>';
    echo '<table class="widefat fixed striped"><thead><tr>';
    echo '<th>User</th><th>Order ID</th><th>Product</th><th>Purchase Date</th><th>EMI</th><th>Status</th><th>Action</th>';
    echo '</tr></thead><tbody>';

    $users = get_users();

    foreach ($users as $user) {
       $plans = get_posts([
    'post_type'      => 'gmp',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'meta_query'     => [
        [
            'key'     => 'user_id',
            'value'   => $user->ID,
            'compare' => '='
        ]
    ]
]);

        if (!$plan) continue;

        $order_id   = $plan['order_id'] ?? '';
        $product_id = $plan['product_id'] ?? '';
        $emi        = $plan['emi'] ?? '';
        $status     = $plan['locked'] ? 'Locked' : ($plan['redeemed'] ? 'Redeemed' : 'Active');
        $start_date = $plan['start_date'] ?? '';
        $product    = get_the_title($product_id) ?: '—';

        echo '<tr>';
        echo '<td>' . esc_html($user->display_name) . '</td>';
        echo '<td>#' . intval($order_id) . '</td>';
        echo '<td>' . esc_html($product) . '</td>';
        echo '<td>' . esc_html($start_date) . '</td>';
        echo '<td>₹' . esc_html($emi) . '</td>';
        echo '<td>' . esc_html($status) . '</td>';
        echo '<td><a class="button" href="' . admin_url('admin.php?page=gmp-view&user_id=' . $user->ID . '&order_id=' . $order_id) . '">View</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

    public static function register_admin_detail_page() {
        add_submenu_page(
            null,
            'GMP Plan Detail',
            'GMP Plan Detail',
            'manage_options',
            'gmp-view',
            [__CLASS__, 'render_detail_page']
        );
    }

  public static function render_detail_page() {
    $user_id = intval($_GET['user_id'] ?? 0);
    $order_id = intval($_GET['order_id'] ?? 0);

    if (!$user_id || !$order_id) {
        echo '<div class="notice notice-error"><p>Missing user ID or order ID.</p></div>';
        return;
    }

    $user = get_userdata($user_id);
    $order = wc_get_order($order_id);
    $plan = get_user_meta($user_id, 'gmp_plan', true);
    $product = isset($plan['product_id']) ? get_the_title($plan['product_id']) : '—';

    echo '<div class="wrap"><h1>GMP Plan Details</h1>';
    echo '<p><strong>User:</strong> ' . esc_html($user->display_name) . '</p>';
    echo '<p><strong>Order ID:</strong> #' . $order_id . '</p>';
    echo '<p><strong>Product:</strong> ' . esc_html($product) . '</p>';
    echo '<p><strong>Start Date:</strong> ' . esc_html($plan['start_date'] ?? '') . '</p>';
    echo '<p><strong>EMI:</strong> ₹' . esc_html($plan['emi'] ?? '') . '</p>';
    echo '<p><strong>Status:</strong> ' . ($plan['locked'] ? 'Locked' : ($plan['redeemed'] ? 'Redeemed' : 'Active')) . '</p>';

    echo '<h3>Nominee Info</h3>';
    echo '<p>Name: ' . esc_html(get_post_meta($order_id, 'gmp_nominee_name', true)) . '</p>';
    echo '<p>Phone: ' . esc_html(get_post_meta($order_id, 'gmp_nominee_phone', true)) . '</p>';

    echo '<h3>KYC Documents</h3><ul>';
    echo '<li>PAN: <a href="' . esc_url(get_post_meta($order_id, 'gmp_pan', true)) . '" target="_blank">View</a></li>';
    echo '<li>Aadhar: <a href="' . esc_url(get_post_meta($order_id, 'gmp_aadhar', true)) . '" target="_blank">View</a></li>';
    echo '<li>Nominee Aadhar: <a href="' . esc_url(get_post_meta($order_id, 'gmp_nominee_aadhar', true)) . '" target="_blank">View</a></li>';
    echo '</ul>';

    echo '<h3>EMI Payments</h3>';
    $payments = get_user_meta($user_id, "gmp_payments_{$order_id}", true);
    if ($payments) {
        echo '<table class="widefat"><thead><tr><th>Month</th><th>Amount</th><th>Date</th></tr></thead><tbody>';
        foreach ($payments as $p) {
            echo '<tr><td>' . esc_html($p['month']) . '</td><td>₹' . esc_html($p['amount']) . '</td><td>' . esc_html($p['date']) . '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No EMI records found.</p>';
    }

    echo '</div>';
}

}
