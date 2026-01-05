<?php
/**
 * URL Field - Preview Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Url_Preview extends YAP_Field_Preview_Base {
    
    public function render() {
        $this->render_wrapper_open();
        
        if ($this->is_empty()) {
            $this->render_empty_state();
        } else {
            echo '<span style="color: #0073aa; font-size: 13px;">🔗 ' . $this->safe_display($this->value) . '</span>';
        }
        
        $this->render_wrapper_close();
    }
}
