<?php

add_action('wp_ajax_yap_refresh_groups', 'yap_refresh_groups_ajax');

function yap_refresh_groups_ajax() {
    check_ajax_referer('yap_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Brak uprawnień']);
    }
    
    global $wpdb;
    
    // Get groups from multiple sources (same as Visual Builder)
    $groups = [];
    
    // 1. From location_rules (nowy system)
    $location_groups = $wpdb->get_col(
        "SELECT DISTINCT group_name FROM {$wpdb->prefix}yap_location_rules WHERE group_name != '' ORDER BY group_name ASC"
    );
    $groups = array_merge($groups, $location_groups);
    
    // 2. From yap-schemas directory (Visual Builder saves)
    $schema_dir = WP_CONTENT_DIR . '/yap-schemas/';
    if (file_exists($schema_dir)) {
        $schema_files = glob($schema_dir . '*.json');
        foreach ($schema_files as $file) {
            $groups[] = basename($file, '.json');
        }
    }
    
    // 3. From existing wp_yap_* tables (stare grupy)
    $yap_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}yap_%_pattern'");
    
    // System tables to filter out
    $system_tables = ['location', 'options', 'field', 'sync', 'data', 'query', 'automations', 'automation'];
    
    foreach ($yap_tables as $table) {
        // Extract group name from wp_yap_GROUPNAME_pattern
        if (preg_match('/^' . $wpdb->prefix . 'yap_(.+)_pattern$/', $table, $matches)) {
            $group_name = $matches[1];
            // Skip system tables
            if (!in_array($group_name, $system_tables)) {
                $groups[] = $group_name;
            }
        }
    }
    
    // Unique and sort
    $groups = array_unique($groups);
    sort($groups);
    
    // Format for group-list.php (expects $group_tables array)
    $filtered_tables = [];
    foreach ($groups as $group_name) {
        if (!empty($group_name) && $group_name !== '__unconfigured__') {
            $table_name = $wpdb->prefix . 'yap_' . $group_name . '_pattern';
            $filtered_tables[] = (object)[
                'table_name' => $table_name,
                'group_name' => $group_name
            ];
        }
    }
    
    $group_tables = $filtered_tables;
    
    // Pobierz parametr show_nested
    $show_nested = isset($_POST['show_nested']) && $_POST['show_nested'] === 'true';
    
    ob_start();
    include plugin_dir_path(dirname(__FILE__)) . 'views/pattern/group-list.php';
    $html = ob_get_clean();
    
    wp_send_json_success([
        'html' => $html,
        'count' => count($group_tables),
        'show_nested' => $show_nested
    ]);
}
