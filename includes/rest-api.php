<?php
/**
 * YAP REST API Integration
 * Expose custom fields in WordPress REST API
 */

class YAP_REST_API {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', [$this, 'register_rest_fields']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    /**
     * Register REST fields for posts
     */
    public function register_rest_fields() {
        global $wpdb;
        
        // Get all field groups
        $groups = $this->get_all_groups();
        
        foreach ($groups as $group_name) {
            // Get location rules to determine which post types
            $location_rules = YAP_Location_Rules::get_instance();
            $rules = $location_rules->get_rules($group_name);
            
            $post_types = $this->extract_post_types($rules);
            
            if (empty($post_types)) {
                $post_types = ['post', 'page']; // Default
            }
            
            foreach ($post_types as $post_type) {
                // Register field for this post type
                register_rest_field($post_type, 'yap_' . sanitize_title($group_name), [
                    'get_callback' => function($object) use ($group_name) {
                        return $this->get_fields_for_rest($object['id'], $group_name);
                    },
                    'update_callback' => function($value, $object) use ($group_name) {
                        return $this->update_fields_from_rest($value, $object->ID, $group_name);
                    },
                    'schema' => $this->get_field_schema($group_name)
                ]);
            }
        }
        
        // Register fields for options pages
        register_rest_route('yap/v1', '/options/(?P<page>[\w-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_options_page'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
        
        register_rest_route('yap/v1', '/options/(?P<page>[\w-]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'update_options_page'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }
    
    /**
     * Register custom REST routes
     */
    public function register_rest_routes() {
        // Block preview endpoint
        register_rest_route('yap/v1', '/block-preview', [
            'methods' => 'POST',
            'callback' => [$this, 'get_block_preview'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
        
        // Get all field groups
        register_rest_route('yap/v1', '/field-groups', [
            'methods' => 'GET',
            'callback' => [$this, 'get_field_groups'],
            'permission_callback' => '__return_true'
        ]);
        
        // Get specific field group
        register_rest_route('yap/v1', '/field-groups/(?P<group>[\w-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_field_group'],
            'permission_callback' => '__return_true'
        ]);
        
        // Get fields for specific post
        register_rest_route('yap/v1', '/fields/post/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_post_fields'],
            'permission_callback' => '__return_true'
        ]);
        
        // Update fields for specific post
        register_rest_route('yap/v1', '/fields/post/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'update_post_fields'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
        
        // Get user fields
        register_rest_route('yap/v1', '/fields/user/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_user_fields'],
            'permission_callback' => '__return_true'
        ]);
        
        // Get term fields
        register_rest_route('yap/v1', '/fields/term/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_term_fields'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * Get all field groups
     */
    public function get_field_groups($request) {
        global $wpdb;
        
        $groups = $this->get_all_groups();
        $result = [];
        
        foreach ($groups as $group_name) {
            $pattern_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
            $safe_table = esc_sql($pattern_table);
            $fields = $wpdb->get_results("SELECT * FROM `{$safe_table}` WHERE parent_id IS NULL");
            
            $result[] = [
                'name' => $group_name,
                'field_count' => count($fields),
                'fields' => array_map(function($field) {
                    return [
                        'name' => $field->generated_name,
                        'label' => $field->user_name,
                        'type' => $field->type
                    ];
                }, $fields)
            ];
        }
        
        return rest_ensure_response($result);
    }
    
    /**
     * Get block preview HTML
     */
    public function get_block_preview($request) {
        $params = $request->get_json_params();
        $block_name = $params['block_name'] ?? '';
        $attributes = $params['attributes'] ?? [];
        
        if (empty($block_name)) {
            return new WP_Error('missing_block_name', 'Block name is required', ['status' => 400]);
        }
        
        // Get block config
        $blocks = YAP_Blocks::get_instance();
        $block = $blocks->get_block('yap/' . $block_name);
        
        if (!$block) {
            return new WP_Error('block_not_found', 'Block not found', ['status' => 404]);
        }
        
        // Render block preview
        $data = [
            'block_id' => 'preview_' . uniqid(),
            'attributes' => $attributes,
            'fields' => [],
            'is_preview' => true,
            'is_admin' => true
        ];
        
        // Get field values from attributes
        foreach ($block['fields'] as $field) {
            $data['fields'][$field['name']] = [
                'value' => $attributes[$field['name']] ?? ($field['default_value'] ?? ''),
                'config' => $field
            ];
        }
        
        ob_start();
        if (!empty($block['render_callback']) && is_callable($block['render_callback'])) {
            call_user_func($block['render_callback'], $data, '', null);
        }
        $html = ob_get_clean();
        
        return rest_ensure_response(['html' => $html]);
    }
    
    /**
     * Get specific field group
     */
    public function get_field_group($request) {
        $group_name = $request['group'];
        
        // Use JSON export to get full structure
        $json_manager = YAP_JSON_Manager::get_instance();
        $json_data = $json_manager->export_group($group_name);
        
        if (is_wp_error($json_data)) {
            return new WP_Error('group_not_found', 'Field group not found', ['status' => 404]);
        }
        
        return rest_ensure_response($json_data);
    }
    
    /**
     * Get all fields for a post
     */
    public function get_post_fields($request) {
        $post_id = $request['id'];
        $groups = $this->get_all_groups();
        $result = [];
        
        foreach ($groups as $group_name) {
            $fields = yap_get_all_fields($post_id, $group_name);
            
            if (!empty($fields)) {
                $result[$group_name] = $this->format_fields_for_rest($fields);
            }
        }
        
        return rest_ensure_response($result);
    }
    
    /**
     * Update post fields
     */
    public function update_post_fields($request) {
        $post_id = $request['id'];
        $data = $request->get_json_params();
        
        foreach ($data as $group_name => $fields) {
            foreach ($fields as $field_name => $value) {
                yap_update_field($field_name, $value, $post_id, $group_name);
            }
        }
        
        return rest_ensure_response(['success' => true]);
    }
    
    /**
     * Get user fields
     */
    public function get_user_fields($request) {
        $user_id = $request['id'];
        
        // Get user meta with yap_ prefix
        $meta = get_user_meta($user_id);
        $result = [];
        
        foreach ($meta as $key => $value) {
            if (strpos($key, 'yap_') === 0) {
                $result[substr($key, 4)] = maybe_unserialize($value[0]);
            }
        }
        
        return rest_ensure_response($result);
    }
    
    /**
     * Get term fields
     */
    public function get_term_fields($request) {
        $term_id = $request['id'];
        
        // Get term meta with yap_ prefix
        $meta = get_term_meta($term_id);
        $result = [];
        
        foreach ($meta as $key => $value) {
            if (strpos($key, 'yap_') === 0) {
                $result[substr($key, 4)] = maybe_unserialize($value[0]);
            }
        }
        
        return rest_ensure_response($result);
    }
    
    /**
     * Get options page fields
     */
    public function get_options_page($request) {
        $page = $request['page'];
        
        $options_manager = YAP_Options_Pages::get_instance();
        $fields = $options_manager->get_all_options($page);
        
        return rest_ensure_response($fields);
    }
    
    /**
     * Update options page fields
     */
    public function update_options_page($request) {
        $page = $request['page'];
        $data = $request->get_json_params();
        
        foreach ($data as $field_name => $value) {
            yap_update_option($page, $field_name, $value);
        }
        
        return rest_ensure_response(['success' => true]);
    }
    
    /**
     * Get fields for REST API response
     */
    private function get_fields_for_rest($post_id, $group_name) {
        $fields = yap_get_all_fields($post_id, $group_name);
        return $this->format_fields_for_rest($fields);
    }
    
    /**
     * Format fields for REST API
     */
    private function format_fields_for_rest($fields) {
        $formatted = [];
        
        foreach ($fields as $field) {
            $value = $field['value'];
            
            // Format specific field types
            switch ($field['type']) {
                case 'image':
                case 'file':
                    if (is_numeric($value)) {
                        $value = [
                            'id' => (int)$value,
                            'url' => wp_get_attachment_url($value),
                            'title' => get_the_title($value),
                            'mime_type' => get_post_mime_type($value)
                        ];
                    }
                    break;
                    
                case 'gallery':
                    if (is_array($value)) {
                        $value = array_map(function($id) {
                            return [
                                'id' => (int)$id,
                                'url' => wp_get_attachment_url($id),
                                'title' => get_the_title($id)
                            ];
                        }, $value);
                    }
                    break;
                    
                case 'post_object':
                case 'relationship':
                    if (is_numeric($value) || is_array($value)) {
                        $ids = (array)$value;
                        $value = array_map(function($id) {
                            $post = get_post($id);
                            return [
                                'id' => (int)$id,
                                'title' => get_the_title($id),
                                'url' => get_permalink($id),
                                'type' => get_post_type($id)
                            ];
                        }, $ids);
                        
                        if ($field['type'] === 'post_object') {
                            $value = $value[0] ?? null;
                        }
                    }
                    break;
                    
                case 'user':
                    if (is_numeric($value)) {
                        $user = get_user_by('id', $value);
                        $value = [
                            'id' => (int)$value,
                            'name' => $user->display_name,
                            'email' => $user->user_email
                        ];
                    }
                    break;
                    
                case 'taxonomy':
                    if (is_numeric($value) || is_array($value)) {
                        $ids = (array)$value;
                        $value = array_map(function($id) {
                            $term = get_term($id);
                            return [
                                'id' => (int)$id,
                                'name' => $term->name,
                                'slug' => $term->slug,
                                'taxonomy' => $term->taxonomy
                            ];
                        }, $ids);
                    }
                    break;
            }
            
            $formatted[$field['label']] = $value;
        }
        
        return $formatted;
    }
    
    /**
     * Update fields from REST API
     */
    private function update_fields_from_rest($value, $post_id, $group_name) {
        foreach ($value as $field_name => $field_value) {
            yap_update_field($field_name, $field_value, $post_id, $group_name);
        }
        return true;
    }
    
    /**
     * Get field schema for REST API
     */
    private function get_field_schema($group_name) {
        return [
            'description' => 'YAP fields for ' . $group_name,
            'type' => 'object',
            'context' => ['view', 'edit'],
            'readonly' => false
        ];
    }
    
    /**
     * Extract post types from location rules
     */
    private function extract_post_types($rules) {
        $post_types = [];
        
        foreach ($rules as $rule) {
            if ($rule->location_type === 'post_type') {
                $post_types[] = $rule->location_value;
            }
        }
        
        return array_unique($post_types);
    }
    
    /**
     * Get all field groups
     */
    private function get_all_groups() {
        global $wpdb;
        
        $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}group_%_pattern'");
        $groups = [];
        
        foreach ($tables as $table) {
            $table_name = reset($table);
            $group_name = str_replace(
                [$wpdb->prefix . 'group_', '_pattern'],
                '',
                $table_name
            );
            $groups[] = $group_name;
        }
        
        return $groups;
    }
}

// Helper functions
function yap_rest_api_init() {
    return YAP_REST_API::get_instance();
}
