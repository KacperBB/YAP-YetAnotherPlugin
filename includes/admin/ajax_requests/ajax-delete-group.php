<?php

add_action('wp_ajax_yap_delete_group', 'yap_delete_group_ajax');

function yap_delete_group_ajax() {
    check_ajax_referer('yap_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Brak uprawnień']);
    }
    
    global $wpdb;
    $table_name = sanitize_text_field($_POST['table']);
    $group_name = isset($_POST['group_name']) ? sanitize_text_field($_POST['group_name']) : '';
    
    if (empty($table_name)) {
        wp_send_json_error(['message' => 'Nie podano nazwy tabeli']);
    }
    
    // Validate table name format and prefix for security (obsługuje zarówno wp_group_* jak i wp_yap_*)
    // Akceptuj: a-z, A-Z, 0-9, _, -
    $is_valid = preg_match('/^' . preg_quote($wpdb->prefix, '/') . '(group|yap)_[a-zA-Z0-9_-]+_pattern$/i', $table_name);
    
    if (!$is_valid) {
        error_log("🚨 SECURITY: Invalid table name format: " . $table_name);
        wp_send_json_error(['message' => 'Nieprawidłowa nazwa tabeli']);
    }
    
    error_log("🗑️ Usuwanie grupy: " . $table_name);
    
    // Pobierz nazwę wyświetlaną
    if (empty($group_name)) {
        if (preg_match('/^' . preg_quote($wpdb->prefix, '/') . 'yap_(.*?)_pattern$/', $table_name, $matches)) {
            $group_name = $matches[1];
        } elseif (preg_match('/^' . preg_quote($wpdb->prefix, '/') . 'group_(.*?)_pattern$/', $table_name, $matches)) {
            $group_name = $matches[1];
        }
    }
    
    // Usuń tabelę pattern jeśli istnieje
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ($table_exists === $table_name) {
        $safe_table_name = esc_sql($table_name);
        $wpdb->query("DROP TABLE IF EXISTS `{$safe_table_name}`");
    }
    
    // Usuń odpowiadającą tabelę data
    $data_table = str_replace('_pattern', '_data', $table_name);
    $data_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $data_table));
    if ($data_exists === $data_table) {
        $safe_data_table = esc_sql($data_table);
        $wpdb->query("DROP TABLE IF EXISTS `{$safe_data_table}`");
    }
    
    // Usuń JSON schema jeśli istnieje
    if (!empty($group_name)) {
        $schema_file = WP_CONTENT_DIR . '/yap-schemas/' . $group_name . '.json';
        if (file_exists($schema_file)) {
            unlink($schema_file);
            error_log("🗑️ Usunięto schema JSON: " . $schema_file);
        }
        
        // Usuń location rules
        $wpdb->delete(
            $wpdb->prefix . 'yap_location_rules',
            ['group_name' => $group_name],
            ['%s']
        );
    }
    
    error_log("✅ Grupa usunięta: " . $group_name);
    
    wp_send_json_success([
        'message' => 'Grupa "' . $group_name . '" została usunięta'
    ]);
}
