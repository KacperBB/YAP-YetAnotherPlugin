<?php
/**
 * Base Field Display Renderer
 * 
 * Klasa bazowa dla wyświetlania pól na frontendie
 * Pokazuje wartość pola w szablonie strony
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class YAP_Field_Display_Base {
    
    protected $field_type = '';
    protected $field_config = [];
    protected $value = '';
    protected $post_id = 0;
    
    public function __construct($field_config = [], $value = '', $post_id = 0) {
        $this->field_config = $field_config;
        $this->value = $value;
        $this->field_type = $field_config['type'] ?? 'unknown';
        $this->post_id = $post_id;
    }
    
    /**
     * Główna metoda renderowania dla frontendu
     */
    abstract public function render();
    
    /**
     * Renderuj wartość - podstawowa sanitacja
     */
    protected function render_value() {
        if (is_array($this->value)) {
            $this->value = reset($this->value);
        }
        echo wp_kses_post($this->value);
    }
    
    /**
     * Czy wartość jest pusta
     */
    protected function is_empty() {
        return empty($this->value);
    }
    
    /**
     * Renderuj wrapper dla pola
     */
    protected function render_wrapper_open($class = '') {
        $classes = 'yap-field-display yap-field-display-' . esc_attr($this->field_type);
        if ($class) {
            $classes .= ' ' . esc_attr($class);
        }
        echo '<div class="' . $classes . '">';
    }
    
    protected function render_wrapper_close() {
        echo '</div>';
    }
    
    /**
     * Renderuj empty state na frontendie
     */
    protected function render_empty_state() {
        // Na frontendie nie pokazujemy "empty state" domyślnie
        // Implementacja może to zmienić
    }
}
