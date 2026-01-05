<?php
/**
 * JSON Schema Fields Saver
 * 
 * Zapisywanie wartości pól z Visual Buildera do post meta
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Zapisz wartości pól z JSON schema
 */
function yap_save_json_schema_fields($post_id) {
    // Sprawdź autosave/revision
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if (wp_is_post_autosave($post_id)) return;
    
    if (!isset($_POST['yap_fields']) || !is_array($_POST['yap_fields'])) {
        return;
    }
    
    foreach ($_POST['yap_fields'] as $group_name => $fields) {
        update_post_meta($post_id, 'yap_' . $group_name, $fields);
    }
}

add_action('save_post', 'yap_save_json_schema_fields', 10);
