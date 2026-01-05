<?php
/**
 * Select Field - Builder Renderer
 * 
 * Renderowanie pola select w visual builderze
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Select_Builder extends YAP_Field_Builder_Base {
    
    public function render() {
        $this->render_wrapper_open();
        $this->render_field_label();
        $this->render_field_description();
        
        $options = $this->field_config['options'] ?? [];
        
        echo '<select 
            id="' . esc_attr($this->input_id) . '" 
            name="' . esc_attr($this->input_name) . '"
            style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; font-family: inherit; margin-top: 8px;"
            ' . $this->get_required_attr() . '
        >';
        
        echo '<option value="">-- Select --</option>';
        
        foreach ($options as $value => $label) {
            $selected = selected($this->value, $value, false);
            echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        
        $this->render_wrapper_close();
    }
}
