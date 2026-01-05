<?php
/**
 * YAP Hooks System
 * Comprehensive filter and action hooks for extending YAP functionality
 */

class YAP_Hooks {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // These hooks are called automatically by YAP core functions
    }
    
    /**
     * Load field value from database with filters
     * 
     * @param mixed $value Field value
     * @param int $post_id Post ID
     * @param array $field Field configuration
     * @return mixed Filtered value
     */
    public static function load_value($value, $post_id, $field) {
        // General load filter
        $value = apply_filters('yap/load_value', $value, $post_id, $field);
        
        // Type-specific load filter
        $value = apply_filters("yap/load_value/type={$field['type']}", $value, $post_id, $field);
        
        // Name-specific load filter
        $value = apply_filters("yap/load_value/name={$field['name']}", $value, $post_id, $field);
        
        // Group-specific load filter
        if (!empty($field['group'])) {
            $value = apply_filters("yap/load_value/group={$field['group']}", $value, $post_id, $field);
        }
        
        return $value;
    }
    
    /**
     * Format field value for display with filters
     * 
     * @param mixed $value Field value
     * @param int $post_id Post ID
     * @param array $field Field configuration
     * @return mixed Formatted value
     */
    public static function format_value($value, $post_id, $field) {
        // General format filter
        $value = apply_filters('yap/format_value', $value, $post_id, $field);
        
        // Type-specific format filter
        $value = apply_filters("yap/format_value/type={$field['type']}", $value, $post_id, $field);
        
        // Name-specific format filter
        $value = apply_filters("yap/format_value/name={$field['name']}", $value, $post_id, $field);
        
        // Group-specific format filter
        if (!empty($field['group'])) {
            $value = apply_filters("yap/format_value/group={$field['group']}", $value, $post_id, $field);
        }
        
        return $value;
    }
    
    /**
     * Update field value with filters
     * 
     * @param mixed $value New value
     * @param int $post_id Post ID
     * @param array $field Field configuration
     * @return mixed Filtered value before save
     */
    public static function update_value($value, $post_id, $field) {
        // General update filter
        $value = apply_filters('yap/update_value', $value, $post_id, $field);
        
        // Type-specific update filter
        $value = apply_filters("yap/update_value/type={$field['type']}", $value, $post_id, $field);
        
        // Name-specific update filter
        $value = apply_filters("yap/update_value/name={$field['name']}", $value, $post_id, $field);
        
        // Group-specific update filter
        if (!empty($field['group'])) {
            $value = apply_filters("yap/update_value/group={$field['group']}", $value, $post_id, $field);
        }
        
        return $value;
    }
    
    /**
     * Load field configuration with filters
     * 
     * @param array $field Field configuration
     * @return array Filtered field config
     */
    public static function load_field($field) {
        // General load field filter
        $field = apply_filters('yap/load_field', $field);
        
        // Type-specific load field filter
        $field = apply_filters("yap/load_field/type={$field['type']}", $field);
        
        // Name-specific load field filter
        $field = apply_filters("yap/load_field/name={$field['name']}", $field);
        
        return $field;
    }
    
    /**
     * Prepare field for edit (admin)
     * 
     * @param array $field Field configuration
     * @return array Filtered field config
     */
    public static function prepare_field($field) {
        // General prepare filter
        $field = apply_filters('yap/prepare_field', $field);
        
        // Type-specific prepare filter
        $field = apply_filters("yap/prepare_field/type={$field['type']}", $field);
        
        return $field;
    }
    
    /**
     * Validate field value
     * 
     * @param bool $valid Validation status
     * @param mixed $value Field value
     * @param array $field Field configuration
     * @param string $input Input name
     * @return bool|string True if valid, error message if invalid
     */
    public static function validate_value($valid, $value, $field, $input) {
        // General validate filter
        $valid = apply_filters('yap/validate_value', $valid, $value, $field, $input);
        
        // Type-specific validate filter
        $valid = apply_filters("yap/validate_value/type={$field['type']}", $valid, $value, $field, $input);
        
        // Name-specific validate filter
        $valid = apply_filters("yap/validate_value/name={$field['name']}", $valid, $value, $field, $input);
        
        return $valid;
    }
    
    /**
     * Render field (admin)
     * 
     * @param array $field Field configuration
     */
    public static function render_field($field) {
        // Before render action
        do_action('yap/render_field', $field);
        do_action("yap/render_field/type={$field['type']}", $field);
        do_action("yap/render_field/name={$field['name']}", $field);
    }
    
    /**
     * Save post action
     * 
     * @param int $post_id Post ID
     */
    public static function save_post($post_id) {
        do_action('yap/save_post', $post_id);
    }
    
    /**
     * Delete post action
     * 
     * @param int $post_id Post ID
     */
    public static function delete_post($post_id) {
        do_action('yap/delete_post', $post_id);
    }
}

/**
 * Enhanced yap_get_field with hooks
 */
function yap_get_field_with_hooks($field_name, $post_id, $group_name) {
    global $wpdb;
    
    $pattern_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
    $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
    
    // Get field config
    $field_config = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $pattern_table WHERE user_name = %s OR generated_name = %s",
        $field_name,
        $field_name
    ), ARRAY_A);
    
    if (!$field_config) {
        return null;
    }
    
    // Apply load_field filter
    $field_config = YAP_Hooks::load_field($field_config);
    
    // Get value using correct schema
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT field_value FROM $data_table
        WHERE associated_id = %d AND (user_name = %s OR generated_name = %s)
        LIMIT 1",
        $post_id,
        $field_name,
        $field_name
    ));
    
    // Apply load_value filter
    $result = YAP_Hooks::load_value($result, $post_id, $field_config);
    
    // Apply format_value filter
    $result = YAP_Hooks::format_value($result, $post_id, $field_config);
    
    return $result;
}

/**
 * Enhanced yap_update_field with hooks
 */
function yap_update_field_with_hooks($field_name, $value, $post_id, $group_name) {
    global $wpdb;
    
    $pattern_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
    $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
    
    // Get field config
    $field_config = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $pattern_table WHERE user_name = %s OR generated_name = %s",
        $field_name,
        $field_name
    ), ARRAY_A);
    
    if (!$field_config) {
        return false;
    }
    
    // Apply update_value filter
    $value = YAP_Hooks::update_value($value, $post_id, $field_config);
    
    // Validate
    $valid = YAP_Hooks::validate_value(true, $value, $field_config, $field_name);
    
    if ($valid !== true) {
        return new WP_Error('validation_failed', $valid);
    }
    
    // Save to database using correct schema
    $generated_name = $field_config['generated_name'];
    $user_name = $field_config['user_name'];
    $field_type = $field_config['field_type'];
    
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $data_table WHERE generated_name = %s AND associated_id = %d",
        $generated_name,
        $post_id
    ));
    
    if ($existing) {
        $wpdb->update(
            $data_table,
            [
                'field_value' => maybe_serialize($value),
                'user_name' => $user_name,
                'field_type' => $field_type
            ],
            ['id' => $existing]
        );
    } else {
        $wpdb->insert($data_table, [
            'generated_name' => $generated_name,
            'user_name' => $user_name,
            'field_type' => $field_type,
            'field_value' => maybe_serialize($value),
            'associated_id' => $post_id
        ]);
    }
    
    return true;
}

/**
 * Available hooks documentation
 * 
 * LOAD HOOKS:
 * - yap/load_value - Filter any field value on load
 * - yap/load_value/type={type} - Filter by field type
 * - yap/load_value/name={name} - Filter by field name
 * - yap/load_value/group={group} - Filter by group name
 * 
 * FORMAT HOOKS:
 * - yap/format_value - Format any field value for display
 * - yap/format_value/type={type} - Format by field type
 * - yap/format_value/name={name} - Format by field name
 * - yap/format_value/group={group} - Format by group name
 * 
 * UPDATE HOOKS:
 * - yap/update_value - Filter value before saving
 * - yap/update_value/type={type} - Filter by field type
 * - yap/update_value/name={name} - Filter by field name
 * - yap/update_value/group={group} - Filter by group name
 * 
 * VALIDATE HOOKS:
 * - yap/validate_value - Validate any field value
 * - yap/validate_value/type={type} - Validate by field type
 * - yap/validate_value/name={name} - Validate by field name
 * 
 * FIELD CONFIG HOOKS:
 * - yap/load_field - Filter field configuration on load
 * - yap/load_field/type={type} - Filter by field type
 * - yap/load_field/name={name} - Filter by field name
 * - yap/prepare_field - Prepare field for admin edit
 * - yap/prepare_field/type={type} - Prepare by field type
 * 
 * RENDER HOOKS:
 * - yap/render_field - Before rendering field in admin
 * - yap/render_field/type={type} - Before rendering by type
 * - yap/render_field/name={name} - Before rendering by name
 * 
 * POST HOOKS:
 * - yap/save_post - After saving post with YAP fields
 * - yap/delete_post - Before deleting post with YAP fields
 * 
 * GROUP HOOKS:
 * - yap/save_group - After saving field group
 * - yap/delete_group - Before deleting field group
 */

// Example usage:
/*
// Format price with currency
add_filter('yap/format_value/name=price', function($value, $post_id, $field) {
    return '$' . number_format($value, 2);
}, 10, 3);

// Auto-uppercase title field
add_filter('yap/update_value/name=product_title', function($value, $post_id, $field) {
    return strtoupper($value);
}, 10, 3);

// Validate email field
add_filter('yap/validate_value/type=email', function($valid, $value, $field, $input) {
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email address';
    }
    return $valid;
}, 10, 4);

// Load field with custom defaults
add_filter('yap/load_field/name=settings', function($field) {
    $field['default_value'] = 'custom default';
    return $field;
});

// Before rendering field in admin
add_action('yap/render_field/type=wysiwyg', function($field) {
    echo '<div class="custom-wrapper">';
});
*/
