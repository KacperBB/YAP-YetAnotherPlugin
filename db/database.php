<?php
function yap_create_dynamic_table($group_name) {
    global $wpdb;

    // NOWY FORMAT: wp_yap_* zamiast wp_group_*
    $pattern_table_name = $wpdb->prefix . 'yap_' . sanitize_title($group_name) . '_pattern';
    $data_table_name = $wpdb->prefix . 'yap_' . sanitize_title($group_name) . '_data';

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
            field_options longtext DEFAULT NULL,
            validation_rules longtext DEFAULT NULL,
            conditional_logic longtext DEFAULT NULL,
            is_repeater tinyint(1) DEFAULT 0,
            repeater_min int DEFAULT 0,
            repeater_max int DEFAULT 0,
            layout_type varchar(50) DEFAULT 'table',
            flexible_layouts longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_pattern_field (generated_name)
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

/**
 * Stwórz tabelę dla reguł lokalizacji grup
 */
function yap_create_location_rules_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'yap_location_rules';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        group_name varchar(255) NOT NULL,
        location_type varchar(50) NOT NULL,
        location_operator varchar(20) DEFAULT '==',
        location_value varchar(255) NOT NULL,
        rule_group int DEFAULT 0,
        rule_order int DEFAULT 0,
        PRIMARY KEY  (id),
        KEY group_name (group_name),
        KEY location_type (location_type),
        UNIQUE KEY unique_location (group_name, location_type, location_value, rule_group)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    error_log("✅ Created location rules table: {$table_name}");
}

/**
 * Stwórz tabelę dla globalnych opcji (options page)
 */
function yap_create_options_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'yap_options';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        option_page varchar(255) NOT NULL,
        field_name varchar(255) NOT NULL,
        field_value longtext,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_option (option_page, field_name)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    error_log("✅ Created options table: {$table_name}");
}

/**
 * Stwórz tabelę dla metadanych pól (Visual Builder)
 */
function yap_create_field_metadata_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'yap_field_metadata';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        group_name varchar(255) NOT NULL,
        field_name varchar(255) NOT NULL,
        field_id varchar(255) NOT NULL,
        field_label varchar(255) NOT NULL,
        field_type varchar(50) NOT NULL DEFAULT 'text',
        field_config longtext,
        field_metadata longtext NOT NULL,
        field_order int DEFAULT 0,
        PRIMARY KEY  (id),
        KEY group_name (group_name),
        KEY field_type (field_type),
        UNIQUE KEY unique_field (group_name, field_name)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    error_log("✅ Created field metadata table: {$table_name}");
}

/**
 * Stwórz tabelę dla logów synchronizacji
 */
function yap_create_sync_log_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'yap_sync_log';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        group_name varchar(255) NOT NULL,
        action varchar(50) NOT NULL,
        environment varchar(255) NOT NULL,
        status varchar(50) NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY group_name (group_name),
        KEY timestamp (timestamp)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    error_log("✅ Created sync log table: {$table_name}");
}

/**
 * Stwórz tabelę dla historii zmian danych (data history)
 */
function yap_create_data_history_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'yap_data_history';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        group_name varchar(255) NOT NULL,
        field_name varchar(255) NOT NULL,
        record_id int NOT NULL,
        old_value longtext,
        new_value longtext,
        diff longtext,
        user_id bigint(20) NOT NULL,
        user_name varchar(255) NOT NULL,
        user_ip varchar(45) NOT NULL,
        changed_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY group_field (group_name, field_name),
        KEY record_id (record_id),
        KEY user_id (user_id),
        KEY changed_at (changed_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    error_log("✅ Created data history table: {$table_name}");
}

/**
 * Stwórz tabelę dla pól z własnym SQL (query fields)
 */
function yap_create_query_fields_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'yap_query_fields';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        field_name varchar(255) NOT NULL,
        label varchar(255) NOT NULL,
        group_name varchar(255) NOT NULL,
        query_sql longtext NOT NULL,
        format varchar(50) DEFAULT 'raw',
        cache_ttl int DEFAULT 300,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_field (field_name, group_name),
        KEY group_name (group_name)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    error_log("✅ Created query fields table: {$table_name}");
}

/**
 * Stwórz tabelę dla reguł automatycznych (automation rules)
 */
function yap_create_automations_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'yap_automations';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        group_name varchar(255) NOT NULL,
        trigger_type varchar(50) NOT NULL,
        trigger_field varchar(255),
        conditions longtext,
        actions longtext NOT NULL,
        schedule varchar(50),
        is_active tinyint(1) DEFAULT 1,
        execution_count int DEFAULT 0,
        last_run datetime,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY group_name (group_name),
        KEY trigger_type (trigger_type),
        KEY is_active (is_active)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    error_log("✅ Created automations table: {$table_name}");
}

/**
 * Stwórz tabelę dla logów wykonania automatyzacji
 */
function yap_create_automation_log_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'yap_automation_log';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        automation_id mediumint(9) NOT NULL,
        record_id int NOT NULL,
        action_type varchar(50) NOT NULL,
        status varchar(50) NOT NULL,
        error_message longtext,
        executed_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY automation_id (automation_id),
        KEY record_id (record_id),
        KEY executed_at (executed_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    error_log("✅ Created automation log table: {$table_name}");
}

/**
 * Aktualizuj istniejące tabele pattern o kolumnę flexible_layouts
 */
function yap_update_pattern_tables_for_flexible() {
    global $wpdb;
    
    // Znajdź wszystkie tabele pattern
    $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}yap_%_pattern'", ARRAY_N);
    
    foreach ($tables as $table) {
        $table_name = $table[0];
        
        // Sprawdź czy kolumna już istnieje
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'flexible_layouts'");
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN flexible_layouts longtext DEFAULT NULL");
            error_log("✅ Added flexible_layouts column to {$table_name}");
        }
    }
}

function yap_generate_field_name() {
    // Use uniqid() with more_entropy for guaranteed uniqueness
    return 'field_' . uniqid('', true);
}
?>
