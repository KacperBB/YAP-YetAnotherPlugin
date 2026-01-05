<?php
/**
 * Base Field Builder Renderer
 * 
 * Klasa bazowa dla wszystkich field renderers w visual builderze
 * Definiuje interfejs i wspólne metody
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class YAP_Field_Builder_Base {
    
    /**
     * Typ pola (text, color, number, itd.)
     */
    protected $field_type = '';
    
    /**
     * Konfiguracja pola
     */
    protected $field_config = [];
    
    /**
     * Aktualna wartość pola
     */
    protected $value = '';
    
    /**
     * Nazwa inputu (name attribute)
     */
    protected $input_name = '';
    
    /**
     * ID inputu (id attribute)
     */
    protected $input_id = '';
    
    /**
     * Konstruktor
     */
    public function __construct($field_config = [], $value = '', $input_name = '', $input_id = '') {
        $this->field_config = $field_config;
        $this->value = $value;
        $this->input_name = $input_name;
        $this->input_id = $input_id;
        $this->field_type = $field_config['type'] ?? 'unknown';
    }
    
    /**
     * Główna metoda renderowania dla builddera
     * MUSI być zaimplementowana w klasie pochodnej
     */
    abstract public function render();
    
    /**
     * Renderuj header pola
     */
    protected function render_field_label() {
        echo '<label for="' . esc_attr($this->input_id) . '" style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px;">';
        echo esc_html($this->field_config['label'] ?? 'Field');
        echo '</label>';
    }
    
    /**
     * Renderuj opis pola jeśli istnieje
     */
    protected function render_field_description() {
        if (isset($this->field_config['description']) && !empty($this->field_config['description'])) {
            echo '<p style="font-size: 12px; color: #666; margin: 4px 0 0; font-style: italic;">';
            echo esc_html($this->field_config['description']);
            echo '</p>';
        }
    }
    
    /**
     * Renderuj wrapper dla pola
     */
    protected function render_wrapper_open($class = '') {
        $classes = 'yap-field-builder-wrapper yap-field-' . esc_attr($this->field_type);
        if ($class) {
            $classes .= ' ' . esc_attr($class);
        }
        echo '<div class="' . $classes . '" style="margin-bottom: 15px; padding: 12px; background: #f9f9f9; border-radius: 4px; border-left: 3px solid #0073aa;">';
    }
    
    /**
     * Zamknij wrapper pola
     */
    protected function render_wrapper_close() {
        echo '</div>';
    }
    
    /**
     * Pobierz wartość atrybutu data-* dla pola
     */
    protected function get_data_attributes() {
        return [
            'data-field-type' => $this->field_type,
            'data-field-name' => $this->field_config['name'] ?? '',
            'data-required' => $this->field_config['required'] ?? false ? 'true' : 'false',
        ];
    }
    
    /**
     * Renderuj data attributes
     */
    protected function render_data_attributes($extra_attrs = []) {
        $attrs = array_merge($this->get_data_attributes(), $extra_attrs);
        foreach ($attrs as $key => $val) {
            echo ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
        }
    }
    
    /**
     * Pomocnicza metoda - jeśli pole jest wymagane
     */
    protected function is_required() {
        return !empty($this->field_config['required']);
    }
    
    /**
     * Pobierz atrybut required HTML
     */
    protected function get_required_attr() {
        return $this->is_required() ? ' required' : '';
    }
}
