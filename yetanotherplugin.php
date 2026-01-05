<?php
/*
Plugin Name: Yet Another Plugin
Description: Plugin do tworzenia niestandardowych grup pól. ACF Killer z zaawansowanymi funkcjami enterprise: Data History, SQL Query Fields, Automation Rules, Live Preview, Module System.
Version: 1.4.0
Author: Kacper Borowiec
Text Domain: yap
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load plugin text domain for translations
add_action('plugins_loaded', function() {
    load_plugin_textdomain(
        'yap',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
});

// Core includes
require_once plugin_dir_path(__FILE__) . 'includes/admin.php';
require_once plugin_dir_path(__FILE__) . 'db/database.php';
require_once plugin_dir_path(__FILE__) . 'includes/display.php';
require_once plugin_dir_path(__FILE__) . 'includes/field-helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/validation.php';
require_once plugin_dir_path(__FILE__) . 'includes/repeater.php';
require_once plugin_dir_path(__FILE__) . 'includes/locations.php';
require_once plugin_dir_path(__FILE__) . 'includes/field-registration.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';

// Initialize location rules AJAX handlers
YAP_Location_Rules::init();
require_once plugin_dir_path(__FILE__) . 'includes/json-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/rest-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks.php';
require_once plugin_dir_path(__FILE__) . 'includes/blocks.php';
require_once plugin_dir_path(__FILE__) . 'includes/theme-json.php';
require_once plugin_dir_path(__FILE__) . 'includes/graphql.php';
require_once plugin_dir_path(__FILE__) . 'includes/backup.php';
require_once plugin_dir_path(__FILE__) . 'includes/computed-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-nested-requests.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-ajax-request.php';

// Advanced features (Round 1 - Performance & Developer Tools)
require_once plugin_dir_path(__FILE__) . 'includes/transformers.php';
require_once plugin_dir_path(__FILE__) . 'includes/sanitizers.php';
require_once plugin_dir_path(__FILE__) . 'includes/cache.php';
require_once plugin_dir_path(__FILE__) . 'includes/field-hooks.php';
require_once plugin_dir_path(__FILE__) . 'includes/debug-overlay.php';

// Advanced features (Round 2 - ACF PRO Killer)
require_once plugin_dir_path(__FILE__) . 'includes/form-renderer.php';
require_once plugin_dir_path(__FILE__) . 'includes/migrations.php';
require_once plugin_dir_path(__FILE__) . 'includes/webhooks.php';
require_once plugin_dir_path(__FILE__) . 'includes/api-fields.php';

// Enterprise features (Round 3 - Nocode & DevOps Tools)
require_once plugin_dir_path(__FILE__) . 'includes/visual-builder.php';
require_once plugin_dir_path(__FILE__) . 'includes/cli-tools.php';
require_once plugin_dir_path(__FILE__) . 'includes/field-mocking.php';
require_once plugin_dir_path(__FILE__) . 'includes/field-templates.php';
require_once plugin_dir_path(__FILE__) . 'includes/field-sync.php';

// Enterprise features (Round 4 - Advanced Enterprise)
require_once plugin_dir_path(__FILE__) . 'includes/data-history.php';
require_once plugin_dir_path(__FILE__) . 'includes/advanced-query-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/automation-rules.php';
require_once plugin_dir_path(__FILE__) . 'includes/live-preview.php';
require_once plugin_dir_path(__FILE__) . 'includes/module-system.php';
require_once plugin_dir_path(__FILE__) . 'includes/flexible-content.php';

// Table Grouping
require_once plugin_dir_path(__FILE__) . 'includes/table-grouping.php';

// Load test auto-loader in development
require_once plugin_dir_path(__FILE__) . 'includes/admin-modules/assets/test-auto-loader.php';

// Initialize core systems
YAP_Blocks::get_instance();
YAP_Theme_JSON::get_instance();
YAP_REST_API::get_instance();
YAP_GraphQL::get_instance();
YAP_Backup::get_instance();
YAP_Computed_Fields::get_instance();

// Initialize advanced features
YAP_Transformers::get_instance();
YAP_Sanitizers::get_instance();
YAP_Cache::get_instance();
YAP_Field_Hooks::get_instance();
YAP_Debug_Overlay::get_instance();
YAP_Form_Renderer::get_instance();
YAP_Migrations::get_instance();
YAP_Webhooks::get_instance();
YAP_API_Fields::get_instance();

// Initialize enterprise features
YAP_Visual_Builder::get_instance();
YAP_Field_Mocking::get_instance();
YAP_Field_Templates::get_instance();
YAP_Field_Sync::get_instance();

// Initialize advanced enterprise features
YAP_Data_History::get_instance();
YAP_Advanced_Query_Fields::get_instance();
YAP_Automation_Rules::get_instance();
YAP_Live_Preview::get_instance();
YAP_Modules::get_instance();

// Aktywacja pluginu
register_activation_hook(__FILE__, 'yap_activate_plugin');
function yap_activate_plugin() {
    yap_create_location_rules_table();
    yap_create_options_table();
    yap_create_sync_log_table();
    yap_create_data_history_table();
    yap_create_query_fields_table();
    yap_create_automations_table();
    yap_create_automation_log_table();
    yap_create_field_metadata_table();
    yap_update_pattern_tables_for_flexible(); // Add flexible_layouts column
}

// Update tables on plugin load (for existing installations)
add_action('admin_init', 'yap_check_flexible_column');
function yap_check_flexible_column() {
    if (get_option('yap_flexible_column_added') !== 'yes') {
        yap_update_pattern_tables_for_flexible();
        update_option('yap_flexible_column_added', 'yes');
    }
}

?>
