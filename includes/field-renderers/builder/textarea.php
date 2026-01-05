<?php
/**
 * Textarea Field - Builder Renderer
 * 
 * Renderowanie pola textarea w visual builderze
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Textarea_Builder extends YAP_Field_Builder_Base {
    
    /**
     * Renderuj pole textarea w builderze
     */
    public function render() {
        $this->render_wrapper_open();
        
        $this->render_field_label();
        $this->render_field_description();
        
        $rows = $this->field_config['rows'] ?? 4;
        
        echo '<textarea 
            id="' . esc_attr($this->input_id) . '" 
            name="' . esc_attr($this->input_name) . '" 
            rows="' . esc_attr($rows) . '"
            class="widefat"
            style="padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; font-family: inherit; margin-top: 8px;"
            ' . $this->get_required_attr() . '
        >' . esc_textarea($this->value) . '</textarea>';
        
        $this->render_wrapper_close();
    }
}
