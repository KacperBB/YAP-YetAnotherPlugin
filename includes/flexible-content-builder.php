<?php
/**
 * YAP Flexible Content - Visual Builder Renderer
 * 
 * Renderer dla flexible content w Visual Builder (editor window)
 * Wyświetla pełną edycję z możliwością dodawania/usuwania sekcji
 * 
 * @package YetAnotherPlugin
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Flexible_Content_Builder_Renderer {
    
    /**
     * Render flexible content field w visual builder
     */
    public static function render($field, $value, $input_name, $input_id) {
        $group_name = $field['group_name'] ?? '';
        $field_name = $field['name'] ?? '';
        
        error_log("🔧 [BUILDER] Flexible Content: group={$group_name}, field={$field_name}");
        
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
        
        // Builder wrapper with full editor interface
        echo '<div class="yap-flexible-builder-wrapper" data-flexible-id="' . esc_attr($flexible_id) . '" data-group="' . esc_attr($group_name) . '" data-field="' . esc_attr($field_name) . '">';
        
        // Header with title
        echo '<div class="yap-flexible-builder-header" style="margin-bottom: 15px; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 4px; color: white;">';
        echo '<h3 style="margin: 0; font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 10px;">';
        echo '<span style="font-size: 20px;">🎨</span>';
        echo esc_html($field['label'] ?? 'Flexible Content');
        echo '</h3>';
        echo '<p style="margin: 8px 0 0; font-size: 12px; opacity: 0.9;">Edytuj sekcje na tej stronie</p>';
        echo '</div>';
        
        if (empty($layouts)) {
            self::render_builder_empty_state($field);
        } else {
            self::render_builder_configured($field, $value, $input_name, $input_id, $layouts, $flexible_id, $group_name, $field_name);
        }
        
        echo '</div>'; // .yap-flexible-builder-wrapper
    }
    
    /**
     * Render empty state w builder
     */
    private static function render_builder_empty_state($field) {
        echo '<div style="padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: white; text-align: center;">';
        echo '<p style="font-size: 48px; margin: 0 0 15px; opacity: 0.9;">🎨</p>';
        echo '<p style="font-size: 16px; font-weight: 600; margin: 0 0 10px;">Brak skonfigurowanych layoutów</p>';
        echo '<p style="font-size: 13px; opacity: 0.9; margin: 0;">Aby zacząć, przejdź do ustawień tej grupy pól i dodaj layouty dla tego pola flexible content.</p>';
        echo '</div>';
    }
    
    /**
     * Render configured state w builder
     */
    private static function render_builder_configured($field, $value, $input_name, $input_id, $layouts, $flexible_id, $group_name, $field_name) {
        // Hidden input for value
        echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr(json_encode($value)) . '" class="yap-flexible-value">';
        
        // Builder toolbar
        echo '<div class="yap-flexible-builder-toolbar" style="margin-bottom: 15px; padding: 12px; background: #f0f6fc; border-left: 3px solid #0073aa; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
        
        echo '<div style="font-size: 12px; color: #0073aa;">';
        echo '<strong>Sekcje:</strong> <span class="section-count">' . count($value) . '</span> | ';
        echo '<strong>Layouty dostępne:</strong> ' . count($layouts);
        echo '</div>';
        
        echo '<div style="display: flex; gap: 6px;">';
        echo '<button type="button" class="button yap-expand-all-sections" style="font-size: 11px; padding: 4px 8px;">↕️ Rozwiń wszystko</button>';
        echo '<button type="button" class="button yap-collapse-all-sections" style="font-size: 11px; padding: 4px 8px;">↔️ Zwiń wszystko</button>';
        echo '</div>';
        
        echo '</div>';
        
        // Sections container with drag-drop support
        echo '<div class="yap-flexible-builder-sections" id="' . esc_attr($flexible_id) . '_sections" data-flexible-id="' . esc_attr($flexible_id) . '" style="margin-bottom: 15px;">';
        
        if (!empty($value)) {
            foreach ($value as $section_index => $section_data) {
                self::render_builder_section($layouts, $section_data, $section_index, $flexible_id, $input_name);
            }
        } else {
            // Show "no sections" message
            echo '<div style="padding: 30px; background: #f0f0f0; border-radius: 4px; text-align: center; border: 2px dashed #999;">';
            echo '<p style="color: #666; margin: 0;">📭 Brak sekcji. Kliknij poniżej aby dodać pierwszą.</p>';
            echo '</div>';
        }
        
        echo '</div>'; // .yap-flexible-builder-sections
        
        // Add section buttons
        echo '<div class="yap-flexible-builder-actions" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px;">';
        
        foreach ($layouts as $layout) {
            echo '<button type="button" class="button button-primary yap-add-flexible-section-builder" data-flexible-id="' . esc_attr($flexible_id) . '" data-layout="' . esc_attr($layout['name']) . '" style="background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); border: none; color: white; cursor: pointer; font-weight: 600; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,115,170,0.15); padding: 10px 15px;">';
            echo '➕ ' . esc_html($layout['label']);
            echo '</button>';
        }
        
        echo '</div>'; // .yap-flexible-builder-actions
        
        // Store layouts data
        echo '<script>
            if (typeof yapFlexibleLayouts === "undefined") {
                window.yapFlexibleLayouts = {};
            }
            yapFlexibleLayouts["' . esc_js($flexible_id) . '"] = ' . json_encode($layouts) . ';
        </script>';
    }
    
    /**
     * Render single section in builder with full edit interface
     */
    private static function render_builder_section($layouts, $section_data, $section_index, $flexible_id, $input_name) {
        $layout_type = $section_data['layout'] ?? '';
        $fields_data = $section_data['fields'] ?? [];
        
        // Find layout config
        $layout_config = null;
        foreach ($layouts as $layout) {
            if ($layout['name'] === $layout_type) {
                $layout_config = $layout;
                break;
            }
        }
        
        if (!$layout_config) {
            return;
        }
        
        $section_id = 'section_' . $flexible_id . '_' . $section_index;
        
        echo '<div class="yap-flexible-builder-section" data-section-id="' . esc_attr($section_id) . '" data-section-index="' . esc_attr($section_index) . '" style="margin-bottom: 12px; border: 1px solid #0073aa; border-radius: 4px; overflow: hidden; background: white;">';
        
        // Section header (collapsible)
        echo '<div class="yap-section-header" style="padding: 12px 15px; background: linear-gradient(135deg, #f0f6fc 0%, #e8f2f9 100%); border-bottom: 1px solid #d1e5f5; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none;">';
        
        echo '<div style="display: flex; align-items: center; gap: 10px; flex: 1;">';
        echo '<span class="section-toggle" style="color: #0073aa; font-weight: bold;">▼</span>';
        echo '<strong style="color: #0073aa;">' . esc_html($layout_config['label']) . '</strong>';
        echo '<span style="color: #666; font-size: 12px;">#' . ($section_index + 1) . '</span>';
        echo '</div>';
        
        echo '<div style="display: flex; gap: 6px;">';
        echo '<button type="button" class="button-link yap-duplicate-section" data-section-id="' . esc_attr($section_id) . '" style="color: #0073aa; text-decoration: none; font-size: 11px; cursor: pointer;">📋 Duplikuj</button>';
        echo '<button type="button" class="button-link yap-delete-section" data-section-id="' . esc_attr($section_id) . '" style="color: #f56565; text-decoration: none; font-size: 11px; cursor: pointer;">🗑️ Usuń</button>';
        echo '</div>';
        
        echo '</div>';
        
        // Section content (collapsible)
        echo '<div class="yap-section-content" style="padding: 15px; border-top: 1px solid #d1e5f5; display: none;">';
        
        if ($layout_config['fields'] ?? false) {
            foreach ($layout_config['fields'] as $sub_field) {
                self::render_builder_field($sub_field, $fields_data, $section_index);
            }
        } else {
            echo '<p style="color: #666; font-size: 12px; margin: 0;">Brak pól do edycji</p>';
        }
        
        echo '</div>';
        
        echo '</div>'; // .yap-flexible-builder-section
    }
    
    /**
     * Render field w builder
     */
    private static function render_builder_field($field_config, $section_data, $section_index) {
        $field_name = $field_config['name'] ?? '';
        $field_label = $field_config['label'] ?? '';
        $field_type = $field_config['type'] ?? 'text';
        $current_value = $section_data[$field_name] ?? '';
        
        echo '<div style="margin-bottom: 15px;">';
        echo '<label style="display: block; font-weight: 500; margin-bottom: 6px; font-size: 13px;">';
        echo esc_html($field_label);
        echo '</label>';
        
        echo '<input type="' . esc_attr($field_type) . '" ';
        echo 'data-section-index="' . esc_attr($section_index) . '" ';
        echo 'data-field-name="' . esc_attr($field_name) . '" ';
        echo 'value="' . esc_attr($current_value) . '" ';
        echo 'class="yap-section-field widefat" ';
        echo 'style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">';
        
        echo '</div>';
    }
    
    /**
     * Get layouts for flexible content field
     */
    private static function get_layouts($group_name, $field_name) {
        return YAP_Flexible_Content::get_layouts($group_name, $field_name);
    }
}
