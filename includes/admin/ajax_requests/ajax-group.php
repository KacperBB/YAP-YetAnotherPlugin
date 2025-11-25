<?php
function yap_update_group_ajax() {
    check_ajax_referer('yap_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error("❌ Nie masz uprawnień do tej akcji.");
        return;
    }

    global $wpdb;

    if (empty($_POST['table_name'])) {
        error_log("❌ ERROR: Brak nazwy tabeli w AJAX!");
        wp_send_json_error("❌ Brak nazwy tabeli.");
        return;
    }

    $table_name = sanitize_text_field($_POST['table_name']);
    parse_str($_POST['form_data'], $form_data);

    // 🔹 Aktualizacja pól głównej grupy
    if (isset($form_data['field_id']) && is_array($form_data['field_id'])) {
        foreach ($form_data['field_id'] as $index => $field_id) {
            if (!isset($form_data['field_name'][$field_id]) || 
                !isset($form_data['field_type'][$field_id]) || 
                !isset($form_data['field_value'][$field_id])) {
                continue;
            }

            $field_name = sanitize_text_field($form_data['field_name'][$field_id]);
            $field_type = sanitize_text_field($form_data['field_type'][$field_id]);
            $field_value = sanitize_text_field($form_data['field_value'][$field_id]);

            $wpdb->update(
                $table_name,
                [
                    'user_name' => $field_name,
                    'field_type' => $field_type,
                    'field_value' => $field_value
                ],
                ['id' => $field_id]
            );
        }
    }

    // 🔹 Obsługa zagnieżdżonych grup
    if (isset($form_data['nested_table_name']) && is_array($form_data['nested_table_name'])) {
        foreach ($form_data['nested_table_name'] as $nested_table_index => $nested_table) {
            if (empty($nested_table)) {
                continue;
            }

            if (!isset($form_data['parent_field_id'][$nested_table_index])) {
                continue;
            }

            $parent_field_id = intval($form_data['parent_field_id'][$nested_table_index]);

            if (isset($form_data['field_id']) && is_array($form_data['field_id'])) {
                foreach ($form_data['field_id'] as $index => $field_id) {
                    if (!isset($form_data['field_name'][$field_id]) || 
                        !isset($form_data['field_type'][$field_id]) || 
                        !isset($form_data['field_value'][$field_id])) {
                        continue;
                    }

                    $field_name = sanitize_text_field($form_data['field_name'][$field_id]);
                    $field_type = sanitize_text_field($form_data['field_type'][$field_id]);
                    $field_value = sanitize_text_field($form_data['field_value'][$field_id]);

                    $wpdb->update(
                        $nested_table,
                        [
                            'user_name' => $field_name,
                            'field_type' => $field_type,
                            'field_value' => $field_value
                        ],
                        ['id' => $field_id]
                    );
                }
            }
        }
    }

    wp_send_json_success(["message" => "✅ Grupa została zaktualizowana."]);
}


function yap_delete_field_ajax() {
    check_ajax_referer('yap_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error("❌ Brak uprawnień.");
        return;
    }

    global $wpdb;
    $field_id = intval($_POST['field_id']);
    $table_name = sanitize_text_field($_POST['table_name']); // Pobranie poprawnej nazwy tabeli
    $is_nested = isset($_POST['is_nested']) ? filter_var($_POST['is_nested'], FILTER_VALIDATE_BOOLEAN) : false;

    if (!$field_id || empty($table_name)) {
        wp_send_json_error("❌ Nieprawidłowe ID pola lub brak tabeli.");
        return;
    }

    // Pobierz szczegóły pola przed usunięciem
    $field = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $field_id));

    if (!$field) {
        wp_send_json_error("❌ Nie znaleziono pola.");
        return;
    }

    // **🔹 Jeśli to jest "nested_group", nie usuwamy całej tabeli - tylko referencję!**
    if ($field->field_type === 'nested_group') {
        // Sprawdzamy, czy to zagnieżdżona grupa i usuwamy referencję w `nested_field_ids`
        $parent_table = $is_nested ? str_replace('_pattern', '', $table_name) . '_pattern' : $table_name;
        $parent_field = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$parent_table} WHERE FIND_IN_SET(%d, nested_field_ids)", $field_id));

        if ($parent_field) {
            $nested_ids = json_decode($parent_field->nested_field_ids, true);
            if (is_array($nested_ids)) {
                $nested_ids = array_filter($nested_ids, function ($id) use ($field_id) {
                    return $id != $field_id;
                });
                $wpdb->update($parent_table, ['nested_field_ids' => json_encode($nested_ids)], ['id' => $parent_field->id]);
            }
        }
    }

    // **🔹 Usuń tylko to konkretne pole, ale NIE CAŁĄ ZAGNIEŻDZONĄ GRUPĘ!**
    $deleted = $wpdb->delete($table_name, ['id' => $field_id]);

    if ($deleted) {
        wp_send_json_success(["message" => "✅ Pole zostało usunięte."]);
    } else {
        wp_send_json_error("❌ Nie udało się usunąć pola.");
    }
}

add_action('wp_ajax_yap_delete_field', 'yap_delete_field_ajax');
add_action('wp_ajax_nopriv_yap_delete_group', 'yap_delete_field_ajax');
// 🔹 Rejestracja akcji AJAX
add_action('wp_ajax_yap_update_group', 'yap_update_group_ajax');
add_action('wp_ajax_nopriv_yap_update_group', 'yap_update_group_ajax');



?>