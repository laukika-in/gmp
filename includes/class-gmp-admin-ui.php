<?php

class GMP_Admin_UI {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_admin_menu']);
        add_filter('manage_gmp_posts_columns', [__CLASS__, 'add_view_column']);
        add_action('manage_gmp_posts_custom_column', [__CLASS__, 'render_view_column'], 10, 2);
        add_action('admin_menu', [__CLASS__, 'register_admin_detail_page']);

    }

    public static function register_admin_menu() {
        add_menu_page(
            'Gold Money Plan',
            'Gold Money Plan',
            'manage_options',
            'gmp-admin',
            [__CLASS__, 'admin_page'],
            'dashicons-money-alt'
        );
    }

    public static function admin_page() {
        echo '<div class="wrap"><h1>Gold Money Plan Subscriptions</h1>';
        $users = get_users();
        echo '<table class="widefat"><thead><tr><th>User</th><th>EMI</th><th>Months Paid</th><th>Balance</th><th>Status</th></tr></thead><tbody>';
        foreach ($users as $user) {
            $plan = get_user_meta($user->ID, 'gmp_plan', true);
            if ($plan) {
                $paid = count($plan['paid_months']);
                $status = $plan['locked'] ? 'Locked' : ($plan['redeemed'] ? 'Redeemed' : 'Active');
                echo '<tr><td>' . esc_html($user->display_name) . '</td>';
                echo '<td>₹' . esc_html($plan['emi']) . '</td>';
                echo '<td>' . esc_html($paid) . '</td>';
                echo '<td>₹' . esc_html($plan['balance']) . '</td>';
                echo '<td>' . esc_html($status) . '</td></tr>';
            }
        }
        echo '</tbody></table></div>';
    }
    public static function add_view_column($columns) {
    $columns['gmp_view'] = 'View';
    return $columns;
}

public static function render_view_column($column, $post_id) {
    if ($column === 'gmp_view') {
        echo '<a href="' . admin_url('admin.php?page=gmp-view&plan_id=' . $post_id) . '">View</a>';
    }
}

public static function register_admin_detail_page() {
    add_submenu_page(
        null, 'GMP Plan Detail', 'GMP Plan Detail', 'manage_woocommerce', 'gmp-view',
        [__CLASS__, 'render_admin_plan_detail']
    );
}

public static function render_admin_plan_detail() {
    if (empty($_GET['plan_id'])) {
        echo '<div class="notice notice-error"><p>No Plan ID provided.</p></div>';
        return;
    }

    $plan_id = intval($_GET['plan_id']);
    $post = get_post($plan_id);
    if (!$post) {
        echo '<div class="notice notice-error"><p>Invalid Plan ID.</p></div>';
        return;
    }

    $user_id = get_post_meta($plan_id, 'user_id', true);
    $order_id = get_post_meta($plan_id, 'order_id', true);
    $order = wc_get_order($order_id);

    echo '<div class="wrap"><h1>GMP Plan Details</h1>';

    echo '<p><strong>User:</strong> ' . esc_html(get_userdata($user_id)->display_name) . '</p>';
    echo '<p><strong>Order ID:</strong> #' . $order_id . '</p>';
    echo '<p><strong>Order Date:</strong> ' . $order->get_date_created()->format('Y-m-d') . '</p>';

    echo '<h3>KYC Documents</h3><ul>';
    echo '<li>PAN: <a href="' . get_post_meta($order_id, 'gmp_pan', true) . '" target="_blank">View</a></li>';
    echo '<li>Aadhar: <a href="' . get_post_meta($order_id, 'gmp_aadhar', true) . '" target="_blank">View</a></li>';
    echo '<li>Nominee Aadhar: <a href="' . get_post_meta($order_id, 'gmp_nominee_aadhar', true) . '" target="_blank">View</a></li>';
    echo '</ul>';

    echo '<h3>Nominee Info</h3>';
    echo '<p>Name: ' . esc_html(get_post_meta($order_id, 'gmp_nominee_name', true)) . '</p>';
    echo '<p>Phone: ' . esc_html(get_post_meta($order_id, 'gmp_nominee_phone', true)) . '</p>';

    $payments = get_user_meta($user_id, "gmp_payments_{$order_id}", true);
    echo '<h3>EMI History</h3>';
    if ($payments) {
        echo '<table class="widefat"><thead><tr><th>Month</th><th>Amount</th><th>Date</th></tr></thead><tbody>';
        foreach ($payments as $entry) {
            echo '<tr><td>' . esc_html($entry['month']) . '</td><td>' . wc_price($entry['amount']) . '</td><td>' . esc_html($entry['date']) . '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No payments found.</p>';
    }

    echo '</div>';
}

}
