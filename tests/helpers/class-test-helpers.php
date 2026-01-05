<?php
/**
 * Test Helpers
 * 
 * Utility functions and classes for unit tests
 * 
 * @package YetAnotherPlugin
 */

/**
 * YAP Test Helpers
 */
class YAP_Test_Helpers {
    
    /**
     * Create a test field group with flexible content
     * 
     * @param array $config Configuration array
     * @return array Created group data
     */
    public static function create_flexible_group($config = []) {
        $defaults = [
            'name' => 'test_flexible_group',
            'label' => 'Test Flexible Group',
            'post_type' => 'page',
            'fields' => []
        ];
        
        $config = array_merge($defaults, $config);
        
        // Create flexible content field
        if (empty($config['fields'])) {
            $config['fields'][] = [
                'name' => 'page_sections',
                'label' => 'Page Sections',
                'type' => 'flexible',
                'layouts' => self::get_default_layouts()
            ];
        }
        
        return $config;
    }
    
    /**
     * Get default test layouts
     * 
     * @return array Array of default layouts
     */
    public static function get_default_layouts() {
        return [
            [
                'name' => 'hero_section',
                'label' => 'Hero Section',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'name' => 'title',
                        'label' => 'Title',
                        'type' => 'text'
                    ],
                    [
                        'name' => 'description',
                        'label' => 'Description',
                        'type' => 'textarea'
                    ],
                    [
                        'name' => 'image',
                        'label' => 'Background Image',
                        'type' => 'image'
                    ]
                ]
            ],
            [
                'name' => 'columns_3',
                'label' => '3 Columns',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'name' => 'col_1_title',
                        'label' => 'Column 1 Title',
                        'type' => 'text'
                    ],
                    [
                        'name' => 'col_2_title',
                        'label' => 'Column 2 Title',
                        'type' => 'text'
                    ],
                    [
                        'name' => 'col_3_title',
                        'label' => 'Column 3 Title',
                        'type' => 'text'
                    ]
                ]
            ],
            [
                'name' => 'testimonials',
                'label' => 'Testimonials',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'name' => 'quote',
                        'label' => 'Quote',
                        'type' => 'textarea'
                    ],
                    [
                        'name' => 'author',
                        'label' => 'Author Name',
                        'type' => 'text'
                    ],
                    [
                        'name' => 'author_image',
                        'label' => 'Author Image',
                        'type' => 'image'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Create repeater with flexible content
     * 
     * @return array Repeater field config
     */
    public static function create_repeater_with_fc() {
        return [
            'name' => 'content_blocks',
            'label' => 'Content Blocks',
            'type' => 'repeater',
            'sub_fields' => [
                [
                    'name' => 'flexible_content',
                    'label' => 'Block Content',
                    'type' => 'flexible',
                    'layouts' => self::get_default_layouts()
                ]
            ]
        ];
    }
    
    /**
     * Generate test section data
     * 
     * @param string $layout Layout name
     * @param array $data Field data
     * @return array Section data
     */
    public static function create_section($layout = 'hero_section', $data = []) {
        $defaults = [
            'layout' => $layout,
            'fields' => []
        ];
        
        // Add default fields based on layout
        switch ($layout) {
            case 'hero_section':
                $defaults['fields'] = array_merge([
                    'title' => 'Test Title',
                    'description' => 'Test Description',
                    'image' => 0
                ], $data);
                break;
                
            case 'columns_3':
                $defaults['fields'] = array_merge([
                    'col_1_title' => 'Column 1',
                    'col_2_title' => 'Column 2',
                    'col_3_title' => 'Column 3'
                ], $data);
                break;
                
            case 'testimonials':
                $defaults['fields'] = array_merge([
                    'quote' => 'Test quote',
                    'author' => 'Test Author',
                    'author_image' => 0
                ], $data);
                break;
        }
        
        return $defaults;
    }
    
    /**
     * Validate layout configuration
     * 
     * @param array $layout Layout array
     * @return bool True if valid
     */
    public static function validate_layout($layout) {
        $required = ['name', 'label', 'sub_fields'];
        
        foreach ($required as $key) {
            if (!isset($layout[$key]) || empty($layout[$key])) {
                return false;
            }
        }
        
        // Validate name is valid slug
        if (!preg_match('/^[a-z0-9_]+$/', $layout['name'])) {
            return false;
        }
        
        // Validate sub_fields are arrays
        if (!is_array($layout['sub_fields'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate field configuration
     * 
     * @param array $field Field array
     * @return bool True if valid
     */
    public static function validate_field($field) {
        $required = ['name', 'type', 'label'];
        
        foreach ($required as $key) {
            if (!isset($field[$key]) || empty($field[$key])) {
                return false;
            }
        }
        
        $allowed_types = [
            'text', 'textarea', 'number', 'email', 'date', 'time',
            'image', 'file', 'select', 'checkbox', 'radio',
            'repeater', 'flexible', 'wysiwyg'
        ];
        
        if (!in_array($field['type'], $allowed_types)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get test schema
     * 
     * @return array Test schema
     */
    public static function get_test_schema() {
        return [
            'name' => 'test_schema',
            'label' => 'Test Schema',
            'post_type' => 'page',
            'fields' => [
                [
                    'name' => 'page_title',
                    'label' => 'Page Title',
                    'type' => 'text'
                ],
                [
                    'name' => 'page_sections',
                    'label' => 'Page Sections',
                    'type' => 'flexible',
                    'layouts' => self::get_default_layouts()
                ],
                [
                    'name' => 'content_blocks',
                    'label' => 'Content Blocks',
                    'type' => 'repeater',
                    'sub_fields' => [
                        [
                            'name' => 'flexible_content',
                            'label' => 'Block Content',
                            'type' => 'flexible',
                            'layouts' => self::get_default_layouts()
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Compare two layouts
     * 
     * @param array $layout1 First layout
     * @param array $layout2 Second layout
     * @return bool True if different
     */
    public static function layouts_are_different($layout1, $layout2) {
        if ($layout1['name'] !== $layout2['name']) {
            return true;
        }
        
        if ($layout1['label'] !== $layout2['label']) {
            return true;
        }
        
        $fields1 = array_map(function($f) { return $f['name']; }, $layout1['sub_fields']);
        $fields2 = array_map(function($f) { return $f['name']; }, $layout2['sub_fields']);
        
        return $fields1 !== $fields2;
    }
    
    /**
     * Generate mock block data
     * 
     * @param int $count Number of blocks to generate
     * @return array Array of blocks
     */
    public static function generate_mock_blocks($count = 3) {
        $blocks = [];
        $layouts = ['hero_section', 'columns_3', 'testimonials'];
        
        for ($i = 0; $i < $count; $i++) {
            $layout = $layouts[$i % count($layouts)];
            $blocks[] = self::create_section($layout, [
                'title' => "Block {$i}"
            ]);
        }
        
        return $blocks;
    }
}

/**
 * Test Data Provider
 */
class YAP_Test_Data_Provider {
    
    /**
     * Get sample layouts
     */
    public static function sample_layouts() {
        return [
            'hero' => [
                'name' => 'hero_section',
                'label' => 'Hero Section'
            ],
            'testimonials' => [
                'name' => 'testimonials',
                'label' => 'Testimonials'
            ],
            'features' => [
                'name' => 'features_list',
                'label' => 'Features List'
            ]
        ];
    }
    
    /**
     * Get sample fields
     */
    public static function sample_fields() {
        return [
            'text' => [
                'type' => 'text',
                'label' => 'Text Field'
            ],
            'textarea' => [
                'type' => 'textarea',
                'label' => 'Text Area'
            ],
            'image' => [
                'type' => 'image',
                'label' => 'Image'
            ]
        ];
    }
}
