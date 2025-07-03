<?php
if (!defined('ABSPATH')) exit;

class GMP_Product_Fields {
    public static function add() {
        global $product_object;

        if ($product_object->get_type() !== 'variable') return;

        echo '<div class="options_group show_if_variable">';
        woocommerce_wp_text_input([
            'id' => '_gmp_lock_period',
            'label' => __('Lock Period (in months)', 'gmp'),
            'type' => 'number',
            'custom_attributes' => ['min' => '0'],
            'desc_tip' => true,
            'description' => 'Duration of EMI cycle before extension interest applies.'
        ]);

        woocommerce_wp_text_input([
            'id' => '_gmp_extension_months',
            'label' => __('Extension Months', 'gmp'),
            'type' => 'number',
            'custom_attributes' => ['min' => '0'],
            'desc_tip' => true,
            'description' => 'Allowed EMI payments beyond lock period.'
        ]);
        echo '</div>';
    }

    public static function save($post_id) {
        if (isset($_POST['_gmp_lock_period'])) {
            update_post_meta($post_id, '_gmp_lock_period', intval($_POST['_gmp_lock_period']));
        }
        if (isset($_POST['_gmp_extension_months'])) {
            update_post_meta($post_id, '_gmp_extension_months', intval($_POST['_gmp_extension_months']));
        }
    }
}
