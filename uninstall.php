<?php
// No data deletion on uninstall – safe uninstall script
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// This plugin does not delete any data upon uninstall
