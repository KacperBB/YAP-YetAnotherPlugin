<?php
/**
 * YAP WPGraphQL Integration
 * Automatic GraphQL schema generation for YAP fields
 */

class YAP_GraphQL {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Check if WPGraphQL is active
        if (!class_exists('WPGraphQL')) {
            return;
        }
        
        add_action('graphql_register_types', [$this, 'register_graphql_fields']);
        add_action('graphql_register_types', [$this, 'register_graphql_types']);
    }
    
    /**
     * Register GraphQL fields for all YAP field groups
     */
    public function register_graphql_fields() {
        global $wpdb;
        
        $groups = $this->get_all_groups();
        
        foreach ($groups as $group_name) {
            $this->register_group_fields($group_name);
        }
    }
    
    /**
     * Register GraphQL types for complex fields
     */
    public function register_graphql_types() {
        // Register Repeater type
        register_graphql_object_type('YapRepeaterRow', [
            'description' => 'YAP Repeater Row',
            'fields' => [
                'order' => ['type' => 'Int'],
                'data' => ['type' => 'String']
            ]
        ]);
        
        // Register Flexible Content type
        register_graphql_object_type('YapFlexibleLayout', [
            'description' => 'YAP Flexible Content Layout',
            'fields' => [
                'type' => ['type' => 'String'],
                'order' => ['type' => 'Int'],
                'fields' => ['type' => 'String']
            ]
        ]);
        
        // Register Image type
        register_graphql_object_type('YapImage', [
            'description' => 'YAP Image Field',
            'fields' => [
                'id' => ['type' => 'ID'],
                'url' => ['type' => 'String'],
                'title' => ['type' => 'String'],
                'alt' => ['type' => 'String'],
                'width' => ['type' => 'Int'],
                'height' => ['type' => 'Int'],
                'mimeType' => ['type' => 'String']
            ]
        ]);
        
        // Register File type
        register_graphql_object_type('YapFile', [
            'description' => 'YAP File Field',
            'fields' => [
                'id' => ['type' => 'ID'],
                'url' => ['type' => 'String'],
                'title' => ['type' => 'String'],
                'filename' => ['type' => 'String'],
                'filesize' => ['type' => 'Int'],
                'mimeType' => ['type' => 'String']
            ]
        ]);
        
        // Register Gallery type
        register_graphql_object_type('YapGallery', [
            'description' => 'YAP Gallery Field',
            'fields' => [
                'images' => [
                    'type' => ['list_of' => 'YapImage'],
                    'description' => 'Gallery images'
                ]
            ]
        ]);
    }
    
    /**
     * Register fields for specific group
     */
    private function register_group_fields($group_name) {
        global $wpdb;
        
        $pattern_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
        
        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $pattern_table)) !== $pattern_table) {
            return;
        }
        
        // Get all fields from pattern table - use esc_sql for table name
        $safe_table = esc_sql($pattern_table);
        $fields = $wpdb->get_results("SELECT * FROM `{$safe_table}`");
        
        // Get location rules to determine which post types
        $location_rules = YAP_Location_Rules::get_instance();
        $rules = $location_rules->get_rules($group_name);
        
        $post_types = $this->extract_post_types($rules);
        
        if (empty($post_types)) {
            $post_types = ['post', 'page'];
        }
        
        foreach ($post_types as $post_type) {
            // Get GraphQL single name for this post type
            $graphql_single_name = $this->get_graphql_single_name($post_type);
            
            if (!$graphql_single_name) {
                continue;
            }
            
            // Register field group
            register_graphql_field($graphql_single_name, 'yap_' . sanitize_title($group_name), [
                'type' => 'Yap_' . $this->pascalize($group_name),
                'description' => 'YAP fields for ' . $group_name,
                'resolve' => function($post) use ($group_name) {
                    return $this->resolve_group_fields($post->ID, $group_name);
                }
            ]);
            
            // Register object type for this group
            $this->register_group_type($group_name, $fields);
        }
        
        // Register for options pages
        if ($this->has_options_location($rules)) {
            register_graphql_field('RootQuery', 'yap_options_' . sanitize_title($group_name), [
                'type' => 'Yap_' . $this->pascalize($group_name),
                'description' => 'YAP options for ' . $group_name,
                'resolve' => function() use ($group_name) {
                    return $this->resolve_options_fields($group_name);
                }
            ]);
        }
    }
    
    /**
     * Register GraphQL object type for field group
     */
    private function register_group_type($group_name, $fields) {
        $type_name = 'Yap_' . $this->pascalize($group_name);
        
        $field_definitions = [];
        
        foreach ($fields as $field) {
            $graphql_type = $this->map_field_type_to_graphql($field->field_type);
            
            $field_definitions[$field->generated_name] = [
                'type' => $graphql_type,
                'description' => $field->user_name
            ];
        }
        
        register_graphql_object_type($type_name, [
            'description' => 'YAP fields for ' . $group_name,
            'fields' => $field_definitions
        ]);
    }
    
    /**
     * Resolve field values for post
     */
    private function resolve_group_fields($post_id, $group_name) {
        $fields = yap_get_all_fields($post_id, $group_name);
        $result = [];
        
        foreach ($fields as $field) {
            $value = $field['value'];
            
            // Format value based on type
            switch ($field['type']) {
                case 'image':
                    $value = $this->format_image_for_graphql($value);
                    break;
                    
                case 'file':
                    $value = $this->format_file_for_graphql($value);
                    break;
                    
                case 'gallery':
                    $value = $this->format_gallery_for_graphql($value);
                    break;
                    
                case 'post_object':
                case 'relationship':
                    $value = $this->format_post_relationship_for_graphql($value);
                    break;
                    
                case 'user':
                    $value = $this->format_user_for_graphql($value);
                    break;
                    
                case 'taxonomy':
                    $value = $this->format_taxonomy_for_graphql($value);
                    break;
                    
                case 'repeater':
                    $value = $this->format_repeater_for_graphql($value, $field['label']);
                    break;
                    
                case 'flexible_content':
                    $value = $this->format_flexible_for_graphql($value);
                    break;
                    
                case 'true_false':
                    $value = (bool)$value;
                    break;
                    
                case 'number':
                case 'range':
                    $value = is_numeric($value) ? (float)$value : null;
                    break;
            }
            
            $result[$field['label']] = $value;
        }
        
        return $result;
    }
    
    /**
     * Resolve options page fields
     */
    private function resolve_options_fields($group_name) {
        $options_manager = YAP_Options_Pages::get_instance();
        $fields = $options_manager->get_all_options($group_name);
        
        return $fields;
    }
    
    /**
     * Format image for GraphQL
     */
    private function format_image_for_graphql($image_id) {
        if (!$image_id) {
            return null;
        }
        
        $attachment = get_post($image_id);
        $metadata = wp_get_attachment_metadata($image_id);
        
        return [
            'id' => $image_id,
            'url' => wp_get_attachment_url($image_id),
            'title' => get_the_title($image_id),
            'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
            'width' => $metadata['width'] ?? null,
            'height' => $metadata['height'] ?? null,
            'mimeType' => get_post_mime_type($image_id)
        ];
    }
    
    /**
     * Format file for GraphQL
     */
    private function format_file_for_graphql($file_id) {
        if (!$file_id) {
            return null;
        }
        
        return [
            'id' => $file_id,
            'url' => wp_get_attachment_url($file_id),
            'title' => get_the_title($file_id),
            'filename' => basename(get_attached_file($file_id)),
            'filesize' => filesize(get_attached_file($file_id)),
            'mimeType' => get_post_mime_type($file_id)
        ];
    }
    
    /**
     * Format gallery for GraphQL
     */
    private function format_gallery_for_graphql($image_ids) {
        if (!is_array($image_ids)) {
            return null;
        }
        
        $images = array_map([$this, 'format_image_for_graphql'], $image_ids);
        
        return ['images' => $images];
    }
    
    /**
     * Format post relationship for GraphQL
     */
    private function format_post_relationship_for_graphql($post_ids) {
        if (empty($post_ids)) {
            return null;
        }
        
        $ids = is_array($post_ids) ? $post_ids : [$post_ids];
        return $ids;
    }
    
    /**
     * Format user for GraphQL
     */
    private function format_user_for_graphql($user_id) {
        return $user_id ? (int)$user_id : null;
    }
    
    /**
     * Format taxonomy for GraphQL
     */
    private function format_taxonomy_for_graphql($term_ids) {
        if (empty($term_ids)) {
            return null;
        }
        
        $ids = is_array($term_ids) ? $term_ids : [$term_ids];
        return $ids;
    }
    
    /**
     * Format repeater for GraphQL
     */
    private function format_repeater_for_graphql($rows, $field_name) {
        if (!is_array($rows)) {
            return null;
        }
        
        return array_map(function($row, $index) {
            return [
                'order' => $index,
                'data' => json_encode($row)
            ];
        }, $rows, array_keys($rows));
    }
    
    /**
     * Format flexible content for GraphQL
     */
    private function format_flexible_for_graphql($layouts) {
        if (!is_array($layouts)) {
            return null;
        }
        
        return array_map(function($layout, $index) {
            return [
                'type' => $layout['type'] ?? '',
                'order' => $index,
                'fields' => json_encode($layout['fields'] ?? [])
            ];
        }, $layouts, array_keys($layouts));
    }
    
    /**
     * Map YAP field type to GraphQL type
     */
    private function map_field_type_to_graphql($field_type) {
        $type_map = [
            'short_text' => 'String',
            'long_text' => 'String',
            'number' => 'Float',
            'range' => 'Float',
            'true_false' => 'Boolean',
            'select' => 'String',
            'radio' => 'String',
            'checkbox' => ['list_of' => 'String'],
            'date' => 'String',
            'datetime' => 'String',
            'time' => 'String',
            'color' => 'String',
            'wysiwyg' => 'String',
            'oembed' => 'String',
            'image' => 'YapImage',
            'file' => 'YapFile',
            'gallery' => 'YapGallery',
            'post_object' => 'ID',
            'relationship' => ['list_of' => 'ID'],
            'user' => 'ID',
            'taxonomy' => ['list_of' => 'ID'],
            'repeater' => ['list_of' => 'YapRepeaterRow'],
            'flexible_content' => ['list_of' => 'YapFlexibleLayout']
        ];
        
        return $type_map[$field_type] ?? 'String';
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
     * Check if has options page location
     */
    private function has_options_location($rules) {
        foreach ($rules as $rule) {
            if ($rule->location_type === 'options_page') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get GraphQL single name for post type
     */
    private function get_graphql_single_name($post_type) {
        $post_type_object = get_post_type_object($post_type);
        
        if (!$post_type_object) {
            return null;
        }
        
        // Check if post type has graphql_single_name
        if (isset($post_type_object->graphql_single_name)) {
            return $post_type_object->graphql_single_name;
        }
        
        // Default mapping
        $mapping = [
            'post' => 'Post',
            'page' => 'Page',
            'attachment' => 'MediaItem'
        ];
        
        return $mapping[$post_type] ?? $this->pascalize($post_type);
    }
    
    /**
     * Convert string to PascalCase
     */
    private function pascalize($string) {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $string)));
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

// Helper function
function yap_graphql_init() {
    return YAP_GraphQL::get_instance();
}
