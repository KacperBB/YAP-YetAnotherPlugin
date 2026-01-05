<?php
/**
 * Flexible Content Field - Builder Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Flexible_Content_Builder extends YAP_Field_Builder_Base {
    
    public function render() {
        $this->render_wrapper_open();
        $this->render_field_label();
        $this->render_field_description();
        
        echo '<div style="border: 2px dashed #999; border-radius: 4px; padding: 15px; text-align: center; background: #f9f9f9; margin-top: 8px;">';
        echo '<p style="color: #666; margin: 0;">⚙️ Flexible Content - Kliknij w edytorze aby skonfigurować sekcje</p>';
        echo '</div>';
        
        $this->render_wrapper_close();
    }
}
