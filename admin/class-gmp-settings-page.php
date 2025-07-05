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
        <h1>Gold Money Plan – EMI Interest Settings</h1>

        <?php if ( isset( $_GET['saved'] ) ): ?>
            <div class="notice notice-success"><p>Settings updated successfully.</p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'gmp_save_settings' ); ?>
            <input type="hidden" name="action" value="gmp_save_settings" />

            <div class="gmp-settings-grid">
    <?php foreach ( $products as $product ) :
        $pid = $product->get_id();
        $lock = get_post_meta( $pid, '_gmp_lock_months', true );
        $ext  = get_post_meta( $pid, '_gmp_extension_months', true );
        $base = get_post_meta( $pid, '_gmp_base_interest', true );
        $ei   = get_post_meta( $pid, '_gmp_extension_interest', true );
        $ei   = is_array( $ei ) ? $ei : maybe_unserialize( $ei );
    ?>
    <div class="gmp-card gmp-card-open">
        <div class="gmp-card-header">
            <span class="gmp-card-title"><?php echo esc_html( $product->get_name() ); ?></span>
            <span class="gmp-toggle-icon">&#9660;</span>
        </div>
        <div class="gmp-card-body">
            <div class="gmp-setting-row">
                <label>Lock Period (Months):</label>
                <div>
                    <?php echo esc_html( $lock ); ?>
                    <input type="hidden" name="lock[<?php echo $pid; ?>]" value="<?php echo esc_attr( $lock ); ?>">
                </div>
            </div>
            <div class="gmp-setting-row">
                <label>Extension Period (Months):</label>
                <div>
                    <?php echo esc_html( $ext ); ?>
                    <input type="hidden" name="ext[<?php echo $pid; ?>]" value="<?php echo esc_attr( $ext ); ?>">
                </div>
            </div>
            <div class="gmp-setting-row">
                <label>Base Interest (%):</label>
                <input type="number" step="0.01" min="0" name="base[<?php echo $pid; ?>]" value="<?php echo esc_attr( $base ); ?>" />
            </div>
            <div class="gmp-setting-row gmp-ext-row">
                <label>Extension Interest per Month:</label>
                <div class="gmp-ext-inputs">
                    <?php for ( $i = 1; $i <= intval($ext); $i++ ) :
                        $val = isset( $ei[$i] ) ? esc_attr( $ei[$i] ) : '';
                    ?>
                        <label class="gmp-ext-item">M<?php echo $i; ?>
                            <input type="number" step="0.01" min="0" name="ei[<?php echo $pid; ?>][<?php echo $i; ?>]" value="<?php echo $val; ?>" />
                        </label>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
 

            <p><input type="submit" class="button button-primary" value="Save All Settings"></p>
        </form>
    </div>
 

    <script>
   jQuery(document).ready(function($){
    $('.gmp-card').addClass('open'); // Keep all open initially

    $('.gmp-card-header').on('click', function(){
        var card = $(this).closest('.gmp-card');
        card.toggleClass('open');
        card.find('.gmp-card-body').slideToggle(150);
    });
});

    </script>
    <?php
}


    public static function save_settings() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Not allowed' );
        }

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
        foreach ( $ei as $pid => $month_vals ) {
            if ( is_array( $month_vals ) ) {
                $assoc = [];
                foreach ( $month_vals as $month => $rate ) {
                    $assoc[ absint($month) ] = floatval($rate);
                }
                update_post_meta( $pid, '_gmp_extension_interest', $assoc );
            }
        }

        wp_redirect( admin_url( 'admin.php?page=gmp-settings&saved=1' ) );
        exit;
    }
}
