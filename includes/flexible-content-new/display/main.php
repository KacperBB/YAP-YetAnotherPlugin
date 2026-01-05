<?php
/**
 * Flexible Content - Display Renderer
 * 
 * Wyświetlanie flexible content pola na frontendie
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Flexible_Content_Display {
    
    /**
     * Renderuj flexible content na frontendie
     */
    public static function render($field, $value, $post_id = 0) {
        if (!is_array($value)) {
            $value = !empty($value) ? json_decode($value, true) : [];
        }
        if (!is_array($value)) {
            $value = [];
        }
        
        if (empty($value)) {
            return;
        }
        
        // Wrapper
        echo '<div class="yap-flexible-content-display">';
        
        foreach ($value as $idx => $section) {
            $layout = $section['layout'] ?? '';
            $fields = $section['fields'] ?? [];
            
            echo '<div class="yap-flexible-section yap-flexible-section-' . esc_attr($layout) . '">';
            
            // Renderuj pola sekcji
            if (!empty($fields)) {
                foreach ($fields as $field_name => $field_value) {
                    echo '<div class="yap-flexible-field yap-flexible-field-' . esc_attr($field_name) . '">';
                    
                    // Tu można dodać logikę do renderowania na podstawie typu pola
                    // Na razie proste wyświetlanie
                    echo wp_kses_post($field_value);
                    
                    echo '</div>';
                }
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }
}
