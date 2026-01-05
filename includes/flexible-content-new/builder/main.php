<?php
/**
 * Flexible Content - Builder Renderer
 * 
 * Renderowanie flexible content pola w visual builderze
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Flexible_Content_Builder {
    
    /**
     * Renderuj flexible content w builderze
     */
    public static function render($field, $value, $input_name, $input_id) {
        $group_name = $field['group_name'] ?? '';
        $field_name = $field['name'] ?? '';
        
        error_log("🎨 [FC-BUILDER] Rendering flexible content: {$group_name}.{$field_name}");
        
        // Get layouts
        $layouts = YAP_Flexible_Content::get_layouts($group_name, $field_name);
        
        // Parse value
        if (!is_array($value)) {
            $value = !empty($value) ? json_decode($value, true) : [];
        }
        if (!is_array($value)) {
            $value = [];
        }
        
        $flexible_id = sanitize_key($input_id);
        
        // Wrapper
        echo '<div class="yap-flexible-content-builder" data-flexible-id="' . esc_attr($flexible_id) . '">';
        
        // Header
        echo '<div style="margin-bottom: 15px; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 4px; color: white;">';
        echo '<h3 style="margin: 0; font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 10px;">';
        echo '<span style="font-size: 20px;">🎨</span>';
        echo esc_html($field['label'] ?? 'Flexible Content');
        echo '</h3>';
        echo '</div>';
        
        if (empty($layouts)) {
            echo '<div style="padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: white; text-align: center;">';
            echo '<p style="margin: 0; font-size: 14px;">Brak skonfigurowanych layoutów</p>';
            echo '</div>';
        } else {
            // Sections container
            echo '<div class="yap-flexible-sections">';
            
            if (empty($value)) {
                // Empty state with example
                self::render_empty_state($field, $layouts);
            } else {
                // Show existing sections
                foreach ($value as $idx => $section) {
                    self::render_section($field, $section, $idx, $layouts);
                }
            }
            
            echo '</div>';
            
            // Action buttons
            echo '<div style="margin-top: 15px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px;">';
            foreach ($layouts as $layout) {
                echo '<button type="button" class="button button-primary" data-layout="' . esc_attr($layout['name']) . '" style="background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); border: none; color: white; cursor: pointer; font-weight: 600;">';
                echo '➕ ' . esc_html($layout['label']);
                echo '</button>';
            }
            echo '</div>';
        }
        
        // Hidden input with value
        echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr(json_encode($value)) . '" class="yap-flexible-value">';
        
        echo '</div>';
    }
    
    /**
     * Renderuj empty state
     */
    private static function render_empty_state($field, $layouts) {
        echo '<div style="background: #f0f0f0; padding: 15px; border-radius: 4px; margin-bottom: 15px;">';
        echo '<p style="font-weight: 600; margin-bottom: 15px; color: #333;">📋 Row 1 (Example)</p>';
        
        $layout = $layouts[0];
        echo '<div style="margin-bottom: 12px;">';
        echo '<label style="display: block; font-weight: 500; margin-bottom: 6px; font-size: 13px;">';
        echo esc_html($layout['label']);
        echo '</label>';
        echo '<div style="border: 2px dashed #999; border-radius: 4px; padding: 15px; text-align: center; background: white;">';
        echo '<p style="color: #666; margin: 0;">⚙️ Configure sections in editor</p>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Renderuj pojedynczą sekcję
     */
    private static function render_section($field, $section, $idx, $layouts) {
        $layout_type = $section['layout'] ?? '';
        $layout = null;
        
        foreach ($layouts as $l) {
            if ($l['name'] === $layout_type) {
                $layout = $l;
                break;
            }
        }
        
        if (!$layout) {
            return;
        }
        
        echo '<div style="border: 1px solid #0073aa; border-radius: 4px; margin-bottom: 12px; overflow: hidden;">';
        
        // Header
        echo '<div style="padding: 12px 15px; background: linear-gradient(135deg, #f0f6fc 0%, #e8f2f9 100%); border-bottom: 1px solid #d1e5f5; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">';
        echo '<div style="display: flex; align-items: center; gap: 10px;">';
        echo '<strong style="color: #0073aa;">' . esc_html($layout['label']) . '</strong>';
        echo '<span style="color: #666; font-size: 12px;">#' . ($idx + 1) . '</span>';
        echo '</div>';
        echo '<div style="display: flex; gap: 6px;">';
        echo '<button type="button" style="background: none; border: none; color: #f56565; cursor: pointer; font-size: 11px;">🗑️ Delete</button>';
        echo '</div>';
        echo '</div>';
        
        // Content
        echo '<div style="padding: 15px;">';
        
        if (isset($layout['fields']) && is_array($layout['fields'])) {
            foreach ($layout['fields'] as $sub_field) {
                $field_name = $sub_field['name'] ?? '';
                $field_value = $section['fields'][$field_name] ?? '';
                $field_type = $sub_field['type'] ?? 'text';
                
                echo '<div style="margin-bottom: 12px;">';
                echo '<label style="display: block; font-weight: 500; margin-bottom: 6px; font-size: 13px;">';
                echo esc_html($sub_field['label'] ?? $field_name);
                echo '</label>';
                
                // Renderuj poddane pole za pomocą loadera
                YAP_Field_Renderers_Loader::render('builder', $field_type, $sub_field, $field_value);
                
                echo '</div>';
            }
        }
        
        echo '</div>';
        echo '</div>';
    }
}
