<?php
/**
 * Table Fields Display
 * 
 * Wyświetlanie pół z tabel w meta boxach (stary system)
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wyświetl pola z tabeli (stary system lub yap_*)
 */
function yap_display_table_fields($post, $data_table, $pattern_table) {
    global $wpdb;
    
    $post_id = $post->ID;
    
    // Pobierz pola z pattern table
    $fields = $wpdb->get_results("SELECT * FROM {$pattern_table}");
    
    if (empty($fields)) {
        echo '<p style="color: #999;">Brak pół w tej grupie.</p>';
        return;
    }
    
    echo '<div class="yap-metabox-fields">';
    
    foreach ($fields as $field) {
        // Pomiń meta pole
        if ($field->generated_name === 'group_meta') {
            continue;
        }
        
        // Pobierz wartość z data table
        $saved_value = $wpdb->get_var($wpdb->prepare(
            "SELECT field_value FROM {$data_table} WHERE generated_name = %s AND associated_id = %d",
            $field->generated_name,
            $post_id
        ));
        
        if ($saved_value === null) {
            $saved_value = '';
        }
        
        echo '<div class="yap-metabox-field" style="margin-bottom: 15px;">';
        echo '<label for="yap_' . esc_attr($field->generated_name) . '" style="display: block; font-weight: 600; margin-bottom: 5px;">';
        echo esc_html($field->user_name);
        echo '</label>';
        
        $input_name = 'yap_table_fields[' . esc_attr($data_table) . '][' . esc_attr($field->generated_name) . ']';
        
        // Renderuj pole
        switch ($field->field_type) {
            case 'long_text':
            case 'textarea':
                echo '<textarea name="' . esc_attr($input_name) . '" rows="5" class="large-text">' . esc_textarea($saved_value) . '</textarea>';
                break;
            case 'number':
                echo '<input type="number" name="' . esc_attr($input_name) . '" value="' . esc_attr($saved_value) . '" class="regular-text">';
                break;
            default:
                echo '<input type="text" name="' . esc_attr($input_name) . '" value="' . esc_attr($saved_value) . '" class="regular-text">';
                break;
        }
        
        echo '</div>';
    }
    
    echo '</div>';
}
