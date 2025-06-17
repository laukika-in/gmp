<?php

class GMP_Plans {
    public static function init() {
        add_action('init', [__CLASS__, 'register_post_type']);
    }

    public static function register_post_type() {
        register_post_type('gmp', [
            'labels' => [
                'name' => 'Gold Money Plans',
                'singular_name' => 'Gold Money Plan',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-money-alt',
            'supports' => ['title'],
            'has_archive' => false,
            'rewrite' => false,
        ]);
    }

    public static function get_available_plans() {
        return [
            '7' => 38,    // 38% interest on EMI
            '10' => 55,   // 55% interest on EMI
        ];
    }

    public static function get_additional_interest($month) {
        return [
            '11' => 65,
            '12' => 75,
        ][$month] ?? 0;
    }

    public static function get_emi_options() {
        return range(2000, 25000, 1000);
    }
}
