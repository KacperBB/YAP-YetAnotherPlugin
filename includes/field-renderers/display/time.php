<?php
/**
 * Time Field - Display Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Time_Display extends YAP_Field_Display_Base {
    
    public function render() {
        if ($this->is_empty()) {
            $this->render_empty_state();
            return;
        }
        
        $this->render_wrapper_open();
        echo '<span style="font-weight: 500;">' . esc_html($this->value) . '</span>';
        $this->render_wrapper_close();
    }
}
