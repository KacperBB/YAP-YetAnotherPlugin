<?php
/**
 * Number Field - Preview Renderer
 * 
 * Renderowanie preview pola number w edytorze
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Number_Preview extends YAP_Field_Preview_Base {
    
    /**
     * Renderuj preview pola number
     */
    public function render() {
        $this->render_wrapper_open();
        
        if ($this->is_empty()) {
            $this->render_empty_state();
        } else {
            echo '<span style="color: #333; font-size: 13px; font-weight: 600;">' . $this->safe_display($this->value) . '</span>';
        }
        
        $this->render_wrapper_close();
    }
}
