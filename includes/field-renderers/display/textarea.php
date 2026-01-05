<?php
/**
 * Textarea Field - Display Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Textarea_Display extends YAP_Field_Display_Base {
    
    public function render() {
        if ($this->is_empty()) {
            $this->render_empty_state();
            return;
        }
        
        $this->render_wrapper_open();
        echo '<div style="white-space: pre-wrap; word-break: break-word;">' . wp_kses_post(nl2br($this->value)) . '</div>';
        $this->render_wrapper_close();
    }
}
