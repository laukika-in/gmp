<?php

class GMP_Plans {
    public static function init() {
        // Reserved for admin-defined settings in future
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
