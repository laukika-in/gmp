<?php
class GMP_Product_Fields {
    public static function add() {
        global $product_object;

        echo '<div class="options_group">';
        
        woocommerce_wp_checkbox([
            'id' => '_gmp_enable_extension',
            'label' => __('Enable Extension Period', 'gmp'),
            'description' => __('Allow paying beyond subscription expiry.'),
            'desc_tip' => true,
        ]);

        woocommerce_wp_text_input([
            'id' => '_gmp_extension_months',
            'label' => __('Number of Extension Months', 'gmp'),
            'type' => 'number',
            'custom_attributes' => ['min' => '0', 'step' => '1'],
            'description' => __('Optional extension months after plan ends.'),
            'desc_tip' => true,
        ]);

        echo '</div>';
        ?>
        <script>
        jQuery(function($) {
            function toggleField() {
                if ($('#_gmp_enable_extension').is(':checked')) {
                    $('#_gmp_extension_months').closest('.form-field').show();
                } else {
                    $('#_gmp_extension_months').closest('.form-field').hide();
                }
            }
            toggleField();
            $('#_gmp_enable_extension').on('change', toggleField);
        });
        </script>
        <?php
    }

    public static function save($post_id) {
        $enabled = isset($_POST['_gmp_enable_extension']) ? 'yes' : 'no';
        update_post_meta($post_id, '_gmp_enable_extension', $enabled);

        if (isset($_POST['_gmp_extension_months'])) {
            update_post_meta($post_id, '_gmp_extension_months', sanitize_text_field($_POST['_gmp_extension_months']));
        }
    }
}
