<?php
/**
 * Test Plugin Functionality
 * 
 * Ten plik pomoże zdiagnozować problemy z wtyczką
 */

// Upewnij się, że WordPress jest załadowany
if (!defined('ABSPATH')) {
    die('WordPress not loaded');
}

echo '<h1>YAP Plugin Test</h1>';

// Test 1: Sprawdź czy pliki wtyczki istnieją
echo '<h2>1. Sprawdzanie plików wtyczki:</h2>';
$plugin_dir = plugin_dir_path(__FILE__);
$required_files = [
    'yetanotherplugin.php',
    'db/database.php',
    'includes/admin.php',
    'includes/display.php',
    'includes/admin/admin-ajax-request.php',
    'includes/admin/admin-nested-requests.php',
];

foreach ($required_files as $file) {
    $path = $plugin_dir . $file;
    $exists = file_exists($path);
    echo ($exists ? '✅' : '❌') . ' ' . $file . '<br>';
}

// Test 2: Sprawdź funkcje
echo '<h2>2. Sprawdzanie funkcji:</h2>';
$functions = [
    'yap_admin_page_html',
    'yap_create_dynamic_table',
    'yap_save_group_ajax',
    'yap_add_field_ajax',
    'yap_add_nested_group',
];

foreach ($functions as $func) {
    $exists = function_exists($func);
    echo ($exists ? '✅' : '❌') . ' ' . $func . '<br>';
}

// Test 3: Sprawdź tabele w bazie danych
echo '<h2>3. Sprawdzanie tabel w bazie:</h2>';
global $wpdb;
$tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}group_%'");
if (empty($tables)) {
    echo '⚠️ Brak tabel grup w bazie danych<br>';
} else {
    foreach ($tables as $table) {
        $table_name = array_values((array)$table)[0];
        echo '✅ ' . $table_name . '<br>';
    }
}

// Test 4: Sprawdź uprawnienia
echo '<h2>4. Sprawdzanie uprawnień:</h2>';
echo 'Current user can manage_options: ' . (current_user_can('manage_options') ? '✅ TAK' : '❌ NIE') . '<br>';
echo 'Current user ID: ' . get_current_user_id() . '<br>';

// Test 5: Sprawdź AJAX
echo '<h2>5. AJAX URL:</h2>';
echo 'Admin AJAX URL: ' . admin_url('admin-ajax.php') . '<br>';

// Test 6: Sprawdź akcje WordPress
echo '<h2>6. Zarejestrowane akcje WordPress:</h2>';
global $wp_filter;
$actions_to_check = [
    'admin_menu',
    'admin_enqueue_scripts',
    'wp_ajax_yap_save_group',
    'wp_ajax_yap_add_field',
];

foreach ($actions_to_check as $action) {
    $has_action = isset($wp_filter[$action]);
    echo ($has_action ? '✅' : '❌') . ' ' . $action . '<br>';
}

echo '<h2>✅ Test zakończony</h2>';
echo '<p><a href="' . admin_url('admin.php?page=yap-admin-page') . '">Przejdź do YAP Admin Page</a></p>';
