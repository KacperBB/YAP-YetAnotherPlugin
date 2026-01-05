<?php
/**
 * Meta Box Registration
 * 
 * Rejestracja meta boxów dla pól na stronach editowania postów
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dodaj meta boxów dla wszystkich pasujących grup
 */
function yap_add_meta_boxes() {
    global $wpdb;
    global $post;

    if (!$post || !isset($post->ID)) {
        return;
    }

    $post_id = $post->ID;
    $post_type = get_post_type($post_id);
    $categories = wp_get_post_terms($post_id, 'category', ['fields' => 'ids']);
    
    // Kontekst dla location rules
    $context = [
        'post_id' => $post_id,
        'post_type' => $post_type,
        'post' => $post,
        'taxonomy_term' => $categories
    ];

    // Pobierz wszystkie grupy z różnych źródeł
    $groups = [];
    
    // 1. Z location_rules (nowy system - Visual Builder)
    $location_groups = $wpdb->get_col(
        "SELECT DISTINCT group_name FROM {$wpdb->prefix}yap_location_rules WHERE group_name != '' ORDER BY group_name ASC"
    );
    
    foreach ($location_groups as $group_name) {
        // Sprawdź czy grupa powinna być wyświetlona w tym kontekście
        if (YAP_Location_Rules::should_show_group($group_name, $context)) {
            $groups[] = [
                'name' => $group_name,
                'type' => 'json_schema' // Visual Builder
            ];
        }
    }
    
    // 2. Ze starych tabel wp_group_* (stary system)
    $old_tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_group_%_data'");
    foreach ($old_tables as $table) {
        $data_table = current((array)$table);
        $pattern_table = str_replace('_data', '_pattern', $data_table);
        $safe_pattern = esc_sql($pattern_table);

        // Pobierz informacje o grupie
        $group_meta = $wpdb->get_row("SELECT * FROM `{$safe_pattern}` WHERE generated_name = 'group_meta'");

        if (!$group_meta) {
            continue;
        }

        $group_meta_data = json_decode($group_meta->field_value, true);
        $group_post_type = $group_meta_data['post_type'] ?? '';
        $group_category = $group_meta_data['category'] ?? '';

        // Filtracja
        if (!empty($group_post_type) && $group_post_type !== $post_type) {
            continue;
        }

        if (!empty($group_category) && !in_array((int)$group_category, $categories)) {
            continue;
        }
        
        $group_name = preg_replace('/^wp_group_(.*?)_pattern$/', '$1', $pattern_table);
        $groups[] = [
            'name' => $group_name,
            'type' => 'old_table',
            'data_table' => $data_table,
            'pattern_table' => $pattern_table
        ];
    }
    
    // 3. Z nowych tabel wp_yap_* (jeśli istnieją)
    $yap_tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_yap_%_data'");
    foreach ($yap_tables as $table) {
        $data_table = current((array)$table);
        $pattern_table = str_replace('_data', '_pattern', $data_table);
        
        // Pomiń systemowe tabele
        $system_tables = ['location', 'options', 'field', 'sync', 'data', 'query', 'automations', 'automation'];
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $pattern_table));
        
        if ($table_exists !== $pattern_table) {
            continue;
        }
        
        $group_name = preg_replace('/^wp_yap_(.*?)_pattern$/', '$1', $pattern_table);
        if (in_array($group_name, $system_tables)) {
            continue;
        }
        
        // Sprawdź czy ma location rules
        if (YAP_Location_Rules::should_show_group($group_name, $context)) {
            $groups[] = [
                'name' => $group_name,
                'type' => 'yap_table',
                'data_table' => $data_table,
                'pattern_table' => $pattern_table
            ];
        }
    }
    
    // Loguj debug info
    error_log("🔍 YAP Metaboxes dla post_id: {$post_id}, post_type: {$post_type}, grupy znalezione: " . count($groups));
    
    if (empty($groups)) {
        error_log("⚠️ Brak grup pasujących do tego posta. Location rules: " . count($location_groups) . ", kontekst: " . json_encode($context));
    }
    
    // Dodaj metaboxy dla wszystkich dopasowanych grup
    foreach ($groups as $group) {
        $group_name = $group['name'];
        $group_type = $group['type'];
        
        error_log("✅ Dodawanie metaboxa dla grupy: {$group_name} (typ: {$group_type}, post_type: {$post_type})");
        
        add_meta_box(
            'yap_fields_' . sanitize_key($group_name),
            '📦 ' . ucwords(str_replace('_', ' ', $group_name)),
            function ($post) use ($group_name, $group_type, $group) {
                if ($group_type === 'json_schema') {
                    yap_display_json_schema_fields($post, $group_name);
                } elseif ($group_type === 'old_table') {
                    yap_display_table_fields($post, $group['data_table'], $group['pattern_table']);
                } elseif ($group_type === 'yap_table') {
                    yap_display_table_fields($post, $group['data_table'], $group['pattern_table']);
                }
            },
            $post_type,
            'normal',
            'high'
        );
    }
}

add_action('add_meta_boxes', 'yap_add_meta_boxes');
