<?php
/**
 * Flexible Content Bootstrap
 * 
 * Załadowanie modułu flexible content
 * Inicjalizuje renderers dla różnych kontekstów
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Załaduj renderers
require_once dirname(__FILE__) . '/builder/main.php';
require_once dirname(__FILE__) . '/preview/main.php';
require_once dirname(__FILE__) . '/display/main.php';

/**
 * Interface do renderowania flexible content
 */
function yap_render_flexible_builder($field, $value, $input_name, $input_id) {
    YAP_Flexible_Content_Builder::render($field, $value, $input_name, $input_id);
}

function yap_render_flexible_preview($field, $value) {
    YAP_Flexible_Content_Preview::render($field, $value);
}

function yap_render_flexible_display($field, $value, $post_id = 0) {
    YAP_Flexible_Content_Display::render($field, $value, $post_id);
}
