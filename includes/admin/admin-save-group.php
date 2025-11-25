<?php

function yap_save_group() {
    global $wpdb;

    $group_name = sanitize_text_field($_POST['group_name']);
    $post_type = sanitize_text_field($_POST['post_type']);
    $category = sanitize_text_field($_POST['category']);
    $tables = yap_create_dynamic_table($group_name);
    $pattern_table = $tables['pattern_table'];

    error_log("Saving group: $group_name, Post type: $post_type, Category: $category, Pattern Table: $pattern_table");

    if (isset($_POST['field_name']) && is_array($_POST['field_name'])) {
        foreach ($_POST['field_name'] as $index => $field_name) {
            $field_type = sanitize_text_field($_POST['field_type'][$index]);
            $field_value = sanitize_text_field($_POST['field_value'][$index]);
            $data = [
                'generated_name' => yap_generate_field_name(),
                'user_name' => sanitize_text_field($field_name),
                'field_type' => $field_type,
                'field_value' => $field_value,
                'field_depth' => 0,
                'nested_field_ids' => ''
            ];
            $wpdb->insert($pattern_table, $data);

            // Pobierz ID wstawionego rekordu
            $inserted_field_id = $wpdb->insert_id;

            error_log("Inserted field with ID: $inserted_field_id, Data: " . print_r($data, true));
        }
    }
}


?>