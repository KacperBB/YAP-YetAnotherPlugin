<?php
/**
 * Color Field - Display Renderer
 * 
 * Wyświetlanie pola color na frontendie
 * Może być używane jako tło, tekst, itp.
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Color_Display extends YAP_Field_Display_Base {
    
    /**
     * Renderuj pole color na frontendie
     */
    public function render() {
        if ($this->is_empty()) {
            $this->render_empty_state();
            return;
        }
        
        // Domyślnie: pokaż kwadrat koloru z wartością
        $this->render_wrapper_open();
        
        echo '<div style="display: flex; align-items: center; gap: 10px;">';
        echo '<div style="width: 40px; height: 40px; background-color: ' . esc_attr($this->value) . '; border-radius: 4px; border: 2px solid #e0e0e0;"></div>';
        echo '<span style="color: #666; font-family: monospace; font-size: 13px;">' . esc_html($this->value) . '</span>';
        echo '</div>';
        
        $this->render_wrapper_close();
    }
    
    /**
     * Alternatywa: zwróć tylko hex wartość (do użycia w CSS)
     */
    public function get_hex_value() {
        return sanitize_hex_color($this->value);
    }
}
