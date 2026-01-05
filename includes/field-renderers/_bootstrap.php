<?php
/**
 * Field Renderers Bootstrap
 * 
 * Załadowanie systemu renderowania pól
 * Inicjalizuje wszystkie klasy bazowe i loader
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Załaduj klasy bazowe
require_once dirname(__FILE__) . '/builder/_base.php';
require_once dirname(__FILE__) . '/preview/_base.php';
require_once dirname(__FILE__) . '/display/_base.php';

// Załaduj loader
require_once dirname(__FILE__) . '/_loader.php';

// Załaduj field renderers dla wszystkich typów
// Builder
require_once dirname(__FILE__) . '/builder/text.php';
require_once dirname(__FILE__) . '/builder/textarea.php';
require_once dirname(__FILE__) . '/builder/number.php';
require_once dirname(__FILE__) . '/builder/email.php';
require_once dirname(__FILE__) . '/builder/url.php';
require_once dirname(__FILE__) . '/builder/tel.php';
require_once dirname(__FILE__) . '/builder/date.php';
require_once dirname(__FILE__) . '/builder/time.php';
require_once dirname(__FILE__) . '/builder/datetime.php';
require_once dirname(__FILE__) . '/builder/color.php';
require_once dirname(__FILE__) . '/builder/select.php';
require_once dirname(__FILE__) . '/builder/checkbox.php';
require_once dirname(__FILE__) . '/builder/radio.php';
require_once dirname(__FILE__) . '/builder/image.php';
require_once dirname(__FILE__) . '/builder/file.php';
require_once dirname(__FILE__) . '/builder/gallery.php';
require_once dirname(__FILE__) . '/builder/wysiwyg.php';
require_once dirname(__FILE__) . '/builder/repeater.php';
require_once dirname(__FILE__) . '/builder/group.php';
require_once dirname(__FILE__) . '/builder/flexible-content.php';

// Preview
require_once dirname(__FILE__) . '/preview/text.php';
require_once dirname(__FILE__) . '/preview/textarea.php';
require_once dirname(__FILE__) . '/preview/number.php';
require_once dirname(__FILE__) . '/preview/email.php';
require_once dirname(__FILE__) . '/preview/url.php';
require_once dirname(__FILE__) . '/preview/tel.php';
require_once dirname(__FILE__) . '/preview/date.php';
require_once dirname(__FILE__) . '/preview/time.php';
require_once dirname(__FILE__) . '/preview/datetime.php';
require_once dirname(__FILE__) . '/preview/color.php';
require_once dirname(__FILE__) . '/preview/select.php';
require_once dirname(__FILE__) . '/preview/checkbox.php';
require_once dirname(__FILE__) . '/preview/radio.php';
require_once dirname(__FILE__) . '/preview/image.php';
require_once dirname(__FILE__) . '/preview/file.php';
require_once dirname(__FILE__) . '/preview/gallery.php';
require_once dirname(__FILE__) . '/preview/wysiwyg.php';
require_once dirname(__FILE__) . '/preview/repeater.php';
require_once dirname(__FILE__) . '/preview/group.php';
require_once dirname(__FILE__) . '/preview/flexible-content.php';

// Display
require_once dirname(__FILE__) . '/display/text.php';
require_once dirname(__FILE__) . '/display/textarea.php';
require_once dirname(__FILE__) . '/display/number.php';
require_once dirname(__FILE__) . '/display/email.php';
require_once dirname(__FILE__) . '/display/url.php';
require_once dirname(__FILE__) . '/display/tel.php';
require_once dirname(__FILE__) . '/display/date.php';
require_once dirname(__FILE__) . '/display/time.php';
require_once dirname(__FILE__) . '/display/datetime.php';
require_once dirname(__FILE__) . '/display/color.php';
require_once dirname(__FILE__) . '/display/select.php';
require_once dirname(__FILE__) . '/display/checkbox.php';
require_once dirname(__FILE__) . '/display/radio.php';
require_once dirname(__FILE__) . '/display/image.php';
require_once dirname(__FILE__) . '/display/file.php';
require_once dirname(__FILE__) . '/display/gallery.php';
require_once dirname(__FILE__) . '/display/wysiwyg.php';
require_once dirname(__FILE__) . '/display/repeater.php';
require_once dirname(__FILE__) . '/display/group.php';
require_once dirname(__FILE__) . '/display/flexible-content.php';

/**
 * Główny interface do renderowania pól
 */
function yap_render_field_builder($field_type, $field_config, $value = '', $input_name = '', $input_id = '') {
    YAP_Field_Renderers_Loader::render('builder', $field_type, $field_config, $value);
}

function yap_render_field_preview($field_type, $field_config, $value = '') {
    YAP_Field_Renderers_Loader::render('preview', $field_type, $field_config, $value);
}

function yap_render_field_display($field_type, $field_config, $value = '', $post_id = 0) {
    YAP_Field_Renderers_Loader::render('display', $field_type, $field_config, $value, $post_id);
}

// Debug function
function yap_debug_field_renderers() {
    return YAP_Field_Renderers_Loader::debug_available();
}
