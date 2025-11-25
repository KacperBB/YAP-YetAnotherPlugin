<?php

add_action('wp_ajax_yap_delete_group', 'yap_delete_group_ajax');

function yap_delete_group_ajax() {
    check_ajax_referer('yap_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Brak uprawnień']);
    }
    
    global $wpdb;
    $table_name = sanitize_text_field($_POST['table']);
    
    if (empty($table_name)) {
        wp_send_json_error(['message' => 'Nie podano nazwy tabeli']);
    }
    
    error_log("🗑️ Usuwanie grupy: " . $table_name);
    
    // Pobierz nazwę wyświetlaną
    $display_name = preg_replace('/^wp_group_(.*?)_pattern$/', '$1', $table_name);
    
    // Usuń tabelę pattern
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    
    // Usuń odpowiadającą tabelę data
    $data_table = str_replace('_pattern', '_data', $table_name);
    $wpdb->query("DROP TABLE IF EXISTS {$data_table}");
    
    error_log("✅ Grupa usunięta: " . $display_name);
    
    wp_send_json_success([
        'message' => 'Grupa "' . $display_name . '" została usunięta'
    ]);
}
