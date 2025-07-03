<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Product_Meta {

    public static function init() {
        add_action( 'woocommerce_product_data_tabs', [ __CLASS__, 'add_tab' ] );
        add_action( 'woocommerce_product_data_panels', [ __CLASS__, 'render_panel' ] );
        add_action( 'woocommerce_process_product_meta', [ __CLASS__, 'save_fields' ] );
    }

    public static function add_tab( $tabs ) {
        $tabs['gmp_settings'] = [
            'label'    => 'GMP Settings',
            'target'   => 'gmp_settings_panel',
            'priority' => 90,
            'class'    => [],
        ];
        return $tabs;
    }

    public static function render_panel() {
        global $post;

        $lock_months      = get_post_meta( $post->ID, '_gmp_lock_months', true );
        $extension_months = get_post_meta( $post->ID, '_gmp_extension_months', true );
        ?>
        <div id="gmp_settings_panel" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                woocommerce_wp_text_input([
                    'id'          => '_gmp_lock_months',
                    'label'       => 'Lock Period (months)',
                    'type'        => 'number',
                    'desc_tip'    => true,
                    'description' => 'Number of months before extension period begins.',
                    'value'       => $lock_months,
                    'custom_attributes' => [ 'min' => 0 ],
                ]);

                woocommerce_wp_text_input([
                    'id'          => '_gmp_extension_months',
                    'label'       => 'Extension Period (months)',
                    'type'        => 'number',
                    'desc_tip'    => true,
                    'description' => 'Number of months allowed in extension period.',
                    'value'       => $extension_months,
                    'custom_attributes' => [ 'min' => 0 ],
                ]);
                ?>
            </div>
        </div>
        <?php
    }

    public static function save_fields( $post_id ) {
        update_post_meta( $post_id, '_gmp_lock_months',      absint( $_POST['_gmp_lock_months'] ?? 0 ) );
        update_post_meta( $post_id, '_gmp_extension_months', absint( $_POST['_gmp_extension_months'] ?? 0 ) );
    }
}
