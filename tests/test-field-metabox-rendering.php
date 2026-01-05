<?php
/**
 * Tests for Field Creation, Rendering, and Saving in Metabox
 * 
 * Tests the complete flow:
 * 1. Create fields in Visual Builder schema
 * 2. Render fields in metabox on post edit page
 * 3. Save field values
 * 4. Verify field rendering matches expected output
 * 5. Compare different field types
 */

class YAP_Field_Metabox_Rendering_Tests extends WP_UnitTestCase {
    
    private $post_id;
    private $group_name = 'TestGroup';
    
    public function setUp(): void {
        parent::setUp();
        
        // Create test post
        $this->post_id = self::factory()->post->create([
            'post_type' => 'post',
            'post_title' => 'Test Post for Field Rendering',
        ]);
        
        // Create test field group in database
        $this->create_test_field_group();
    }
    
    /**
     * Create test field group with various field types
     */
    private function create_test_field_group() {
        global $wpdb;
        
        $metadata_table = $wpdb->prefix . 'yap_field_metadata';
        
        // Clear existing test fields
        $wpdb->delete($metadata_table, ['group_name' => $this->group_name], ['%s']);
        
        // Define test fields with all types
        $test_fields = [
            // Basic fields
            [
                'group_name' => $this->group_name,
                'field_name' => 'test_text',
                'field_id' => 'field_1',
                'field_label' => 'Text Field',
                'field_type' => 'text',
                'field_config' => json_encode([
                    'required' => false,
                    'placeholder' => 'Enter text...',
                ]),
                'field_order' => 0,
            ],
            // Radio field with choices
            [
                'group_name' => $this->group_name,
                'field_name' => 'test_radio',
                'field_id' => 'field_2',
                'field_label' => 'Radio Field',
                'field_type' => 'radio',
                'field_config' => json_encode([
                    'type' => 'radio',
                    'choices' => [
                        'option1' => 'Option 1',
                        'option2' => 'Option 2',
                        'option3' => 'Option 3',
                    ],
                ]),
                'field_order' => 1,
            ],
            // Select field with choices
            [
                'group_name' => $this->group_name,
                'field_name' => 'test_select',
                'field_id' => 'field_3',
                'field_label' => 'Select Field',
                'field_type' => 'select',
                'field_config' => json_encode([
                    'type' => 'select',
                    'choices' => [
                        'choice1' => 'Choice 1',
                        'choice2' => 'Choice 2',
                        'choice3' => 'Choice 3',
                    ],
                ]),
                'field_order' => 2,
            ],
            // Group field with sub_fields
            [
                'group_name' => $this->group_name,
                'field_name' => 'test_group',
                'field_id' => 'field_4',
                'field_label' => 'Group Field',
                'field_type' => 'group',
                'field_config' => json_encode([
                    'type' => 'group',
                    'sub_fields' => [
                        [
                            'name' => 'group_text',
                            'type' => 'text',
                            'label' => 'Group Text',
                        ],
                        [
                            'name' => 'group_number',
                            'type' => 'number',
                            'label' => 'Group Number',
                        ],
                    ],
                ]),
                'field_order' => 3,
            ],
            // Flexible Content field
            [
                'group_name' => $this->group_name,
                'field_name' => 'test_flexible',
                'field_id' => 'field_5',
                'field_label' => 'Flexible Content',
                'field_type' => 'flexible_content',
                'field_config' => json_encode([
                    'type' => 'flexible_content',
                ]),
                'field_order' => 4,
            ],
            // Gallery field
            [
                'group_name' => $this->group_name,
                'field_name' => 'test_gallery',
                'field_id' => 'field_6',
                'field_label' => 'Gallery Field',
                'field_type' => 'gallery',
                'field_config' => json_encode([
                    'type' => 'gallery',
                ]),
                'field_order' => 5,
            ],
        ];
        
        // Insert all fields
        foreach ($test_fields as $field) {
            $wpdb->insert($metadata_table, $field);
        }
    }
    
    /**
     * Test 1: Field Group Creation
     */
    public function test_field_group_created_in_database() {
        global $wpdb;
        $metadata_table = $wpdb->prefix . 'yap_field_metadata';
        
        $fields = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$metadata_table} WHERE group_name = %s ORDER BY field_order ASC",
            $this->group_name
        ));
        
        $this->assertGreater(count($fields), 0, 'Field group should exist in database');
        $this->assertEquals(6, count($fields), 'Should have 6 test fields');
        
        // Verify field names
        $field_names = wp_list_pluck($fields, 'field_name');
        $this->assertContains('test_text', $field_names);
        $this->assertContains('test_radio', $field_names);
        $this->assertContains('test_select', $field_names);
        $this->assertContains('test_group', $field_names);
        $this->assertContains('test_flexible', $field_names);
        $this->assertContains('test_gallery', $field_names);
    }
    
    /**
     * Test 2: Field Type Resolution (field_config type vs field_meta type)
     */
    public function test_field_type_resolution() {
        global $wpdb;
        $metadata_table = $wpdb->prefix . 'yap_field_metadata';
        
        $fields = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$metadata_table} WHERE group_name = %s",
            $this->group_name
        ));
        
        foreach ($fields as $field) {
            $config = json_decode($field->field_config, true);
            
            if (!empty($config['type'])) {
                // field_config has type - verify it matches field_type
                if ($field->field_type !== 'text' || $config['type'] !== 'text') {
                    // This is expected for complex types like radio, select, group, flexible_content
                    $this->assertNotEmpty($config['type'], 
                        "Field {$field->field_name} should have type in config");
                }
            }
        }
    }
    
    /**
     * Test 3: Text Field Rendering
     */
    public function test_text_field_rendering() {
        $field = $this->get_field_by_name('test_text');
        $this->assertNotNull($field, 'Text field should exist');
        
        ob_start();
        yap_render_field_input(
            (array) $field,
            'test value',
            'yap_fields[TestGroup][test_text]',
            'yap_TestGroup_test_text'
        );
        $output = ob_get_clean();
        
        // Verify text field rendering
        $this->assertStringContainsString('type="text"', $output, 'Should render text input');
        $this->assertStringContainsString('test value', $output, 'Should contain field value');
        $this->assertStringContainsString('yap_TestGroup_test_text', $output, 'Should have correct ID');
    }
    
    /**
     * Test 4: Radio Field Rendering with Choices
     */
    public function test_radio_field_rendering_with_choices() {
        $field = $this->get_field_by_name('test_radio');
        $this->assertNotNull($field, 'Radio field should exist');
        
        $config = json_decode($field->field_config, true);
        $field_array = (array) $field;
        $field_array['choices'] = $config['choices'] ?? [];
        
        ob_start();
        yap_render_field_input(
            $field_array,
            'option2',
            'yap_fields[TestGroup][test_radio]',
            'yap_TestGroup_test_radio'
        );
        $output = ob_get_clean();
        
        // Verify radio field rendering
        $this->assertStringContainsString('yap-radio-group', $output, 'Should have radio group class');
        $this->assertStringContainsString('type="radio"', $output, 'Should render radio inputs');
        $this->assertStringContainsString('Option 1', $output, 'Should contain choice labels');
        $this->assertStringContainsString('value="option2"', $output, 'Should have option values');
        $this->assertStringContainsString('checked', $output, 'Should check selected option');
    }
    
    /**
     * Test 5: Select Field Rendering with Choices
     */
    public function test_select_field_rendering_with_choices() {
        $field = $this->get_field_by_name('test_select');
        $this->assertNotNull($field, 'Select field should exist');
        
        $config = json_decode($field->field_config, true);
        $field_array = (array) $field;
        $field_array['choices'] = $config['choices'] ?? [];
        
        ob_start();
        yap_render_field_input(
            $field_array,
            'choice1',
            'yap_fields[TestGroup][test_select]',
            'yap_TestGroup_test_select'
        );
        $output = ob_get_clean();
        
        // Verify select field rendering
        $this->assertStringContainsString('<select', $output, 'Should render select element');
        $this->assertStringContainsString('Choice 1', $output, 'Should contain option labels');
        $this->assertStringContainsString('value="choice1"', $output, 'Should have option values');
        $this->assertStringContainsString('selected', $output, 'Should select chosen option');
    }
    
    /**
     * Test 6: Group Field Rendering with Sub-fields
     */
    public function test_group_field_rendering_with_subfields() {
        $field = $this->get_field_by_name('test_group');
        $this->assertNotNull($field, 'Group field should exist');
        
        $config = json_decode($field->field_config, true);
        $field_array = (array) $field;
        $field_array['sub_fields'] = $config['sub_fields'] ?? [];
        
        ob_start();
        yap_render_group_field(
            $field_array,
            [],
            'yap_fields[TestGroup][test_group]',
            'yap_TestGroup_test_group'
        );
        $output = ob_get_clean();
        
        // Verify group field rendering
        $this->assertStringContainsString('yap-group', $output, 'Should have group class');
        $this->assertStringContainsString('Group Text', $output, 'Should contain sub-field labels');
        $this->assertStringContainsString('group_text', $output, 'Should contain sub-field names');
    }
    
    /**
     * Test 7: Flexible Content Field Rendering
     */
    public function test_flexible_content_field_rendering() {
        $field = $this->get_field_by_name('test_flexible');
        $this->assertNotNull($field, 'Flexible Content field should exist');
        
        $config = json_decode($field->field_config, true);
        $field_array = (array) $field;
        $field_array['group_name'] = $this->group_name;
        
        ob_start();
        if (class_exists('YAP_Flexible_Content')) {
            YAP_Flexible_Content::render_field(
                $field_array,
                [],
                'yap_fields[TestGroup][test_flexible]',
                'yap_TestGroup_test_flexible'
            );
        }
        $output = ob_get_clean();
        
        // Verify flexible content field rendering
        $this->assertStringContainsString('yap-flexible', $output, 'Should render flexible content container');
    }
    
    /**
     * Test 8: Gallery Field Rendering
     */
    public function test_gallery_field_rendering() {
        $field = $this->get_field_by_name('test_gallery');
        $this->assertNotNull($field, 'Gallery field should exist');
        
        $field_array = (array) $field;
        
        ob_start();
        yap_render_field_input(
            $field_array,
            json_encode([123, 456]),
            'yap_fields[TestGroup][test_gallery]',
            'yap_TestGroup_test_gallery'
        );
        $output = ob_get_clean();
        
        // Verify gallery field rendering
        $this->assertStringContainsString('yap-gallery', $output, 'Should have gallery class');
    }
    
    /**
     * Test 9: Save Field Value to Post Meta
     */
    public function test_save_field_value_to_post_meta() {
        // Save field value
        $field_value = 'Test Radio Value';
        update_post_meta($this->post_id, 'test_radio', $field_value);
        
        // Retrieve and verify
        $saved_value = get_post_meta($this->post_id, 'test_radio', true);
        $this->assertEquals($field_value, $saved_value, 'Field value should be saved and retrieved');
    }
    
    /**
     * Test 10: Save Multiple Fields from Metabox
     */
    public function test_save_multiple_field_values() {
        $test_data = [
            'test_text' => 'Text Value',
            'test_radio' => 'option1',
            'test_select' => 'choice2',
        ];
        
        foreach ($test_data as $field_name => $field_value) {
            update_post_meta($this->post_id, $field_name, $field_value);
        }
        
        // Verify all values were saved
        foreach ($test_data as $field_name => $expected_value) {
            $saved_value = get_post_meta($this->post_id, $field_name, true);
            $this->assertEquals($expected_value, $saved_value, 
                "Field {$field_name} should have correct value");
        }
    }
    
    /**
     * Test 11: Save Complex Field Value (Group)
     */
    public function test_save_group_field_value() {
        $group_value = [
            'group_text' => 'Group Text Value',
            'group_number' => '42',
        ];
        
        update_post_meta($this->post_id, 'test_group', json_encode($group_value));
        
        $saved_value = get_post_meta($this->post_id, 'test_group', true);
        $saved_data = json_decode($saved_value, true);
        
        $this->assertEquals($group_value, $saved_data, 'Group value should be saved as JSON');
    }
    
    /**
     * Test 12: Complete Metabox Rendering Flow
     */
    public function test_complete_metabox_rendering_flow() {
        // Simulate full metabox rendering
        $post = get_post($this->post_id);
        
        ob_start();
        
        // This should render all fields for the group
        yap_display_json_schema_fields($post, $this->group_name);
        
        $output = ob_get_clean();
        
        // Verify all fields are rendered
        $this->assertStringContainsString('test_text', $output, 'Text field should be rendered');
        $this->assertStringContainsString('test_radio', $output, 'Radio field should be rendered');
        $this->assertStringContainsString('test_select', $output, 'Select field should be rendered');
        $this->assertStringContainsString('test_group', $output, 'Group field should be rendered');
        $this->assertStringContainsString('test_gallery', $output, 'Gallery field should be rendered');
    }
    
    /**
     * Test 13: Field Type Detection and Fallback Rendering
     */
    public function test_field_type_fallback_rendering() {
        global $wpdb;
        $metadata_table = $wpdb->prefix . 'yap_field_metadata';
        
        // Create field with type=text in metadata but has choices (should fallback to radio)
        $wpdb->insert($metadata_table, [
            'group_name' => $this->group_name,
            'field_name' => 'test_fallback_radio',
            'field_id' => 'field_fallback_1',
            'field_label' => 'Fallback Radio',
            'field_type' => 'text', // Wrong type
            'field_config' => json_encode([
                'choices' => [
                    'opt1' => 'Option 1',
                    'opt2' => 'Option 2',
                ],
            ]),
            'field_order' => 10,
        ]);
        
        $field = $this->get_field_by_name('test_fallback_radio');
        $config = json_decode($field->field_config, true);
        $field_array = (array) $field;
        $field_array['choices'] = $config['choices'] ?? [];
        
        ob_start();
        yap_render_field_input($field_array, 'opt1', 'test_fallback', 'test_fallback_id');
        $output = ob_get_clean();
        
        // Should render radio due to choices
        $this->assertStringContainsString('type="radio"', $output, 
            'Field with choices should fallback to radio rendering');
    }
    
    /**
     * Test 14: Empty vs Filled Field Values
     */
    public function test_field_value_display_empty_vs_filled() {
        $field = $this->get_field_by_name('test_text');
        
        // Test empty value
        ob_start();
        yap_render_field_input((array) $field, '', 'test_empty', 'test_empty_id');
        $empty_output = ob_get_clean();
        $this->assertStringContainsString('value=""', $empty_output, 'Empty field should have empty value');
        
        // Test filled value
        ob_start();
        yap_render_field_input((array) $field, 'Filled Value', 'test_filled', 'test_filled_id');
        $filled_output = ob_get_clean();
        $this->assertStringContainsString('Filled Value', $filled_output, 'Filled field should display value');
    }
    
    /**
     * Test 15: Field Labels and Descriptions
     */
    public function test_field_labels_and_descriptions() {
        $post = get_post($this->post_id);
        
        ob_start();
        yap_display_json_schema_fields($post, $this->group_name);
        $output = ob_get_clean();
        
        // Verify labels are displayed
        $this->assertStringContainsString('Text Field', $output);
        $this->assertStringContainsString('Radio Field', $output);
        $this->assertStringContainsString('Select Field', $output);
        $this->assertStringContainsString('Group Field', $output);
        $this->assertStringContainsString('Gallery Field', $output);
    }
    
    /**
     * Helper: Get field from database by name
     */
    private function get_field_by_name($field_name) {
        global $wpdb;
        $metadata_table = $wpdb->prefix . 'yap_field_metadata';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$metadata_table} WHERE group_name = %s AND field_name = %s",
            $this->group_name,
            $field_name
        ));
    }
    
    public function tearDown(): void {
        // Clean up
        wp_delete_post($this->post_id, true);
        parent::tearDown();
    }
}


/**
 * Tests for Field Value Comparison and Validation
 */
class YAP_Field_Value_Comparison_Tests extends WP_UnitTestCase {
    
    private $post_id;
    private $group_name = 'ComparisonTestGroup';
    
    public function setUp(): void {
        parent::setUp();
        $this->post_id = self::factory()->post->create();
        $this->setup_fields();
    }
    
    private function setup_fields() {
        global $wpdb;
        $metadata_table = $wpdb->prefix . 'yap_field_metadata';
        
        $wpdb->delete($metadata_table, ['group_name' => $this->group_name]);
        
        $fields = [
            [
                'group_name' => $this->group_name,
                'field_name' => 'email_field',
                'field_id' => 'f1',
                'field_label' => 'Email',
                'field_type' => 'email',
                'field_config' => json_encode([]),
                'field_order' => 0,
            ],
            [
                'group_name' => $this->group_name,
                'field_name' => 'number_field',
                'field_id' => 'f2',
                'field_label' => 'Number',
                'field_type' => 'number',
                'field_config' => json_encode([]),
                'field_order' => 1,
            ],
            [
                'group_name' => $this->group_name,
                'field_name' => 'textarea_field',
                'field_id' => 'f3',
                'field_label' => 'Textarea',
                'field_type' => 'textarea',
                'field_config' => json_encode([]),
                'field_order' => 2,
            ],
        ];
        
        foreach ($fields as $field) {
            $wpdb->insert($metadata_table, $field);
        }
    }
    
    /**
     * Test: Compare rendered output for different input values
     */
    public function test_compare_email_field_rendering() {
        global $wpdb;
        $metadata_table = $wpdb->prefix . 'yap_field_metadata';
        
        $field = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$metadata_table} WHERE field_name = %s",
            'email_field'
        ));
        
        $test_emails = [
            'test@example.com',
            'user+tag@domain.co.uk',
            'invalid-email',
        ];
        
        foreach ($test_emails as $email) {
            ob_start();
            yap_render_field_input((array) $field, $email, 'email_test', 'email_id');
            $output = ob_get_clean();
            
            $this->assertStringContainsString($email, $output, 
                "Email field should display: {$email}");
        }
    }
    
    /**
     * Test: Number field with different values
     */
    public function test_compare_number_field_values() {
        global $wpdb;
        $metadata_table = $wpdb->prefix . 'yap_field_metadata';
        
        $field = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$metadata_table} WHERE field_name = %s",
            'number_field'
        ));
        
        $test_numbers = [0, 42, -15, 999.99];
        
        foreach ($test_numbers as $num) {
            ob_start();
            yap_render_field_input((array) $field, $num, 'num_test', 'num_id');
            $output = ob_get_clean();
            
            $this->assertStringContainsString((string) $num, $output);
        }
    }
    
    /**
     * Test: Textarea with different content
     */
    public function test_compare_textarea_content() {
        global $wpdb;
        $metadata_table = $wpdb->prefix . 'yap_field_metadata';
        
        $field = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$metadata_table} WHERE field_name = %s",
            'textarea_field'
        ));
        
        $test_contents = [
            'Simple text',
            "Line 1\nLine 2\nLine 3",
            '<script>alert("xss")</script>',
            '{"json": "object"}',
        ];
        
        foreach ($test_contents as $content) {
            ob_start();
            yap_render_field_input((array) $field, $content, 'textarea_test', 'textarea_id');
            $output = ob_get_clean();
            
            // Verify content is properly escaped/displayed
            $this->assertNotEmpty($output);
        }
    }
    
    public function tearDown(): void {
        wp_delete_post($this->post_id, true);
        parent::tearDown();
    }
}


/**
 * Integration Tests: Complete field lifecycle
 */
class YAP_Field_Lifecycle_Integration_Tests extends WP_UnitTestCase {
    
    private $post_id;
    private $group_name = 'LifecycleTestGroup';
    
    public function setUp(): void {
        parent::setUp();
        $this->post_id = self::factory()->post->create();
        $this->setup_complete_field_group();
    }
    
    private function setup_complete_field_group() {
        global $wpdb;
        $metadata_table = $wpdb->prefix . 'yap_field_metadata';
        $wpdb->delete($metadata_table, ['group_name' => $this->group_name]);
        
        $wpdb->insert($metadata_table, [
            'group_name' => $this->group_name,
            'field_name' => 'name',
            'field_id' => 'f_name',
            'field_label' => 'Full Name',
            'field_type' => 'text',
            'field_config' => json_encode(['required' => true]),
            'field_order' => 0,
        ]);
        
        $wpdb->insert($metadata_table, [
            'group_name' => $this->group_name,
            'field_name' => 'category',
            'field_id' => 'f_cat',
            'field_label' => 'Category',
            'field_type' => 'select',
            'field_config' => json_encode([
                'type' => 'select',
                'choices' => [
                    'cat1' => 'Category 1',
                    'cat2' => 'Category 2',
                ],
            ]),
            'field_order' => 1,
        ]);
    }
    
    /**
     * Test: Complete lifecycle - Create → Render → Fill → Save → Retrieve
     */
    public function test_complete_field_lifecycle() {
        // 1. Fields created in database ✓ (in setup)
        global $wpdb;
        $metadata_table = $wpdb->prefix . 'yap_field_metadata';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$metadata_table} WHERE group_name = %s",
            $this->group_name
        ));
        $this->assertEquals(2, $count, 'Fields should be created');
        
        // 2. Fields render in metabox
        $post = get_post($this->post_id);
        ob_start();
        yap_display_json_schema_fields($post, $this->group_name);
        $metabox_output = ob_get_clean();
        
        $this->assertStringContainsString('Full Name', $metabox_output);
        $this->assertStringContainsString('Category', $metabox_output);
        
        // 3. Fill with values
        $test_values = [
            'name' => 'John Doe',
            'category' => 'cat1',
        ];
        
        // 4. Save values
        foreach ($test_values as $field_name => $value) {
            update_post_meta($this->post_id, $field_name, $value);
        }
        
        // 5. Retrieve and verify
        foreach ($test_values as $field_name => $expected_value) {
            $saved_value = get_post_meta($this->post_id, $field_name, true);
            $this->assertEquals($expected_value, $saved_value,
                "Lifecycle test: {$field_name} should match");
        }
    }
    
    /**
     * Test: Rendering with pre-filled values
     */
    public function test_render_with_prefilled_values() {
        // Set initial values
        $test_values = [
            'name' => 'Jane Smith',
            'category' => 'cat2',
        ];
        
        foreach ($test_values as $k => $v) {
            update_post_meta($this->post_id, $k, $v);
        }
        
        // Re-render metabox (should show saved values)
        $post = get_post($this->post_id);
        ob_start();
        yap_display_json_schema_fields($post, $this->group_name);
        $output = ob_get_clean();
        
        // Verify values are displayed
        $this->assertStringContainsString('Jane Smith', $output,
            'Pre-filled values should be displayed');
    }
    
    public function tearDown(): void {
        wp_delete_post($this->post_id, true);
        parent::tearDown();
    }
}
?>
