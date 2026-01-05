<?php
/**
 * YAP Field Registration System
 * Programmatic field group registration (similar to ACF's register_field_group)
 */

class YAP_Field_Registration {
    
    private static $instance = null;
    private $registered_groups = [];
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'sync_registered_groups'], 20);
    }
    
    /**
     * Register field group programmatically
     * 
     * @param array $group_config Field group configuration
     * @return bool Success status
     * 
     * Example:
     * yap_register_field_group([
     *     'group_name' => 'product_details',
     *     'title' => 'Product Details',
     *     'fields' => [
     *         [
     *             'name' => 'price',
     *             'label' => 'Price',
     *             'type' => 'number',
     *             'required' => true
     *         ]
     *     ],
     *     'location' => [
     *         [
     *             ['type' => 'post_type', 'operator' => '==', 'value' => 'product']
     *         ]
     *     ]
     * ]);
     */
    public function register_group($group_config) {
        // Validate required fields
        if (empty($group_config['group_name']) || empty($group_config['fields'])) {
            return false;
        }
        
        // Store for later sync
        $this->registered_groups[$group_config['group_name']] = $group_config;
        
        return true;
    }
    
    /**
     * Sync all registered groups to database
     */
    public function sync_registered_groups() {
        global $wpdb;
        
        foreach ($this->registered_groups as $group_name => $config) {
            // Check if group exists
            $table_name = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
            $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
            
            $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
            
            if (!$table_exists) {
                // Create tables
                $this->create_group_tables($group_name);
            }
            
            // Sync fields
            $this->sync_fields($group_name, $config['fields']);
            
            // Sync location rules
            if (!empty($config['location'])) {
                $this->sync_location_rules($group_name, $config['location']);
            }
        }
    }
    
    /**
     * Create database tables for field group
     */
    private function create_group_tables($group_name) {
        global $wpdb;
        
        $sanitized_name = sanitize_title($group_name);
        $pattern_table = $wpdb->prefix . 'group_' . $sanitized_name . '_pattern';
        $data_table = $wpdb->prefix . 'group_' . $sanitized_name . '_data';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Pattern table
        $pattern_sql = "CREATE TABLE IF NOT EXISTS $pattern_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_name VARCHAR(255) NOT NULL,
            generated_name VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT 'short_text',
            field_options TEXT,
            validation_rules TEXT,
            conditional_logic TEXT,
            is_repeater TINYINT(1) DEFAULT 0,
            repeater_min INT DEFAULT NULL,
            repeater_max INT DEFAULT NULL,
            layout_type VARCHAR(50) DEFAULT NULL,
            parent_id INT DEFAULT NULL
        ) $charset_collate;";
        
        // Data table
        $data_sql = "CREATE TABLE IF NOT EXISTS $data_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pattern_id INT NOT NULL,
            post_id INT NOT NULL,
            value TEXT,
            parent_row_id INT DEFAULT NULL,
            row_order INT DEFAULT 0,
            FOREIGN KEY (pattern_id) REFERENCES $pattern_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($pattern_sql);
        dbDelta($data_sql);
    }
    
    /**
     * Sync fields to database
     */
    private function sync_fields($group_name, $fields) {
        global $wpdb;
        
        $pattern_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
        
        foreach ($fields as $field) {
            // Check if field exists (both by generated_name and user_name)
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $pattern_table 
                 WHERE generated_name = %s OR user_name = %s
                 LIMIT 1",
                $field['name'],
                $field['label'] ?? $field['name']
            ));
            
            $field_data = [
                'user_name' => $field['label'] ?? $field['name'],
                'generated_name' => $field['name'],
                'type' => $field['type'] ?? 'short_text',
                'field_options' => isset($field['options']) ? json_encode($field['options']) : null,
                'validation_rules' => isset($field['validation']) ? json_encode($field['validation']) : null,
                'conditional_logic' => isset($field['conditional_logic']) ? json_encode($field['conditional_logic']) : null,
                'is_repeater' => isset($field['is_repeater']) ? (int)$field['is_repeater'] : 0,
                'repeater_min' => $field['repeater_min'] ?? null,
                'repeater_max' => $field['repeater_max'] ?? null,
                'layout_type' => $field['layout_type'] ?? null,
                'parent_id' => $field['parent_id'] ?? null
            ];
            
            if ($existing) {
                // Update existing field
                $wpdb->update(
                    $pattern_table,
                    $field_data,
                    ['id' => $existing->id]
                );
            } else {
                // Insert new field
                $wpdb->insert($pattern_table, $field_data);
            }
            
            // Handle sub-fields (for repeater/flexible content)
            if (!empty($field['sub_fields'])) {
                $parent_id = $existing ? $existing->id : $wpdb->insert_id;
                foreach ($field['sub_fields'] as $sub_field) {
                    $sub_field['parent_id'] = $parent_id;
                    $this->sync_fields($group_name, [$sub_field]);
                }
            }
        }
    }
    
    /**
     * Sync location rules to database
     */
    private function sync_location_rules($group_name, $location_rules) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'yap_location_rules';
        
        // Delete existing rules
        $wpdb->delete($table, ['group_name' => $group_name]);
        
        // Insert new rules (using INSERT IGNORE to prevent duplicates)
        $rule_group = 0;
        foreach ($location_rules as $rule_set) {
            $rule_order = 0;
            foreach ($rule_set as $rule) {
                // Use INSERT IGNORE to handle UNIQUE constraint violations
                $wpdb->query($wpdb->prepare(
                    "INSERT IGNORE INTO {$table} 
                    (group_name, location_type, location_operator, location_value, rule_group, rule_order)
                    VALUES (%s, %s, %s, %s, %d, %d)",
                    $group_name,
                    $rule['type'] ?? 'post_type',
                    $rule['operator'] ?? '==',
                    $rule['value'] ?? '',
                    $rule_group,
                    $rule_order
                ));
                $rule_order++;
            }
            $rule_group++;
        }
    }
    
    /**
     * Get all registered groups
     */
    public function get_registered_groups() {
        return $this->registered_groups;
    }
    
    /**
     * Unregister field group
     */
    public function unregister_group($group_name) {
        unset($this->registered_groups[$group_name]);
    }
}

// Helper function for registering field groups
function yap_register_field_group($group_config) {
    return YAP_Field_Registration::get_instance()->register_group($group_config);
}

// Helper function to get registered groups
function yap_get_registered_groups() {
    return YAP_Field_Registration::get_instance()->get_registered_groups();
}

// Helper function to unregister group
function yap_unregister_field_group($group_name) {
    return YAP_Field_Registration::get_instance()->unregister_group($group_name);
}
