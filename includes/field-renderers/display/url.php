<?php
/**
 * URL Field - Display Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Url_Display extends YAP_Field_Display_Base {
    
    public function render() {
        if ($this->is_empty()) {
            $this->render_empty_state();
            return;
        }
        
        $this->render_wrapper_open();
        echo '<a href="' . esc_url($this->value) . '" target="_blank" rel="noopener noreferrer">' . esc_html($this->value) . '</a>';
        $this->render_wrapper_close();
    }
}
