<?php

add_action('wp_ajax_yap_refresh_groups', 'yap_refresh_groups_ajax');

function yap_refresh_groups_ajax() {
    check_ajax_referer('yap_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Brak uprawnień']);
    }
    
    global $wpdb;
    $group_tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}group_%_pattern'");
    
    // Pobierz parametr show_nested
    $show_nested = isset($_POST['show_nested']) && $_POST['show_nested'] === 'true';
    
    ob_start();
    include plugin_dir_path(dirname(__FILE__)) . 'views/pattern/group-list.php';
    $html = ob_get_clean();
    
    wp_send_json_success([
        'html' => $html,
        'count' => count($group_tables),
        'show_nested' => $show_nested
    ]);
}
