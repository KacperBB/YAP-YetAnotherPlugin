<?php
/**
 * Repeater Field - Builder Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Repeater_Builder extends YAP_Field_Builder_Base {
    
    public function render() {
        $this->render_wrapper_open();
        $this->render_field_label();
        $this->render_field_description();
        
        echo '<div style="border: 2px dashed #0073aa; border-radius: 4px; padding: 15px; background: #f9f9f9; margin-top: 8px;">';
        echo '<p style="margin: 0 0 15px; color: #666;">📋 Repeater pole - Kliknij w edytorze aby dodać rzędy</p>';
        echo '<button type="button" class="button button-primary" style="background: #0073aa; color: white; border: none;">+ Dodaj rząd</button>';
        echo '</div>';
        
        $this->render_wrapper_close();
    }
}
