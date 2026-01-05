<?php
/**
 * Date Field - Builder Renderer
 * 
 * Renderowanie pola date w visual builderze
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Date_Builder extends YAP_Field_Builder_Base {
    
    public function render() {
        $this->render_wrapper_open();
        $this->render_field_label();
        $this->render_field_description();
        
        echo '<input 
            type="date" 
            id="' . esc_attr($this->input_id) . '" 
            name="' . esc_attr($this->input_name) . '" 
            value="' . esc_attr($this->value) . '" 
            class="widefat"
            style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; font-family: inherit; margin-top: 8px;"
            ' . $this->get_required_attr() . '
        >';
        
        $this->render_wrapper_close();
    }
}
