<?php
/**
 * Base Field Preview Renderer
 * 
 * Klasa bazowa dla preview pól w edytorze
 * Pokazuje podgląd wartości pola w edytorze
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class YAP_Field_Preview_Base {
    
    protected $field_type = '';
    protected $field_config = [];
    protected $value = '';
    
    public function __construct($field_config = [], $value = '') {
        $this->field_config = $field_config;
        $this->value = $value;
        $this->field_type = $field_config['type'] ?? 'unknown';
    }
    
    /**
     * Główna metoda renderowania preview
     */
    abstract public function render();
    
    /**
     * Renderuj wrapper preview
     */
    protected function render_wrapper_open($class = '') {
        $classes = 'yap-field-preview yap-field-preview-' . esc_attr($this->field_type);
        if ($class) {
            $classes .= ' ' . esc_attr($class);
        }
        echo '<div class="' . $classes . '" style="padding: 8px; background: white; border-radius: 3px; border: 1px solid #ddd;">';
    }
    
    protected function render_wrapper_close() {
        echo '</div>';
    }
    
    /**
     * Renderuj empty state
     */
    protected function render_empty_state() {
        echo '<span style="color: #999; font-size: 12px; font-style: italic;">[Brak wartości]</span>';
    }
    
    /**
     * Czy wartość jest pusta
     */
    protected function is_empty() {
        return empty($this->value);
    }
    
    /**
     * Sanituj wartość do wyświetlenia
     */
    protected function safe_display($value) {
        return esc_html($value);
    }
}
