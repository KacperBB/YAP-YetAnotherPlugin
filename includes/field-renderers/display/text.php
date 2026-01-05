<?php
/**
 * Text Field - Display Renderer
 * 
 * Wyświetlanie pola text na frontendie
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Text_Display extends YAP_Field_Display_Base {
    
    /**
     * Renderuj pole text na frontendie
     */
    public function render() {
        if ($this->is_empty()) {
            $this->render_empty_state();
            return;
        }
        
        $this->render_wrapper_open();
        $this->render_value();
        $this->render_wrapper_close();
    }
}
