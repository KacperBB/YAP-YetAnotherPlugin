<?php
/**
 * Post Fields Saver
 * 
 * Zapisywanie wartości pól z tabeli danych
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Zapisz wartości pól do tabeli danych
 */
function yap_save_post_fields($post_id) {
    global $wpdb;

    // Zabezpieczenia przed duplikatami
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    if (wp_is_post_autosave($post_id)) {
        return;
    }

    if (!isset($_POST['yap_fields']) || !is_array($_POST['yap_fields'])) {
        return;
    }

    foreach ($_POST['yap_fields'] as $data_table => $fields) {
        foreach ($fields as $generated_name => $field_value) {
            $wpdb->update(
                $data_table,
                ['field_value' => sanitize_text_field($field_value)],
                ['generated_name' => $generated_name, 'associated_id' => $post_id]
            );
        }
    }
}

add_action('save_post', 'yap_save_post_fields');
