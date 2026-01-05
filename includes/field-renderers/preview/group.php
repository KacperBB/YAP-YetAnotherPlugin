<?php
/**
 * Group Field - Preview Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Group_Preview extends YAP_Field_Preview_Base {
    
    public function render() {
        $this->render_wrapper_open();
        echo '<span style="color: #0073aa; font-size: 13px;">👥 Group field</span>';
        $this->render_wrapper_close();
    }
}
