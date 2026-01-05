<?php
function yap_add_field_ajax() {
    error_log("🔵 yap_add_field_ajax() wywołane!");
    error_log("🔵 POST dane: " . print_r($_POST, true));
    
    check_ajax_referer('yap_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        error_log("🚨 ERROR: Insufficient permissions");
        wp_send_json_error("Insufficient permissions.");
        return;
    }

    global $wpdb;

    $table_name = sanitize_text_field($_POST['table_name'] ?? '');
    $field_name = sanitize_text_field($_POST['new_field_name'] ?? '');
    $field_type = sanitize_text_field($_POST['new_field_type'] ?? '');
    $field_value = sanitize_text_field($_POST['new_field_value'] ?? '');
    $parent_field_id = intval($_POST['parent_field_id'] ?? 0);

    error_log("🔵 Parsed values - Table: {$table_name}, Field: {$field_name}, Type: {$field_type}");

    if (empty($table_name) || empty($field_name) || empty($field_type)) {
        error_log("🚨 ERROR: Missing required fields. Table Name: {$table_name}, Field Name: {$field_name}, Field Type: {$field_type}");
        wp_send_json_error("Missing required fields.");
        return;
    }

    // Obsługa pola typu "nested_group"
    if ($field_type === 'nested_group') {
        // Najpierw wstaw pole do bazy danych, aby wygenerować ID
        $result = $wpdb->insert(
            $table_name,
            [
                'generated_name' => yap_generate_field_name(),
                'user_name' => $field_name,
                'field_type' => $field_type,
                'field_value' => $field_value,
                'field_depth' => 0,
                'nested_field_ids' => json_encode([]), // Pusta lista na ID zagnieżdżonych pól
            ]
        );

        if (!$result) {
            error_log("🚨 ERROR: Failed to add field for nested group to table: {$table_name}");
            wp_send_json_error("Failed to add field for nested group.");
            return;
        }

        // Pobierz ID nowo dodanego pola
        $parent_field_id = $wpdb->insert_id;

        error_log("⚙️ Tworzenie zagnieżdżonej grupy dla Parent Field ID: {$parent_field_id} w tabeli: {$table_name}");
        $nested_group_table = yap_add_nested_group($table_name, $parent_field_id);

        if (!$nested_group_table) {
            error_log("🚨 ERROR: Failed to create nested group for Table: {$table_name}, Parent Field ID: {$parent_field_id}");
            wp_send_json_error("Failed to create nested group.");
        } else {
            wp_send_json_success([
                'message' => "Nested group created successfully.",
                'nested_group_table' => $nested_group_table
            ]);
        }
        return;
    }

    // Sprawdź czy pole o takiej nazwie już istnieje
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table_name} WHERE user_name = %s",
        $field_name
    ));
    
    if ($existing) {
        error_log("🚨 DUPLICATE: Field '{$field_name}' already exists in {$table_name}");
        wp_send_json_error([
            'message' => "Pole o nazwie '{$field_name}' już istnieje w tej grupie."
        ]);
        return;
    }
    
    // Dodanie zwykłego pola
    $result = $wpdb->insert(
        $table_name,
        [
            'generated_name' => yap_generate_field_name(),
            'user_name' => $field_name,
            'field_type' => $field_type,
            'field_value' => $field_value,
            'field_depth' => 0,
            'nested_field_ids' => null
        ]
    );

    if (!$result) {
        $error = $wpdb->last_error;
        error_log("🚨 ERROR: Failed to add field to table: {$table_name} | Error: {$error}");
        wp_send_json_error("Failed to add field: {$error}");
    } else {
        $parent_field_id = $wpdb->insert_id; // Pobierz ID wstawionego pola
        wp_send_json_success([
            'message' => "Field added successfully with ID: {$parent_field_id}",
            'field_id' => $parent_field_id
        ]);
    }
}

add_action('wp_ajax_yap_add_field', 'yap_add_field_ajax');
add_action('wp_ajax_nopriv_yap_add_field', 'yap_add_field_ajax');

?>