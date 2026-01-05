<?php
/**
 * Number Field - Builder Renderer
 * 
 * Renderowanie pola number w visual builderze
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Number_Builder extends YAP_Field_Builder_Base {
    
    /**
     * Renderuj pole number w builderze
     */
    public function render() {
        $this->render_wrapper_open();
        
        $this->render_field_label();
        $this->render_field_description();
        
        $min = $this->field_config['min'] ?? '';
        $max = $this->field_config['max'] ?? '';
        $step = $this->field_config['step'] ?? '1';
        
        echo '<input 
            type="number" 
            id="' . esc_attr($this->input_id) . '" 
            name="' . esc_attr($this->input_name) . '" 
            value="' . esc_attr($this->value) . '" 
            class="widefat"
            style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-top: 8px;"
            ' . ($min !== '' ? 'min="' . esc_attr($min) . '"' : '') . '
            ' . ($max !== '' ? 'max="' . esc_attr($max) . '"' : '') . '
            step="' . esc_attr($step) . '"
            ' . $this->get_required_attr() . '
        >';
        
        $this->render_wrapper_close();
    }
}
