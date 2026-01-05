<?php
/**
 * Color Field - Builder Renderer
 * 
 * Renderowanie pola color w visual builderze z niestandardowym stylowaniem
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Color_Builder extends YAP_Field_Builder_Base {
    
    /**
     * Renderuj pole color w builderze
     */
    public function render() {
        $this->render_wrapper_open();
        
        $this->render_field_label();
        $this->render_field_description();
        
        $unique_id = uniqid('color_');
        
        echo '<div class="yap-color-picker-wrapper" style="margin-top: 8px; display: flex; gap: 10px; align-items: center;">';
        
        // Color input
        echo '<input 
            type="color" 
            id="' . esc_attr($this->input_id) . '" 
            name="' . esc_attr($this->input_name) . '" 
            value="' . esc_attr($this->value ?: '#000000') . '" 
            class="yap-builder-color-input"
            data-unique-id="' . esc_attr($unique_id) . '"
            style="width: 50px; height: 40px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;"
            ' . $this->get_required_attr() . '
        >';
        
        // Hex value display
        echo '<input 
            type="text" 
            class="yap-color-value" 
            id="' . esc_attr($unique_id) . '" 
            value="' . esc_attr($this->value ?: '#000000') . '" 
            placeholder="#000000"
            style="width: 100px; padding: 6px 8px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 12px;"
            readonly
        >';
        
        echo '</div>';
        
        echo '<script>
        (function() {
            const colorInput = document.getElementById("' . esc_attr($this->input_id) . '");
            const hexDisplay = document.getElementById("' . esc_attr($unique_id) . '");
            
            if (colorInput && hexDisplay) {
                colorInput.addEventListener("change", function() {
                    hexDisplay.value = this.value;
                });
            }
        })();
        </script>';
        
        $this->render_wrapper_close();
    }
}
