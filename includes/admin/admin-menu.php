<?php 
function yap_admin_menu() {
    add_menu_page(
        'Yet Another Plugin',
        'Yet Another Plugin',
        'manage_options',
        'yap-admin-page',
        'yap_admin_page_html'
    );
    add_submenu_page(
        'yap-admin-page',
        'Zarządzaj Grupami',
        'Zarządzaj Grupami',
        'manage_options',
        'yap-manage-groups',
        'yap_manage_groups_page_html'
    );
}
add_action('admin_menu', 'yap_admin_menu');

?>