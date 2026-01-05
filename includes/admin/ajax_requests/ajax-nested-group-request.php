<?php

function yap_add_nested_group($parent_table, $parent_field_id) {
    global $wpdb;

    $nested_group_name = 'nested_group_' . uniqid('', true);
    $nested_tables = yap_create_dynamic_table($nested_group_name);
    
    if (!$nested_tables || !isset($nested_tables['pattern_table'])) {
        error_log("🚨 ERROR: Nie udało się utworzyć tabeli dla zagnieżdżonej grupy.");
        return false;
    }

    $nested_table_name = $nested_tables['pattern_table'];

    // Pobranie istniejących zagnieżdżonych grup
    $existing_nested_group = $wpdb->get_var($wpdb->prepare(
        "SELECT nested_field_ids FROM {$parent_table} WHERE id = %d",
        $parent_field_id
    ));

    $nested_field_ids = $existing_nested_group ? json_decode($existing_nested_group, true) : [];

    if (!in_array($nested_table_name, $nested_field_ids)) {
        $nested_field_ids[] = $nested_table_name;
        $wpdb->update(
            $parent_table,
            ['nested_field_ids' => json_encode($nested_field_ids)],
            ['id' => $parent_field_id]
        );
    }

    return $nested_table_name;
}

add_action('wp_ajax_yap_add_nested_group', 'yap_add_nested_group_ajax');
add_action('wp_ajax_nopriv_yap_add_nested_group', 'yap_add_nested_group_ajax');

function yap_add_nested_group_ajax() {
    check_ajax_referer('yap_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error("❌ Brak uprawnień.");
        return;
    }

    global $wpdb;

    $parent_table = sanitize_text_field($_POST['parent_table'] ?? '');
    $parent_field_id = intval($_POST['parent_field_id'] ?? 0);

    // Sprawdzamy, czy wszystkie dane są podane
    if (empty($parent_table) || empty($parent_field_id)) {
        error_log("🚨 ERROR: Brak wymaganych argumentów. parent_table: {$parent_table}, parent_field_id: {$parent_field_id}");
        wp_send_json_error("❌ Brak wymaganych argumentów: parent_table lub parent_field_id.");
        return;
    }

    // Tworzymy nową tabelę dla zagnieżdżonej grupy
    $nested_group_name = 'nested_group_' . uniqid('', true);
    $nested_table_name = yap_create_dynamic_table($nested_group_name)['pattern_table'];

    if (!$nested_table_name) {
        error_log("🚨 ERROR: Nie udało się utworzyć zagnieżdżonej grupy.");
        wp_send_json_error("❌ Nie udało się utworzyć zagnieżdżonej grupy.");
        return;
    }

    // Pobierz aktualną listę zagnieżdżonych grup
    $parent_field = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$parent_table} WHERE id = %d", $parent_field_id));
    if ($parent_field) {
        $nested_field_ids = json_decode($parent_field->nested_field_ids, true);
        if (!is_array($nested_field_ids)) {
            $nested_field_ids = [];
        }
        $nested_field_ids[] = $nested_table_name;

        // Zaktualizuj `nested_field_ids` w grupie nadrzędnej
        $wpdb->update(
            $parent_table,
            ['nested_field_ids' => json_encode($nested_field_ids)],
            ['id' => $parent_field_id]
        );
    }

    error_log("✅ Zagnieżdżona grupa została utworzona: {$nested_table_name}");

    wp_send_json_success([
        'message' => "✅ Zagnieżdżona grupa została utworzona.",
        'nested_table_name' => $nested_table_name,
    ]);
}


// Zarejestruj funkcję AJAX
add_action('wp_ajax_yap_add_nested_group', 'yap_add_nested_group_ajax');
add_action('wp_ajax_nopriv_yap_add_nested_group', 'yap_add_nested_group_ajax');

?>