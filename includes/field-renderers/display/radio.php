<?php
/**
 * Radio Field - Display Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Radio_Display extends YAP_Field_Display_Base {
    
    public function render() {
        if ($this->is_empty()) {
            $this->render_empty_state();
            return;
        }
        
        $this->render_wrapper_open();
        echo '<span style="background: #f0f0f0; padding: 4px 8px; border-radius: 3px; display: inline-block;">' . esc_html($this->value) . '</span>';
        $this->render_wrapper_close();
    }
}
