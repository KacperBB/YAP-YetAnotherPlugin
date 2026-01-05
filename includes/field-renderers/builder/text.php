<?php
/**
 * Text Field - Builder Renderer
 * 
 * Renderowanie pola text w visual builderze
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Text_Builder extends YAP_Field_Builder_Base {
    
    /**
     * Renderuj pole text w builderze
     */
    public function render() {
        $this->render_wrapper_open();
        
        $this->render_field_label();
        $this->render_field_description();
        
        echo '<input 
            type="text" 
            id="' . esc_attr($this->input_id) . '" 
            name="' . esc_attr($this->input_name) . '" 
            value="' . esc_attr($this->value) . '" 
            class="widefat"
            style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-top: 8px;"
            ' . $this->get_required_attr() . '
        >';
        
        $this->render_wrapper_close();
    }
}
