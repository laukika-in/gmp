<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GMP_Admin_List {

    public static function render_page() {
        include GMP_PLUGIN_DIR . 'views/admin-cycle-list.php';
    }
}
