<?php
/**
 * Checkbox Field - Preview Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Checkbox_Preview extends YAP_Field_Preview_Base {
    
    public function render() {
        $this->render_wrapper_open();
        
        if ($this->is_empty()) {
            echo '<span style="color: #999; font-size: 13px;">☐ Unchecked</span>';
        } else {
            echo '<span style="color: #4caf50; font-size: 13px; font-weight: 600;">☑️ Checked</span>';
        }
        
        $this->render_wrapper_close();
    }
}
