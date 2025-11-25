<?php 

function yap_add_nested_field_ajax() {
    check_ajax_referer('yap_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error("Insufficient permissions.");
        return;
    }

    global $wpdb;

    // Pobierz dane z żądania AJAX
    $nested_table_name = sanitize_text_field($_POST['nested_table_name'] ?? '');
    $field_name = sanitize_text_field($_POST['field_name'] ?? '');
    $field_type = sanitize_text_field($_POST['field_type'] ?? '');
    $field_value = sanitize_text_field($_POST['field_value'] ?? '');
    $parent_field_id = intval($_POST['parent_field_id'] ?? 0);

    error_log("⚙️ Próba dodania pola do tabeli: {$nested_table_name}");
    error_log("Field Name: {$field_name}");
    error_log("Field Type: {$field_type}");
    error_log("Field Value: {$field_value}");
    error_log("Parent Field ID: {$parent_field_id}");

    // Walidacja
    if (empty($nested_table_name) || empty($field_name) || empty($field_type)) {
        error_log("🚨 ERROR: Missing required fields.");
        wp_send_json_error("Missing required fields.");
        return;
    }

    // Sprawdzenie tabeli
    $check_table = $wpdb->get_var("SHOW TABLES LIKE '{$nested_table_name}'");
    if (!$check_table) {
        error_log("🚨 ERROR: Tabela {$nested_table_name} nie istnieje.");
        wp_send_json_error("Table does not exist.");
        return;
    }

    // Domyślna głębokość
    $field_depth = 0;

    if ($parent_field_id) {
        $parent_field = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$nested_table_name} WHERE id = %d", $parent_field_id));
        if ($parent_field) {
            $field_depth = $parent_field->field_depth + 1;
        } else {
            error_log("🚨 ERROR: Pole nadrzędne nie istnieje w tabeli {$nested_table_name} dla ID {$parent_field_id}");
        }
    }

    // Dodanie pola
    $result = $wpdb->insert(
        $nested_table_name,
        [
            'generated_name' => 'field_' . time(),
            'user_name' => $field_name,
            'field_type' => $field_type,
            'field_value' => $field_value,
            'field_depth' => $field_depth,
            'nested_field_ids' => ($field_type === 'nested_group') ? json_encode([]) : null
        ]
    );

    if (!$result) {
        error_log("🚨 ERROR: Nie udało się wstawić pola do tabeli {$nested_table_name}");
        wp_send_json_error("Failed to insert field.");
        return;
    }

    $new_field_id = $wpdb->insert_id;
    error_log("✅ Pole dodane pomyślnie z ID: {$new_field_id}");

    wp_send_json_success([
        'message' => 'Field added successfully.',
        'field_id' => $new_field_id
    ]);
}



// Register AJAX actions
add_action('wp_ajax_yap_add_nested_field', 'yap_add_nested_field_ajax');
add_action('wp_ajax_nopriv_yap_add_nested_field', 'yap_add_nested_field_ajax');

?>