<?php

/**
 * Pobiera wartość pojedynczego pola z grupy dla danego posta
 * 
 * @param string $field_name Nazwa pola (user_name)
 * @param int $post_id ID posta
 * @param string $group_name Nazwa grupy (bez prefiksu wp_group_ i sufiksu _pattern/_data)
 * @param bool $return_url Dla pól typu image, czy zwrócić URL zamiast ID (domyślnie false)
 * @return string|null Wartość pola lub null jeśli nie znaleziono
 */
function yap_get_field($field_name, $post_id, $group_name, $return_url = false) {
    global $wpdb;
    $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
    
    $field = $wpdb->get_row($wpdb->prepare(
        "SELECT field_value, field_type FROM {$data_table} WHERE user_name = %s AND associated_id = %d",
        $field_name,
        $post_id
    ));
    
    if (!$field) {
        return null;
    }
    
    // Jeśli to pole obrazu i chcemy URL
    if ($field->field_type === 'image' && $return_url && is_numeric($field->field_value)) {
        return wp_get_attachment_url($field->field_value);
    }
    
    return $field->field_value;
}

/**
 * Pobiera URL obrazu z pola typu image
 * 
 * @param string $field_name Nazwa pola
 * @param int $post_id ID posta
 * @param string $group_name Nazwa grupy
 * @param string $size Rozmiar obrazu (thumbnail, medium, large, full)
 * @return string|false URL obrazu lub false jeśli nie znaleziono
 */
function yap_get_image($field_name, $post_id, $group_name, $size = 'full') {
    $image_id = yap_get_field($field_name, $post_id, $group_name);
    
    if (!$image_id || !is_numeric($image_id)) {
        return false;
    }
    
    $image = wp_get_attachment_image_src($image_id, $size);
    return $image ? $image[0] : false;
}

/**
 * Pobiera wszystkie pola z grupy dla danego posta
 * 
 * @param int $post_id ID posta
 * @param string $group_name Nazwa grupy
 * @return array Tablica pól z kluczami: label, value, type
 */
function yap_get_all_fields($post_id, $group_name) {
    global $wpdb;
    $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
    
    $fields = $wpdb->get_results($wpdb->prepare(
        "SELECT user_name, field_value, field_type FROM {$data_table} WHERE associated_id = %d",
        $post_id
    ));
    
    $result = [];
    foreach ($fields as $field) {
        $result[] = [
            'label' => $field->user_name,
            'value' => $field->field_value,
            'type' => $field->field_type
        ];
    }
    
    return $result;
}

/**
 * Pobiera zagnieżdżoną grupę
 * 
 * @param string $nested_field_name Nazwa pola typu nested_group
 * @param int $post_id ID posta
 * @param string $group_name Nazwa grupy głównej
 * @return array Tablica zagnieżdżonych wartości
 */
function yap_get_nested_group($nested_field_name, $post_id, $group_name) {
    global $wpdb;
    $pattern_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
    
    // Znajdź pole nested_group
    $nested_field = $wpdb->get_row($wpdb->prepare(
        "SELECT nested_field_ids FROM {$pattern_table} WHERE user_name = %s AND field_type = 'nested_group'",
        $nested_field_name
    ));
    
    if (!$nested_field || empty($nested_field->nested_field_ids)) {
        return [];
    }
    
    $nested_tables = json_decode($nested_field->nested_field_ids, true);
    $result = [];
    
    foreach ($nested_tables as $nested_table) {
        $data_table = str_replace('_pattern', '_data', $nested_table);
        $fields = $wpdb->get_results($wpdb->prepare(
            "SELECT user_name, field_value FROM {$data_table} WHERE associated_id = %d",
            $post_id
        ));
        
        $item = [];
        foreach ($fields as $field) {
            $item[$field->user_name] = $field->field_value;
        }
        if (!empty($item)) {
            $result[] = $item;
        }
    }
    
    return $result;
}

/**
 * Wyświetla niestandardowe pola (opcjonalne - nie dodaje automatycznie do treści)
 * Używaj powyższych funkcji w swoim motywie zamiast tego filtra
 */
function yap_display_custom_fields($content) {
    // Ta funkcja jest obecnie wyłączona - używaj funkcji yap_get_field() w swoim motywie
    return $content;
}
// add_filter('the_content', 'yap_display_custom_fields'); // Zakomentowane - odkomentuj jeśli chcesz automatyczne wyświetlanie
?>
