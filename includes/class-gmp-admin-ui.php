<?php

class GMP_Admin_UI {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_admin_menu']);
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
}
