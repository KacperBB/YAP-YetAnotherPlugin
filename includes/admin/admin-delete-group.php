<?php
function yap_safe_strpos($haystack, $needle) {
    if (is_null($haystack) || is_null($needle)) {
        return false;
    }
    return strpos($haystack, $needle);
}

function yap_safe_str_replace($search, $replace, $subject) {
    if (is_null($subject)) {
        return $subject; // Zwróć niezmieniony temat, jeśli jest null
    }
    return str_replace($search, $replace, $subject);
}

function yap_delete_field($field_id, $pattern_table, $data_table) {
    global $wpdb;

    // Usuń pole z tabeli wzorca
    $field = $wpdb->get_row($wpdb->prepare("SELECT * FROM $pattern_table WHERE id = %d", $field_id));

    if ($field) {
        $wpdb->delete($pattern_table, ['id' => $field_id]);

        // Usuń powiązane pola z tabeli danych
        $wpdb->delete($data_table, ['generated_name' => $field->generated_name]);

        // Usuń powiązane grupy zagnieżdżone
        if ($field->field_type === 'nested_group' && !empty($field->nested_field_ids)) {
            $nested_field_ids = json_decode($field->nested_field_ids, true);
            if (is_array($nested_field_ids)) {
                foreach ($nested_field_ids as $nested_table) {
                    $wpdb->query("DROP TABLE IF EXISTS $nested_table");
                }
            }
        }
    }
}

function yap_delete_group_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $pattern_table_name = sanitize_text_field($_GET['table']);
    $data_table_name = str_replace('_pattern', '_data', $pattern_table_name);

    // Usuń obie tabele
    $wpdb->query("DROP TABLE IF EXISTS $pattern_table_name");
    $wpdb->query("DROP TABLE IF EXISTS $data_table_name");

    echo '<div class="updated"><p>Grupa została usunięta wraz z powiązanymi danymi.</p></div>';
    echo '<a href="' . admin_url('admin.php?page=yap-manage-groups') . '">Powrót do zarządzania grupami</a>';
}


?>