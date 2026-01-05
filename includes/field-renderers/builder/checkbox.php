<?php
/**
 * Checkbox Field - Builder Renderer
 * 
 * Renderowanie pola checkbox w visual builderze
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Checkbox_Builder extends YAP_Field_Builder_Base {
    
    public function render() {
        $this->render_wrapper_open();
        $this->render_field_label();
        $this->render_field_description();
        
        echo '<label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 8px;">';
        echo '<input type="checkbox" 
            id="' . esc_attr($this->input_id) . '" 
            name="' . esc_attr($this->input_name) . '" 
            value="1"
            style="width: auto;"
            ' . checked($this->value, 1, false) . '
        >';
        echo '<span>' . esc_html($this->field_config['label'] ?? 'Checkbox') . '</span>';
        echo '</label>';
        
        $this->render_wrapper_close();
    }
}
