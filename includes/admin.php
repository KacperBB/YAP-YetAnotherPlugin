<?php
/**
 * Admin Module Loader
 * 
 * Główny punkt wejścia dla systemu administracyjnego.
 * Ładuje zarówno starsze moduły admin jak i nowy system modularny.
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 * 
 * STRUKTURA MODUŁÓW:
 * 
 * admin-modules/
 * ├── assets/
 * │   └── enqueue.php              # Ładowanie skryptów i stylów
 * ├── menu/
 * │   └── menu-register.php        # Rejestracja menu
 * ├── field-savers/
 * │   ├── field-generator.php      # Generowanie pól dla nowych postów
 * │   ├── post-fields-saver.php    # Zapisywanie pól do tabeli
 * │   ├── json-schema-saver.php    # Zapisywanie pól JSON do post meta
 * │   └── table-fields-saver.php   # Zapisywanie pól tabeli (legacy)
 * ├── field-renderers/
 * │   ├── field-input.php          # Uniwersalny renderer dla wszystkich typów
 * │   ├── simple-field.php         # Proste pola dla repeater/group
 * │   ├── repeater.php             # Renderer dla pola repeater
 * │   └── group.php                # Renderer dla pola group
 * ├── meta-boxes/
 * │   ├── register.php             # Rejestracja meta boxów
 * │   ├── json-schema-display.php  # Wyświetlanie pół JSON
 * │   └── table-display.php        # Wyświetlanie pół z tabel
 * └── _bootstrap.php               # Ładowanie wszystkich modułów
 * 
 * FLOW:
 * 
 * 1. admin.php (ten plik) - punkt wejścia
 * 2. admin-modules/_bootstrap.php - ładuje wszystkie moduły
 * 3. Moduły rejestrują hooki WP
 * 4. Na POST edit page:
 *    - enqueue.php ładuje assets
 *    - register.php tworzy meta boxy
 *    - field-renderers wyświetlają pola
 *    - field-savers zapisują dane
 */

if (!defined('ABSPATH')) {
    exit;
}

error_log("🟦 ADMIN.PHP LOADED - Main entry point");

// ===========================
// LEGACY ADMIN MODULES
// ===========================
// Te moduły są starsze i zawierają funkcjonalność:
// - Grupowanie pół
// - Zarządzanie grupami z poziomu admin
// - Import/Export
// - AJAX operacje dla grup

require_once plugin_dir_path(__FILE__) . 'admin/admin-save-group.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-delete-group.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-edit-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-group-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/ajax_requests/ajax-refresh-groups.php';
require_once plugin_dir_path(__FILE__) . 'admin/ajax_requests/ajax-delete-group.php';

// ===========================
// NEW MODULAR ADMIN SYSTEM
// ===========================
// Nowy, bardziej modularny system zarządzania polami:
// - Assets (enqueue skryptów/stylów)
// - Menu (rejestracja menu)
// - Field Savers (zapisywanie wartości)
// - Field Renderers (wyświetlanie formularzy)
// - Meta Boxes (integracja z post editor)

require_once plugin_dir_path(__FILE__) . 'admin-modules/_bootstrap.php';

// ===========================
// HELPER FUNCTIONS
// ===========================

/**
 * Helper: Pobierz istniejące pola dla posta
 * Używane w legacy systemach
 * 
 * @param string $table_name Nazwa tabeli wzorca
 * @param string $post_type  Typ posta
 * @param int    $category   ID kategorii
 */
function yap_create_fields_for_existing_posts($table_name, $post_type, $category) {
    global $wpdb;

    // Pobierz wszystkie posty odpowiadające wybranym kryteriom
    $query = "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'";
    $params = [$post_type];

    if (!empty($category)) {
        $query .= " AND ID IN (SELECT object_id FROM {$wpdb->term_relationships} AS tr
                   INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                   WHERE tt.term_id = %d)";
        $params[] = $category;
    }

    $posts = $wpdb->get_results($wpdb->prepare($query, $params));
    $fields = $wpdb->get_results("SELECT * FROM {$table_name}");

    // Tworz pola dla każdego posta
    foreach ($posts as $post) {
        $post_id = $post->ID;
        
        foreach ($fields as $field) {
            $existing_field = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_name}_data WHERE generated_name = %s AND associated_id = %d",
                $field->generated_name,
                $post_id
            ));

            if (!$existing_field) {
                $wpdb->insert(
                    "{$table_name}_data",
                    [
                        'generated_name' => $field->generated_name,
                        'user_name' => $field->user_name,
                        'field_type' => $field->field_type,
                        'field_value' => '',
                        'associated_id' => $post_id
                    ]
                );
            }
        }
    }
}

/**
 * Debug: Pokaż wszystkie location rules
 * Dostępne na: ?yap_debug_rules=1
 */
function yap_debug_location_rules() {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['yap_debug_rules'])) return;
    
    global $wpdb;
    $rules = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yap_location_rules ORDER BY group_name, rule_group, rule_order");
    
    echo '<div class="notice notice-info" style="padding: 20px;"><h3>🔍 Location Rules Debug</h3><pre>';
    foreach ($rules as $rule) {
        echo sprintf(
            "Grupa: %s | Typ: %s | Operator: %s | Wartość: %s | Group: %d | Order: %d\n",
            $rule->group_name,
            $rule->location_type,
            $rule->location_operator,
            $rule->location_value,
            $rule->rule_group,
            $rule->rule_order
        );
    }
    echo '</pre></div>';
}
add_action('admin_notices', 'yap_debug_location_rules');
