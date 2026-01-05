<?php
/**
 * Admin Module Loader
 * 
 * Ładuje starsze moduły admin oraz nowy system modularny
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

error_log("🟦 ADMIN.PHP LOADED!");

// ===========================
// Legacy Admin Modules (stare)
// ===========================
require_once plugin_dir_path(__FILE__) . 'admin/admin-save-group.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-delete-group.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-edit-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-group-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/ajax_requests/ajax-refresh-groups.php';
require_once plugin_dir_path(__FILE__) . 'admin/ajax_requests/ajax-delete-group.php';

// ===========================
// New Modular Admin System
// ===========================
require_once plugin_dir_path(__FILE__) . 'admin-modules/_bootstrap.php';

/**
 * ===========================
 * LEGACY HELPER FUNCTIONS
 * ===========================
 * 
 * Te funkcje zostały przeniesione do admin-modules
 * ale można je tutaj znaleźć dla kompatybilności
 */

/**
 * Helper: Pobierz istniejące pola dla posta
 * Używane w legacy systemach
 */
function yap_create_fields_for_existing_posts($table_name, $post_type, $category) {
    global $wpdb;

    // Pobierz wszystkie posty odpowiadające wybranym kryteriom
    $query = "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'";
    $params = [$post_type];

    if (!empty($category)) {
        $query .= " AND ID IN (SELECT object_id FROM {$wpdb->term_relationships} AS tr
                   INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                   WHERE tt.term_id = %d)";
        $params[] = $category;
    }

    $posts = $wpdb->get_results($wpdb->prepare($query, $params));

    // Pobierz pola wzorca
    $fields = $wpdb->get_results("SELECT * FROM {$table_name}");

    // Tworz pola dla każdego posta
    foreach ($posts as $post) {
        $post_id = $post->ID;
        
        foreach ($fields as $field) {
            $existing_field = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_name}_data WHERE generated_name = %s AND associated_id = %d",
                $field->generated_name,
                $post_id
            ));

            if (!$existing_field) {
                $wpdb->insert(
                    "{$table_name}_data",
                    [
                        'generated_name' => $field->generated_name,
                        'user_name' => $field->user_name,
                        'field_type' => $field->field_type,
                        'field_value' => '', // Domyślna wartość
                        'associated_id' => $post_id
                    ]
                );
            }
        }
    }
}

/**
 * Debug: Pokaż wszystkie location rules
 */
function yap_debug_location_rules() {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['yap_debug_rules'])) return;
    
    global $wpdb;
    $rules = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yap_location_rules ORDER BY group_name, rule_group, rule_order");
    
    echo '<div class="notice notice-info" style="padding: 20px;"><h3>🔍 Location Rules Debug</h3><pre>';
    foreach ($rules as $rule) {
        echo sprintf(
            "Grupa: %s | Typ: %s | Operator: %s | Wartość: %s | Group: %d | Order: %d\n",
            $rule->group_name,
            $rule->location_type,
            $rule->location_operator,
            $rule->location_value,
            $rule->rule_group,
            $rule->rule_order
        );
    }
    echo '</pre></div>';
}
add_action('admin_notices', 'yap_debug_location_rules');



function yap_display_post_fields($post, $data_table, $expected_post_type, $expected_category) {
    global $wpdb;

    if (!is_object($post)) {
        $post = get_post();
    }

    if (!$post || !isset($post->ID)) {
        error_log("🚨 Invalid post object.");
        return;
    }

    $post_id = $post->ID;
    $post_type = get_post_type($post_id);
    $categories = wp_get_post_terms($post_id, 'category', ['fields' => 'ids']);

    error_log("📄 Processing Post ID: {$post_id}, Post Type: {$post_type}, Categories: " . implode(', ', $categories));

    // Pobierz meta informacje o grupie
    $pattern_table = str_replace('_data', '_pattern', $data_table);
    $group_meta = $wpdb->get_row("SELECT * FROM {$pattern_table} WHERE generated_name = 'group_meta'");

    if (!$group_meta) {
        error_log("🚨 No meta field found in pattern table: {$pattern_table}");
        return;
    }

    // Dekoduj informacje o grupie
    $group_meta_data = json_decode($group_meta->field_value, true);
    $group_post_type = $group_meta_data['post_type'] ?? null;
    $group_category = $group_meta_data['category'] ?? null;

    error_log("📊 Group Meta Data: Post Type: {$group_post_type}, Category: {$group_category}");

    // 🔍 **Filtracja pól**: Sprawdzamy zgodność `post_type` oraz `category`
    if ($group_post_type !== $post_type) {
        error_log("🚨 Skipping fields: Post Type mismatch (expected: {$group_post_type}, got: {$post_type}).");
        return;
    }

    if (!empty($group_category) && !in_array((int)$group_category, $categories)) {
        error_log("🚨 Skipping fields: Category mismatch (expected: {$group_category}, got: " . implode(', ', $categories) . ").");
        return;
    }

    // Pobierz pola powiązane z tym postem
    $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$data_table} WHERE associated_id = %d", $post_id));

    if (!empty($fields)) {
        echo '<div class="yap-custom-fields">';
        foreach ($fields as $field) {
            // Pomiń meta pole
            if ($field->generated_name === 'group_meta') {
                continue;
            }
            
            echo '<div class="yap-metabox-field">';
            echo '<label for="yap_' . esc_attr($field->generated_name) . '">';
            echo '<strong>' . esc_html($field->user_name) . '</strong>';
            echo '</label>';
            
            // Różne typy pól
            switch ($field->field_type) {
                case 'long_text':
                    echo '<textarea id="yap_' . esc_attr($field->generated_name) . '" name="yap_fields[' . esc_attr($data_table) . '][' . esc_attr($field->generated_name) . ']" rows="5" class="widefat">' . esc_textarea($field->field_value) . '</textarea>';
                    break;
                case 'number':
                    echo '<input type="number" id="yap_' . esc_attr($field->generated_name) . '" name="yap_fields[' . esc_attr($data_table) . '][' . esc_attr($field->generated_name) . ']" value="' . esc_attr($field->field_value) . '" class="widefat">';
                    break;
                case 'image':
                    $image_id = $field->field_value;
                    $image_url = '';
                    if (is_numeric($image_id)) {
                        $image_url = wp_get_attachment_url($image_id);
                    }
                    echo '<div class="yap-image-field-wrapper">';
                    echo '<div style="flex: 1;">';
                    echo '<input type="hidden" id="yap_' . esc_attr($field->generated_name) . '" name="yap_fields[' . esc_attr($data_table) . '][' . esc_attr($field->generated_name) . ']" value="' . esc_attr($field->field_value) . '" class="yap-image-id">';
                    echo '<button type="button" class="button yap-upload-image-button" data-field="yap_' . esc_attr($field->generated_name) . '">Wybierz obraz z biblioteki</button>';
                    if ($image_url) {
                        echo '<img src="' . esc_url($image_url) . '" class="yap-image-preview" style="margin-top: 10px; max-width: 150px; display: block;">';
                    } else {
                        echo '<img src="" class="yap-image-preview" style="margin-top: 10px; max-width: 150px; display: none;">';
                    }
                    echo '<button type="button" class="button yap-remove-image-button" style="margin-top: 5px; display: ' . ($image_url ? 'inline-block' : 'none') . ';">Usuń obraz</button>';
                    echo '</div>';
                    echo '</div>';
                    break;
                case 'nested_group':
                    echo '<p class="description">To jest zagnieżdżona grupa - zarządzaj nią w ustawieniach wtyczki</p>';
                    break;
                default: // short_text
                    echo '<input type="text" id="yap_' . esc_attr($field->generated_name) . '" name="yap_fields[' . esc_attr($data_table) . '][' . esc_attr($field->generated_name) . ']" value="' . esc_attr($field->field_value) . '" class="widefat">';
                    break;
            }
            
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>Brak pól do wyświetlenia. Pola zostaną utworzone przy pierwszym zapisie posta.</p>';
        error_log("ℹ️ No fields found for Post ID: {$post_id} in table {$data_table} - they will be created on save.");
    }
}


function yap_create_fields_for_existing_posts($table_name, $post_type, $category) {
    global $wpdb;

    // Pobierz wszystkie posty odpowiadające wybranym kryteriom
    $query = "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'";
    $params = [$post_type];

    if (!empty($category)) {
        $query .= " AND ID IN (SELECT object_id FROM {$wpdb->term_relationships} AS tr
                   INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                   WHERE tt.term_id = %d)";
        $params[] = $category;
    }

    $posts = $wpdb->get_results($wpdb->prepare($query, $params));

    // Pobierz pola wzorca
    $fields = $wpdb->get_results("SELECT * FROM {$table_name}");

    // Zmieniona walidacja w yap_create_fields_for_existing_posts
    foreach ($posts as $post) {
        $post_id = $post->ID;
        
        foreach ($fields as $field) {
            $existing_field = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_name}_data WHERE generated_name = %s AND associated_id = %d",
                $field->generated_name,
                $post_id
            ));

            if (!$existing_field) {
                $wpdb->insert(
                    "{$table_name}_data",
                    [
                        'generated_name' => $field->generated_name,
                        'user_name' => $field->user_name,
                        'field_type' => $field->field_type,
                        'field_value' => '', // Domyślna wartość
                        'associated_id' => $post_id
                    ]
                );
            }
        }
    }
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
                error_log("   → Type from field_config: {$field_type}");
            }
            // Source 2: Try field_metadata (nested JSON)
            elseif (!empty($field_meta->field_metadata)) {
                $field_metadata_arr = json_decode($field_meta->field_metadata, true);
                if (!empty($field_metadata_arr['type'])) {
                    $field_type = $field_metadata_arr['type'];
                    error_log("   → Type from field_metadata JSON: {$field_type}");
                    // Add to config for future use
                    $field_config['type'] = $field_type;
                }
            }
            // Source 3: Fall back to field_type from metadata (least authoritative)
            if (empty($field_type)) {
                $field_type = $field_meta->field_type ?? 'text';
                error_log("   → Type from field_meta->field_type: {$field_type}");
                // Add to config for future use
                $field_config['type'] = $field_type;
            }
            
            // Merge config first, then override with metadata (metadata has priority for id, name, label)
            $field_data = array_merge($field_config, [
                'id' => $field_meta->field_id,
                'name' => $field_meta->field_name,
                'label' => $field_meta->field_label,
                'type' => $field_type,
            ]);
            
            $schema['fields'][] = $field_data;
            
            $has_sub = isset($field_data['sub_fields']) ? 'YES (' . count($field_data['sub_fields']) . ')' : 'NO';
            $has_choices = isset($field_data['choices']) ? 'YES (' . count($field_data['choices']) . ')' : 'NO';
            error_log("🔍 Field loaded: {$field_meta->field_name} | Label: {$field_meta->field_label} | Type: {$field_type} | Sub-fields: {$has_sub} | Choices: {$has_choices}");
            
            // SPECIAL DEBUG FOR CONTAINER FIELDS
            if (in_array($field_type, ['flexible_content', 'group', 'repeater'])) {
                error_log("   ⚠️ CONTAINER FIELD DEBUG:");
                error_log("      final field_type: {$field_type}");
                error_log("      field_data keys: " . json_encode(array_keys($field_data)));
                if (in_array($field_type, ['group', 'repeater']) && isset($field_data['sub_fields'])) {
                    error_log("      sub_fields count: " . count($field_data['sub_fields']));
                }
            }
        }
    } else {
        // Fallback do JSON file
        $schema_file = WP_CONTENT_DIR . '/yap-schemas/' . $group_name . '.json';
        if (file_exists($schema_file)) {
            $schema_json = file_get_contents($schema_file);
            $schema = json_decode($schema_json, true);
        }
    }
    
    if (!$schema || !isset($schema['fields']) || empty($schema['fields'])) {
        echo '<div style="padding: 20px; text-align: center; background: #f9f9f9; border: 2px dashed #ddd; border-radius: 8px;">';
        echo '<p style="margin: 0; color: #666;"><strong>⚠️ Brak pól w grupie:</strong> ' . esc_html($group_name) . '</p>';
        echo '<p style="margin: 10px 0 0; font-size: 13px; color: #999;">Dodaj pola w Visual Builder lub sprawdź konfigurację location rules.</p>';
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
        $field_value = $saved_values[$field_name] ?? ($field['default_value'] ?? '');
        
        error_log("📋 Rendering field: {$field_name} | Label: {$field_label} | Type: {$field_type}");
        
        // Don't show label wrapper for group/repeater/flexible_content - they handle their own layout
        if ($field_type === 'repeater' || $field_type === 'group' || $field_type === 'flexible_content') {
            echo '<div class=\"yap-metabox-field\" style=\"margin-bottom: 20px;\">';
            echo '<div style=\"font-weight: 600; margin-bottom: 10px; font-size: 14px; display: flex; align-items: center; gap: 8px;\">';
            echo '<span>' . esc_html($field_label) . '</span>';
            if (!empty($field['required'])) {
                echo ' <span style="color: red;">*</span>';
            }
            // Add tooltip with shortcode
            $shortcode = "[yap_field group='{$group_name}' field='{$field_name}']";
            echo '<button type="button" class="yap-field-tooltip" data-tooltip="Użyj: ' . esc_attr($shortcode) . '" style="background: none; border: none; padding: 0 4px; margin: 0; cursor: pointer;"><span class="dashicons dashicons-info" style="font-size: 18px; color: #72aee6;"></span></button>';
            if (!empty($field['description'])) {
                echo '<p class="description" style="font-weight: normal; margin: 5px 0 10px; font-size: 13px;">' . esc_html($field['description']) . '</p>';
            }
            echo '</div>';
            
            $input_name = 'yap_fields[' . esc_attr($group_name) . '][' . esc_attr($field_name) . ']';
            $input_id = 'yap_' . esc_attr($group_name) . '_' . esc_attr($field_name);
            
            yap_render_field_input($field, $field_value, $input_name, $input_id, $group_name);
            
            echo '</div>';
        } else {
            echo '<div class="yap-metabox-field" style="margin-bottom: 15px;">';
            echo '<label for="yap_' . esc_attr($group_name) . '_' . esc_attr($field_name) . '" style="display: block; font-weight: 600; margin-bottom: 5px;">';
            echo esc_html($field_label);
            if (!empty($field['required'])) {
                echo ' <span style="color: red;">*</span>';
            }
            // Add tooltip with shortcode
            $shortcode = "[yap_field group='{$group_name}' field='{$field_name}']";
            echo ' <button type="button" class="yap-field-tooltip" data-tooltip="Użyj: ' . esc_attr($shortcode) . '" style="background: none; border: none; padding: 0 4px; margin: 0; cursor: pointer; vertical-align: middle;"><span class="dashicons dashicons-info" style="font-size: 16px; color: #72aee6;"></span></button>';
            echo '</label>';
            
            $input_name = 'yap_fields[' . esc_attr($group_name) . '][' . esc_attr($field_name) . ']';
            $input_id = 'yap_' . esc_attr($group_name) . '_' . esc_attr($field_name);
            
            // Renderuj pole w zależności od typu
            yap_render_field_input($field, $field_value, $input_name, $input_id, $group_name);
            
            if (!empty($field['description'])) {
                echo '<p class="description">' . esc_html($field['description']) . '</p>';
            }
            
            echo '</div>';
        }
    }
    
    echo '</div>';
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
        echo '<p style="color: #999;">Brak pól w tej grupie.</p>';
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

/**
 * Renderuj input dla pola (używane przez JSON schema)
 */
function yap_render_field_input($field, $value, $input_name, $input_id, $group_name = '') {
    $type = $field['type'] ?? 'text';
    $placeholder = $field['placeholder'] ?? '';
    $css_class = !empty($field['css_class']) ? $field['css_class'] : 'regular-text';
    
    switch ($type) {
        case 'text':
            echo '<input type="text" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'textarea':
            echo '<textarea id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" rows="5" placeholder="' . esc_attr($placeholder) . '" class="large-text">' . esc_textarea($value) . '</textarea>';
            break;
        case 'number':
            echo '<input type="number" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'email':
            echo '<input type="email" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'url':
            echo '<input type="url" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'wysiwyg':
            wp_editor($value, $input_id, [
                'textarea_name' => $input_name,
                'textarea_rows' => 10,
                'teeny' => false
            ]);
            break;
        case 'select':
            $choices = $field['choices'] ?? [];
            error_log("📋 SELECT FIELD: name={$field['name']}, choices count=" . count($choices));
            
            // FALLBACK: If no choices, create test data
            if (empty($choices)) {
                error_log("   ⚠️ CHOICES ARE EMPTY! Creating test choices...");
                $choices = [
                    'option1' => 'Option 1',
                    'option2' => 'Option 2',
                    'option3' => 'Option 3',
                ];
            }
            
            echo '<select id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" class="' . esc_attr($css_class) . '">';
            echo '<option value="">-- Wybierz --</option>';
            foreach ($choices as $choice_value => $choice_label) {
                $selected = ($value == $choice_value) ? 'selected' : '';
                echo '<option value="' . esc_attr($choice_value) . '" ' . $selected . '>' . esc_html($choice_label) . '</option>';
            }
            echo '</select>';
            break;
        case 'checkbox':
            $checked = !empty($value) ? 'checked' : '';
            echo '<label><input type="checkbox" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="1" ' . $checked . '> ' . esc_html($field['label'] ?? 'Tak') . '</label>';
            break;
        case 'date':
            echo '<input type="date" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'time':
            echo '<input type="time" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'datetime':
        case 'datetime-local':
            echo '<input type="datetime-local" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="' . esc_attr($css_class) . '">';
            break;
        case 'color':
            $color_value = $value ?: '#000000';
            echo '<div class="yap-color-picker-wrapper">';
            echo '<input type="color" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($color_value) . '" class="' . esc_attr($css_class) . '" title="Pick a color">';
            echo '<span class="yap-color-value" data-color-display="' . esc_attr($input_id) . '">' . strtoupper(esc_html($color_value)) . '</span>';
            echo '</div>';
            echo '<script>
            (function() {
                var input = document.getElementById("' . esc_js($input_id) . '");
                var display = document.querySelector("[data-color-display=\"' . esc_js($input_id) . '\"]");
                if (input && display) {
                    input.addEventListener("change", function() {
                        display.textContent = this.value.toUpperCase();
                    });
                    input.addEventListener("input", function() {
                        display.textContent = this.value.toUpperCase();
                    });
                }
            })();
            </script>';
            break;
        case 'radio':
            $choices = $field['choices'] ?? [];
            error_log("📻 RADIO FIELD: name={$field['name']}, choices count=" . count($choices));
            error_log("   Choices: " . json_encode($choices));
            error_log("   Full field: " . json_encode($field));
            
            // FALLBACK: If no choices, create test data to show they should exist
            if (empty($choices)) {
                error_log("   ⚠️ CHOICES ARE EMPTY! Creating test choices for display...");
                // Fallback untuk testing - w produkcji choices powinny być zawsze
                $choices = [
                    'option1' => 'Option 1',
                    'option2' => 'Option 2',
                    'option3' => 'Option 3',
                ];
            }
            
            echo '<div class="yap-radio-group">';
            foreach ($choices as $choice_value => $choice_label) {
                $checked = ($value == $choice_value) ? 'checked' : '';
                $radio_id = $input_id . '_' . sanitize_key($choice_value);
                echo '<label style="display: inline-block; margin-right: 15px;">';
                echo '<input type="radio" id="' . esc_attr($radio_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($choice_value) . '" ' . $checked . '> ';
                echo esc_html($choice_label);
                echo '</label>';
            }
            echo '</div>';
            break;
        case 'file':
            $file_id = $value;
            $file_url = '';
            $file_name = '';
            if (is_numeric($file_id)) {
                $file_url = wp_get_attachment_url($file_id);
                $file_name = basename(get_attached_file($file_id));
            }
            echo '<div class="yap-file-field-wrapper">';
            echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="yap-file-id">';
            echo '<button type="button" class="button yap-upload-file-button" data-field="' . esc_attr($input_id) . '">Wybierz plik</button>';
            if ($file_url) {
                echo '<div class="yap-file-preview" style="margin-top: 10px; padding: 8px; background: #f5f5f5; border-radius: 4px;">';
                echo '<a href="' . esc_url($file_url) . '" target="_blank" style="text-decoration: none;">📄 ' . esc_html($file_name) . '</a>';
                echo '<button type="button" class="button yap-remove-file-button" style="margin-left: 10px;">Usuń</button>';
                echo '</div>';
            }
            echo '</div>';
            break;
        case 'gallery':
            $gallery_ids = is_array($value) ? $value : (!empty($value) ? explode(',', $value) : []);
            echo '<div class="yap-gallery-field-wrapper" data-field-id="' . esc_attr($input_id) . '">';
            echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr(is_array($value) ? implode(',', $value) : $value) . '" class="yap-gallery-ids">';
            echo '<button type="button" class="button yap-add-gallery-images" data-field="' . esc_attr($input_id) . '">Dodaj obrazy</button>';
            echo '<div class="yap-gallery-preview" style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 10px;">';
            foreach ($gallery_ids as $img_id) {
                if (is_numeric($img_id) && $img_id > 0) {
                    $img_url = wp_get_attachment_image_url($img_id, 'thumbnail');
                    if ($img_url) {
                        echo '<div class="yap-gallery-item" data-id="' . esc_attr($img_id) . '" style="position: relative;">';
                        echo '<img src="' . esc_url($img_url) . '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">';
                        echo '<button type="button" class="yap-remove-gallery-item" style="position: absolute; top: 5px; right: 5px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px; line-height: 1;">×</button>';
                        echo '</div>';
                    }
                }
            }
            echo '</div>';
            echo '</div>';
            break;
        case 'image':
            $image_id = $value;
            $image_url = '';
            if (is_numeric($image_id)) {
                $image_url = wp_get_attachment_url($image_id);
            }
            echo '<div class="yap-image-field-wrapper">';
            echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="yap-image-id">';
            echo '<button type="button" class="button yap-upload-image-button" data-field="' . esc_attr($input_id) . '">Wybierz obraz</button>';
            if ($image_url) {
                echo '<img src="' . esc_url($image_url) . '" class="yap-image-preview" style="margin-top: 10px; max-width: 150px; display: block;">';
                echo '<button type="button" class="button yap-remove-image-button" style="margin-top: 5px;">Usuń obraz</button>';
            } else {
                echo '<img src="" class="yap-image-preview" style="margin-top: 10px; max-width: 150px; display: none;">';
                echo '<button type="button" class="button yap-remove-image-button" style="margin-top: 5px; display: none;">Usuń obraz</button>';
            }
            echo '</div>';
            break;
        case 'flexible_content':
            // Pass group_name to flexible content renderer
            $field['group_name'] = $group_name;
            error_log("🎨 FLEXIBLE CONTENT CASE: group={$group_name}, field=" . ($field['name'] ?? 'NO_NAME') . ", field_type={$field['type']}");
            error_log("   Full field data keys: " . json_encode(array_keys($field)));
            error_log("   Full field data: " . json_encode($field));
            
            // DEBUG: Check if this is actually in case flexible_content or if it's in default
            if ($field['type'] !== 'flexible_content') {
                error_log("   ⚠️ WARNING: Field type mismatch! field['type'] = {$field['type']} but we're in flexible_content case!");
            }
            
            YAP_Flexible_Content::render_field($field, $value, $input_name, $input_id);
            break;
        case 'repeater':
            yap_render_repeater_field($field, $value, $input_name, $input_id);
            break;
        case 'group':
            error_log("🏷️ GROUP CASE: group={$group_name}, field=" . ($field['name'] ?? 'NO_NAME') . ", has_subfields=" . (isset($field['sub_fields']) ? count($field['sub_fields']) : 'NO'));
            yap_render_group_field($field, $value, $input_name, $input_id);
            break;
        default:
            // SPECIAL FALLBACK: Check if field_config has a different type stored
            $config_type = $field['type'] ?? 'unknown';
            $has_sub = isset($field['sub_fields']) ? count($field['sub_fields']) : 0;
            $has_choices = isset($field['choices']) ? count($field['choices']) : 0;
            
            error_log("⚠️ DEFAULT CASE for field: " . ($field['name'] ?? 'NO_NAME'));
            error_log("   field['type']: {$config_type}");
            error_log("   has sub_fields: {$has_sub}");
            error_log("   has choices: {$has_choices}");
            error_log("   Rendered as text input - THIS IS A FALLBACK!");
            
            // FALLBACK HEURISTIC: If field has flexible_content markers, treat as flexible_content
            if (isset($field['sub_fields']) && is_array($field['sub_fields']) && !empty($field['sub_fields'])) {
                // Check if it looks like flexible_content (has multiple sub_fields types)
                $sub_types = [];
                foreach ($field['sub_fields'] as $sub) {
                    if (isset($sub['type'])) {
                        $sub_types[] = $sub['type'];
                    }
                }
                
                if (count($sub_types) > 1 || ($config_type === 'text' && count($sub_types) > 0)) {
                    error_log("   → Heuristic: Field has multiple sub_fields ({count($sub_types)} types), treating as FLEXIBLE CONTENT!");
                    $field['group_name'] = $group_name;
                    YAP_Flexible_Content::render_field($field, $value, $input_name, $input_id);
                    break;
                }
            }
            
            // FALLBACK HEURISTIC: If field has sub_fields but type is text, treat as group
            if (!empty($field['sub_fields']) && $config_type === 'text') {
                error_log("   → Heuristic: Field has sub_fields but type=text, treating as GROUP!");
                yap_render_group_field($field, $value, $input_name, $input_id);
            }
            // FALLBACK: If field has choices but type is text, treat as radio
            elseif (!empty($field['choices']) && $config_type === 'text') {
                error_log("   → Heuristic: Field has choices but type=text, treating as RADIO!");
                $choices = $field['choices'];
                echo '<div class="yap-radio-group">';
                foreach ($choices as $choice_value => $choice_label) {
                    $checked = ($value == $choice_value) ? 'checked' : '';
                    $radio_id = $input_id . '_' . sanitize_key($choice_value);
                    echo '<label style="display: inline-block; margin-right: 15px;">';
                    echo '<input type="radio" id="' . esc_attr($radio_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($choice_value) . '" ' . $checked . '> ';
                    echo esc_html($choice_label);
                    echo '</label>';
                }
                echo '</div>';
            }
            // DEFAULT: render as text
            else {
                echo '<input type="text" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="' . esc_attr($css_class) . '">';
            }
            break;
    }
}

/**
 * Renderuj pole typu repeater
 */
function yap_render_repeater_field($field, $value, $input_name, $input_id) {
    // Wartość repeatera to tablica wierszy
    if (!is_array($value)) {
        $value = !empty($value) ? json_decode($value, true) : [];
    }
    if (!is_array($value)) {
        $value = [];
    }
    
    $sub_fields = $field['sub_fields'] ?? [];
    $min_rows = $field['min'] ?? 0;
    $max_rows = $field['max'] ?? 0;
    
    // Jeśli brak sub_fields, pokaż notice
    if (empty($sub_fields)) {
        echo '<div class="yap-repeater-empty" style="padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">';
        echo '<p style="margin: 0;"><strong>⚠️ Repeater bez pól:</strong> ' . esc_html($field['label']) . '</p>';
        echo '<p style="margin: 5px 0 0; font-size: 13px; color: #856404;">Dodaj pola zagnieżdżone w Visual Builder aby móc używać tego repeatera.</p>';
        echo '</div>';
        return;
    }
    
    // Jeśli brak wartości, dodaj jeden pusty wiersz
    if (empty($value)) {
        $value = [[]];
    }
    
    $repeater_id = sanitize_key($input_id);
    
    echo '<div class="yap-repeater-container" data-repeater-id="' . esc_attr($repeater_id) . '" data-min="' . esc_attr($min_rows) . '" data-max="' . esc_attr($max_rows) . '">';
    
    // Add usage info - extract group and field from input_name
    // input_name format: yap_fields[GroupName][field_name]
    preg_match('/yap_fields\[([^\]]+)\]\[([^\]]+)\]/', $input_name, $matches);
    $usage_group = $matches[1] ?? $group_name ?? 'group_name';
    $usage_field = $matches[2] ?? $field['name'] ?? 'field_name';
    
    echo '<div class="yap-repeater-info" style="margin-bottom: 10px; padding: 8px 12px; background: #f0f6fc; border-left: 3px solid #72aee6; font-size: 12px; color: #1e293b;">';
    echo '<strong style="color: #0073aa;">ℹ️ Jak użyć w szablonie:</strong> <code style="background: white; padding: 2px 6px; border-radius: 3px; font-family: monospace;">';
    echo htmlspecialchars("<?php yap_repeater('{$usage_group}', '{$usage_field}'); ?>");
    echo '</code>';
    echo '</div>';
    
    echo '<div class="yap-repeater-rows" id="' . esc_attr($repeater_id) . '_rows">';
    
    // Renderuj istniejące wiersze
    foreach ($value as $row_index => $row_data) {
        yap_render_repeater_row($sub_fields, $row_data, $input_name, $row_index, $repeater_id);
    }
    
    echo '</div>'; // .yap-repeater-rows
    
    echo '<div class="yap-repeater-actions" style="margin-top: 10px;">';
    echo '<button type="button" class="button yap-add-repeater-row" data-repeater-id="' . esc_attr($repeater_id) . '">';
    echo '<span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span> Dodaj wiersz';
    echo '</button>';
    echo '</div>';
    
    echo '</div>'; // .yap-repeater-container
    
    // Szablon wiersza (ukryty)
    echo '<script type="text/template" id="' . esc_attr($repeater_id) . '_template">';
    yap_render_repeater_row($sub_fields, [], $input_name, '{{INDEX}}', $repeater_id, true);
    echo '</script>';
}

/**
 * Renderuj pojedynczy wiersz repeatera
 */
function yap_render_repeater_row($sub_fields, $row_data, $input_name, $row_index, $repeater_id, $is_template = false) {
    $row_class = $is_template ? 'yap-repeater-row-template' : 'yap-repeater-row';
    
    echo '<div class="' . $row_class . '">';
    
    // Drag handle
    echo '<div class="yap-repeater-row-handle">';
    echo '<span class="dashicons dashicons-menu"></span>';
    echo '</div>';
    
    echo '<div class="yap-repeater-row-content">';
    
    // Renderuj pola zagnieżdżone
    foreach ($sub_fields as $sub_field) {
        $sub_field_name = $sub_field['name'] ?? $sub_field['id'];
        $sub_field_label = $sub_field['label'] ?? ucwords(str_replace('_', ' ', $sub_field_name));
        $sub_field_value = $row_data[$sub_field_name] ?? ($sub_field['default_value'] ?? '');
        $sub_field_type = $sub_field['type'] ?? 'text';
        
        $sub_input_name = $input_name . '[' . $row_index . '][' . $sub_field_name . ']';
        $sub_input_id = $repeater_id . '_' . $row_index . '_' . $sub_field_name;
        
        echo '<div class="yap-repeater-field" style="margin-bottom: 10px;">';
        echo '<label style="display: block; font-weight: 600; margin-bottom: 3px; font-size: 13px;">' . esc_html($sub_field_label);
        if (!empty($sub_field['required'])) {
            echo ' <span style="color: red;">*</span>';
        }
        echo '</label>';
        
        // Renderuj pole (bez rekurencji dla repeater/group)
        yap_render_simple_field($sub_field, $sub_field_value, $sub_input_name, $sub_input_id);
        
        echo '</div>';
    }
    
    echo '</div>'; // .yap-repeater-row-content
    
    // Przycisk usuwania
    echo '<button type="button" class="button yap-remove-repeater-row" title="Usuń wiersz">';
    echo '<span class="dashicons dashicons-trash"></span>';
    echo '</button>';
    
    echo '</div>'; // .yap-repeater-row
}

/**
 * Renderuj proste pole (bez rekurencji dla repeater/group)
 */
function yap_render_simple_field($field, $value, $input_name, $input_id) {
    $type = $field['type'] ?? 'text';
    $placeholder = $field['placeholder'] ?? '';
    
    switch ($type) {
        case 'text':
        case 'short_text':
            echo '<input type="text" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="widefat">';
            break;
        case 'textarea':
        case 'long_text':
            echo '<textarea name="' . esc_attr($input_name) . '" rows="3" placeholder="' . esc_attr($placeholder) . '" class="widefat">' . esc_textarea($value) . '</textarea>';
            break;
        case 'number':
            echo '<input type="number" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="widefat">';
            break;
        case 'email':
            echo '<input type="email" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="widefat">';
            break;
        case 'url':
            echo '<input type="url" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="widefat">';
            break;
        case 'tel':
        case 'phone':
            echo '<input type="tel" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="widefat">';
            break;
        case 'select':
            $choices = $field['choices'] ?? [];
            echo '<select name="' . esc_attr($input_name) . '" class="widefat">';
            echo '<option value="">-- Wybierz --</option>';
            foreach ($choices as $choice_value => $choice_label) {
                $selected = ($value == $choice_value) ? 'selected' : '';
                echo '<option value="' . esc_attr($choice_value) . '" ' . $selected . '>' . esc_html($choice_label) . '</option>';
            }
            echo '</select>';
            break;
        case 'checkbox':
            $checked = !empty($value) ? 'checked' : '';
            echo '<label><input type="checkbox" name="' . esc_attr($input_name) . '" value="1" ' . $checked . '> Tak</label>';
            break;
        case 'date':
            echo '<input type="date" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="widefat">';
            break;
        case 'time':
            echo '<input type="time" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="widefat">';
            break;
        case 'datetime':
        case 'datetime-local':
            echo '<input type="datetime-local" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="widefat">';
            break;
        case 'color':
            $color_value = $value ?: '#000000';
            $color_id = 'color_' . uniqid();
            echo '<div class="yap-color-picker-wrapper">';
            echo '<input type="color" id="' . esc_attr($color_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($color_value) . '" class="yap-repeater-color">';
            echo '<span class="yap-color-value" data-color-display="' . esc_attr($color_id) . '">' . strtoupper(esc_html($color_value)) . '</span>';
            echo '</div>';
            echo '<script>
            (function() {
                var input = document.getElementById("' . esc_js($color_id) . '");
                var display = document.querySelector("[data-color-display=\"' . esc_js($color_id) . '\"]");
                if (input && display) {
                    input.addEventListener("change", function() {
                        display.textContent = this.value.toUpperCase();
                    });
                    input.addEventListener("input", function() {
                        display.textContent = this.value.toUpperCase();
                    });
                }
            })();
            </script>';
            break;
        case 'radio':
            $choices = $field['choices'] ?? [];
            echo '<div class="yap-radio-group">';
            foreach ($choices as $choice_value => $choice_label) {
                $checked = ($value == $choice_value) ? 'checked' : '';
                $radio_id = $input_id . '_' . sanitize_key($choice_value);
                echo '<label style="display: block; margin-bottom: 5px;">';
                echo '<input type="radio" id="' . esc_attr($radio_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($choice_value) . '" ' . $checked . '> ';
                echo esc_html($choice_label);
                echo '</label>';
            }
            echo '</div>';
            break;
        case 'password':
            echo '<input type="password" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="widefat">';
            break;
        case 'wysiwyg':
            wp_editor($value, $input_id, [
                'textarea_name' => $input_name,
                'textarea_rows' => 5,
                'teeny' => false,
                'media_buttons' => true
            ]);
            break;
        case 'file':
            $file_id = $value;
            $file_url = '';
            $file_name = '';
            if (is_numeric($file_id)) {
                $file_url = wp_get_attachment_url($file_id);
                $file_name = basename(get_attached_file($file_id));
            }
            echo '<div class="yap-file-field-wrapper">';
            echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="yap-file-id">';
            echo '<button type="button" class="button yap-upload-file-button" data-field="' . esc_attr($input_id) . '">Wybierz plik</button>';
            if ($file_url) {
                echo '<div class="yap-file-preview" style="margin-top: 8px; padding: 6px; background: #f5f5f5; border-radius: 3px; font-size: 12px;">';
                echo '<a href="' . esc_url($file_url) . '" target="_blank">📄 ' . esc_html($file_name) . '</a>';
                echo '<button type="button" class="button yap-remove-file-button" style="margin-left: 8px; font-size: 11px;">Usuń</button>';
                echo '</div>';
            }
            echo '</div>';
            break;
        case 'gallery':
            $gallery_ids = is_array($value) ? $value : (!empty($value) ? explode(',', $value) : []);
            echo '<div class="yap-gallery-field-wrapper" data-field-id="' . esc_attr($input_id) . '">';
            echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr(is_array($value) ? implode(',', $value) : $value) . '" class="yap-gallery-ids">';
            echo '<button type="button" class="button yap-add-gallery-images" data-field="' . esc_attr($input_id) . '">Dodaj obrazy</button>';
            echo '<div class="yap-gallery-preview" style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 8px;">';
            foreach ($gallery_ids as $img_id) {
                if (is_numeric($img_id) && $img_id > 0) {
                    $img_url = wp_get_attachment_image_url($img_id, 'thumbnail');
                    if ($img_url) {
                        echo '<div class="yap-gallery-item" data-id="' . esc_attr($img_id) . '" style="position: relative;">';
                        echo '<img src="' . esc_url($img_url) . '" style="width: 80px; height: 80px; object-fit: cover; border-radius: 3px;">';
                        echo '<button type="button" class="yap-remove-gallery-item" style="position: absolute; top: 2px; right: 2px; background: red; color: white; border: none; border-radius: 50%; width: 18px; height: 18px; cursor: pointer; font-size: 12px; line-height: 1;">×</button>';
                        echo '</div>';
                    }
                }
            }
            echo '</div>';
            echo '</div>';
            break;
        case 'image':
            $image_id = $value;
            $image_url = '';
            if (is_numeric($image_id)) {
                $image_url = wp_get_attachment_url($image_id);
            }
            echo '<div class="yap-image-field-wrapper">';
            echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" class="yap-image-id">';
            echo '<button type="button" class="button yap-upload-image-button" data-field="' . esc_attr($input_id) . '">Wybierz obraz</button>';
            if ($image_url) {
                echo '<img src="' . esc_url($image_url) . '" class="yap-image-preview" style="margin-top: 10px; max-width: 100px; display: block;">';
                echo '<button type="button" class="button yap-remove-image-button" style="margin-top: 5px;">Usuń</button>';
            } else {
                echo '<img src="" class="yap-image-preview" style="margin-top: 10px; max-width: 100px; display: none;">';
                echo '<button type="button" class="button yap-remove-image-button" style="margin-top: 5px; display: none;">Usuń</button>';
            }
            echo '</div>';
            break;
        case 'flexible_content':
            echo '<div style="padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; font-size: 11px;">';
            echo '⚠️ Flexible Content nie może być zagnieżdżony w repeaterze/grupie';
            echo '</div>';
            break;
        case 'repeater':
        case 'group':
            echo '<div style="padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; font-size: 12px;">';
            echo '⚠️ Zagnieżdżone repeatery/grupy nie są jeszcze wspierane';
            echo '</div>';
            break;
        default:
            echo '<input type="text" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="widefat">';
            break;
    }
}

/**
 * Renderuj pole typu group
 */
function yap_render_group_field($field, $value, $input_name, $input_id) {
    // Wartość group to pojedynczy obiekt
    if (!is_array($value)) {
        $value = !empty($value) ? json_decode($value, true) : [];
    }
    if (!is_array($value)) {
        $value = [];
    }
    
    $sub_fields = $field['sub_fields'] ?? [];
    
    // Extract group and field from input_name for usage info
    preg_match('/yap_fields\[([^\]]+)\]\[([^\]]+)\]/', $input_name, $matches);
    $usage_group = $matches[1] ?? 'group_name';
    $usage_field = $matches[2] ?? $field['name'] ?? 'field_name';
    
    if (empty($sub_fields)) {
        echo '<div class="yap-group-empty" style="padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">';
        echo '<p style="margin: 0;"><strong>⚠️ Grupa bez pól:</strong> ' . esc_html($field['label']) . '</p>';
        echo '<p style="margin: 5px 0 0; font-size: 13px; color: #856404;">Dodaj pola zagnieżdżone w Visual Builder.</p>';
        echo '</div>';
        return;
    }
    
    echo '<div class="yap-group-container">';
    
    // Add usage info
    echo '<div class="yap-group-info">';
    echo '<strong style="color: #0073aa;">ℹ️ Jak użyć w szablonie:</strong><br>';
    echo '<code style="background: white; padding: 2px 6px; border-radius: 3px; font-family: monospace; display: inline-block; margin-top: 4px;">';
    echo htmlspecialchars("<?php yap_group('{$usage_group}', '{$usage_field}'); ?>");
    echo '</code>';
    echo '</div>';
    
    echo '<div class="yap-group-fields">';
    
    foreach ($sub_fields as $sub_field) {
        $sub_field_name = $sub_field['name'] ?? $sub_field['id'];
        $sub_field_label = $sub_field['label'] ?? ucwords(str_replace('_', ' ', $sub_field_name));
        $sub_field_value = $value[$sub_field_name] ?? ($sub_field['default_value'] ?? '');
        
        $sub_input_name = $input_name . '[' . $sub_field_name . ']';
        $sub_input_id = $input_id . '_' . $sub_field_name;
        
        echo '<div class="yap-group-field">';
        echo '<label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">' . esc_html($sub_field_label);
        if (!empty($sub_field['required'])) {
            echo ' <span style="color: red;">*</span>';
        }
        echo '</label>';
        
        yap_render_simple_field($sub_field, $sub_field_value, $sub_input_name, $sub_input_id);
        
        echo '</div>';
    }
    
    echo '</div>'; // .yap-group-fields
    echo '</div>'; // .yap-group-container
}

/**
 * Zapisz pola z JSON schema do post meta
 */
function yap_save_json_schema_fields($post_id) {
    // Sprawdź autosave/revision
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if (wp_is_post_autosave($post_id)) return;
    
    if (!isset($_POST['yap_fields']) || !is_array($_POST['yap_fields'])) {
        return;
    }
    
    foreach ($_POST['yap_fields'] as $group_name => $fields) {
        update_post_meta($post_id, 'yap_' . $group_name, $fields);
    }
}
add_action('save_post', 'yap_save_json_schema_fields', 10);

/**
 * Zapisz pola z tabel
 */
function yap_save_table_fields($post_id) {
    global $wpdb;
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if (wp_is_post_autosave($post_id)) return;
    
    if (!isset($_POST['yap_table_fields']) || !is_array($_POST['yap_table_fields'])) {
        return;
    }
    
    foreach ($_POST['yap_table_fields'] as $data_table => $fields) {
        foreach ($fields as $generated_name => $field_value) {
            $wpdb->update(
                $data_table,
                ['field_value' => sanitize_text_field($field_value)],
                [
                    'generated_name' => $generated_name,
                    'associated_id' => $post_id
                ]
            );
        }
    }
}
add_action('save_post', 'yap_save_table_fields', 10);

/**
 * Debug: Pokaż wszystkie location rules
 */
function yap_debug_location_rules() {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['yap_debug_rules'])) return;
    
    global $wpdb;
    $rules = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yap_location_rules ORDER BY group_name, rule_group, rule_order");
    
    echo '<div class="notice notice-info" style="padding: 20px;"><h3>🔍 Location Rules Debug</h3><pre>';
    foreach ($rules as $rule) {
        echo sprintf(
            "Grupa: %s | Typ: %s | Operator: %s | Wartość: %s | Group: %d | Order: %d\n",
            $rule->group_name,
            $rule->location_type,
            $rule->location_operator,
            $rule->location_value,
            $rule->rule_group,
            $rule->rule_order
        );
    }
    echo '</pre></div>';
}
add_action('admin_notices', 'yap_debug_location_rules');

?>
