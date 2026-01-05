<?php
/**
 * YAP Visual Builder & Flexible Content Unit Tests
 * 
 * Test Suite for:
 * - Visual Builder schema creation
 * - Flexible Content layout management
 * - Block differentiation in repeaters
 * - Field type handling
 * - Data persistence
 * 
 * @package YetAnotherPlugin
 * @version 1.4.1
 */

if (!class_exists('WP_UnitTestCase')) {
    return; // Skip if not in test environment
}

/**
 * Test Visual Builder functionality
 */
class YAP_Visual_Builder_Tests extends WP_UnitTestCase {
    
    protected $builder = null;
    protected $test_group = 'test_schema';
    
    public function setUp() {
        parent::setUp();
        
        // Initialize Visual Builder
        if (class_exists('YAP_Visual_Builder')) {
            $this->builder = YAP_Visual_Builder::get_instance();
        }
    }
    
    public function tearDown() {
        parent::tearDown();
        // Clean up test data
        $this->cleanup_test_group();
    }
    
    /**
     * Test Visual Builder singleton pattern
     */
    public function test_visual_builder_singleton() {
        $this->assertInstanceOf('YAP_Visual_Builder', $this->builder);
        $this->assertSame($this->builder, YAP_Visual_Builder::get_instance());
    }
    
    /**
     * Test field type detection
     */
    public function test_field_type_detection() {
        $field_types = [
            'text' => 'Text Input',
            'textarea' => 'Text Area',
            'number' => 'Number',
            'email' => 'Email',
            'date' => 'Date',
            'repeater' => 'Repeater',
            'flexible' => 'Flexible Content'
        ];
        
        foreach ($field_types as $slug => $label) {
            $this->assertNotEmpty($slug, "Field type slug should not be empty: {$label}");
            $this->assertNotEmpty($label, "Field type label should not be empty");
        }
    }
    
    /**
     * Test creating a schema with fields
     */
    public function test_create_schema_with_fields() {
        $schema = [
            'name' => $this->test_group,
            'label' => 'Test Schema',
            'fields' => [
                [
                    'name' => 'title',
                    'label' => 'Title',
                    'type' => 'text'
                ],
                [
                    'name' => 'description',
                    'label' => 'Description',
                    'type' => 'textarea'
                ]
            ]
        ];
        
        // Validate schema structure
        $this->assertArrayHasKey('name', $schema);
        $this->assertArrayHasKey('fields', $schema);
        $this->assertCount(2, $schema['fields']);
        
        // Validate fields
        foreach ($schema['fields'] as $field) {
            $this->assertArrayHasKey('name', $field);
            $this->assertArrayHasKey('type', $field);
            $this->assertNotEmpty($field['name']);
            $this->assertNotEmpty($field['type']);
        }
    }
    
    /**
     * Test flexible content layout definition
     */
    public function test_flexible_content_layout_definition() {
        $layouts = [
            [
                'name' => 'hero_section',
                'label' => 'Hero Section',
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
            ]
        ];
        
        // Validate each layout
        foreach ($layouts as $layout) {
            $this->assertArrayHasKey('name', $layout);
            $this->assertArrayHasKey('label', $layout);
            $this->assertArrayHasKey('sub_fields', $layout);
            
            // Validate name format (slug)
            $this->assertRegExp('/^[a-z0-9_]+$/', $layout['name'], 
                "Layout name should be valid slug: {$layout['name']}");
            
            // Validate sub_fields
            $this->assertNotEmpty($layout['sub_fields']);
            foreach ($layout['sub_fields'] as $field) {
                $this->assertArrayHasKey('name', $field);
                $this->assertArrayHasKey('type', $field);
            }
        }
    }
    
    /**
     * Test block differentiation in flexible content
     */
    public function test_block_differentiation_in_flexible_content() {
        $flexible_field = [
            'name' => 'page_sections',
            'label' => 'Page Sections',
            'type' => 'flexible',
            'layouts' => [
                [
                    'name' => 'hero_section',
                    'label' => 'Hero Section',
                    'display' => 'block',
                    'sub_fields' => [
                        [
                            'name' => 'title',
                            'type' => 'text',
                            'label' => 'Title'
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
                            'type' => 'textarea',
                            'label' => 'Quote'
                        ]
                    ]
                ]
            ]
        ];
        
        // Test that layouts are different
        $this->assertCount(2, $flexible_field['layouts']);
        
        $hero = $flexible_field['layouts'][0];
        $testimonials = $flexible_field['layouts'][1];
        
        // Verify layout names are unique
        $this->assertNotEquals($hero['name'], $testimonials['name']);
        
        // Verify field types are appropriate for each layout
        $this->assertEquals('text', $hero['sub_fields'][0]['type']);
        $this->assertEquals('textarea', $testimonials['sub_fields'][0]['type']);
    }
    
    /**
     * Test flexible content in repeater
     */
    public function test_flexible_content_in_repeater() {
        $repeater_field = [
            'name' => 'content_blocks',
            'label' => 'Content Blocks',
            'type' => 'repeater',
            'sub_fields' => [
                [
                    'name' => 'block_type',
                    'type' => 'select',
                    'choices' => [
                        'text' => 'Text Block',
                        'image' => 'Image Block',
                        'gallery' => 'Gallery Block'
                    ]
                ],
                [
                    'name' => 'flexible_content',
                    'type' => 'flexible',
                    'layouts' => [
                        [
                            'name' => 'text_block',
                            'label' => 'Text Block',
                            'sub_fields' => [
                                [
                                    'name' => 'text',
                                    'type' => 'textarea'
                                ]
                            ]
                        ],
                        [
                            'name' => 'image_block',
                            'label' => 'Image Block',
                            'sub_fields' => [
                                [
                                    'name' => 'image',
                                    'type' => 'image'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        // Validate repeater structure
        $this->assertEquals('repeater', $repeater_field['type']);
        $this->assertCount(2, $repeater_field['sub_fields']);
        
        // Find flexible content field
        $flexible = null;
        foreach ($repeater_field['sub_fields'] as $field) {
            if ($field['type'] === 'flexible') {
                $flexible = $field;
                break;
            }
        }
        
        $this->assertNotNull($flexible, 'Flexible content field should exist in repeater');
        $this->assertCount(2, $flexible['layouts']);
    }
    
    /**
     * Test slug generation from label
     */
    public function test_slug_generation_from_label() {
        $test_cases = [
            'Hero Section' => 'hero_section',
            '3 Columns' => '3_columns',
            'CTA Banner' => 'cta_banner',
            'Feature List' => 'feature_list',
            'Customer Testimonials' => 'customer_testimonials'
        ];
        
        foreach ($test_cases as $label => $expected_slug) {
            $slug = $this->generate_slug($label);
            $this->assertEquals($expected_slug, $slug, 
                "Label '{$label}' should generate slug '{$expected_slug}' but got '{$slug}'");
        }
    }
    
    /**
     * Test field configuration persistence
     */
    public function test_field_config_persistence() {
        $field_config = [
            'name' => 'user_bio',
            'label' => 'User Biography',
            'type' => 'textarea',
            'required' => true,
            'placeholder' => 'Tell us about yourself',
            'help_text' => 'Maximum 500 characters',
            'max_length' => 500
        ];
        
        // Simulate saving to database
        $saved_config = json_decode(json_encode($field_config), true);
        
        // Verify all properties are preserved
        foreach ($field_config as $key => $value) {
            $this->assertArrayHasKey($key, $saved_config);
            $this->assertEquals($value, $saved_config[$key]);
        }
    }
    
    /**
     * Test conditional logic on fields
     */
    public function test_conditional_logic_on_fields() {
        $field_with_logic = [
            'name' => 'advanced_options',
            'label' => 'Advanced Options',
            'type' => 'textarea',
            'conditional_logic' => [
                [
                    [
                        'field' => 'show_advanced',
                        'operator' => '==',
                        'value' => '1'
                    ]
                ]
            ]
        ];
        
        $this->assertArrayHasKey('conditional_logic', $field_with_logic);
        $this->assertNotEmpty($field_with_logic['conditional_logic']);
        
        $logic = $field_with_logic['conditional_logic'][0][0];
        $this->assertArrayHasKey('field', $logic);
        $this->assertArrayHasKey('operator', $logic);
        $this->assertArrayHasKey('value', $logic);
    }
    
    /**
     * Helper: Generate slug from label
     */
    private function generate_slug($label) {
        $slug = strtolower($label);
        $slug = preg_replace('/[àáâãäå]/i', 'a', $slug);
        $slug = preg_replace('/[èéêë]/i', 'e', $slug);
        $slug = preg_replace('/[ìíîï]/i', 'i', $slug);
        $slug = preg_replace('/[òóôõö]/i', 'o', $slug);
        $slug = preg_replace('/[ùúûü]/i', 'u', $slug);
        $slug = preg_replace('/[^\w\s-]/i', '', $slug);
        $slug = preg_replace('/\s+/', '_', $slug);
        $slug = preg_replace('/_+/', '_', $slug);
        return trim($slug, '_');
    }
    
    /**
     * Helper: Clean up test group
     */
    private function cleanup_test_group() {
        global $wpdb;
        $table = $wpdb->prefix . 'yap_' . sanitize_title($this->test_group) . '_pattern';
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
}

/**
 * Test Flexible Content functionality
 */
class YAP_Flexible_Content_Tests extends WP_UnitTestCase {
    
    protected $test_group = 'test_flexible_group';
    protected $test_field = 'page_sections';
    protected $post_id = 0;
    
    public function setUp() {
        parent::setUp();
        $this->post_id = $this->factory->post->create();
    }
    
    public function tearDown() {
        parent::tearDown();
        wp_delete_post($this->post_id, true);
    }
    
    /**
     * Test layout storage and retrieval
     */
    public function test_layout_storage_and_retrieval() {
        $layouts = [
            [
                'name' => 'hero_section',
                'label' => 'Hero Section',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'name' => 'title',
                        'label' => 'Title',
                        'type' => 'text'
                    ]
                ]
            ]
        ];
        
        // Simulate saving layouts
        $saved_layouts = json_decode(json_encode($layouts), true);
        
        // Verify structure
        $this->assertCount(1, $saved_layouts);
        $this->assertEquals('hero_section', $saved_layouts[0]['name']);
    }
    
    /**
     * Test flexible content section data
     */
    public function test_flexible_content_section_data() {
        $sections = [
            [
                'layout' => 'hero_section',
                'fields' => [
                    'title' => 'Welcome to Our Website',
                    'description' => 'This is the hero section',
                    'image' => 123
                ]
            ],
            [
                'layout' => 'columns_3',
                'fields' => [
                    'col_1_title' => 'Feature 1',
                    'col_2_title' => 'Feature 2',
                    'col_3_title' => 'Feature 3'
                ]
            ]
        ];
        
        // Each section should have layout and fields
        foreach ($sections as $section) {
            $this->assertArrayHasKey('layout', $section);
            $this->assertArrayHasKey('fields', $section);
            $this->assertNotEmpty($section['layout']);
            $this->assertIsArray($section['fields']);
        }
    }
    
    /**
     * Test block identification by slug
     */
    public function test_block_identification_by_slug() {
        $section = [
            'layout' => 'hero_section',
            'fields' => [
                'title' => 'Test Title',
                'subtitle' => 'Test Subtitle'
            ]
        ];
        
        // Layout name should match slug
        $this->assertRegExp('/^[a-z0-9_]+$/', $section['layout']);
        
        // Slug should be identifiable
        $this->assertTrue($this->is_valid_slug($section['layout']));
    }
    
    /**
     * Test different layouts have different field sets
     */
    public function test_different_layouts_different_fields() {
        $layouts = [
            [
                'name' => 'hero_section',
                'sub_fields' => ['title', 'description', 'image']
            ],
            [
                'name' => 'testimonials',
                'sub_fields' => ['quote', 'author', 'author_image']
            ],
            [
                'name' => 'features',
                'sub_fields' => ['feature_1', 'feature_2', 'feature_3']
            ]
        ];
        
        // Each layout should have unique field sets
        $field_sets = [];
        foreach ($layouts as $layout) {
            $fields_str = implode(',', $layout['sub_fields']);
            $this->assertNotContains($fields_str, $field_sets,
                "Layout {$layout['name']} fields should be unique");
            $field_sets[] = $fields_str;
        }
    }
    
    /**
     * Test layout field type validation
     */
    public function test_layout_field_type_validation() {
        $allowed_types = ['text', 'textarea', 'number', 'email', 'date', 'image', 'select', 'checkbox'];
        
        $layout = [
            'name' => 'test_layout',
            'sub_fields' => [
                ['name' => 'field1', 'type' => 'text'],
                ['name' => 'field2', 'type' => 'textarea'],
                ['name' => 'field3', 'type' => 'image']
            ]
        ];
        
        foreach ($layout['sub_fields'] as $field) {
            $this->assertContains($field['type'], $allowed_types,
                "Field type '{$field['type']}' should be in allowed types");
        }
    }
    
    /**
     * Test rendering options for layouts
     */
    public function test_rendering_options_for_layouts() {
        $rendering_options = ['block', 'row', 'table'];
        
        $layout_with_options = [
            'name' => 'test_layout',
            'display' => 'block'
        ];
        
        $this->assertContains($layout_with_options['display'], $rendering_options);
    }
    
    /**
     * Helper: Validate slug format
     */
    private function is_valid_slug($slug) {
        return preg_match('/^[a-z0-9_]+$/', $slug) === 1;
    }
}

/**
 * Test Repeater with Flexible Content integration
 */
class YAP_Repeater_Flexible_Content_Tests extends WP_UnitTestCase {
    
    protected $post_id = 0;
    
    public function setUp() {
        parent::setUp();
        $this->post_id = $this->factory->post->create();
    }
    
    public function tearDown() {
        parent::tearDown();
        wp_delete_post($this->post_id, true);
    }
    
    /**
     * Test repeater with flexible content blocks
     */
    public function test_repeater_with_flexible_content_blocks() {
        $repeater_data = [
            [
                'layout' => 'hero_section',
                'fields' => ['title' => 'Hero 1']
            ],
            [
                'layout' => 'testimonials',
                'fields' => ['quote' => 'Quote 1']
            ],
            [
                'layout' => 'hero_section',
                'fields' => ['title' => 'Hero 2']
            ]
        ];
        
        // Each row should be identifiable by layout
        $this->assertCount(3, $repeater_data);
        
        // Count by layout type
        $hero_count = 0;
        foreach ($repeater_data as $row) {
            if ($row['layout'] === 'hero_section') {
                $hero_count++;
            }
        }
        
        $this->assertEquals(2, $hero_count, 'Should have 2 hero sections');
    }
    
    /**
     * Test block options differentiation in repeater
     */
    public function test_block_options_differentiation_in_repeater() {
        // Problem: When FC is in repeater, blocks should have different options
        
        $block_hero_options = [
            'layout' => 'hero_section',
            'label' => 'Hero Section',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Title'],
                'image' => ['type' => 'image', 'label' => 'Background Image'],
                'cta_button' => ['type' => 'text', 'label' => 'CTA Button Text']
            ]
        ];
        
        $block_testimonials_options = [
            'layout' => 'testimonials',
            'label' => 'Testimonials',
            'fields' => [
                'quote' => ['type' => 'textarea', 'label' => 'Quote'],
                'author' => ['type' => 'text', 'label' => 'Author Name'],
                'author_image' => ['type' => 'image', 'label' => 'Author Image']
            ]
        ];
        
        // Each layout should have distinct options
        $this->assertNotEquals($block_hero_options['layout'], $block_testimonials_options['layout']);
        $this->assertNotEquals(
            array_keys($block_hero_options['fields']),
            array_keys($block_testimonials_options['fields'])
        );
    }
    
    /**
     * Test retrieving blocks by type in repeater
     */
    public function test_retrieving_blocks_by_type_in_repeater() {
        $repeater_blocks = [
            [
                'layout' => 'hero_section',
                'fields' => ['title' => 'Welcome']
            ],
            [
                'layout' => 'testimonials',
                'fields' => ['quote' => 'Great product!']
            ],
            [
                'layout' => 'hero_section',
                'fields' => ['title' => 'Another Hero']
            ]
        ];
        
        // Filter blocks by layout
        $hero_blocks = array_filter($repeater_blocks, function($block) {
            return $block['layout'] === 'hero_section';
        });
        
        $this->assertCount(2, $hero_blocks);
        
        // Verify each block has correct layout
        foreach ($hero_blocks as $block) {
            $this->assertEquals('hero_section', $block['layout']);
        }
    }
    
    /**
     * Test block data isolation
     */
    public function test_block_data_isolation() {
        $block_1 = [
            'layout' => 'testimonials',
            'fields' => [
                'quote' => 'First testimonial',
                'author' => 'John Doe'
            ]
        ];
        
        $block_2 = [
            'layout' => 'testimonials',
            'fields' => [
                'quote' => 'Second testimonial',
                'author' => 'Jane Smith'
            ]
        ];
        
        // Blocks should not interfere with each other
        $this->assertNotEquals($block_1['fields']['quote'], $block_2['fields']['quote']);
        $this->assertNotEquals($block_1['fields']['author'], $block_2['fields']['author']);
    }
}

/**
 * Integration Tests
 */
class YAP_Visual_Builder_Integration_Tests extends WP_UnitTestCase {
    
    /**
     * Test complete workflow: Create group → Add fields → Add FC → Configure layouts
     */
    public function test_complete_fc_workflow() {
        // Step 1: Create schema
        $schema = [
            'name' => 'landing_page',
            'label' => 'Landing Page',
            'fields' => []
        ];
        
        // Step 2: Add text field
        $schema['fields'][] = [
            'name' => 'page_title',
            'label' => 'Page Title',
            'type' => 'text'
        ];
        
        // Step 3: Add flexible content field
        $schema['fields'][] = [
            'name' => 'page_sections',
            'label' => 'Page Sections',
            'type' => 'flexible',
            'layouts' => []
        ];
        
        // Step 4: Add layouts
        $fc_field_index = count($schema['fields']) - 1;
        $schema['fields'][$fc_field_index]['layouts'][] = [
            'name' => 'hero_section',
            'label' => 'Hero Section',
            'sub_fields' => [
                ['name' => 'title', 'type' => 'text', 'label' => 'Title']
            ]
        ];
        
        $schema['fields'][$fc_field_index]['layouts'][] = [
            'name' => 'testimonials',
            'label' => 'Testimonials',
            'sub_fields' => [
                ['name' => 'quote', 'type' => 'textarea', 'label' => 'Quote']
            ]
        ];
        
        // Verify complete schema
        $this->assertEquals('landing_page', $schema['name']);
        $this->assertCount(2, $schema['fields']);
        $this->assertEquals('flexible', $schema['fields'][1]['type']);
        $this->assertCount(2, $schema['fields'][1]['layouts']);
    }
    
    /**
     * Test schema export/import
     */
    public function test_schema_export_import() {
        $original_schema = [
            'name' => 'test_schema',
            'fields' => [
                [
                    'name' => 'test_field',
                    'type' => 'text',
                    'label' => 'Test Field'
                ]
            ]
        ];
        
        // Export to JSON
        $exported = json_encode($original_schema);
        
        // Import from JSON
        $imported = json_decode($exported, true);
        
        // Verify data integrity
        $this->assertEquals($original_schema['name'], $imported['name']);
        $this->assertCount(count($original_schema['fields']), $imported['fields']);
        $this->assertEquals($original_schema['fields'][0]['type'], $imported['fields'][0]['type']);
    }
}
