<?php
/**
 * Color Field - Preview Renderer
 * 
 * Renderowanie preview pola color w edytorze - pokazuje kwadrat koloru
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Color_Preview extends YAP_Field_Preview_Base {
    
    /**
     * Renderuj preview pola color
     */
    public function render() {
        $this->render_wrapper_open();
        
        if ($this->is_empty()) {
            $this->render_empty_state();
        } else {
            echo '<div style="display: flex; align-items: center; gap: 8px;">';
            echo '<div style="width: 24px; height: 24px; background-color: ' . esc_attr($this->value) . '; border-radius: 3px; border: 1px solid #ddd;"></div>';
            echo '<span style="color: #333; font-size: 12px; font-family: monospace;">' . esc_html($this->value) . '</span>';
            echo '</div>';
        }
        
        $this->render_wrapper_close();
    }
}
