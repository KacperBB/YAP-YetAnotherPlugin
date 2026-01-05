<?php
/**
 * Group Field - Builder Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Group_Builder extends YAP_Field_Builder_Base {
    
    public function render() {
        $this->render_wrapper_open();
        $this->render_field_label();
        $this->render_field_description();
        
        echo '<fieldset style="border: 1px solid #ddd; border-radius: 4px; padding: 15px; background: #f9f9f9; margin-top: 8px;">';
        echo '<p style="margin: 0; color: #666; font-style: italic;">⚙️ Pola grupy - edytuj w głównym edytorze</p>';
        echo '</fieldset>';
        
        $this->render_wrapper_close();
    }
}
