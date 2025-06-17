<?php

class GMP_Redeem {
    public static function init() {
        add_action('wp_ajax_gmp_redeem', [__CLASS__, 'handle_redeem']);
    }

    public static function handle_redeem() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }

        $user_id = get_current_user_id();
        $plan = get_user_meta($user_id, 'gmp_plan', true);

        if (!$plan || $plan['locked'] || $plan['redeemed']) {
            wp_send_json_error('Invalid plan');
        }

        $emi = $plan['emi'];
        $months = count($plan['paid_months']);
        $base = $emi * $months;

        $interest = ($months > 10) ? GMP_Plans::get_additional_interest($months) : GMP_Plans::get_available_plans()[$plan['duration']];
        $total_interest = round($emi * ($interest / 100));

        $plan['balance'] = $base + $total_interest;
        $plan['locked'] = true;
        $plan['redeemed'] = true;

        update_user_meta($user_id, 'gmp_plan', $plan);
update_user_meta($user_id, '_gmp_locked_' . $subscription_id, true);

        wp_send_json_success(['balance' => $plan['balance']]);
    }
}
add_filter('wcs_can_user_update_subscription_to_status', function($can_update, $subscription, $new_status) {
    $user_id = $subscription->get_user_id();
    $subscription_id = $subscription->get_id();

    if (get_user_meta($user_id, '_gmp_locked_' . $subscription_id, true)) {
        return false;
    }

    return $can_update;
}, 10, 3);
