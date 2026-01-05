<?php
/**
 * YAP Visual Schema Builder
 * 
 * Drag & drop builder dla grup pól w stylu Elementor/nocode.
 * Wizualne tworzenie struktur bez kodowania.
 * 
 * Features:
 * - Drag & drop interface
 * - Live preview
 * - Conditional logic builder
 * - Field templates library
 * - Export/Import schemas
 * 
 * @package YetAnotherPlugin
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Visual_Builder {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Admin menu
        add_action('admin_menu', [$this, 'add_builder_page'], 20);
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_builder_assets']);
        
        // AJAX endpoints
        add_action('wp_ajax_yap_builder_save_schema', [$this, 'save_schema']);
        add_action('wp_ajax_yap_builder_load_schema', [$this, 'load_schema']);
        add_action('wp_ajax_yap_builder_preview', [$this, 'generate_preview']);
    }
    
    /**
     * Dodaje stronę buildera do menu
     */
    public function add_builder_page() {
        add_submenu_page(
            'yap-admin-page',
            'Visual Builder',
            '🎨 Visual Builder',
            'manage_options',
            'yap-visual-builder',
            [$this, 'render_builder_page']
        );
    }
    
    /**
     * Enqueue scripts i styles dla buildera
     */
    public function enqueue_builder_assets($hook) {
        if ($hook !== 'yet-another-plugin_page_yap-visual-builder') {
            return;
        }
        
        // Enqueue unified advanced features CSS
        wp_enqueue_style(
            'yap-advanced-features',
            plugin_dir_url(__DIR__) . 'includes/css/yap-advanced-features.css',
            [],
            '1.4.0'
        );
        
        // jQuery UI dla drag & drop
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
        wp_enqueue_script('jquery-ui-sortable');
        
        // Field stabilization system (after jQuery, before builder)
        wp_enqueue_script(
            'yap-field-stabilization',
            plugin_dir_url(__DIR__) . 'includes/js/field-stabilization.js',
            ['jquery'],
            '1.5.0',
            true
        );
        
        // Field presets library (after jQuery, before builder)
        wp_enqueue_script(
            'yap-field-presets',
            plugin_dir_url(__DIR__) . 'includes/js/presets.js',
            ['jquery'],
            '2.0.2',
            true
        );
        
        // Field history/undo-redo system (after jQuery, before builder)
        wp_enqueue_script(
            'yap-field-history',
            plugin_dir_url(__DIR__) . 'includes/js/history.js',
            ['jquery'],
            '2.0.0',
            true
        );
        
        // Builder script (depends on jQuery for wp_localize data, jQuery-UI for functionality)
        wp_enqueue_script(
            'yap-visual-builder',
            plugin_dir_url(__DIR__) . 'includes/js/visual-builder.js',
            ['jquery', 'jquery-ui-droppable', 'jquery-ui-draggable', 'jquery-ui-sortable', 'yap-field-types'],
            '1.4.7',
            true
        );
        
        // Builder extensions (toast, modal, multi-conditions)
        wp_enqueue_script(
            'yap-visual-builder-ext',
            plugin_dir_url(__DIR__) . 'includes/js/visual-builder-extensions.js',
            ['jquery', 'yap-visual-builder'],
            '1.4.0',
            true
        );
        
        // Custom templates/blocks system
        wp_enqueue_script(
            'yap-custom-templates',
            plugin_dir_url(__DIR__) . 'includes/js/custom-templates.js',
            ['jquery', 'yap-visual-builder', 'yap-field-stabilization', 'yap-field-history'],
            '1.1.0',
            true
        );
        
        // Field type settings (type-specific configurations)
        wp_enqueue_script(
            'yap-field-type-settings',
            plugin_dir_url(__DIR__) . 'includes/js/field-type-settings.js',
            ['jquery', 'yap-visual-builder'],
            '1.4.0',
            true
        );
        
        // ===========================
        // TEST SCRIPTS - Always load in Visual Builder
        // ===========================
        // Załaduj testy testów automatycznie w Visual Builderze
        wp_enqueue_script(
            'yap-test-config',
            plugin_dir_url(__DIR__) . 'includes/js/tests/test-config.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_enqueue_script(
            'yap-builder-tests',
            plugin_dir_url(__DIR__) . 'includes/js/tests/visual-builder-field-editing.test.js',
            ['jquery', 'yap-test-config', 'yap-visual-builder'],
            '1.0.0',
            true
        );
        
        wp_enqueue_script(
            'yap-advanced-tests',
            plugin_dir_url(__DIR__) . 'includes/js/tests/visual-builder-advanced.test.js',
            ['jquery', 'yap-test-config', 'yap-visual-builder'],
            '1.0.0',
            true
        );
        
        // Localize data
        wp_localize_script('yap-visual-builder', 'yapBuilder', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yap_builder_nonce'),
            'fieldTypes' => $this->get_field_types(),
            'templates' => $this->get_field_templates(),
            'autoLoadGroup' => isset($_GET['group']) ? sanitize_text_field($_GET['group']) : '',
        ]);
        
        // Debug: Inline script to check if yapBuilder is available
        wp_add_inline_script('yap-visual-builder', "
            if (typeof window.yapBuilder !== 'undefined') {
                console.log('✅ yapBuilder is available');
                console.log('yapBuilder.fieldTypes:', window.yapBuilder.fieldTypes);
            } else {
                console.error('❌ yapBuilder NOT available - wp_localize_script failed!');
            }
        ");
        
        // Additional inline styles for Visual Builder specific layout
        wp_add_inline_style('yap-advanced-features', $this->get_builder_inline_styles());
    }
    
    /**
     * Renderuje stronę buildera
     */
    public function render_builder_page() {
        ?>
        <div class="yap-visual-builder-wrap">
            <div class="notice notice-info" style="margin: 20px 20px 0;">
                <p><strong>ℹ️ What is a Schema?</strong></p>
                <p>A <strong>schema</strong> is another name for a <strong>field group</strong>. Use this builder to:</p>
                <ul style="margin-left: 20px;">
                    <li>📝 Create new field groups visually (drag & drop)</li>
                    <li>✏️ Edit existing field groups</li>
                    <li>🎯 Add conditional logic to fields</li>
                    <li>💾 Save schema = Create database table + field definitions</li>
                </ul>
                <p><strong>How to use:</strong> Enter group name → Drag fields → Configure → Save → Add location rules in "Zarządzaj Grupami"</p>
            </div>
            
            <div class="yap-builder-header">
                <div class="yap-builder-title">
                    <h1>🎨 Visual Schema Builder</h1>
                    <p>Drag & drop builder for field groups</p>
                </div>
                <div class="yap-builder-actions">
                    <select id="yap-builder-group-select" class="yap-select">
                        <option value="">-- Create New Group --</option>
                        <?php
                        global $wpdb;
                        
                        // Get groups from multiple sources
                        $groups = [];
                        
                        // 1. From location_rules (nowy system)
                        $location_groups = $wpdb->get_col(
                            "SELECT DISTINCT group_name FROM {$wpdb->prefix}yap_location_rules WHERE group_name != '' ORDER BY group_name ASC"
                        );
                        $groups = array_merge($groups, $location_groups);
                        
                        // 2. From yap-schemas directory (Visual Builder saves)
                        $schema_dir = WP_CONTENT_DIR . '/yap-schemas/';
                        if (file_exists($schema_dir)) {
                            $schema_files = glob($schema_dir . '*.json');
                            foreach ($schema_files as $file) {
                                $groups[] = basename($file, '.json');
                            }
                        }
                        
                        // 3. From existing wp_yap_* tables (stare grupy)
                        $yap_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}yap_%_pattern'");
                        foreach ($yap_tables as $table) {
                            // Extract group name from wp_yap_GROUPNAME_pattern
                            if (preg_match('/^' . $wpdb->prefix . 'yap_(.+)_pattern$/', $table, $matches)) {
                                $groups[] = $matches[1];
                            }
                        }
                        
                        // Unique and sort
                        $groups = array_unique($groups);
                        sort($groups);
                        
                        foreach ($groups as $group_name) {
                            if (!empty($group_name) && $group_name !== '__unconfigured__') {
                                echo '<option value="' . esc_attr($group_name) . '">' . esc_html(ucwords(str_replace('_', ' ', $group_name))) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <button id="yap-builder-group-settings" class="button" style="display: none;">
                        <span class="dashicons dashicons-admin-generic"></span> Group Settings
                    </button>
                    <button id="yap-builder-save" class="button button-primary">
                        <span class="dashicons dashicons-saved"></span> Save Schema
                    </button>
                    <button id="yap-builder-export" class="button">
                        <span class="dashicons dashicons-download"></span> Export JSON
                    </button>
                    <button id="yap-builder-preview" class="button">
                        <span class="dashicons dashicons-visibility"></span> Preview
                    </button>
                </div>
            </div>
            
            <!-- Location Rules Section -->
            <div class="yap-builder-location-section" id="yap-builder-location-section" style="display: none;">
                <div class="yap-location-card">
                    <div class="yap-location-header">
                        <h2>📍 Gdzie wyświetlić tę grupę pól?</h2>
                        <p class="description">Określ gdzie ta grupa pól powinna być wyświetlana. Możesz dodać wiele reguł.</p>
                    </div>
                    
                    <?php include plugin_dir_path(__FILE__) . 'admin/views/location-rules-ui.php'; ?>
                </div>
            </div>
            
            <div class="yap-builder-container">
                <!-- Left Sidebar: Field Types -->
                <div class="yap-builder-sidebar">
                    <div class="yap-sidebar-section">
                        <h3>Field Types</h3>
                        <div class="yap-field-types">
                            <?php echo $this->render_field_types(); ?>
                        </div>
                    </div>
                    
                    <div class="yap-sidebar-section">
                        <h3>Field Templates</h3>
                        <div class="yap-field-templates">
                            <?php echo $this->render_field_templates(); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Main Canvas: Drop Zone -->
                <div class="yap-builder-canvas">
                    <div class="yap-canvas-header">
                        <input type="text" 
                               id="yap-group-name" 
                               class="yap-group-name-input" 
                               placeholder="Enter group name..."
                               value="">
                        <div class="yap-canvas-mode">
                            <label>
                                <input type="radio" name="canvas_mode" value="edit" checked> Edit Mode
                            </label>
                            <label>
                                <input type="radio" name="canvas_mode" value="preview"> Preview Mode
                            </label>
                        </div>
                    </div>
                    
                    <div id="yap-drop-zone" class="yap-drop-zone">
                        <div class="yap-drop-zone-placeholder">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <p>Drag fields here to build your schema</p>
                        </div>
                    </div>
                </div>
                
                <!-- Right Sidebar: Field Settings & History -->
                <div class="yap-builder-inspector">
                    <!-- Tabs Navigation -->
                    <div class="yap-inspector-tabs">
                        <button class="yap-inspector-tab active" data-tab="settings">
                            <span class="dashicons dashicons-admin-generic"></span>
                            Settings
                        </button>
                        <button class="yap-inspector-tab" data-tab="history">
                            <span class="dashicons dashicons-backup"></span>
                            History
                            <span class="yap-history-badge" style="display:none;"></span>
                        </button>
                    </div>

                    <!-- Settings Tab -->
                    <div class="yap-inspector-header">
                        <h3>Field Settings</h3>
                        <button class="yap-inspector-close">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    
                    <div id="yap-inspector-content" class="yap-inspector-content yap-inspector-tab-content active" data-tab="settings">
                        <p class="yap-inspector-placeholder">Select a field to edit its settings</p>
                    </div>

                    <!-- History Tab -->
                    <div id="yap-inspector-history" class="yap-inspector-tab-content" data-tab="history">
                        <div class="yap-history-controls">
                            <div class="yap-history-actions">
                                <button id="yap-history-undo" class="yap-history-btn" title="Undo (CTRL+Z)">
                                    <span class="dashicons dashicons-undo"></span>
                                </button>
                                <button id="yap-history-redo" class="yap-history-btn" title="Redo (CTRL+Y)">
                                    <span class="dashicons dashicons-redo"></span>
                                </button>
                                <button id="yap-history-clear" class="yap-history-btn" title="Clear history">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                            <div class="yap-history-position">
                                <span id="yap-history-position-text">0/0</span>
                            </div>
                        </div>

                        <div id="yap-history-timeline" class="yap-history-timeline">
                            <p class="yap-history-placeholder">No changes yet</p>
                        </div>

                        <div id="yap-history-stats" class="yap-history-stats">
                            <h4>Statistics</h4>
                            <div id="yap-history-stats-content"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Field Item Template -->
        <script type="text/template" id="yap-field-item-template">
            <div class="yap-field-item" data-field-id="{{id}}" data-field-type="{{type}}">
                <div class="yap-field-item-header">
                    <span class="yap-field-icon">{{icon}}</span>
                    <span class="yap-field-label">{{label}}</span>
                    <div class="yap-field-actions">
                        <button class="yap-field-edit" title="Edit">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button class="yap-field-duplicate" title="Duplicate">
                            <span class="dashicons dashicons-admin-page"></span>
                        </button>
                        <button class="yap-field-delete" title="Delete">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                        <span class="yap-field-drag-handle dashicons dashicons-menu"></span>
                    </div>
                </div>
                <div class="yap-field-item-body">
                    <div class="yap-field-meta">
                        <span class="yap-field-name">{{name}}</span>
                        <span class="yap-field-type-badge">{{type}}</span>
                    </div>
                    {{#if conditional}}
                    <div class="yap-field-conditional">
                        <span class="dashicons dashicons-randomize"></span> Conditional Logic
                    </div>
                    {{/if}}
                </div>
            </div>
        </script>
        
        <!-- Modal: Preview -->
        <div id="yap-preview-modal" class="yap-modal" style="display:none;">
            <div class="yap-modal-overlay"></div>
            <div class="yap-modal-content">
                <div class="yap-modal-header">
                    <h2>Schema Preview</h2>
                    <button class="yap-modal-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="yap-modal-body" id="yap-preview-content">
                    <!-- Preview will be loaded here -->
                </div>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Zwraca typy pól
     */
    private function get_field_types() {
        return [
            'text' => [
                'label' => 'Text',
                'icon' => '📝',
                'category' => 'basic',
            ],
            'textarea' => [
                'label' => 'Textarea',
                'icon' => '📄',
                'category' => 'basic',
            ],
            'number' => [
                'label' => 'Number',
                'icon' => '🔢',
                'category' => 'basic',
            ],
            'email' => [
                'label' => 'Email',
                'icon' => '✉️',
                'category' => 'basic',
            ],
            'url' => [
                'label' => 'URL',
                'icon' => '🔗',
                'category' => 'basic',
            ],
            'tel' => [
                'label' => 'Phone',
                'icon' => '📞',
                'category' => 'basic',
            ],
            'date' => [
                'label' => 'Date',
                'icon' => '📅',
                'category' => 'basic',
            ],
            'time' => [
                'label' => 'Time',
                'icon' => '⏰',
                'category' => 'basic',
            ],
            'datetime' => [
                'label' => 'Date Time',
                'icon' => '📆',
                'category' => 'basic',
            ],
            'color' => [
                'label' => 'Color Picker',
                'icon' => '🎨',
                'category' => 'basic',
            ],
            'select' => [
                'label' => 'Select',
                'icon' => '📋',
                'category' => 'choice',
            ],
            'checkbox' => [
                'label' => 'Checkbox',
                'icon' => '☑️',
                'category' => 'choice',
            ],
            'radio' => [
                'label' => 'Radio',
                'icon' => '🔘',
                'category' => 'choice',
            ],
            'image' => [
                'label' => 'Image',
                'icon' => '🖼️',
                'category' => 'media',
            ],
            'file' => [
                'label' => 'File',
                'icon' => '📎',
                'category' => 'media',
            ],
            'gallery' => [
                'label' => 'Gallery',
                'icon' => '🖼️',
                'category' => 'media',
            ],
            'wysiwyg' => [
                'label' => 'WYSIWYG Editor',
                'icon' => '✏️',
                'category' => 'content',
            ],
            'repeater' => [
                'label' => 'Repeater',
                'icon' => '🔁',
                'category' => 'advanced',
            ],
            'flexible_content' => [
                'label' => 'Flexible Content',
                'icon' => '🧩',
                'category' => 'advanced',
            ],
            'group' => [
                'label' => 'Group',
                'icon' => '📦',
                'category' => 'advanced',
            ],
        ];
    }
    
    /**
     * Renderuje listę typów pól
     */
    private function render_field_types() {
        $types = $this->get_field_types();
        $categories = [
            'basic' => 'Basic Fields',
            'choice' => 'Choice Fields',
            'media' => 'Media Fields',
            'content' => 'Content Fields',
            'advanced' => 'Advanced Fields',
        ];
        
        $output = '';
        
        foreach ($categories as $cat_key => $cat_label) {
            $output .= '<div class="yap-field-category">';
            $output .= '<h4>' . esc_html($cat_label) . '</h4>';
            $output .= '<div class="yap-field-type-list">';
            
            foreach ($types as $type_key => $type_data) {
                if ($type_data['category'] === $cat_key) {
                    $output .= sprintf(
                        '<div class="yap-field-type-item" data-field-type="%s" draggable="true">
                            <span class="yap-field-type-icon">%s</span>
                            <span class="yap-field-type-label">%s</span>
                        </div>',
                        esc_attr($type_key),
                        $type_data['icon'],
                        esc_html($type_data['label'])
                    );
                }
            }
            
            $output .= '</div></div>';
        }
        
        return $output;
    }
    
    /**
     * Zwraca field templates
     */
    private function get_field_templates() {
        return [
            'price_field' => [
                'label' => 'Price Field',
                'icon' => '💰',
                'description' => 'Price with validation, sanitization & formatting',
                'fields' => [
                    [
                        'name' => 'price',
                        'type' => 'number',
                        'label' => 'Price',
                        'validation' => 'required|numeric|min:0',
                        'sanitizer' => 'price',
                        'transformer' => 'price',
                    ],
                ],
            ],
            'person_field' => [
                'label' => 'Person',
                'icon' => '👤',
                'description' => 'First name + Last name + Avatar',
                'fields' => [
                    [
                        'name' => 'first_name',
                        'type' => 'text',
                        'label' => 'First Name',
                    ],
                    [
                        'name' => 'last_name',
                        'type' => 'text',
                        'label' => 'Last Name',
                    ],
                    [
                        'name' => 'avatar',
                        'type' => 'image',
                        'label' => 'Avatar',
                    ],
                ],
            ],
            'address_field' => [
                'label' => 'Address',
                'icon' => '📍',
                'description' => 'Full address with country, state, city, zip, street',
                'fields' => [
                    [
                        'name' => 'country',
                        'type' => 'select',
                        'label' => 'Country',
                        'options' => ['PL' => 'Poland', 'US' => 'United States', 'DE' => 'Germany'],
                    ],
                    [
                        'name' => 'state',
                        'type' => 'text',
                        'label' => 'State/Province',
                    ],
                    [
                        'name' => 'city',
                        'type' => 'text',
                        'label' => 'City',
                    ],
                    [
                        'name' => 'zip_code',
                        'type' => 'text',
                        'label' => 'ZIP/Postal Code',
                    ],
                    [
                        'name' => 'street_address',
                        'type' => 'text',
                        'label' => 'Street Address',
                    ],
                ],
            ],
            'social_media' => [
                'label' => 'Social Media',
                'icon' => '🌐',
                'description' => 'Social media links',
                'fields' => [
                    [
                        'name' => 'facebook',
                        'type' => 'url',
                        'label' => 'Facebook',
                    ],
                    [
                        'name' => 'twitter',
                        'type' => 'url',
                        'label' => 'Twitter',
                    ],
                    [
                        'name' => 'instagram',
                        'type' => 'url',
                        'label' => 'Instagram',
                    ],
                    [
                        'name' => 'linkedin',
                        'type' => 'url',
                        'label' => 'LinkedIn',
                    ],
                ],
            ],
            'seo_meta' => [
                'label' => 'SEO Meta',
                'icon' => '🔍',
                'description' => 'SEO title, description, keywords',
                'fields' => [
                    [
                        'name' => 'seo_title',
                        'type' => 'text',
                        'label' => 'SEO Title',
                        'validation' => 'max:60',
                    ],
                    [
                        'name' => 'seo_description',
                        'type' => 'textarea',
                        'label' => 'SEO Description',
                        'validation' => 'max:160',
                    ],
                    [
                        'name' => 'seo_keywords',
                        'type' => 'text',
                        'label' => 'SEO Keywords',
                    ],
                ],
            ],
        ];
    }
    
    /**
     * Renderuje field templates
     */
    private function render_field_templates() {
        $templates = $this->get_field_templates();
        
        $output = '<div class="yap-template-list">';
        
        foreach ($templates as $key => $template) {
            $output .= sprintf(
                '<div class="yap-template-item" data-template-key="%s">
                    <span class="yap-template-icon">%s</span>
                    <div class="yap-template-info">
                        <strong>%s</strong>
                        <p>%s</p>
                    </div>
                    <button class="yap-template-use button button-small">Use</button>
                </div>',
                esc_attr($key),
                $template['icon'],
                esc_html($template['label']),
                esc_html($template['description'])
            );
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Zwraca CSS dla buildera
     */
    private function get_builder_inline_styles() {
        return '
        .yap-visual-builder-wrap {
            margin: 20px 20px 20px 0;
            background: #f5f5f5;
            min-height: calc(100vh - 100px);
        }
        
        .yap-builder-header {
            background: white;
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .yap-builder-title h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .yap-builder-title p {
            margin: 5px 0 0;
            color: #666;
        }
        
        .yap-builder-actions {
            display: flex;
            gap: 10px;
        }
        
        .yap-builder-container {
            display: flex;
            height: calc(100vh - 180px);
        }
        
        /* Sidebar */
        .yap-builder-sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid #ddd;
            overflow-y: auto;
            padding: 20px;
        }
        
        .yap-sidebar-section {
            margin-bottom: 30px;
        }
        
        .yap-sidebar-section h3 {
            margin: 0 0 15px;
            font-size: 14px;
            text-transform: uppercase;
            color: #666;
        }
        
        .yap-field-category {
            margin-bottom: 20px;
        }
        
        .yap-field-category h4 {
            font-size: 12px;
            color: #999;
            margin: 0 0 10px;
            text-transform: uppercase;
        }
        
        .yap-field-type-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            margin-bottom: 8px;
            cursor: move;
            transition: all 0.2s;
        }
        
        .yap-field-type-item:hover {
            background: #fff;
            border-color: #0073aa;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .yap-field-type-icon {
            font-size: 20px;
            margin-right: 10px;
        }
        
        .yap-field-type-label {
            font-size: 13px;
            font-weight: 500;
        }
        
        /* Canvas */
        .yap-builder-canvas {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .yap-canvas-header {
            background: white;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .yap-group-name-input {
            font-size: 18px;
            font-weight: 600;
            border: 1px solid #ddd;
            padding: 8px 12px;
            border-radius: 4px;
            width: 300px;
        }
        
        .yap-drop-zone {
            background: white;
            border: 2px dashed #ccc;
            border-radius: 8px;
            min-height: 400px;
            padding: 20px;
        }
        
        .yap-drop-zone.dragover {
            border-color: #0073aa;
            background: #f0f8ff;
        }
        
        .yap-drop-zone-placeholder {
            text-align: center;
            padding: 100px 20px;
            color: #999;
        }
        
        .yap-drop-zone-placeholder .dashicons {
            font-size: 60px;
            width: 60px;
            height: 60px;
            opacity: 0.3;
        }
        
        .yap-field-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
            transition: all 0.2s;
        }
        
        .yap-field-item:hover {
            border-color: #0073aa;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .yap-field-item-header {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background: #f9f9f9;
            border-bottom: 1px solid #eee;
        }
        
        .yap-field-icon {
            font-size: 20px;
            margin-right: 10px;
        }
        
        .yap-field-label {
            flex: 1;
            font-weight: 600;
            font-size: 14px;
        }
        
        .yap-field-actions {
            display: flex;
            gap: 5px;
        }
        
        .yap-field-actions button {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #666;
        }
        
        .yap-field-actions button:hover {
            color: #0073aa;
        }
        
        .yap-field-drag-handle {
            cursor: move;
            color: #999;
        }
        
        .yap-field-item-body {
            padding: 15px;
        }
        
        .yap-field-meta {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .yap-field-name {
            font-family: monospace;
            font-size: 12px;
            color: #666;
            background: #f5f5f5;
            padding: 2px 8px;
            border-radius: 3px;
        }
        
        .yap-field-type-badge {
            font-size: 11px;
            background: #0073aa;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
        }
        
        .yap-field-conditional {
            margin-top: 10px;
            font-size: 12px;
            color: #46b450;
        }
        
        /* Inspector */
        .yap-builder-inspector {
            width: 320px;
            background: white;
            border-left: 1px solid #ddd;
            overflow-y: auto;
        }
        
        .yap-inspector-header {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .yap-inspector-header h3 {
            margin: 0;
            font-size: 14px;
        }
        
        .yap-inspector-close {
            background: transparent;
            border: none;
            cursor: pointer;
            color: #666;
        }
        
        /* Inspector Tabs */
        .yap-inspector-tabs {
            display: flex;
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
            gap: 0;
        }
        
        .yap-inspector-tab {
            flex: 1;
            padding: 12px 15px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 12px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .yap-inspector-tab:hover {
            background: #f0f0f0;
            color: #333;
        }
        
        .yap-inspector-tab.active {
            border-bottom-color: #0073aa;
            color: #0073aa;
            background: white;
        }
        
        .yap-inspector-tab .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        .yap-history-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            border-radius: 10px;
            font-size: 10px;
            padding: 2px 6px;
            margin-left: 4px;
        }
        
        /* Inspector Header */
        .yap-inspector-header {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .yap-inspector-header h3 {
            margin: 0;
            font-size: 14px;
        }
        
        .yap-inspector-close {
            background: transparent;
            border: none;
            cursor: pointer;
            color: #666;
        }
        
        /* Tab Content */
        .yap-inspector-content {
            padding: 20px;
        }
        
        .yap-inspector-tab-content {
            display: none;
            padding: 15px;
        }
        
        .yap-inspector-tab-content.active {
            display: block;
        }
        
        .yap-inspector-placeholder {
            text-align: center;
            color: #999;
            padding: 50px 20px;
        }
        
        /* History Controls */
        .yap-history-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f9f9f9;
            border-bottom: 1px solid #ddd;
            gap: 10px;
        }
        
        .yap-history-actions {
            display: flex;
            gap: 5px;
        }
        
        .yap-history-btn {
            padding: 6px 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            min-height: 36px;
        }
        
        .yap-history-btn:hover:not(:disabled) {
            background: #f0f0f0;
            border-color: #999;
        }
        
        .yap-history-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .yap-history-btn .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        .yap-history-position {
            font-size: 12px;
            color: #666;
            min-width: 50px;
            text-align: right;
        }
        
        /* History Timeline */
        .yap-history-timeline {
            max-height: 300px;
            overflow-y: auto;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .yap-history-item {
            padding: 10px 12px;
            border-left: 3px solid #ddd;
            margin-left: 0;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 12px;
        }
        
        .yap-history-item:hover {
            background: #f5f5f5;
            border-left-color: #0073aa;
        }
        
        .yap-history-item.current {
            background: #e8f5ff;
            border-left-color: #0073aa;
        }
        
        .yap-history-item-icon {
            font-size: 14px;
            min-width: 16px;
            flex-shrink: 0;
        }
        
        .yap-history-item-info {
            flex: 1;
        }
        
        .yap-history-item-type {
            font-weight: bold;
            color: #0073aa;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .yap-history-item-desc {
            color: #333;
            margin: 2px 0;
        }
        
        .yap-history-item-time {
            color: #999;
            font-size: 11px;
            margin-top: 2px;
        }
        
        .yap-history-placeholder {
            text-align: center;
            color: #999;
            padding: 30px 20px;
            font-size: 12px;
        }
        
        /* History Stats */
        .yap-history-stats {
            padding: 12px;
        }
        
        .yap-history-stats h4 {
            margin: 0 0 10px 0;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
        }
        
        .yap-history-stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .yap-history-stat-item {
            padding: 8px;
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            text-align: center;
        }
        
        .yap-history-stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #0073aa;
        }
        
        .yap-history-stat-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            margin-top: 4px;
        }
        
        /* Templates */
        .yap-template-item {
            display: flex;
            align-items: flex-start;
            padding: 12px;
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .yap-template-icon {
            font-size: 24px;
            margin-right: 12px;
        }
        
        .yap-template-info {
            flex: 1;
        }
        
        .yap-template-info strong {
            display: block;
            margin-bottom: 4px;
        }
        
        .yap-template-info p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }
        
        /* Modal */
        .yap-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 100000;
        }
        
        .yap-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
        }
        
        .yap-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 8px;
            max-width: 900px;
            width: 90%;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        .yap-modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .yap-modal-header h2 {
            margin: 0;
        }
        
        .yap-modal-close {
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 24px;
        }
        
        .yap-modal-body {
            padding: 20px;
            max-height: calc(80vh - 80px);
            overflow-y: auto;
        }
        ';
    }
    
    /**
     * AJAX: Zapisuje schema
     */
    public function save_schema() {
        check_ajax_referer('yap_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $group_name = sanitize_text_field($_POST['group_name']);
        $schema = json_decode(stripslashes($_POST['schema']), true);
        $location_rules = isset($_POST['location_rules']) ? json_decode(stripslashes($_POST['location_rules']), true) : [];
        
        if (empty($group_name) || empty($schema)) {
            wp_send_json_error(['message' => 'Invalid data']);
        }
        
        // Zapisz schema jako JSON
        $schema_dir = WP_CONTENT_DIR . '/yap-schemas/';
        if (!file_exists($schema_dir)) {
            wp_mkdir_p($schema_dir);
        }
        
        $schema_file = $schema_dir . $group_name . '.json';
        file_put_contents($schema_file, json_encode($schema, JSON_PRETTY_PRINT));
        
        // Utwórz/zaktualizuj grupę w bazie
        $this->create_group_from_schema($group_name, $schema);
        
        // Zapisz location rules
        $this->save_location_rules($group_name, $location_rules);
        
        wp_send_json_success([
            'message' => 'Schema saved successfully',
            'group_name' => $group_name,
        ]);
    }
    
    /**
     * Tworzy grupę z schema - NOWY SYSTEM (location_rules + metadata)
     */
    private function create_group_from_schema($group_name, $schema) {
        global $wpdb;
        
        $metadata_table = $wpdb->prefix . 'yap_field_metadata';
        
        // Wyczyść stare metadane grupy
        $wpdb->delete($metadata_table, ['group_name' => $group_name], ['%s']);
        
        // Dodaj pola ze schema jako metadane
        foreach ($schema['fields'] as $index => $field) {
            // field_config zawiera wszystkie dodatkowe właściwości (sub_fields, choices, etc.)
            $field_config = [
                'required' => $field['required'] ?? false,
                'placeholder' => $field['placeholder'] ?? '',
                'default_value' => $field['default_value'] ?? '',
                'css_class' => $field['css_class'] ?? '',
                'description' => $field['description'] ?? '',
                'conditional' => $field['conditional'] ?? false,
                'conditional_action' => $field['conditional_action'] ?? 'show',
                'conditional_field' => $field['conditional_field'] ?? '',
                'conditional_operator' => $field['conditional_operator'] ?? '==',
                'conditional_value' => $field['conditional_value'] ?? '',
                'conditional_message' => $field['conditional_message'] ?? '',
            ];
            
            // Dodaj sub_fields dla repeater/group
            if (isset($field['sub_fields'])) {
                $field_config['sub_fields'] = $field['sub_fields'];
            }
            
            // Dodaj choices dla select
            if (isset($field['choices'])) {
                $field_config['choices'] = $field['choices'];
            }
            
            // Dodaj min/max dla repeater
            if (isset($field['min'])) {
                $field_config['min'] = $field['min'];
            }
            if (isset($field['max'])) {
                $field_config['max'] = $field['max'];
            }
            
            // Backward compatibility - zachowaj field_metadata dla starych wersji
            $field_metadata = array_merge([
                'label' => $field['label'] ?? $field['name'],
                'type' => $field['type'],
            ], $field_config);
            
            $wpdb->insert(
                $metadata_table,
                [
                    'group_name' => $group_name,
                    'field_name' => $field['name'],
                    'field_id' => $field['id'] ?? $field['name'],
                    'field_label' => $field['label'] ?? $field['name'],
                    'field_type' => $field['type'] ?? 'text',
                    'field_config' => json_encode($field_config),
                    'field_metadata' => json_encode($field_metadata),
                    'field_order' => $index,
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d']
            );
        }
        
        // Location rules będą zapisane przez save_location_rules()
    }
    
    /**
     * Save location rules for group
     */
    private function save_location_rules($group_name, $location_rules) {
        global $wpdb;
        $location_table = $wpdb->prefix . 'yap_location_rules';
        
        // Wyczyść stare location rules dla tej grupy
        $wpdb->delete($location_table, ['group_name' => $group_name], ['%s']);
        
        // Jeśli nie ma location rules, dodaj placeholder
        if (empty($location_rules)) {
            $wpdb->insert(
                $location_table,
                [
                    'group_name' => $group_name,
                    'location_type' => 'post_type',
                    'location_operator' => '==',
                    'location_value' => '__unconfigured__',
                    'rule_group' => 0,
                    'rule_order' => 0,
                ],
                ['%s', '%s', '%s', '%s', '%d', '%d']
            );
            return;
        }
        
        // Zapisz każdą grupę reguł (OR)
        foreach ($location_rules as $group_index => $rule_group) {
            // Zapisz każdą regułę w grupie (AND)
            foreach ($rule_group as $rule_index => $rule) {
                $wpdb->insert(
                    $location_table,
                    [
                        'group_name' => $group_name,
                        'location_type' => sanitize_text_field($rule['type']),
                        'location_operator' => sanitize_text_field($rule['operator']),
                        'location_value' => sanitize_text_field($rule['value']),
                        'rule_group' => $group_index,
                        'rule_order' => $rule_index,
                    ],
                    ['%s', '%s', '%s', '%s', '%d', '%d']
                );
            }
        }
    }
    
    /**
     * AJAX: Ładuje schema
     */
    public function load_schema() {
        check_ajax_referer('yap_builder_nonce', 'nonce');
        
        $group_name = sanitize_text_field($_POST['group_name']);
        
        $schema_file = WP_CONTENT_DIR . '/yap-schemas/' . $group_name . '.json';
        
        if (file_exists($schema_file)) {
            $schema = json_decode(file_get_contents($schema_file), true);
            
            // Load location rules for this group
            $location_rules = $this->load_location_rules($group_name);
            
            wp_send_json_success([
                'schema' => $schema,
                'location_rules' => $location_rules
            ]);
        } else {
            wp_send_json_error(['message' => 'Schema not found']);
        }
    }
    
    /**
     * Load location rules for group
     */
    private function load_location_rules($group_name) {
        global $wpdb;
        $location_table = $wpdb->prefix . 'yap_location_rules';
        
        $rules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$location_table} WHERE group_name = %s ORDER BY rule_group, rule_order",
            $group_name
        ), ARRAY_A);
        
        // Group rules by rule_group
        $grouped_rules = [];
        foreach ($rules as $rule) {
            // Skip placeholder rules
            if ($rule['location_value'] === '__unconfigured__') {
                continue;
            }
            
            $group_index = $rule['rule_group'];
            if (!isset($grouped_rules[$group_index])) {
                $grouped_rules[$group_index] = [];
            }
            
            $grouped_rules[$group_index][] = [
                'type' => $rule['location_type'],
                'operator' => $rule['location_operator'],
                'value' => $rule['location_value']
            ];
        }
        
        return array_values($grouped_rules);
    }
    
    /**
     * AJAX: Generuje preview
     */
    public function generate_preview() {
        check_ajax_referer('yap_builder_nonce', 'nonce');
        
        $schema = json_decode(stripslashes($_POST['schema']), true);
        
        ob_start();
        
        echo '<div class="yap-preview-form">';
        
        foreach ($schema['fields'] as $field) {
            echo '<div class="yap-preview-field">';
            echo '<label>' . esc_html($field['label'] ?: $field['name']) . '</label>';
            
            switch ($field['type']) {
                case 'text':
                case 'email':
                case 'url':
                case 'tel':
                    echo '<input type="' . esc_attr($field['type']) . '" placeholder="' . esc_attr($field['name']) . '">';
                    break;
                    
                case 'textarea':
                    echo '<textarea rows="4"></textarea>';
                    break;
                    
                case 'select':
                    echo '<select><option>-- Select --</option></select>';
                    break;
                    
                case 'checkbox':
                    echo '<input type="checkbox"> ' . esc_html($field['label']);
                    break;
                    
                default:
                    echo '<input type="text" placeholder="' . esc_attr($field['type']) . ' field">';
                    break;
            }
            
            echo '</div>';
        }
        
        echo '</div>';
        
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }
}

// Initialize
YAP_Visual_Builder::get_instance();
