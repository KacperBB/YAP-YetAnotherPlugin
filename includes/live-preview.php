<?php
/**
 * YAP Live Preview System
 * 
 * Real-time preview of field changes without saving the post.
 * Features:
 * - Split-screen or modal preview
 * - Live updates as fields change
 * - Template preview (hero, gallery, layout)
 * - Device simulation (desktop/tablet/mobile)
 * - Custom template rendering
 * 
 * @package YetAnotherPlugin
 * @subpackage LivePreview
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Live_Preview {
    
    private static $instance = null;
    private $preview_templates = [];
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Admin hooks
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_footer', [$this, 'render_preview_modal']);
        
        // AJAX endpoints
        add_action('wp_ajax_yap_render_preview', [$this, 'ajax_render_preview']);
        add_action('wp_ajax_yap_save_preview_template', [$this, 'ajax_save_preview_template']);
        add_action('wp_ajax_yap_get_preview_templates', [$this, 'ajax_get_preview_templates']);
        add_action('wp_ajax_yap_delete_preview_template', [$this, 'ajax_delete_preview_template']);
        
        // Register default templates
        $this->register_default_templates();
        
        // Admin page
        add_action('admin_menu', [$this, 'add_admin_menu'], 100);
    }
    
    /**
     * Register default preview templates
     */
    private function register_default_templates() {
        // Hero section template
        $this->register_template('hero_section', [
            'name' => 'Hero Section',
            'description' => 'Full-width hero with background image and text',
            'template' => '
                <div class="hero-section" style="background-image: url({hero_bg}); background-size: cover; background-position: center; min-height: 500px; display: flex; align-items: center; justify-content: center; color: white; text-align: center; padding: 40px;">
                    <div class="hero-content">
                        <h1 style="font-size: 48px; margin-bottom: 20px; font-weight: bold;">{hero_title}</h1>
                        <p style="font-size: 20px; margin-bottom: 30px;">{hero_subtitle}</p>
                        <a href="{hero_link}" class="hero-button" style="background: #0073aa; color: white; padding: 15px 30px; text-decoration: none; border-radius: 4px; display: inline-block;">{hero_button_text}</a>
                    </div>
                </div>
            ',
            'fields' => ['hero_bg', 'hero_title', 'hero_subtitle', 'hero_link', 'hero_button_text']
        ]);
        
        // Gallery template
        $this->register_template('gallery_grid', [
            'name' => 'Gallery Grid',
            'description' => '3-column responsive image gallery',
            'template' => '
                <div class="gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; padding: 20px;">
                    {gallery_images}
                </div>
            ',
            'fields' => ['gallery_images'],
            'renderer' => function($field_values) {
                $images = $field_values['gallery_images'] ?? [];
                if (!is_array($images)) {
                    $images = explode(',', $images);
                }
                
                $html = '';
                foreach ($images as $img) {
                    $img_url = is_numeric($img) ? wp_get_attachment_url($img) : $img;
                    $html .= sprintf(
                        '<div class="gallery-item"><img src="%s" style="width: 100%%; height: 200px; object-fit: cover; border-radius: 8px;" /></div>',
                        esc_url($img_url)
                    );
                }
                return $html;
            }
        ]);
        
        // Card layout template
        $this->register_template('info_card', [
            'name' => 'Info Card',
            'description' => 'Card with icon, title, and description',
            'template' => '
                <div class="info-card" style="max-width: 400px; margin: 20px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center;">
                    <div class="card-icon" style="font-size: 48px; margin-bottom: 20px;">{card_icon}</div>
                    <h3 style="font-size: 24px; margin-bottom: 15px; color: #333;">{card_title}</h3>
                    <p style="font-size: 16px; color: #666; line-height: 1.6;">{card_description}</p>
                </div>
            ',
            'fields' => ['card_icon', 'card_title', 'card_description']
        ]);
        
        // Pricing table template
        $this->register_template('pricing_table', [
            'name' => 'Pricing Table',
            'description' => 'Pricing card with features list',
            'template' => '
                <div class="pricing-table" style="max-width: 350px; margin: 20px auto; padding: 40px 30px; background: white; border: 2px solid #0073aa; border-radius: 12px; text-align: center;">
                    <h2 style="font-size: 28px; margin-bottom: 10px; color: #333;">{pricing_plan_name}</h2>
                    <div class="price" style="font-size: 48px; font-weight: bold; color: #0073aa; margin-bottom: 20px;">
                        <span style="font-size: 24px;">$</span>{pricing_amount}<span style="font-size: 18px; color: #666;">/mo</span>
                    </div>
                    <ul style="list-style: none; padding: 0; margin: 30px 0; text-align: left;">
                        {pricing_features}
                    </ul>
                    <button style="width: 100%; padding: 15px; background: #0073aa; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer;">Get Started</button>
                </div>
            ',
            'fields' => ['pricing_plan_name', 'pricing_amount', 'pricing_features'],
            'renderer' => function($field_values) {
                $features = $field_values['pricing_features'] ?? '';
                if (!is_array($features)) {
                    $features = explode("\n", $features);
                }
                
                $html = '';
                foreach ($features as $feature) {
                    if (trim($feature)) {
                        $html .= sprintf(
                            '<li style="padding: 10px 0; border-bottom: 1px solid #eee;">✓ %s</li>',
                            esc_html(trim($feature))
                        );
                    }
                }
                return $html;
            }
        ]);
    }
    
    /**
     * Register a preview template
     */
    public function register_template($id, $config) {
        $this->preview_templates[$id] = array_merge([
            'name' => '',
            'description' => '',
            'template' => '',
            'fields' => [],
            'renderer' => null,
            'css' => '',
            'js' => ''
        ], $config);
    }
    
    /**
     * Get all registered templates
     */
    public function get_templates() {
        return $this->preview_templates;
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php', 'toplevel_page_yap-live-preview'])) {
            return;
        }
        
        // Enqueue unified advanced features CSS
        wp_enqueue_style(
            'yap-advanced-features',
            plugin_dir_url(__DIR__) . 'includes/css/yap-advanced-features.css',
            [],
            '1.4.0'
        );
        
        wp_enqueue_script(
            'yap-live-preview',
            plugin_dir_url(__DIR__) . 'includes/js/live-preview.js',
            ['jquery'],
            '1.4.0',
            true
        );
        
        wp_localize_script('yap-live-preview', 'yapLivePreview', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yap_live_preview'),
            'templates' => $this->preview_templates,
            'strings' => [
                'loading' => __('Loading preview...', 'yap'),
                'error' => __('Preview failed to load', 'yap'),
                'noTemplate' => __('Please select a template', 'yap'),
                'saved' => __('Template saved!', 'yap'),
            ]
        ]);
    }
    
    /**
     * Render preview modal in admin footer
     */
    public function render_preview_modal() {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->base, ['post', 'toplevel_page_yap-live-preview'])) {
            return;
        }
        ?>
        <div id="yap-preview-modal" class="yap-modal" style="display: none;">
            <div class="yap-modal-overlay"></div>
            <div class="yap-modal-content">
                <div class="yap-modal-header">
                    <h2>🔍 Live Preview</h2>
                    <div class="yap-preview-controls">
                        <select id="yap-preview-template" class="yap-select">
                            <option value="">Select Template...</option>
                            <?php foreach ($this->preview_templates as $id => $template): ?>
                                <option value="<?php echo esc_attr($id); ?>">
                                    <?php echo esc_html($template['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div class="yap-device-switcher">
                            <button class="yap-device-btn active" data-device="desktop" title="Desktop">
                                <span class="dashicons dashicons-desktop"></span>
                            </button>
                            <button class="yap-device-btn" data-device="tablet" title="Tablet">
                                <span class="dashicons dashicons-tablet"></span>
                            </button>
                            <button class="yap-device-btn" data-device="mobile" title="Mobile">
                                <span class="dashicons dashicons-smartphone"></span>
                            </button>
                        </div>
                        
                        <button id="yap-preview-refresh" class="button">
                            <span class="dashicons dashicons-update"></span> Refresh
                        </button>
                        
                        <button id="yap-preview-close" class="button">
                            <span class="dashicons dashicons-no"></span> Close
                        </button>
                    </div>
                </div>
                
                <div class="yap-modal-body">
                    <div id="yap-preview-container" class="yap-preview-desktop">
                        <iframe id="yap-preview-frame" frameborder="0"></iframe>
                    </div>
                </div>
                
                <div class="yap-modal-footer">
                    <div class="yap-preview-info">
                        <span id="yap-preview-status">Ready</span>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .yap-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 999999;
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
                position: relative;
                width: 90%;
                height: 90%;
                margin: 5% auto;
                background: white;
                border-radius: 8px;
                display: flex;
                flex-direction: column;
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
            
            .yap-preview-controls {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            
            .yap-device-switcher {
                display: flex;
                gap: 5px;
                border: 1px solid #ddd;
                border-radius: 4px;
                overflow: hidden;
            }
            
            .yap-device-btn {
                padding: 5px 12px;
                border: none;
                background: white;
                cursor: pointer;
                transition: background 0.2s;
            }
            
            .yap-device-btn:hover {
                background: #f0f0f0;
            }
            
            .yap-device-btn.active {
                background: #0073aa;
                color: white;
            }
            
            .yap-modal-body {
                flex: 1;
                padding: 20px;
                overflow: auto;
            }
            
            #yap-preview-container {
                width: 100%;
                height: 100%;
                margin: 0 auto;
                transition: all 0.3s;
            }
            
            #yap-preview-container.yap-preview-desktop {
                max-width: 100%;
            }
            
            #yap-preview-container.yap-preview-tablet {
                max-width: 768px;
            }
            
            #yap-preview-container.yap-preview-mobile {
                max-width: 375px;
            }
            
            #yap-preview-frame {
                width: 100%;
                height: 100%;
                border: 1px solid #ddd;
                border-radius: 4px;
                background: white;
            }
            
            .yap-modal-footer {
                padding: 15px 20px;
                border-top: 1px solid #ddd;
                background: #f9f9f9;
            }
            
            .yap-preview-info {
                font-size: 13px;
                color: #666;
            }
        </style>
        <?php
    }
    
    /**
     * AJAX: Render preview
     */
    public function ajax_render_preview() {
        check_ajax_referer('yap_live_preview', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $template_id = sanitize_text_field($_POST['template_id'] ?? '');
        $field_values = $_POST['field_values'] ?? [];
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!isset($this->preview_templates[$template_id])) {
            wp_send_json_error(['message' => 'Template not found']);
        }
        
        $template = $this->preview_templates[$template_id];
        
        // Render the template
        $html = $this->render_template($template, $field_values, $post_id);
        
        // Add wrapper with styling
        $output = sprintf(
            '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
                    %s
                </style>
            </head>
            <body>
                %s
                <script>%s</script>
            </body>
            </html>',
            $template['css'] ?? '',
            $html,
            $template['js'] ?? ''
        );
        
        wp_send_json_success(['html' => $output]);
    }
    
    /**
     * Render a template with field values
     */
    private function render_template($template, $field_values, $post_id) {
        $html = $template['template'];
        
        // Use custom renderer if available
        if (is_callable($template['renderer'])) {
            foreach ($template['fields'] as $field) {
                $rendered = call_user_func($template['renderer'], $field_values);
                $html = str_replace('{' . $field . '}', $rendered, $html);
            }
        } else {
            // Default rendering - simple replacement
            foreach ($template['fields'] as $field) {
                $value = $field_values[$field] ?? '';
                
                // Handle different field types
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                
                // Handle image fields
                if (is_numeric($value) && strpos($field, 'image') !== false) {
                    $value = wp_get_attachment_url($value);
                }
                
                $html = str_replace('{' . $field . '}', esc_html($value), $html);
            }
        }
        
        return $html;
    }
    
    /**
     * AJAX: Save custom preview template
     */
    public function ajax_save_preview_template() {
        check_ajax_referer('yap_live_preview', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $template_id = sanitize_key($_POST['template_id'] ?? '');
        $template_name = sanitize_text_field($_POST['template_name'] ?? '');
        $template_html = wp_kses_post($_POST['template_html'] ?? '');
        $template_css = sanitize_textarea_field($_POST['template_css'] ?? '');
        $template_fields = array_map('sanitize_text_field', $_POST['template_fields'] ?? []);
        
        if (empty($template_id) || empty($template_name) || empty($template_html)) {
            wp_send_json_error(['message' => 'Missing required fields']);
        }
        
        // Save to options
        $custom_templates = get_option('yap_custom_preview_templates', []);
        $custom_templates[$template_id] = [
            'name' => $template_name,
            'template' => $template_html,
            'css' => $template_css,
            'fields' => $template_fields,
            'created_at' => current_time('mysql'),
            'author' => get_current_user_id()
        ];
        
        update_option('yap_custom_preview_templates', $custom_templates);
        
        // Register template
        $this->register_template($template_id, $custom_templates[$template_id]);
        
        wp_send_json_success(['message' => 'Template saved']);
    }
    
    /**
     * AJAX: Get all templates (built-in + custom)
     */
    public function ajax_get_preview_templates() {
        check_ajax_referer('yap_live_preview', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $custom_templates = get_option('yap_custom_preview_templates', []);
        
        wp_send_json_success([
            'builtin' => array_keys($this->preview_templates),
            'custom' => $custom_templates
        ]);
    }
    
    /**
     * AJAX: Delete custom template
     */
    public function ajax_delete_preview_template() {
        check_ajax_referer('yap_live_preview', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $template_id = sanitize_key($_POST['template_id'] ?? '');
        
        $custom_templates = get_option('yap_custom_preview_templates', []);
        
        if (isset($custom_templates[$template_id])) {
            unset($custom_templates[$template_id]);
            update_option('yap_custom_preview_templates', $custom_templates);
            wp_send_json_success(['message' => 'Template deleted']);
        }
        
        wp_send_json_error(['message' => 'Template not found']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'yap-admin-page',
            'Live Preview',
            '🔍 Live Preview',
            'manage_options',
            'yap-live-preview',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $custom_templates = get_option('yap_custom_preview_templates', []);
        ?>
        <div class="wrap">
            <h1>🔍 Live Preview Templates</h1>
            
            <div class="yap-preview-admin">
                <div class="yap-card">
                    <h2>Built-in Templates</h2>
                    <p>These templates are included with YAP and ready to use:</p>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Template Name</th>
                                <th>Description</th>
                                <th>Required Fields</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->preview_templates as $id => $template): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($template['name']); ?></strong></td>
                                    <td><?php echo esc_html($template['description']); ?></td>
                                    <td><code><?php echo esc_html(implode(', ', $template['fields'])); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="yap-card" style="margin-top: 20px;">
                    <h2>Custom Templates</h2>
                    
                    <?php if (empty($custom_templates)): ?>
                        <p>No custom templates yet. Create your first template below!</p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Template Name</th>
                                    <th>Fields</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($custom_templates as $id => $template): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($template['name']); ?></strong></td>
                                        <td><code><?php echo esc_html(implode(', ', $template['fields'])); ?></code></td>
                                        <td><?php echo esc_html($template['created_at']); ?></td>
                                        <td>
                                            <button class="button yap-delete-template" data-template-id="<?php echo esc_attr($id); ?>">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <div class="yap-card" style="margin-top: 20px;">
                    <h2>Create Custom Template</h2>
                    
                    <form id="yap-create-template-form">
                        <table class="form-table">
                            <tr>
                                <th><label for="template_id">Template ID</label></th>
                                <td>
                                    <input type="text" id="template_id" name="template_id" class="regular-text" required />
                                    <p class="description">Unique identifier (lowercase, underscores only)</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label for="template_name">Template Name</label></th>
                                <td>
                                    <input type="text" id="template_name" name="template_name" class="regular-text" required />
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label for="template_fields">Required Fields</label></th>
                                <td>
                                    <input type="text" id="template_fields" name="template_fields" class="regular-text" placeholder="field1, field2, field3" required />
                                    <p class="description">Comma-separated list of field names</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label for="template_html">HTML Template</label></th>
                                <td>
                                    <textarea id="template_html" name="template_html" rows="10" class="large-text code" required></textarea>
                                    <p class="description">Use {field_name} placeholders for field values</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label for="template_css">Custom CSS</label></th>
                                <td>
                                    <textarea id="template_css" name="template_css" rows="6" class="large-text code"></textarea>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">Create Template</button>
                        </p>
                    </form>
                </div>
                
                <div class="yap-card" style="margin-top: 20px;">
                    <h2>📖 How to Use Live Preview</h2>
                    
                    <ol>
                        <li><strong>In Post Editor:</strong> Look for the "Live Preview" button in the YAP field groups meta box</li>
                        <li><strong>Select Template:</strong> Choose a template from the dropdown that matches your field structure</li>
                        <li><strong>Real-time Updates:</strong> As you type in fields, the preview updates automatically</li>
                        <li><strong>Device Preview:</strong> Switch between desktop, tablet, and mobile views</li>
                        <li><strong>Custom Templates:</strong> Create your own templates for specific layouts</li>
                    </ol>
                    
                    <h3>Template Syntax</h3>
                    <ul>
                        <li><code>{field_name}</code> - Insert field value</li>
                        <li><code>{hero_bg}</code> - Image field (automatically converts to URL)</li>
                        <li>Standard HTML and inline CSS supported</li>
                    </ul>
                    
                    <h3>Example Template</h3>
                    <pre><code>&lt;div class="card"&gt;
    &lt;h2&gt;{title}&lt;/h2&gt;
    &lt;p&gt;{description}&lt;/p&gt;
    &lt;img src="{image}" /&gt;
&lt;/div&gt;</code></pre>
                </div>
            </div>
        </div>
        
        <style>
            .yap-preview-admin .yap-card {
                background: white;
                padding: 20px;
                border: 1px solid #ccc;
                border-radius: 4px;
            }
            
            .yap-preview-admin h2 {
                margin-top: 0;
            }
            
            .yap-preview-admin pre {
                background: #f5f5f5;
                padding: 15px;
                border-radius: 4px;
                overflow-x: auto;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Create template form
            $('#yap-create-template-form').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    action: 'yap_save_preview_template',
                    nonce: '<?php echo wp_create_nonce('yap_live_preview'); ?>',
                    template_id: $('#template_id').val(),
                    template_name: $('#template_name').val(),
                    template_html: $('#template_html').val(),
                    template_css: $('#template_css').val(),
                    template_fields: $('#template_fields').val().split(',').map(f => f.trim())
                };
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        alert('Template created successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                });
            });
            
            // Delete template
            $('.yap-delete-template').on('click', function() {
                if (!confirm('Delete this template?')) return;
                
                const templateId = $(this).data('template-id');
                
                $.post(ajaxurl, {
                    action: 'yap_delete_preview_template',
                    nonce: '<?php echo wp_create_nonce('yap_live_preview'); ?>',
                    template_id: templateId
                }, function(response) {
                    if (response.success) {
                        alert('Template deleted!');
                        location.reload();
                    }
                });
            });
        });
        </script>
        <?php
    }
}

// Initialize
YAP_Live_Preview::get_instance();
