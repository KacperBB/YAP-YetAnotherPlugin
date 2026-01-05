<?php
/**
 * Admin Modules Bootstrap
 * 
 * Ładowanie wszystkich modułów admin systemu
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ===========================
// Assets Management
// ===========================
require_once dirname(__FILE__) . '/assets/enqueue.php';

// ===========================
// Menu Registration
// ===========================
require_once dirname(__FILE__) . '/menu/menu-register.php';

// ===========================
// Field Savers (zapisywanie danych)
// ===========================
require_once dirname(__FILE__) . '/field-savers/field-generator.php';
require_once dirname(__FILE__) . '/field-savers/post-fields-saver.php';
require_once dirname(__FILE__) . '/field-savers/json-schema-saver.php';
require_once dirname(__FILE__) . '/field-savers/table-fields-saver.php';

// ===========================
// Field Renderers (wyświetlanie pól)
// ===========================
require_once dirname(__FILE__) . '/field-renderers/field-input.php';
require_once dirname(__FILE__) . '/field-renderers/simple-field.php';
require_once dirname(__FILE__) . '/field-renderers/repeater.php';
require_once dirname(__FILE__) . '/field-renderers/group.php';

// ===========================
// Meta Box Display
// ===========================
require_once dirname(__FILE__) . '/meta-boxes/register.php';
require_once dirname(__FILE__) . '/meta-boxes/json-schema-display.php';
require_once dirname(__FILE__) . '/meta-boxes/table-display.php';

error_log("✅ Admin Modules Bootstrap: Wszystkie moduły załadowane");
