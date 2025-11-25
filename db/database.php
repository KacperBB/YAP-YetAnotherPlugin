<?php
function yap_create_dynamic_table($group_name) {
    global $wpdb;

    $pattern_table_name = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
    $data_table_name = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';

    $charset_collate = $wpdb->get_charset_collate();

    // Sprawdź, czy tabela wzorca już istnieje
    $pattern_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $pattern_table_name));
    if ($pattern_exists === $pattern_table_name) {
        error_log("ℹ️ Pattern table {$pattern_table_name} already exists.");
    } else {
        $pattern_sql = "CREATE TABLE IF NOT EXISTS $pattern_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            generated_name varchar(255) NOT NULL,
            user_name varchar(255) NOT NULL,
            field_type varchar(50) NOT NULL,
            field_value longtext NOT NULL,
            field_depth int NOT NULL,
            associated_fields longtext DEFAULT NULL,
            nested_field_ids longtext DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($pattern_sql);
        error_log("✅ Created pattern table: {$pattern_table_name}");
    }

    // Sprawdź, czy tabela danych już istnieje
    $data_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $data_table_name));
    if ($data_exists === $data_table_name) {
        error_log("ℹ️ Data table {$data_table_name} already exists.");
    } else {
        $data_sql = "CREATE TABLE IF NOT EXISTS $data_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            generated_name varchar(255) NOT NULL,
            user_name varchar(255) NOT NULL,
            field_type varchar(50) NOT NULL,
            field_value longtext NOT NULL,
            associated_id int NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_field (generated_name, associated_id)
        ) $charset_collate;";

        dbDelta($data_sql);
        error_log("✅ Created data table: {$data_table_name}");
    }

    return ['pattern_table' => $pattern_table_name, 'data_table' => $data_table_name];
}
function yap_generate_field_name() {
    return 'field_' . date('YmdHis');
}
?>
