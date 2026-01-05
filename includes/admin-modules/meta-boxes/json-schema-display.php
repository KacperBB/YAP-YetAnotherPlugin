<?php
/**
 * JSON Schema Fields Display
 * 
 * Wyświetlanie pól z Visual Buildera w meta boxach
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wyświetl pola z JSON schema (Visual Builder)
 */
function yap_display_json_schema_fields($post, $group_name) {
    global $wpdb;
    
    // Najpierw sprawdź wp_yap_field_metadata (priorytet - zawsze aktualne)
    $metadata_table = $wpdb->prefix . 'yap_field_metadata';
    $fields_metadata = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$metadata_table} WHERE group_name = %s ORDER BY field_order ASC",
        $group_name
    ));
    
    $schema = null;
    
    if (!empty($fields_metadata)) {
        // Zbuduj schema z metadanych
        $schema = ['fields' => []];
        foreach ($fields_metadata as $field_meta) {
            $field_config = !empty($field_meta->field_config) ? json_decode($field_meta->field_config, true) : [];
            
            // CRITICAL: Try to get type from multiple sources
            $field_type = null;
            
            // Source 1: Try field_config['type'] first (most authoritative)
            if (!empty($field_config['type'])) {
                $field_type = $field_config['type'];
            }
            // Source 2: Try field_metadata (nested JSON)
            elseif (!empty($field_meta->field_metadata)) {
                $field_metadata_arr = json_decode($field_meta->field_metadata, true);
                if (!empty($field_metadata_arr['type'])) {
                    $field_type = $field_metadata_arr['type'];
                    $field_config['type'] = $field_type;
                }
            }
            // Source 3: Fall back to field_type from metadata (least authoritative)
            if (empty($field_type)) {
                $field_type = $field_meta->field_type ?? 'text';
                $field_config['type'] = $field_type;
            }
            
            // Merge config first, then override with metadata
            $field_data = array_merge($field_config, [
                'id' => $field_meta->field_id,
                'name' => $field_meta->field_name,
                'label' => $field_meta->field_label,
                'type' => $field_type,
            ]);
            
            $schema['fields'][] = $field_data;
        }
    } else {
        // Fallback do JSON file
        $schema_file = WP_CONTENT_DIR . '/yap-schemas/' . $group_name . '.json';
        if (file_exists($schema_file)) {
            error_log("📦 Loading schema from JSON file: {$schema_file}");
            $schema_json = file_get_contents($schema_file);
            $schema = json_decode($schema_json, true);
            
            // Normalize field structure from JSON file
            if ($schema && isset($schema['fields'])) {
                foreach ($schema['fields'] as &$field) {
                    // Ensure 'type' field is set from the JSON
                    if (!isset($field['type']) && isset($field['field_type'])) {
                        $field['type'] = $field['field_type'];
                    }
                    // Set group_name for renderers that need it
                    $field['group_name'] = $group_name;
                }
            }
        }
    }
    
    if (!$schema || !isset($schema['fields']) || empty($schema['fields'])) {
        echo '<div style="padding: 20px; text-align: center; background: #f9f9f9; border: 2px dashed #ddd; border-radius: 8px;">';
        echo '<p style="margin: 0; color: #666;"><strong>⚠️ Brak pól w grupie:</strong> ' . esc_html($group_name) . '</p>';
        echo '<p style="margin: 10px 0 0; font-size: 13px; color: #999;">Dodaj pola w Visual Builder.</p>';
        echo '</div>';
        return;
    }
    
    // Pobierz zapisane wartości z post meta
    $saved_values = get_post_meta($post->ID, 'yap_' . $group_name, true);
    if (!is_array($saved_values)) {
        $saved_values = [];
    }
    
    echo '<div class="yap-metabox-fields">';
    echo '<input type="hidden" name="yap_group_names[]" value="' . esc_attr($group_name) . '">';
    
    foreach ($schema['fields'] as $field) {
        $field_name = $field['name'] ?? $field['id'];
        $field_label = $field['label'] ?? ucwords(str_replace('_', ' ', $field_name));
        $field_type = $field['type'] ?? 'text';
        
        // Normalize field type (handle variations like 'flexible-content' -> 'flexible_content')
        $field_type = str_replace('-', '_', strtolower($field_type));
        
        error_log("📦 JSON Schema Field: name={$field_name}, type={$field_type}, label={$field_label}");
        
        $field_value = $saved_values[$field_name] ?? ($field['default_value'] ?? '');
        
        // Nie pokazuj label dla group/repeater/flexible_content - oni zarządzają swoim layoutem
        if ($field_type === 'repeater' || $field_type === 'group' || $field_type === 'flexible_content') {
            error_log("📦 Complex field detected: {$field_type}");
            echo '<div class="yap-metabox-field" style="margin-bottom: 20px;">';
            echo '<div style="font-weight: 600; margin-bottom: 10px; font-size: 14px; display: flex; align-items: center; gap: 8px;">';
            echo '<span>' . esc_html($field_label) . '</span>';
            if (!empty($field['required'])) {
                echo ' <span style="color: red;">*</span>';
            }
            $shortcode = "[yap_field group='{$group_name}' field='{$field_name}']";
            echo '<button type="button" class="yap-field-tooltip" data-tooltip="Użyj: ' . esc_attr($shortcode) . '" style="background: none; border: none; padding: 0 4px; margin: 0; cursor: pointer;"><span class="dashicons dashicons-info" style="font-size: 18px; color: #72aee6;"></span></button>';
            if (!empty($field['description'])) {
                echo '<p class="description" style="font-weight: normal; margin: 5px 0 10px; font-size: 13px;">' . esc_html($field['description']) . '</p>';
            }
            echo '</div>';
            
            $input_name = 'yap_fields[' . esc_attr($group_name) . '][' . esc_attr($field_name) . ']';
            $input_id = 'yap_' . esc_attr($group_name) . '_' . esc_attr($field_name);
            
            // Update field with normalized type and group_name
            $field['type'] = $field_type;
            $field['group_name'] = $group_name;
            
            yap_render_field_input($field, $field_value, $input_name, $input_id, $group_name);
            
            echo '</div>';
        } else {
            echo '<div class="yap-metabox-field" style="margin-bottom: 15px;">';
            echo '<label for="yap_' . esc_attr($group_name) . '_' . esc_attr($field_name) . '" style="display: block; font-weight: 600; margin-bottom: 5px;">';
            echo esc_html($field_label);
            if (!empty($field['required'])) {
                echo ' <span style="color: red;">*</span>';
            }
            $shortcode = "[yap_field group='{$group_name}' field='{$field_name}']";
            echo ' <button type="button" class="yap-field-tooltip" data-tooltip="Użyj: ' . esc_attr($shortcode) . '" style="background: none; border: none; padding: 0 4px; margin: 0; cursor: pointer; vertical-align: middle;"><span class="dashicons dashicons-info" style="font-size: 16px; color: #72aee6;"></span></button>';
            echo '</label>';
            
            $input_name = 'yap_fields[' . esc_attr($group_name) . '][' . esc_attr($field_name) . ']';
            $input_id = 'yap_' . esc_attr($group_name) . '_' . esc_attr($field_name);
            
            // Update field with normalized type and group_name
            $field['type'] = $field_type;
            $field['group_name'] = $group_name;
            
            yap_render_field_input($field, $field_value, $input_name, $input_id, $group_name);
            
            if (!empty($field['description'])) {
                echo '<p class="description">' . esc_html($field['description']) . '</p>';
            }
            
            echo '</div>';
        }
    }
    
    echo '</div>';
}
