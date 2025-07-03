<?php
class GMP_Cycle_Front {

    public static function init() {
        add_action('woocommerce_account_gmp-subscriptions_endpoint', [__CLASS__, 'render_subscriptions_page']);
        add_filter('woocommerce_account_menu_items', [__CLASS__, 'add_menu_item']);
    }

    public static function add_menu_item($items) {
        $items['gmp-subscriptions'] = 'Gold Plan Subscriptions';
        return $items;
    }

    public static function render_subscriptions_page() {
        $user_id = get_current_user_id();
        $cycles = GMP_Cycle_Model::get_cycles_for_user($user_id);
        include GMP_PLUGIN_PATH . 'views/frontend-cycles.php';
    }
}
