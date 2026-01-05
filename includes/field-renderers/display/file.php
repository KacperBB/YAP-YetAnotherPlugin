<?php
/**
 * File Field - Display Renderer
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_File_Display extends YAP_Field_Display_Base {
    
    public function render() {
        if ($this->is_empty()) {
            $this->render_empty_state();
            return;
        }
        
        $this->render_wrapper_open();
        
        $filename = basename($this->value);
        echo '<a href="' . esc_url($this->value) . '" download style="background: #0073aa; color: white; padding: 10px 15px; border-radius: 4px; text-decoration: none; display: inline-block;">';
        echo '📥 Pobierz: ' . esc_html($filename);
        echo '</a>';
        
        $this->render_wrapper_close();
    }
}
