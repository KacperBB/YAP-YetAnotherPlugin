<?php
/**
 * Flexible Content - Preview Renderer
 * 
 * Preview flexible content pola w edytorze
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Flexible_Content_Preview {
    
    /**
     * Renderuj preview flexible content
     */
    public static function render($field, $value) {
        if (!is_array($value)) {
            $value = !empty($value) ? json_decode($value, true) : [];
        }
        if (!is_array($value)) {
            $value = [];
        }
        
        // Wrapper
        echo '<div class="yap-flexible-content-preview" style="padding: 8px; background: white; border-radius: 3px; border: 1px solid #ddd;">';
        
        if (empty($value)) {
            echo '<span style="color: #999; font-size: 12px; font-style: italic;">[Brak sekcji]</span>';
        } else {
            echo '<div style="font-size: 12px; color: #666;">';
            echo '<strong>Sekcje:</strong> ' . count($value);
            echo '</div>';
            
            // Preview pierwszych 2 sekcji
            foreach (array_slice($value, 0, 2) as $idx => $section) {
                $layout = $section['layout'] ?? 'Unknown';
                echo '<div style="font-size: 11px; color: #999; margin-top: 4px;">';
                echo '└─ ' . esc_html($layout) . ' #' . ($idx + 1);
                echo '</div>';
            }
            
            if (count($value) > 2) {
                echo '<div style="font-size: 11px; color: #999; margin-top: 4px;">';
                echo '└─ +' . (count($value) - 2) . ' więcej';
                echo '</div>';
            }
        }
        
        echo '</div>';
    }
}
