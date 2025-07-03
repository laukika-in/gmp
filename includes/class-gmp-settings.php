<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Settings {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function add_menu() {
        add_submenu_page(
            'edit.php?post_type=gmp_subscription',
            'GMP Interest Settings',
            'Interest Settings',
            'manage_options',
            'gmp-interest-settings',
            [ __CLASS__, 'settings_page' ]
        );
    }

    public static function register_settings() {
        register_setting( 'gmp_interest_settings_group', 'gmp_interest_settings' );
    }

    public static function settings_page() {
        $products = wc_get_products([ 'limit' => -1, 'type' => ['variable'], 'status' => 'publish' ]);
        $settings = get_option( 'gmp_interest_settings', [] );
        ?>
        <div class="wrap">
            <h1>Gold Money Plan – Interest Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'gmp_interest_settings_group' ); ?>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Base Interest (%)</th>
                            <th>Extension Interest (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $products as $product ) : 
                            $product_id = $product->get_id();
                            $base = isset( $settings[$product_id]['base'] ) ? $settings[$product_id]['base'] : '';
                            $ext  = isset( $settings[$product_id]['ext'] ) ? implode( ',', $settings[$product_id]['ext'] ) : '';
                        ?>
                        <tr>
                            <td><?php echo esc_html( $product->get_name() ); ?></td>
                            <td>
                                <input type="number" step="0.01" name="gmp_interest_settings[<?php echo $product_id; ?>][base]" value="<?php echo esc_attr( $base ); ?>" />
                            </td>
                            <td>
                                <input type="text" name="gmp_interest_settings[<?php echo $product_id; ?>][ext]" value="<?php echo esc_attr( $ext ); ?>" placeholder="e.g., 10,12,15" />
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
