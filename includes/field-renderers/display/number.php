<?php
/**
 * Number Field - Display Renderer
 * 
 * Wyświetlanie pola number na frontendie
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Number_Display extends YAP_Field_Display_Base {
    
    /**
     * Renderuj pole number na frontendie
     */
    public function render() {
        if ($this->is_empty()) {
            $this->render_empty_state();
            return;
        }
        
        $this->render_wrapper_open();
        echo '<span style="color: #333; font-weight: 600; font-size: 16px;">';
        $this->render_value();
        echo '</span>';
        $this->render_wrapper_close();
    }
    
    /**
     * Zwróć wartość jako liczbę
     */
    public function get_numeric_value() {
        return (float) $this->value;
    }
}
