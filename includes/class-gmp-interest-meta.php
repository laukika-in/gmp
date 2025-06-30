<?php
class GMP_Interest_Meta {
    public static function add_admin_column($row, $order, $subscription) {
        $row['gmp_interest'] = self::get_column($order);
        return $row;
    }

    public static function get_column($order) {
        $total = 0;
        $percent = 0;

        foreach ($order->get_items() as $item) {
            $amt = $item->get_meta('_gmp_interest_amount');
            $pct = $item->get_meta('_gmp_interest_percent');
            if ($amt > 0) {
                $total += floatval($amt);
                $percent = floatval($pct);
            }
        }

        return $total > 0 ? wc_price($total) . " ({$percent}%)" : '—';
    }
}
