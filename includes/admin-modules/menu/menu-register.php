<?php
/**
 * Admin Menu Registration
 * 
 * Rejestracja menu i submenu w panelu admin
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dodaj submenu do admin menu
 */
function yap_admin_menu_register() {
    add_submenu_page(
        null,
        'Edytuj Grupę',
        'Edytuj Grupę',
        'manage_options',
        'yap-edit-group',
        'yap_edit_group_page_html'
    );
    
    add_submenu_page(
        null,
        'Usuń Grupę',
        'Usuń Grupę',
        'manage_options',
        'yap-delete-group',
        'yap_delete_group_page_html'
    );
}

add_action('admin_menu', 'yap_admin_menu_register');
