<?php
/**
 * Radio Field - Builder Renderer
 * 
 * Renderowanie pola radio w visual builderze
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Radio_Builder extends YAP_Field_Builder_Base {
    
    public function render() {
        $this->render_wrapper_open();
        $this->render_field_label();
        $this->render_field_description();
        
        $options = $this->field_config['options'] ?? [];
        
        echo '<div style="display: flex; flex-direction: column; gap: 8px; margin-top: 8px;">';
        
        foreach ($options as $value => $label) {
            $checked = checked($this->value, $value, false);
            echo '<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">';
            echo '<input type="radio" 
                name="' . esc_attr($this->input_name) . '" 
                value="' . esc_attr($value) . '" 
                style="width: auto;"
                ' . $checked . '
            >';
            echo '<span>' . esc_html($label) . '</span>';
            echo '</label>';
        }
        
        echo '</div>';
        
        $this->render_wrapper_close();
    }
}
