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
        $base_interest    = get_post_meta( $post->ID, '_gmp_base_interest', true );
        $extension_interest = get_post_meta( $post->ID, '_gmp_extension_interest', true );
        $extension_interest = is_array( $extension_interest ) ? $extension_interest : maybe_unserialize( $extension_interest );
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

                woocommerce_wp_text_input([
                    'id'          => '_gmp_base_interest',
                    'label'       => 'Base Interest (%)',
                    'type'        => 'number',
                    'desc_tip'    => true,
                    'description' => 'Interest rate during lock period.',
                    'value'       => $base_interest,
                    'custom_attributes' => [ 'min' => 0, 'step' => 0.01 ],
                ]);
                ?>
                <p class="form-field">
                    <label><strong>Extension Interest per Month (%)</strong></label><br />
                    <?php
                    $ext_months = intval( $extension_months );
                    for ( $i = 1; $i <= $ext_months; $i++ ) {
                        $val = isset( $extension_interest[$i] ) ? esc_attr( $extension_interest[$i] ) : '';
                        echo "<label style='display:inline-block; width:80px;'>M{$i} <input type='number' step='0.01' min='0' name='_gmp_extension_interest[$i]' value='$val' style='width:60px; margin-left:3px;' /></label> ";
                    }
                    ?>
                </p>
            </div>
        </div>
        <?php
    }

    public static function save_fields( $post_id ) {
        update_post_meta( $post_id, '_gmp_lock_months',      absint( $_POST['_gmp_lock_months'] ?? 0 ) );
        update_post_meta( $post_id, '_gmp_extension_months', absint( $_POST['_gmp_extension_months'] ?? 0 ) );
        update_post_meta( $post_id, '_gmp_base_interest',    floatval( $_POST['_gmp_base_interest'] ?? 0 ) );

        if ( isset( $_POST['_gmp_extension_interest'] ) && is_array( $_POST['_gmp_extension_interest'] ) ) {
            $safe_interest = array_map( 'floatval', $_POST['_gmp_extension_interest'] );
            update_post_meta( $post_id, '_gmp_extension_interest', $safe_interest );
        }
    }
}
