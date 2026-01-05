<?php
/**
 * Group Field - Display Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Group_Display extends YAP_Field_Display_Base {
    
    public function render() {
        if ($this->is_empty()) {
            $this->render_empty_state();
            return;
        }
        
        $this->render_wrapper_open();
        echo '<div style="background: #f9f9f9; padding: 15px; border-radius: 4px; border-left: 4px solid #0073aa;">';
        echo '<p style="color: #666; margin: 0; font-style: italic;">Group content</p>';
        echo '</div>';
        $this->render_wrapper_close();
    }
}
