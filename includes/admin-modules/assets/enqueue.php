<?php
/**
 * Admin Assets Enqueue
 * 
 * Zarządzanie ładowaniem skryptów i stylów w panelu administracyjnym
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Załaduj skrypty i style dla admin
 * Używane na wszystkich stronach admin
 */
function yap_admin_enqueue_scripts($hook) {
    error_log("🔵 yap_admin_enqueue_scripts called for hook: " . $hook);
    error_log("🔵 GET page parameter: " . ($_GET['page'] ?? 'NOT SET'));
    
    // Główne skrypty i style
    wp_enqueue_script('yap-admin-js', plugin_dir_url(dirname(dirname(__FILE__))) . 'js/admin/admin.js', ['jquery', 'jquery-ui-sortable'], '1.1.1', true);
    wp_enqueue_script('yap-conditional-repeater-js', plugin_dir_url(dirname(dirname(__FILE__))) . 'js/admin/conditional-repeater.js', ['jquery', 'jquery-ui-sortable'], '1.0.0', true);
    wp_enqueue_style('yap-admin-css', plugin_dir_url(dirname(dirname(__FILE__))) . 'css/admin/admin-style.css', [], '1.1.1');
    
    // Media uploader dla YAP i post edit pages
    if (strpos($hook, 'yap') !== false || strpos($hook, 'post') !== false) {
        wp_enqueue_media();
    }
    
    // ===========================
    // Field Type Registry System
    // ===========================
    // Core field type management
    $plugin_dir = plugin_dir_url(dirname(dirname(dirname(__FILE__))));
    
    // Load registry first (dependency)
    wp_enqueue_script(
        'yap-field-type-registry',
        $plugin_dir . 'includes/js/field-types/registry.js',
        ['jquery'],
        '1.0.0',
        true
    );
    
    // Load built-in field types
    wp_enqueue_script(
        'yap-field-types',
        $plugin_dir . 'includes/js/field-types/field-types.js',
        ['yap-field-type-registry'],
        '1.0.0',
        true
    );
    
    error_log('✅ Field Type Registry loaded');
    
    // ===========================
    // Field Stabilization System
    // ===========================
    // Auto-generate name/key and provide stability checks
    wp_enqueue_script(
        'yap-field-stabilization',
        $plugin_dir . 'includes/js/field-stabilization.js',
        ['jquery'],
        '1.0.0',
        true
    );
    
    error_log('✅ Field Stabilization System loaded');
    
    // ===========================
    // Field Presets Library
    // ===========================
    // Pre-built field configurations (Address, CTA, SEO, Product, etc.)
    wp_enqueue_script(
        'yap-field-presets',
        $plugin_dir . 'includes/js/presets.js',
        ['jquery', 'yap-field-stabilization'],
        '2.0.0',
        true
    );
    
    error_log('✅ Field Presets Library loaded');
    
    // ===========================
    // Field History & Undo/Redo
    // ===========================
    // Complete change history tracking with CTRL+Z/Y support
    wp_enqueue_script(
        'yap-field-history',
        $plugin_dir . 'includes/js/history.js',
        ['jquery', 'yap-field-stabilization'],
        '2.0.0',
        true
    );
    
    error_log('✅ Field History & Undo/Redo System loaded');
    
    // ===========================
    // Test Custom Type (Development Only)
    // ===========================
    // Load test slug field type for testing custom implementations
    if (defined('WP_DEBUG') && WP_DEBUG) {
        wp_enqueue_script(
            'yap-test-custom-type',
            $plugin_dir . 'includes/js/field-types/test-custom-type.js',
            ['yap-field-types'],
            '1.0.0',
            true
        );
        error_log('✅ Test Custom Field Type (Slug) loaded');
    }
    
    // ===========================
    // Test Scripts (Development Only)
    // ===========================
    // Ładuj testy Visual Buildera tylko w dev/debug mode
    if (defined('WP_DEBUG') && WP_DEBUG) {
        // Load main test index (which loads all test files)
        wp_enqueue_script(
            'yap-tests-index',
            $plugin_dir . 'includes/js/tests/index.js',
            ['yap-field-types'], // Depend on field types registry
            '1.5.0',
            true
        );
        
        // Load custom field type tests
        wp_enqueue_script(
            'yap-test-custom-field-type',
            $plugin_dir . 'includes/js/tests/test-custom-field-type.js',
            ['yap-field-types', 'yap-test-custom-type'], // Depends on registry and custom type
            '1.5.0',
            true
        );
        
        // Load field duplication tests
        wp_enqueue_script(
            'yap-test-field-duplication',
            $plugin_dir . 'includes/js/tests/test-field-duplication.js',
            ['yap-field-stabilization'], // Depends on field stabilization
            '1.5.0',
            true
        );
        
        // Load presets and history tests
        wp_enqueue_script(
            'yap-test-presets-history',
            $plugin_dir . 'includes/js/tests/test-presets-history.js',
            ['yap-field-presets', 'yap-field-history'],
            '2.0.0',
            true
        );
        
        error_log('✅ YAP Tests loaded (Refactored Structure)');
        error_log('✅ Custom Field Type Tests loaded');
        error_log('✅ Field Duplication Tests loaded');
        error_log('✅ Presets & History Tests loaded');
    }
    
    // AJAX data
    wp_localize_script('yap-admin-js', 'yap_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('yap_nonce')
    ]);
    
    error_log("✅ Script enqueued: " . plugin_dir_url(dirname(dirname(__FILE__))) . 'js/admin/admin.js?ver=1.0.4');
}

/**
 * Załaduj dodatkowe skrypty dla głównej strony YAP
 */
function yap_enqueue_admin_scripts($hook) {
    if ('toplevel_page_yap-admin-page' === $hook) {
        $plugin_dir = plugin_dir_url(dirname(dirname(__FILE__)));
        
        wp_enqueue_script('add-nested-field-js', $plugin_dir . 'js/admin/includes/add-nested-field.js', ['jquery', 'yap-admin-js'], '1.0.0', true);
        wp_enqueue_script('change-nested-field-js', $plugin_dir . 'js/admin/includes/change-nested-field.js', ['jquery', 'yap-admin-js'], '1.0.0', true);
        wp_enqueue_script('form-submit-js', $plugin_dir . 'js/admin/includes/form-submit.js', ['jquery', 'yap-admin-js'], '1.0.0', true);
    }
    
    // Developer Overlay - dostępny wszędzie w admin
    if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
        $plugin_dir = plugin_dir_url(dirname(dirname(__FILE__)));
        
        // Enqueue overlay script
        wp_enqueue_script(
            'yap-developer-overlay',
            $plugin_dir . 'js/admin/developer-overlay.js',
            ['jquery'],
            '1.5.0',
            true
        );
        
        // Enable debug mode in JS
        wp_localize_script('yap-developer-overlay', 'yapDebugMode', true);
    }
}

// Rejestracja hooki
error_log("🟦 Registering admin_enqueue_scripts hooks...");
add_action('admin_enqueue_scripts', 'yap_admin_enqueue_scripts');
add_action('admin_enqueue_scripts', 'yap_enqueue_admin_scripts');
error_log("🟦 Hooks registered!");
