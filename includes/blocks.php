<?php
/**
 * YAP Gutenberg Blocks System
 * Custom blocks with YAP fields (ACF Blocks equivalent)
 */

class YAP_Blocks {
    
    private static $instance = null;
    private $registered_blocks = [];
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'register_blocks'], 5);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
        add_filter('block_categories_all', [$this, 'register_block_category'], 10, 2);
    }
    
    /**
     * Register a custom Gutenberg block with YAP fields
     * 
     * @param array $block_config Block configuration
     * 
     * Example:
     * yap_register_block([
     *     'name' => 'testimonial',
     *     'title' => 'Testimonial',
     *     'description' => 'A custom testimonial block',
     *     'category' => 'yap-blocks',
     *     'icon' => 'format-quote',
     *     'keywords' => ['testimonial', 'quote', 'review'],
     *     'mode' => 'preview',
     *     'supports' => [
     *         'align' => true,
     *         'mode' => true,
     *         'jsx' => true,
     *         'anchor' => true
     *     ],
     *     'fields' => [
     *         [
     *             'name' => 'author_name',
     *             'label' => 'Author Name',
     *             'type' => 'short_text'
     *         ]
     *     ],
     *     'render_callback' => 'render_testimonial_block'
     * ]);
     */
    public function register_block($block_config) {
        // Validate required fields
        if (empty($block_config['name']) || empty($block_config['title'])) {
            return false;
        }
        
        // Set defaults
        $defaults = [
            'description' => '',
            'category' => 'yap-blocks',
            'icon' => 'admin-generic',
            'keywords' => [],
            'mode' => 'preview', // 'preview', 'edit', 'auto'
            'supports' => [
                'align' => false,
                'mode' => true,
                'multiple' => true,
                'jsx' => true,
                'anchor' => false,
                'customClassName' => true,
                'reusable' => true
            ],
            'fields' => [],
            'render_callback' => null,
            'render_template' => null,
            'enqueue_style' => null,
            'enqueue_script' => null,
            'enqueue_assets' => null,
            'post_types' => [], // Empty = all post types
            'example' => []
        ];
        
        $block_config = array_merge($defaults, $block_config);
        
        // Generate block name with namespace
        $block_name = 'yap/' . $block_config['name'];
        
        // Store for later registration
        $this->registered_blocks[$block_name] = $block_config;
        
        return true;
    }
    
    /**
     * Register all blocks with WordPress
     */
    public function register_blocks() {
        if (empty($this->registered_blocks)) {
            return;
        }
        
        foreach ($this->registered_blocks as $block_name => $config) {
            // Register field group for this block
            if (!empty($config['fields'])) {
                $this->register_block_fields($block_name, $config);
            }
            
            // Prepare block registration args
            $args = [
                'title' => $config['title'],
                'description' => $config['description'],
                'category' => $config['category'],
                'icon' => $this->parse_icon($config['icon']),
                'keywords' => $config['keywords'],
                'supports' => $config['supports'],
                'render_callback' => function($attributes, $content, $block) use ($config) {
                    return $this->render_block($config, $attributes, $content, $block);
                },
                'attributes' => $this->get_block_attributes($config),
                'editor_script' => 'yap-blocks-editor',
                'editor_style' => 'yap-blocks-editor-style',
            ];
            
            // Add example for block preview
            if (!empty($config['example'])) {
                $args['example'] = $config['example'];
            }
            
            // Register the block
            register_block_type($block_name, $args);
        }
    }
    
    /**
     * Register fields for block
     */
    private function register_block_fields($block_name, $config) {
        $group_name = 'block_' . str_replace('/', '_', $block_name);
        
        yap_register_field_group([
            'group_name' => $group_name,
            'title' => $config['title'] . ' Fields',
            'fields' => $config['fields'],
            'location' => [
                [
                    ['type' => 'block', 'operator' => '==', 'value' => $block_name]
                ]
            ]
        ]);
    }
    
    /**
     * Render block content
     */
    private function render_block($config, $attributes, $content, $block) {
        // Get block ID (unique identifier for this block instance)
        $block_id = 'block_' . $block->context['postId'] . '_' . uniqid();
        
        // Prepare data for template
        $data = [
            'block_id' => $block_id,
            'attributes' => $attributes,
            'content' => $content,
            'block' => $block,
            'fields' => $this->get_block_fields($block_id, $config),
            'is_preview' => defined('REST_REQUEST') && REST_REQUEST,
            'is_admin' => is_admin()
        ];
        
        // Apply filters
        $data = apply_filters('yap/block/data', $data, $config);
        $data = apply_filters("yap/block/data/name={$config['name']}", $data, $config);
        
        // Enqueue assets
        $this->enqueue_block_assets($config);
        
        // Render with callback or template
        if (!empty($config['render_callback']) && is_callable($config['render_callback'])) {
            ob_start();
            call_user_func($config['render_callback'], $data, $content, $block);
            return ob_get_clean();
        } elseif (!empty($config['render_template'])) {
            return $this->render_template($config['render_template'], $data);
        } else {
            return $this->default_block_render($data);
        }
    }
    
    /**
     * Get fields for block instance
     */
    private function get_block_fields($block_id, $config) {
        $fields = [];
        
        foreach ($config['fields'] as $field_config) {
            $field_name = $field_config['name'];
            
            // Get value from block attributes or metadata
            $value = get_post_meta($block_id, $field_name, true);
            
            // If empty, use default
            if (empty($value) && isset($field_config['default_value'])) {
                $value = $field_config['default_value'];
            }
            
            $fields[$field_name] = [
                'value' => $value,
                'config' => $field_config
            ];
        }
        
        return $fields;
    }
    
    /**
     * Render template file
     */
    private function render_template($template_path, $data) {
        // Look for template in theme first
        $template_locations = [
            get_stylesheet_directory() . '/template-parts/blocks/' . $template_path,
            get_template_directory() . '/template-parts/blocks/' . $template_path,
            plugin_dir_path(__FILE__) . '../blocks/templates/' . $template_path
        ];
        
        $template_file = null;
        foreach ($template_locations as $location) {
            if (file_exists($location)) {
                $template_file = $location;
                break;
            }
        }
        
        if (!$template_file) {
            return '<p>Template not found: ' . esc_html($template_path) . '</p>';
        }
        
        // Extract data to variables
        extract($data);
        
        // Render template
        ob_start();
        include $template_file;
        return ob_get_clean();
    }
    
    /**
     * Default block render (fallback)
     */
    private function default_block_render($data) {
        $output = '<div class="yap-block">';
        $output .= '<h3>' . esc_html($data['block']->name) . '</h3>';
        
        foreach ($data['fields'] as $field_name => $field) {
            $output .= '<p><strong>' . esc_html($field_name) . ':</strong> ';
            $output .= esc_html($field['value']) . '</p>';
        }
        
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Enqueue block assets
     */
    private function enqueue_block_assets($config) {
        // Enqueue style
        if (!empty($config['enqueue_style'])) {
            wp_enqueue_style('yap-block-' . $config['name'], $config['enqueue_style']);
        }
        
        // Enqueue script
        if (!empty($config['enqueue_script'])) {
            wp_enqueue_script('yap-block-' . $config['name'], $config['enqueue_script']);
        }
        
        // Custom enqueue callback
        if (!empty($config['enqueue_assets']) && is_callable($config['enqueue_assets'])) {
            call_user_func($config['enqueue_assets']);
        }
    }
    
    /**
     * Get block attributes schema
     */
    private function get_block_attributes($config) {
        $attributes = [
            'mode' => [
                'type' => 'string',
                'default' => $config['mode']
            ],
            'data' => [
                'type' => 'object',
                'default' => []
            ],
            'align' => [
                'type' => 'string'
            ],
            'className' => [
                'type' => 'string'
            ],
            'anchor' => [
                'type' => 'string'
            ]
        ];
        
        // Add attributes for each field
        foreach ($config['fields'] as $field) {
            $attributes[$field['name']] = [
                'type' => $this->get_field_attribute_type($field['type']),
                'default' => $field['default_value'] ?? null
            ];
        }
        
        return $attributes;
    }
    
    /**
     * Map field type to attribute type
     */
    private function get_field_attribute_type($field_type) {
        $type_map = [
            'short_text' => 'string',
            'long_text' => 'string',
            'number' => 'number',
            'true_false' => 'boolean',
            'image' => 'number',
            'file' => 'number',
            'gallery' => 'array',
            'select' => 'string',
            'checkbox' => 'array',
            'radio' => 'string',
            'date' => 'string',
            'datetime' => 'string',
            'time' => 'string',
            'color' => 'string',
            'wysiwyg' => 'string',
            'post_object' => 'number',
            'relationship' => 'array',
            'taxonomy' => 'array',
            'user' => 'number'
        ];
        
        return $type_map[$field_type] ?? 'string';
    }
    
    /**
     * Parse icon (dashicon or custom)
     */
    private function parse_icon($icon) {
        if (is_array($icon)) {
            return $icon;
        }
        
        // If it's a dashicon name, return as dashicon
        if (strpos($icon, 'dashicons-') === 0 || strpos($icon, 'admin-') === 0) {
            return $icon;
        }
        
        return 'dashicons-' . $icon;
    }
    
    /**
     * Register YAP block category
     */
    public function register_block_category($categories, $post) {
        return array_merge(
            $categories,
            [
                [
                    'slug' => 'yap-blocks',
                    'title' => 'YAP Blocks',
                    'icon' => 'admin-generic'
                ]
            ]
        );
    }
    
    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        // Register editor script
        wp_register_script(
            'yap-blocks-editor',
            plugins_url('../js/blocks/blocks-editor.js', __FILE__),
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'],
            '1.0.0',
            true
        );
        
        // Pass block data to JavaScript
        wp_localize_script('yap-blocks-editor', 'yapBlocks', [
            'blocks' => $this->get_blocks_for_editor(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yap_blocks')
        ]);
        
        // Register editor styles
        wp_register_style(
            'yap-blocks-editor-style',
            plugins_url('../css/blocks/blocks-editor.css', __FILE__),
            ['wp-edit-blocks'],
            '1.0.0'
        );
    }
    
    /**
     * Get blocks data for editor
     */
    private function get_blocks_for_editor() {
        $blocks_data = [];
        
        foreach ($this->registered_blocks as $block_name => $config) {
            $blocks_data[$block_name] = [
                'title' => $config['title'],
                'icon' => $config['icon'],
                'category' => $config['category'],
                'keywords' => $config['keywords'],
                'fields' => $config['fields'],
                'mode' => $config['mode'],
                'supports' => $config['supports']
            ];
        }
        
        return $blocks_data;
    }
    
    /**
     * Get registered blocks
     */
    public function get_registered_blocks() {
        return $this->registered_blocks;
    }
    
    /**
     * Get specific block config
     */
    public function get_block($block_name) {
        return $this->registered_blocks[$block_name] ?? null;
    }
}

// Helper function
function yap_register_block($block_config) {
    return YAP_Blocks::get_instance()->register_block($block_config);
}

function yap_get_registered_blocks() {
    return YAP_Blocks::get_instance()->get_registered_blocks();
}

function yap_get_block($block_name) {
    return YAP_Blocks::get_instance()->get_block($block_name);
}

/**
 * Get field value from block
 * 
 * @param string $field_name Field name
 * @param array $block Block object or attributes
 * @return mixed Field value
 */
function yap_get_block_field($field_name, $block = null) {
    if (is_array($block) && isset($block['attrs'][$field_name])) {
        return $block['attrs'][$field_name];
    }
    
    if (is_object($block) && isset($block->context['postId'])) {
        $block_id = 'block_' . $block->context['postId'];
        return get_post_meta($block_id, $field_name, true);
    }
    
    return null;
}
