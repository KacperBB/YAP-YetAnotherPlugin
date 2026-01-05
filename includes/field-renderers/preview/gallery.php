<?php
/**
 * Gallery Field - Preview Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Gallery_Preview extends YAP_Field_Preview_Base {
    
    public function render() {
        $this->render_wrapper_open();
        
        if ($this->is_empty()) {
            echo '<span style="color: #999; font-size: 13px;">🖼️ Empty gallery</span>';
        } else {
            echo '<span style="color: #0073aa; font-size: 13px;">🖼️ Gallery with images</span>';
        }
        
        $this->render_wrapper_close();
    }
}
