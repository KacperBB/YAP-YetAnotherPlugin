<?php
error_log("🟦 ADMIN.PHP LOADED!");

require_once plugin_dir_path(__FILE__) . 'admin/admin-save-group.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-delete-group.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-edit-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-group-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';

function yap_admin_enqueue_scripts($hook) {
    // ZAWSZE ładuj skrypty na stronach admin - prostsze rozwiązanie
    error_log("🔵 yap_admin_enqueue_scripts called for hook: " . $hook);
    error_log("🔵 GET page parameter: " . ($_GET['page'] ?? 'NOT SET'));
    
    wp_enqueue_script('yap-admin-js', plugin_dir_url(__FILE__) . 'js/admin/admin.js', ['jquery'], '1.0.4', true);
    wp_enqueue_style('yap-admin-css', plugin_dir_url(__FILE__) . 'css/admin/admin-style.css', [], '1.0.4');
    
    // Enqueue WordPress media uploader on YAP pages AND post edit pages
    if (strpos($hook, 'yap') !== false || strpos($hook, 'post') !== false) {
        wp_enqueue_media();
    }
    
    wp_localize_script('yap-admin-js', 'yap_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('yap_nonce')
    ]);
    
    error_log("✅ Script enqueued: " . plugin_dir_url(__FILE__) . 'js/admin/admin.js?ver=1.0.4');
}
error_log("🟦 Registering admin_enqueue_scripts hook...");
add_action('admin_enqueue_scripts', 'yap_admin_enqueue_scripts');
error_log("🟦 Hook registered!");

function yap_enqueue_admin_scripts($hook) {
    if ('toplevel_page_yap-admin-page' === $hook) {
        wp_enqueue_script('add-nested-field-js', plugin_dir_url(__FILE__) . 'js/admin/includes/add-nested-field.js', ['jquery', 'yap-admin-js'], '1.0.0', true);
        wp_enqueue_script('change-nested-field-js', plugin_dir_url(__FILE__) . 'js/admin/includes/change-nested-field.js', ['jquery', 'yap-admin-js'], '1.0.0', true);
        wp_enqueue_script('form-submit-js', plugin_dir_url(__FILE__) . 'js/admin/includes/form-submit.js', ['jquery', 'yap-admin-js'], '1.0.0', true);
    }
}
add_action('admin_enqueue_scripts', 'yap_enqueue_admin_scripts');

add_action('admin_menu', function() {
    add_submenu_page(null, 'Edytuj Grupę', 'Edytuj Grupę', 'manage_options', 'yap-edit-group', 'yap_edit_group_page_html');
    add_submenu_page(null, 'Usuń Grupę', 'Usuń Grupę', 'manage_options', 'yap-delete-group', 'yap_delete_group_page_html');
});

function yap_save_post_fields($post_id) {
    global $wpdb;

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
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


function yap_generate_fields_for_post($post_id) {
    global $wpdb;

    $all_pattern_tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_group_%_pattern'");
    foreach ($all_pattern_tables as $table) {
        $pattern_table = current((array)$table);
        $data_table = str_replace('_pattern', '_data', $pattern_table);

        $fields = $wpdb->get_results("SELECT * FROM {$pattern_table}");

        foreach ($fields as $field) {
            $existing_field = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$data_table} WHERE generated_name = %s AND associated_id = %d",
                $field->generated_name,
                $post_id
            ));

            if ($existing_field == 0) {
                $wpdb->insert(
                    $data_table,
                    [
                        'generated_name' => $field->generated_name,
                        'user_name' => $field->user_name,
                        'field_type' => $field->field_type,
                        'field_value' => '', // Default value
                        'associated_id' => $post_id
                    ]
                );
            }
        }
    }
}
add_action('save_post', 'yap_generate_fields_for_post');

function yap_add_meta_boxes() {
    global $wpdb;
    global $post;

    if (!$post || !isset($post->ID)) {
        return;
    }

    $post_id = $post->ID;
    $post_type = get_post_type($post_id);
    $categories = wp_get_post_terms($post_id, 'category', ['fields' => 'ids']);

    $all_data_tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_group_%_data'");

    foreach ($all_data_tables as $table) {
        $data_table = current((array)$table);
        $pattern_table = str_replace('_data', '_pattern', $data_table);

        // Pobierz informacje o grupie
        $group_meta = $wpdb->get_row("SELECT * FROM {$pattern_table} WHERE generated_name = 'group_meta'");

        if (!$group_meta) {
            continue;
        }

        $group_meta_data = json_decode($group_meta->field_value, true);
        $group_post_type = $group_meta_data['post_type'] ?? '';
        $group_category = $group_meta_data['category'] ?? '';

        // 🔥 **FILTRACJA PRZED DODANIEM METABOXA**
        // Jeśli ustawiono konkretny post type i nie pasuje - pomijamy
        if (!empty($group_post_type) && $group_post_type !== $post_type) {
            error_log("🚨 Metabox NIE został dodany: Post Type mismatch (expected: {$group_post_type}, got: {$post_type}).");
            continue;
        }

        // Jeśli ustawiono konkretną kategorię i post jej nie ma - pomijamy
        if (!empty($group_category) && !in_array((int)$group_category, $categories)) {
            error_log("🚨 Metabox NIE został dodany: Category mismatch (expected: {$group_category}, got: " . implode(', ', $categories) . ").");
            continue;
        }
        
        error_log("✅ Metabox zostanie dodany dla grupy: {$pattern_table} (post_type: {$post_type}, categories: " . implode(',', $categories) . ")");

        // ✅ **Dodajemy metabox tylko jeśli warunki są spełnione!**
        // Użyj aktualnego post_type jeśli grupa ma puste ustawienie
        $metabox_post_type = !empty($group_post_type) ? $group_post_type : $post_type;
        
        add_meta_box(
            'yap_custom_fields_' . esc_attr($data_table),
            esc_html(str_replace(['wp_group_', '_data'], '', $data_table)), 
            function ($post) use ($data_table, $group_post_type, $group_category) {
                yap_display_post_fields($post, $data_table, $group_post_type, $group_category);
            },
            $metabox_post_type
        );
    }
}
add_action('add_meta_boxes', 'yap_add_meta_boxes');



function yap_display_post_fields($post, $data_table, $expected_post_type, $expected_category) {
    global $wpdb;

    if (!is_object($post)) {
        $post = get_post();
    }

    if (!$post || !isset($post->ID)) {
        error_log("🚨 Invalid post object.");
        return;
    }

    $post_id = $post->ID;
    $post_type = get_post_type($post_id);
    $categories = wp_get_post_terms($post_id, 'category', ['fields' => 'ids']);

    error_log("📄 Processing Post ID: {$post_id}, Post Type: {$post_type}, Categories: " . implode(', ', $categories));

    // Pobierz meta informacje o grupie
    $pattern_table = str_replace('_data', '_pattern', $data_table);
    $group_meta = $wpdb->get_row("SELECT * FROM {$pattern_table} WHERE generated_name = 'group_meta'");

    if (!$group_meta) {
        error_log("🚨 No meta field found in pattern table: {$pattern_table}");
        return;
    }

    // Dekoduj informacje o grupie
    $group_meta_data = json_decode($group_meta->field_value, true);
    $group_post_type = $group_meta_data['post_type'] ?? null;
    $group_category = $group_meta_data['category'] ?? null;

    error_log("📊 Group Meta Data: Post Type: {$group_post_type}, Category: {$group_category}");

    // 🔍 **Filtracja pól**: Sprawdzamy zgodność `post_type` oraz `category`
    if ($group_post_type !== $post_type) {
        error_log("🚨 Skipping fields: Post Type mismatch (expected: {$group_post_type}, got: {$post_type}).");
        return;
    }

    if (!empty($group_category) && !in_array((int)$group_category, $categories)) {
        error_log("🚨 Skipping fields: Category mismatch (expected: {$group_category}, got: " . implode(', ', $categories) . ").");
        return;
    }

    // Pobierz pola powiązane z tym postem
    $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$data_table} WHERE associated_id = %d", $post_id));

    if (!empty($fields)) {
        echo '<div class="yap-custom-fields">';
        foreach ($fields as $field) {
            // Pomiń meta pole
            if ($field->generated_name === 'group_meta') {
                continue;
            }
            
            echo '<div class="yap-metabox-field">';
            echo '<label for="yap_' . esc_attr($field->generated_name) . '">';
            echo '<strong>' . esc_html($field->user_name) . '</strong>';
            echo '</label>';
            
            // Różne typy pól
            switch ($field->field_type) {
                case 'long_text':
                    echo '<textarea id="yap_' . esc_attr($field->generated_name) . '" name="yap_fields[' . esc_attr($data_table) . '][' . esc_attr($field->generated_name) . ']" rows="5" class="widefat">' . esc_textarea($field->field_value) . '</textarea>';
                    break;
                case 'number':
                    echo '<input type="number" id="yap_' . esc_attr($field->generated_name) . '" name="yap_fields[' . esc_attr($data_table) . '][' . esc_attr($field->generated_name) . ']" value="' . esc_attr($field->field_value) . '" class="widefat">';
                    break;
                case 'image':
                    $image_id = $field->field_value;
                    $image_url = '';
                    if (is_numeric($image_id)) {
                        $image_url = wp_get_attachment_url($image_id);
                    }
                    echo '<div class="yap-image-field-wrapper">';
                    echo '<div style="flex: 1;">';
                    echo '<input type="hidden" id="yap_' . esc_attr($field->generated_name) . '" name="yap_fields[' . esc_attr($data_table) . '][' . esc_attr($field->generated_name) . ']" value="' . esc_attr($field->field_value) . '" class="yap-image-id">';
                    echo '<button type="button" class="button yap-upload-image-button" data-field="yap_' . esc_attr($field->generated_name) . '">Wybierz obraz z biblioteki</button>';
                    if ($image_url) {
                        echo '<img src="' . esc_url($image_url) . '" class="yap-image-preview" style="margin-top: 10px; max-width: 150px; display: block;">';
                    } else {
                        echo '<img src="" class="yap-image-preview" style="margin-top: 10px; max-width: 150px; display: none;">';
                    }
                    echo '<button type="button" class="button yap-remove-image-button" style="margin-top: 5px; display: ' . ($image_url ? 'inline-block' : 'none') . ';">Usuń obraz</button>';
                    echo '</div>';
                    echo '</div>';
                    break;
                case 'nested_group':
                    echo '<p class="description">To jest zagnieżdżona grupa - zarządzaj nią w ustawieniach wtyczki</p>';
                    break;
                default: // short_text
                    echo '<input type="text" id="yap_' . esc_attr($field->generated_name) . '" name="yap_fields[' . esc_attr($data_table) . '][' . esc_attr($field->generated_name) . ']" value="' . esc_attr($field->field_value) . '" class="widefat">';
                    break;
            }
            
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>Brak pól do wyświetlenia. Pola zostaną utworzone przy pierwszym zapisie posta.</p>';
        error_log("ℹ️ No fields found for Post ID: {$post_id} in table {$data_table} - they will be created on save.");
    }
}


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

    // Pobierz pola wzorca
    $fields = $wpdb->get_results("SELECT * FROM {$table_name}");

    // Zmieniona walidacja w yap_create_fields_for_existing_posts
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
                        'field_value' => '', // Domyślna wartość
                        'associated_id' => $post_id
                    ]
                );
            }
        }
    }
}

?>
