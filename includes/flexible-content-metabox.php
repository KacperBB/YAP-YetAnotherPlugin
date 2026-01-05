<?php
/**
 * YAP Flexible Content - Metabox Renderer
 * 
 * Renderer dla flexible content w oknie metabox (post edit screen)
 * Wyświetla uproszczoną wersję z możliwością edycji danych
 * 
 * @package YetAnotherPlugin
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Flexible_Content_Metabox_Renderer {
    
    /**
     * Render flexible content field w metabox
     */
    public static function render($field, $value, $input_name, $input_id) {
        $group_name = $field['group_name'] ?? '';
        $field_name = $field['name'] ?? '';
        
        error_log("📦 [METABOX] Flexible Content: group={$group_name}, field={$field_name}");
        
        // Get layouts configuration
        $layouts = self::get_layouts($group_name, $field_name);
        
        // Parse value
        if (!is_array($value)) {
            $value = !empty($value) ? json_decode($value, true) : [];
        }
        if (!is_array($value)) {
            $value = [];
        }
        
        $flexible_id = sanitize_key($input_id);
        $field_id = 'field_' . time();
        
        // Start wrapper with metadata
        echo '<div class="yap-preview-field-wrapper" data-field-id="' . esc_attr($field_id) . '" data-conditional="false" data-conditional-action="show" data-conditional-field="" data-conditional-operator="==" data-conditional-value="" data-conditional-message="" style="margin-bottom: 25px;">';
        
        // Label
        echo '<label for="' . esc_attr($input_id) . '" style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px;">';
        echo esc_html($field['label'] ?? 'Flexible Content');
        echo '</label>';
        
        if (empty($layouts)) {
            self::render_empty_state($field, $input_name, $input_id);
        } else {
            self::render_configured_state($field, $value, $input_name, $input_id, $layouts, $flexible_id);
        }
        
        // Conditional message box
        echo '<div class="yap-conditional-message-box" style="display: none; margin-top: 10px; padding: 12px; border-radius: 4px;"></div>';
        
        echo '</div>'; // .yap-preview-field-wrapper
    }
    
    /**
     * Render empty state (no layouts configured)
     */
    private static function render_empty_state($field, $input_name, $input_id) {
        echo '<div style="border: 2px dashed #0073aa; border-radius: 4px; padding: 15px; background: #f9f9f9;">';
        echo '<div style="background: #f0f0f0; padding: 15px; border-radius: 4px; margin-top: 10px;">';
        echo '<p style="font-weight: 600; margin-bottom: 15px; color: #333;">📋 ' . esc_html($field['label'] ?? 'Flexible Content') . ' 1 (Przykład)</p>';
        
        echo '<div style="margin-bottom: 12px;">';
        echo '<label style="display: block; font-weight: 500; margin-bottom: 6px; font-size: 13px;">';
        echo esc_html($field['label'] ?? 'Flexible Content') . ' 1';
        echo '</label>';
        echo '<div style="border: 2px dashed #999; border-radius: 4px; padding: 15px; text-align: center; background: #f9f9f9;">';
        echo '<p style="color: #666; margin: 0;">⚙️ Kliknij w edytorze aby skonfigurować sekcje</p>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '<button type="button" style="margin-top: 15px; padding: 8px 15px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">';
        echo '+ Dodaj rząd';
        echo '</button>';
        echo '</div>';
    }
    
    /**
     * Render configured state (with layouts)
     */
    private static function render_configured_state($field, $value, $input_name, $input_id, $layouts, $flexible_id) {
        $group_name = $field['group_name'] ?? '';
        $field_name = $field['name'] ?? '';
        
        echo '<div class="yap-flexible-container" data-flexible-id="' . esc_attr($flexible_id) . '" data-group="' . esc_attr($group_name) . '" data-field="' . esc_attr($field_name) . '" style="border: 2px dashed #0073aa; border-radius: 4px; padding: 15px; background: #f9f9f9;">';
        
        // Hidden input for JSON value
        echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr(json_encode($value)) . '" class="yap-flexible-value">';
        
        // Info box
        echo '<div class="yap-flexible-info" style="margin-bottom: 10px; padding: 8px 12px; background: #f0f6fc; border-left: 3px solid #72aee6; font-size: 12px;">';
        echo '<strong>ℹ️ Jak użyć:</strong> <code style="background: white; padding: 2px 6px; border-radius: 3px;">';
        echo htmlspecialchars("<?php yap_flexible('{$group_name}', '{$field_name}'); ?>");
        echo '</code>';
        echo '</div>';
        
        // Sections container
        echo '<div class="yap-flexible-sections" id="' . esc_attr($flexible_id) . '_sections">';
        
        if (empty($value)) {
            // Show example row
            self::render_example_row($layouts);
        } else {
            // Show existing sections
            foreach ($value as $section_index => $section_data) {
                self::render_section($layouts, $section_data, $section_index, $flexible_id, $input_name);
            }
        }
        
        echo '</div>'; // .yap-flexible-sections
        
        // Action buttons
        echo '<div class="yap-flexible-actions" style="margin-top: 15px; padding: 15px; background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%); border: none; border-radius: 4px; display: flex; flex-wrap: wrap; gap: 8px;">';
        
        foreach ($layouts as $layout) {
            echo '<button type="button" class="button button-primary yap-add-flexible-section" data-flexible-id="' . esc_attr($flexible_id) . '" data-layout="' . esc_attr($layout['name']) . '" style="background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); border: none; color: white; cursor: pointer; font-weight: 600; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,115,170,0.15);">';
            echo '➕ ' . esc_html($layout['label']);
            echo '</button>';
        }
        
        echo '</div>'; // .yap-flexible-actions
        
        echo '</div>'; // .yap-flexible-container
        
        // Store layouts in JavaScript
        echo '<script>
            if (typeof yapFlexibleLayouts === "undefined") {
                window.yapFlexibleLayouts = {};
            }
            yapFlexibleLayouts["' . esc_js($flexible_id) . '"] = ' . json_encode($layouts) . ';
        </script>';
    }
    
    /**
     * Render example row when no data exists
     */
    private static function render_example_row($layouts) {
        echo '<div style="background: #f0f0f0; padding: 15px; border-radius: 4px; margin-top: 10px;">';
        echo '<p style="font-weight: 600; margin-bottom: 15px; color: #333;">📋 Row 1 (Example)</p>';
        
        foreach ($layouts as $layout) {
            echo '<div style="margin-bottom: 12px;">';
            echo '<label style="display: block; font-weight: 500; margin-bottom: 6px; font-size: 13px;">';
            echo esc_html($layout['label']);
            echo '</label>';
            echo '<div style="border: 2px dashed #999; border-radius: 4px; padding: 15px; text-align: center; background: #f9f9f9;">';
            echo '<p style="color: #666; margin: 0;">⚙️ Kliknij w edytorze aby skonfigurować sekcje</p>';
            echo '</div>';
            echo '</div>';
            break; // Only first layout as example
        }
        
        echo '</div>';
    }
    
    /**
     * Render single section (stub - calls parent implementation)
     */
    private static function render_section($layouts, $section_data, $section_index, $flexible_id, $input_name) {
        // Delegate to main flexible content class
        YAP_Flexible_Content::render_section_internal($layouts, $section_data, $section_index, $flexible_id, $input_name);
    }
    
    /**
     * Get layouts for flexible content field
     */
    private static function get_layouts($group_name, $field_name) {
        return YAP_Flexible_Content::get_layouts($group_name, $field_name);
    }
}
