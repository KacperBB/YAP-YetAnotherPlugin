<?php
/**
 * Group Field Renderer
 * 
 * Renderer dla pola group - pola zgrupowane
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
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
    
    // Informacja o użyciu
    preg_match('/yap_fields\[([^\]]+)\]\[([^\]]+)\]/', $input_name, $matches);
    $usage_group = $matches[1] ?? 'group_name';
    $usage_field = $matches[2] ?? $field['name'] ?? 'field_name';
    
    if (empty($sub_fields)) {
        echo '<div class="yap-group-empty" style="padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">';
        echo '<p style="margin: 0;"><strong>⚠️ Grupa bez pół:</strong> ' . esc_html($field['label']) . '</p>';
        echo '<p style="margin: 5px 0 0; font-size: 13px; color: #856404;">Dodaj pola zagnieżdżone w Visual Builder.</p>';
        echo '</div>';
        return;
    }
    
    echo '<div class="yap-group-container">';
    
    // Informacja o użyciu
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
