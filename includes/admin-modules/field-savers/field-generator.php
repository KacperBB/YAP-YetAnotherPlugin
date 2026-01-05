<?php
/**
 * Field Generator
 * 
 * Automatyczne generowanie pól dla nowych postów
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wygeneruj pola dla nowego posta
 * Uruchamia się z wyższym priorytetem niż zapis
 */
function yap_generate_fields_for_post($post_id) {
    global $wpdb;
    
    // Zabezpieczenia przed duplikatami
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    if (wp_is_post_autosave($post_id)) {
        return;
    }

    $all_pattern_tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_group_%_pattern'");
    foreach ($all_pattern_tables as $table) {
        $pattern_table = current((array)$table);
        $data_table = str_replace('_pattern', '_data', $pattern_table);

        $fields = $wpdb->get_results("SELECT * FROM {$pattern_table}");

        foreach ($fields as $field) {
            // Użyj INSERT IGNORE aby uniknąć race condition
            $wpdb->query($wpdb->prepare(
                "INSERT IGNORE INTO {$data_table}
                (generated_name, user_name, field_type, field_value, associated_id)
                VALUES (%s, %s, %s, %s, %d)",
                $field->generated_name,
                $field->user_name,
                $field->field_type,
                '',
                $post_id
            ));
        }
    }
}

// Wyższy priorytet (5) aby wykonało się przed yap_save_post_fields (10)
add_action('save_post', 'yap_generate_fields_for_post', 5);
