<?php
/**
 * Repeater Field Renderer
 * 
 * Renderer dla pola repeater - dynamiczne wiersze
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
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
        echo '<p style="margin: 5px 0 0; font-size: 13px; color: #856404;">Dodaj pola zagnieżdżone w Visual Builder.</p>';
        echo '</div>';
        return;
    }
    
    // Jeśli brak wartości, dodaj jeden pusty wiersz
    if (empty($value)) {
        $value = [[]];
    }
    
    $repeater_id = sanitize_key($input_id);
    
    echo '<div class="yap-repeater-container" data-repeater-id="' . esc_attr($repeater_id) . '" data-min="' . esc_attr($min_rows) . '" data-max="' . esc_attr($max_rows) . '">';
    
    // Informacja o użyciu
    preg_match('/yap_fields\[([^\]]+)\]\[([^\]]+)\]/', $input_name, $matches);
    $usage_group = $matches[1] ?? 'group_name';
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
        
        $sub_input_name = $input_name . '[' . $row_index . '][' . $sub_field_name . ']';
        $sub_input_id = $repeater_id . '_' . $row_index . '_' . $sub_field_name;
        
        echo '<div class="yap-repeater-field" style="margin-bottom: 10px;">';
        echo '<label style="display: block; font-weight: 600; margin-bottom: 3px; font-size: 13px;">' . esc_html($sub_field_label);
        if (!empty($sub_field['required'])) {
            echo ' <span style="color: red;">*</span>';
        }
        echo '</label>';
        
        // Renderuj pole (bez rekurencji)
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
