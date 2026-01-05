<?php
/**
 * YAP JSON Export/Import System
 * Export and import field groups as JSON files
 */

class YAP_JSON_Manager {
    
    private static $instance = null;
    private $json_save_path = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Default path: wp-content/uploads/yap-json/
        $upload_dir = wp_upload_dir();
        $this->json_save_path = $upload_dir['basedir'] . '/yap-json/';
        
        // Allow filtering the JSON save path
        $this->json_save_path = apply_filters('yap/json_save_path', $this->json_save_path);
        
        // Auto-sync on init
        add_action('init', [$this, 'auto_sync_json'], 15);
        
        // Save to JSON when group is saved in admin
        add_action('yap/save_group', [$this, 'save_group_to_json']);
    }
    
    /**
     * Set custom JSON save path
     */
    public function set_json_save_path($path) {
        $this->json_save_path = trailingslashit($path);
    }
    
    /**
     * Get JSON save path
     */
    public function get_json_save_path() {
        return $this->json_save_path;
    }
    
    /**
     * Export field group to JSON
     * 
     * @param string $group_name Group name to export
     * @return array|WP_Error JSON data or error
     */
    public function export_group($group_name) {
        global $wpdb;
        
        $pattern_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
        
        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $pattern_table)) !== $pattern_table) {
            return new WP_Error('group_not_found', 'Group not found');
        }
        
        // Get all fields ordered by depth and ID - use esc_sql for table name
        $safe_table = esc_sql($pattern_table);
        $fields = $wpdb->get_results("SELECT * FROM `{$safe_table}` ORDER BY field_depth, id");
        
        if (empty($fields)) {
            return new WP_Error('no_fields', 'No fields found in group');
        }
        
        // Get location rules
        $location_rules_table = $wpdb->prefix . 'yap_location_rules';
        $location_rules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $location_rules_table WHERE group_name = %s ORDER BY rule_group, rule_order",
            $group_name
        ));
        
        // Build JSON structure
        $json_data = [
            'version' => '1.0',
            'group_name' => $group_name,
            'fields' => [],
            'location' => []
        ];
        
        // Process fields
        $fields_by_id = [];
        $root_fields = [];
        
        foreach ($fields as $field) {
            $field_data = [
                'id' => $field->id,
                'name' => $field->generated_name,
                'label' => $field->user_name,
                'type' => $field->field_type,
                'options' => $field->field_options ? json_decode($field->field_options, true) : null,
                'validation' => $field->validation_rules ? json_decode($field->validation_rules, true) : null,
                'conditional_logic' => $field->conditional_logic ? json_decode($field->conditional_logic, true) : null,
                'is_repeater' => (bool)$field->is_repeater,
                'repeater_min' => $field->repeater_min,
                'repeater_max' => $field->repeater_max,
                'layout_type' => $field->layout_type,
                'parent_id' => $field->parent_id,
                'sub_fields' => []
            ];
            
            $fields_by_id[$field->id] = $field_data;
            
            if ($field->parent_id === null) {
                $root_fields[] = &$fields_by_id[$field->id];
            }
        }
        
        // Build hierarchy
        foreach ($fields_by_id as $id => &$field) {
            if ($field['parent_id'] !== null && isset($fields_by_id[$field['parent_id']])) {
                $fields_by_id[$field['parent_id']]['sub_fields'][] = &$field;
            }
        }
        
        $json_data['fields'] = $root_fields;
        
        // Process location rules
        $current_group = null;
        $rule_set = [];
        
        foreach ($location_rules as $rule) {
            if ($current_group !== $rule->rule_group) {
                if (!empty($rule_set)) {
                    $json_data['location'][] = $rule_set;
                }
                $rule_set = [];
                $current_group = $rule->rule_group;
            }
            
            $rule_set[] = [
                'type' => $rule->location_type,
                'operator' => $rule->location_operator,
                'value' => $rule->location_value
            ];
        }
        
        if (!empty($rule_set)) {
            $json_data['location'][] = $rule_set;
        }
        
        return $json_data;
    }
    
    /**
     * Import field group from JSON
     * 
     * @param array $json_data JSON data
     * @return bool|WP_Error Success or error
     */
    public function import_group($json_data) {
        // Validate JSON structure
        if (empty($json_data['group_name']) || empty($json_data['fields'])) {
            return new WP_Error('invalid_json', 'Invalid JSON structure');
        }
        
        // Use field registration system to sync to database
        $config = [
            'group_name' => $json_data['group_name'],
            'title' => $json_data['title'] ?? $json_data['group_name'],
            'fields' => $this->flatten_fields($json_data['fields']),
            'location' => $json_data['location'] ?? []
        ];
        
        return yap_register_field_group($config);
    }
    
    /**
     * Flatten fields hierarchy for registration
     */
    private function flatten_fields($fields) {
        $flattened = [];
        
        foreach ($fields as $field) {
            $field_data = $field;
            $sub_fields = $field['sub_fields'] ?? [];
            unset($field_data['sub_fields']);
            
            $flattened[] = $field_data;
            
            if (!empty($sub_fields)) {
                $flattened = array_merge($flattened, $this->flatten_fields($sub_fields));
            }
        }
        
        return $flattened;
    }
    
    /**
     * Save field group to JSON file
     * 
     * @param string $group_name Group name
     * @return bool|WP_Error Success or error
     */
    public function save_group_to_json($group_name) {
        // Export to JSON
        $json_data = $this->export_group($group_name);
        
        if (is_wp_error($json_data)) {
            return $json_data;
        }
        
        // Ensure directory exists
        if (!file_exists($this->json_save_path)) {
            wp_mkdir_p($this->json_save_path);
        }
        
        // Save to file
        $filename = sanitize_title($group_name) . '.json';
        $filepath = $this->json_save_path . $filename;
        
        $result = file_put_contents(
            $filepath,
            json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        
        if ($result === false) {
            return new WP_Error('save_failed', 'Failed to save JSON file');
        }
        
        return true;
    }
    
    /**
     * Load field group from JSON file
     * 
     * @param string $filename JSON filename
     * @return array|WP_Error JSON data or error
     */
    public function load_json_file($filename) {
        $filepath = $this->json_save_path . $filename;
        
        if (!file_exists($filepath)) {
            return new WP_Error('file_not_found', 'JSON file not found');
        }
        
        $json_content = file_get_contents($filepath);
        $json_data = json_decode($json_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', 'Invalid JSON: ' . json_last_error_msg());
        }
        
        return $json_data;
    }
    
    /**
     * Auto-sync JSON files to database (Local JSON feature)
     */
    public function auto_sync_json() {
        // Check if JSON directory exists
        if (!file_exists($this->json_save_path)) {
            return;
        }
        
        // Get all JSON files
        $json_files = glob($this->json_save_path . '*.json');
        
        if (empty($json_files)) {
            return;
        }
        
        foreach ($json_files as $filepath) {
            $json_data = $this->load_json_file(basename($filepath));
            
            if (is_wp_error($json_data)) {
                continue;
            }
            
            // Check if group needs update
            if ($this->needs_sync($json_data)) {
                $this->import_group($json_data);
            }
        }
    }
    
    /**
     * Check if JSON needs sync with database
     */
    private function needs_sync($json_data) {
        global $wpdb;
        
        $group_name = $json_data['group_name'];
        $pattern_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
        
        // If table doesn't exist, needs sync
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $pattern_table)) !== $pattern_table) {
            return true;
        }
        
        // Compare field count
        $safe_table = esc_sql($pattern_table);
        $db_field_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$safe_table}`");
        $json_field_count = count($this->flatten_fields($json_data['fields']));
        
        if ($db_field_count != $json_field_count) {
            return true;
        }
        
        // Store JSON hash for change detection
        $json_hash = md5(json_encode($json_data));
        $stored_hash = get_option('yap_json_hash_' . $group_name);
        
        if ($json_hash !== $stored_hash) {
            update_option('yap_json_hash_' . $group_name, $json_hash);
            return true;
        }
        
        return false;
    }
    
    /**
     * Export all groups to JSON
     */
    public function export_all_groups() {
        global $wpdb;
        
        // Get all group tables
        $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}group_%_pattern'");
        $exported = [];
        
        foreach ($tables as $table) {
            $table_name = reset($table);
            $group_name = str_replace(
                [$wpdb->prefix . 'group_', '_pattern'],
                '',
                $table_name
            );
            
            $result = $this->save_group_to_json($group_name);
            
            if (!is_wp_error($result)) {
                $exported[] = $group_name;
            }
        }
        
        return $exported;
    }
    
    /**
     * Import all JSON files
     */
    public function import_all_json() {
        if (!file_exists($this->json_save_path)) {
            return [];
        }
        
        $json_files = glob($this->json_save_path . '*.json');
        $imported = [];
        
        foreach ($json_files as $filepath) {
            $json_data = $this->load_json_file(basename($filepath));
            
            if (!is_wp_error($json_data)) {
                $result = $this->import_group($json_data);
                
                if (!is_wp_error($result)) {
                    $imported[] = $json_data['group_name'];
                }
            }
        }
        
        return $imported;
    }
}

// Helper functions
function yap_export_group_to_json($group_name) {
    return YAP_JSON_Manager::get_instance()->export_group($group_name);
}

function yap_import_group_from_json($json_data) {
    return YAP_JSON_Manager::get_instance()->import_group($json_data);
}

function yap_save_group_to_json($group_name) {
    return YAP_JSON_Manager::get_instance()->save_group_to_json($group_name);
}

function yap_set_json_save_path($path) {
    return YAP_JSON_Manager::get_instance()->set_json_save_path($path);
}

function yap_get_json_save_path() {
    return YAP_JSON_Manager::get_instance()->get_json_save_path();
}

function yap_export_all_groups() {
    return YAP_JSON_Manager::get_instance()->export_all_groups();
}

function yap_import_all_json() {
    return YAP_JSON_Manager::get_instance()->import_all_json();
}
