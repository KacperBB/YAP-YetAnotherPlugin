<?php
function yap_save_group_ajax() {
    check_ajax_referer('yap_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        error_log("🚨 Permission error: User does not have 'manage_options'.");
        wp_send_json_error("Insufficient permissions.");
        return;
    }

    global $wpdb;

    $group_name = sanitize_text_field($_POST['group_name'] ?? '');
    $post_type = sanitize_text_field($_POST['post_type'] ?? '');
    $category = sanitize_text_field($_POST['category'] ?? '');

    if (empty($group_name)) {
        error_log("🚨 Error: Group name is empty.");
        wp_send_json_error("Group name is required.");
        return;
    }

    // Tworzenie tabeli wzorca i danych
    $tables = yap_create_dynamic_table($group_name);

    if (!$tables || !isset($tables['pattern_table'])) {
        error_log("🚨 Error: Failed to create tables for group: {$group_name}.");
        wp_send_json_error("Failed to create the group tables.");
        return;
    }

    $pattern_table = $tables['pattern_table'];
    error_log("✅ Created pattern table: {$pattern_table}");

    // Check if group_meta already exists for this group
    $existing_meta = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$pattern_table} WHERE generated_name = 'group_meta' AND user_name = %s",
        $group_name
    ));

    if ($existing_meta) {
        // Update existing group_meta
        $update_result = $wpdb->update(
            $pattern_table,
            [
                'field_value' => json_encode([
                    'post_type' => $post_type,
                    'category' => $category
                ])
            ],
            ['id' => $existing_meta->id]
        );
        
        if ($update_result === false) {
            $db_error = $wpdb->last_error;
            error_log("🚨 Error updating group metadata in pattern table {$pattern_table}. DB Error: {$db_error}");
            wp_send_json_error("Failed to update group metadata in the pattern table.");
            return;
        }
        error_log("✅ Group meta data updated successfully in pattern table: {$pattern_table}");
    } else {
        // Insert new group_meta
        $insert_result = $wpdb->insert(
            $pattern_table,
            [
                'generated_name' => 'group_meta',
                'user_name' => $group_name,
                'field_type' => 'meta',
                'field_value' => json_encode([
                    'post_type' => $post_type,
                    'category' => $category
                ]),
                'field_depth' => 0,
                'nested_field_ids' => null
            ]
        );

        if (!$insert_result) {
            $db_error = $wpdb->last_error;
            error_log("🚨 Error inserting group metadata into pattern table {$pattern_table}. DB Error: {$db_error}");
            wp_send_json_error("Failed to insert group metadata into the pattern table.");
            return;
        }
        error_log("✅ Group meta data inserted successfully into pattern table: {$pattern_table}");
    }

    error_log("✅ Group meta data inserted successfully into pattern table: {$pattern_table}");

    wp_send_json_success([
        'message' => "Group created successfully.",
        'pattern_table' => $pattern_table
    ]);
}

add_action('wp_ajax_yap_save_group', 'yap_save_group_ajax');
add_action('wp_ajax_nopriv_yap_save_group', 'yap_save_group_ajax');
?>
