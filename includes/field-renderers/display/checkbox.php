<?php
/**
 * Checkbox Field - Display Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Checkbox_Display extends YAP_Field_Display_Base {
    
    public function render() {
        $this->render_wrapper_open();
        
        if ($this->is_empty()) {
            echo '<span style="color: #999;">☐ Nie zaznaczono</span>';
        } else {
            echo '<span style="color: #4caf50; font-weight: 600;">☑️ Zaznaczono</span>';
        }
        
        $this->render_wrapper_close();
    }
}
