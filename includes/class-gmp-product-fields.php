<?php
// file: includes/class-gmp-product-fields.php
class GMP_Product_Fields {

  /** Show the meta‐box fields in the General tab */
  public static function add() {
    echo '<div class="options_group">';
    woocommerce_wp_checkbox([
      'id'          => '_gmp_enable_extension',
      'label'       => __('Enable Extension Period', 'gmp'),
      'description' => __('Allow extra installments after lock-period end.', 'gmp'),
      'desc_tip'    => true,
    ]);
    woocommerce_wp_text_input([
      'id'                => '_gmp_extension_months',
      'label'             => __('Number of Extension Months', 'gmp'),
      'type'              => 'number',
      'custom_attributes' => [ 'min' => '0', 'step' => '1' ],
      'description'       => __('How many extra payments can be made after lock-period?', 'gmp'),
      'desc_tip'          => true,
    ]);
    echo '</div>';

    // Tiny JS to show/hide extension-months field
    ?>
    <script>
    jQuery(function($){
      function toggle() {
        var on = $('#_gmp_enable_extension').is(':checked');
        $('#_gmp_extension_months').closest('.form-field').toggle(on);
      }
      toggle();
      $('#_gmp_enable_extension').change(toggle);
    });
    </script>
    <?php
  }

  /** Save the product meta */
  public static function save( $post_id ) {
    $enabled = isset( $_POST['_gmp_enable_extension'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_gmp_enable_extension', $enabled );

    if ( isset( $_POST['_gmp_extension_months'] ) ) {
      update_post_meta( $post_id, '_gmp_extension_months', intval( $_POST['_gmp_extension_months'] ) );
    }
  }
}
