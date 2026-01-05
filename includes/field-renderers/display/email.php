<?php
/**
 * Email Field - Display Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Email_Display extends YAP_Field_Display_Base {
    
    public function render() {
        if ($this->is_empty()) {
            $this->render_empty_state();
            return;
        }
        
        $this->render_wrapper_open();
        echo '<a href="mailto:' . esc_attr($this->value) . '">' . esc_html($this->value) . '</a>';
        $this->render_wrapper_close();
    }
}
