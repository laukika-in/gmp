<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Settings_Page {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_post_gmp_save_settings', [ __CLASS__, 'save_settings' ] );
    }

    public static function add_menu() {
        add_submenu_page(
            'woocommerce',
            'GMP Settings',
            'GMP Settings',
            'manage_woocommerce',
            'gmp-settings',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function render_page() {
        $products = wc_get_products([
            'status'    => 'publish',
            'limit'     => -1,
            'type'      => 'variable',
            'category'  => [ 'gmp-plan' ],
            'orderby'   => 'title',
            'order'     => 'ASC',
        ]);

        ?>
        <div class="wrap">
            <h1>Gold Money Plan Settings</h1>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'gmp_save_settings' ); ?>
                <input type="hidden" name="action" value="gmp_save_settings" />

                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Lock Period (months)</th>
                            <th>Extension Period (months)</th>
                            <th>Base Interest (%)</th>
                            <th>Extension Interest % (comma-separated)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $products as $product ) :
                        $pid = $product->get_id();
                        $lock = get_post_meta( $pid, '_gmp_lock_months', true );
                        $ext  = get_post_meta( $pid, '_gmp_extension_months', true );
                        $base = get_post_meta( $pid, '_gmp_base_interest', true );
                        $ei   = get_post_meta( $pid, '_gmp_extension_interest', true );
                        $ei   = is_array( $ei ) ? implode( ',', $ei ) : maybe_unserialize( $ei );
                        ?>
                        <tr>
                            <td><?php echo esc_html( $product->get_name() ); ?></td>
                            <td><input type="number" name="lock[<?php echo $pid; ?>]" value="<?php echo esc_attr( $lock ); ?>" min="0" /></td>
                            <td><input type="number" name="ext[<?php echo $pid; ?>]" value="<?php echo esc_attr( $ext ); ?>" min="0" /></td>
                            <td><input type="number" step="0.01" name="base[<?php echo $pid; ?>]" value="<?php echo esc_attr( $base ); ?>" min="0" /></td>
                            <td><input type="text" name="ei[<?php echo $pid; ?>]" value="<?php echo esc_attr( $ei ); ?>" placeholder="e.g. 10,12" /></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <p><input type="submit" class="button-primary" value="Save Settings" /></p>
            </form>
        </div>
        <?php
    }

    public static function save_settings() {
        check_admin_referer( 'gmp_save_settings' );

        $lock = $_POST['lock'] ?? [];
        $ext  = $_POST['ext'] ?? [];
        $base = $_POST['base'] ?? [];
        $ei   = $_POST['ei'] ?? [];

        foreach ( $lock as $pid => $val ) {
            update_post_meta( $pid, '_gmp_lock_months', absint( $val ) );
        }
        foreach ( $ext as $pid => $val ) {
            update_post_meta( $pid, '_gmp_extension_months', absint( $val ) );
        }
        foreach ( $base as $pid => $val ) {
            update_post_meta( $pid, '_gmp_base_interest', floatval( $val ) );
        }
        foreach ( $ei as $pid => $val ) {
            $parts = array_map( 'floatval', array_filter( array_map( 'trim', explode( ',', $val ) ) ) );
            $assoc = [];
            foreach ( $parts as $i => $v ) {
                $assoc[$i + 1] = $v;
            }
            update_post_meta( $pid, '_gmp_extension_interest', $assoc );
        }

        wp_redirect( admin_url( 'admin.php?page=gmp-settings&saved=1' ) );
        exit;
    }
}
