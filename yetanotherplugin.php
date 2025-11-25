<?php
/*
Plugin Name: Yet Another Plugin
Description: Plugin do tworzenia niestandardowych grup pól.
Version: 1.1
Author: Kacper Borowiec
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . 'includes/admin.php';
require_once plugin_dir_path(__FILE__) . 'db/database.php';
require_once plugin_dir_path(__FILE__) . 'includes/display.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-nested-requests.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-ajax-request.php';

// Aktywacja pluginu
register_activation_hook(__FILE__, 'yap_activate_plugin');
function yap_activate_plugin() {
    // No need to create a database on activation
}

?>
