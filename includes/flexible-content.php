<?php
/**
 * YAP Flexible Content Implementation
 * 
 * Flexible Content allows users to build dynamic layouts with different section types,
 * each with its own set of fields. Perfect for page builders and landing pages.
 * 
 * Example structure:
 * sections[0] = { type: 'hero', fields: { title: 'Welcome', image: 123 } }
 * sections[1] = { type: 'columns', fields: { column_1: '...', column_2: '...' } }
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Flexible_Content {
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX handlers for managing layouts
        add_action('wp_ajax_yap_add_flexible_layout', [$this, 'ajax_add_layout']);
        add_action('wp_ajax_yap_remove_flexible_layout', [$this, 'ajax_remove_layout']);
        add_action('wp_ajax_yap_update_flexible_layout', [$this, 'ajax_update_layout']);
        add_action('wp_ajax_yap_reorder_flexible_layouts', [$this, 'ajax_reorder_layouts']);
        add_action('wp_ajax_yap_get_flexible_layouts', [__CLASS__, 'ajax_get_flexible_layouts']);
        add_action('wp_ajax_yap_save_flexible_layouts', [__CLASS__, 'ajax_save_flexible_layouts']);
        
        // Add admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Add modal to admin footer
        add_action('admin_footer', [$this, 'render_layouts_modal']);
    }
    
    /**
     * Render layouts modal in admin footer
     */
    public function render_layouts_modal() {
        $screen = get_current_screen();
        if ($screen && (
            strpos($screen->id, 'yap-') !== false || 
            strpos($screen->id, 'yet-another-plugin_page_yap-') !== false ||
            $screen->id === 'post' || 
            $screen->id === 'page'
        )) {
            $modal_path = plugin_dir_path(dirname(__FILE__)) . 'includes/admin/views/flexible-layouts-modal.php';
            if (file_exists($modal_path)) {
                include $modal_path;
            }
        }
    }
    
    /**
     * Enqueue scripts for flexible content
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'yap-') !== false || 
            strpos($hook, 'yet-another-plugin_page_yap-') !== false ||
            $hook === 'post.php' || 
            $hook === 'post-new.php') {
            
            // Enqueue advanced features CSS for flexible content styling
            wp_enqueue_style(
                'yap-advanced-features',
                plugins_url('css/yap-advanced-features.css', dirname(__FILE__)),
                [],
                '1.4.0'
            );
            
            wp_enqueue_script(
                'yap-flexible-content',
                plugins_url('../includes/js/admin/flexible-content.js', __FILE__),
                ['jquery', 'jquery-ui-sortable'],
                '1.0.0',
                true
            );
            
            wp_localize_script('yap-flexible-content', 'yapFlexible', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('yap_flexible_nonce')
            ]);
            
            // Also localize for existing admin.js
            wp_localize_script('yap-admin-js', 'yapFlexible', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('yap_flexible_nonce')
            ]);
        }
    }
    
    /**
     * Get layouts configuration for a flexible content field
     */
    public static function get_layouts($group_name, $field_name) {
        global $wpdb;
        
        error_log("🔍 get_layouts called - Group: {$group_name}, Field: {$field_name}");
        
        // Try wp_yap_field_metadata first (JSON schema groups)
        $metadata_table = $wpdb->prefix . 'yap_field_metadata';
        $field_config = $wpdb->get_var($wpdb->prepare(
            "SELECT field_config FROM {$metadata_table} WHERE group_name = %s AND field_name = %s",
            $group_name,
            $field_name
        ));
        
        error_log("🔍 field_config from DB: " . ($field_config ? 'FOUND' : 'NOT FOUND'));
        if ($field_config) {
            error_log("🔍 field_config content: " . $field_config);
        }
        
        if ($field_config) {
            $config = json_decode($field_config, true);
            if (isset($config['layouts'])) {
                error_log("🔍 Returning " . count($config['layouts']) . " layouts");
                return $config['layouts'];
            }
        }
        
        // Fallback to pattern table (old format)
        $pattern_table = $wpdb->prefix . 'yap_' . sanitize_title($group_name) . '_pattern';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$pattern_table}'");
        if ($table_exists) {
            $generated_name = sanitize_title($group_name) . '_' . sanitize_title($field_name);
            
            $layouts = $wpdb->get_var($wpdb->prepare(
                "SELECT flexible_layouts FROM {$pattern_table} WHERE generated_name = %s",
                $generated_name
            ));
            
            if ($layouts) {
                return json_decode($layouts, true);
            }
        }
        
        return [];
    }
    
    /**
     * Save layouts configuration
     */
    public static function save_layouts($group_name, $field_name, $layouts) {
        global $wpdb;
        
        error_log("💾 save_layouts called - Group: {$group_name}, Field: {$field_name}");
        error_log("💾 Layouts to save: " . print_r($layouts, true));
        
        // Save to wp_yap_field_metadata (JSON schema groups)
        $metadata_table = $wpdb->prefix . 'yap_field_metadata';
        
        // Check if record exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$metadata_table} WHERE group_name = %s AND field_name = %s",
            $group_name,
            $field_name
        ));
        
        error_log("💾 Record exists in metadata: " . ($exists ? 'YES' : 'NO'));
        
        if ($exists) {
            // Get current field_config and update
            $current_config = $wpdb->get_var($wpdb->prepare(
                "SELECT field_config FROM {$metadata_table} WHERE group_name = %s AND field_name = %s",
                $group_name,
                $field_name
            ));
            
            $config = $current_config ? json_decode($current_config, true) : [];
            $config['layouts'] = $layouts;
            
            $result = $wpdb->update(
                $metadata_table,
                ['field_config' => json_encode($config)],
                [
                    'group_name' => $group_name,
                    'field_name' => $field_name
                ]
            );
            
            error_log("💾 UPDATE result: " . ($result !== false ? 'SUCCESS' : 'FAILED'));
            
            if ($result !== false) {
                return true;
            }
        } else {
            // Insert new record
            $config = ['layouts' => $layouts];
            
            $result = $wpdb->insert(
                $metadata_table,
                [
                    'group_name' => $group_name,
                    'field_name' => $field_name,
                    'field_config' => json_encode($config)
                ]
            );
            
            error_log("💾 INSERT result: " . ($result !== false ? 'SUCCESS' : 'FAILED'));
            error_log("💾 Last error: " . $wpdb->last_error);
            
            if ($result !== false) {
                return true;
            }
        }
        
        // Fallback to pattern table if it exists
        $pattern_table = $wpdb->prefix . 'yap_' . sanitize_title($group_name) . '_pattern';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$pattern_table}'");
        
        if ($table_exists) {
            $generated_name = sanitize_title($group_name) . '_' . sanitize_title($field_name);
            
            return $wpdb->update(
                $pattern_table,
                ['flexible_layouts' => json_encode($layouts)],
                ['generated_name' => $generated_name]
            );
        }
        
        return false;
    }
    
    /**
     * AJAX: Add new layout to flexible field
     */
    public function ajax_add_layout() {
        check_ajax_referer('yap_flexible_nonce', 'nonce');
        
        $group_name = sanitize_text_field($_POST['group_name'] ?? '');
        $field_name = sanitize_text_field($_POST['field_name'] ?? '');
        $layout_name = sanitize_text_field($_POST['layout_name'] ?? '');
        $layout_label = sanitize_text_field($_POST['layout_label'] ?? '');
        
        if (empty($group_name) || empty($field_name) || empty($layout_name)) {
            wp_send_json_error('Missing required parameters');
        }
        
        $layouts = self::get_layouts($group_name, $field_name);
        
        // Check if layout already exists
        foreach ($layouts as $layout) {
            if ($layout['name'] === $layout_name) {
                wp_send_json_error('Layout already exists');
            }
        }
        
        // Add new layout
        $layouts[] = [
            'name' => $layout_name,
            'label' => $layout_label,
            'display' => 'block', // or 'row' or 'table'
            'sub_fields' => [],
            'min' => 0,
            'max' => 0
        ];
        
        self::save_layouts($group_name, $field_name, $layouts);
        
        wp_send_json_success([
            'message' => 'Layout added successfully',
            'layouts' => $layouts
        ]);
    }
    
    /**
     * AJAX: Remove layout
     */
    public function ajax_remove_layout() {
        check_ajax_referer('yap_flexible_nonce', 'nonce');
        
        $group_name = sanitize_text_field($_POST['group_name'] ?? '');
        $field_name = sanitize_text_field($_POST['field_name'] ?? '');
        $layout_name = sanitize_text_field($_POST['layout_name'] ?? '');
        
        $layouts = self::get_layouts($group_name, $field_name);
        
        $layouts = array_filter($layouts, function($layout) use ($layout_name) {
            return $layout['name'] !== $layout_name;
        });
        
        $layouts = array_values($layouts); // Reindex array
        
        self::save_layouts($group_name, $field_name, $layouts);
        
        wp_send_json_success([
            'message' => 'Layout removed successfully',
            'layouts' => $layouts
        ]);
    }
    
    /**
     * AJAX: Update layout configuration
     */
    public function ajax_update_layout() {
        check_ajax_referer('yap_flexible_nonce', 'nonce');
        
        $group_name = sanitize_text_field($_POST['group_name'] ?? '');
        $field_name = sanitize_text_field($_POST['field_name'] ?? '');
        $layout_name = sanitize_text_field($_POST['layout_name'] ?? '');
        $layout_data = json_decode(stripslashes($_POST['layout_data'] ?? '{}'), true);
        
        $layouts = self::get_layouts($group_name, $field_name);
        
        foreach ($layouts as &$layout) {
            if ($layout['name'] === $layout_name) {
                $layout = array_merge($layout, $layout_data);
                break;
            }
        }
        
        self::save_layouts($group_name, $field_name, $layouts);
        
        wp_send_json_success([
            'message' => 'Layout updated successfully',
            'layouts' => $layouts
        ]);
    }
    
    /**
     * AJAX: Reorder layouts
     */
    public function ajax_reorder_layouts() {
        check_ajax_referer('yap_flexible_nonce', 'nonce');
        
        $group_name = sanitize_text_field($_POST['group_name'] ?? '');
        $field_name = sanitize_text_field($_POST['field_name'] ?? '');
        $order = json_decode(stripslashes($_POST['order'] ?? '[]'), true);
        
        $layouts = self::get_layouts($group_name, $field_name);
        
        // Reorder based on provided order array
        $reordered = [];
        foreach ($order as $layout_name) {
            foreach ($layouts as $layout) {
                if ($layout['name'] === $layout_name) {
                    $reordered[] = $layout;
                    break;
                }
            }
        }
        
        self::save_layouts($group_name, $field_name, $reordered);
        
        wp_send_json_success([
            'message' => 'Layouts reordered successfully',
            'layouts' => $reordered
        ]);
    }
    
    /**
     * AJAX: Get layouts for Visual Builder
     */
    public static function ajax_get_flexible_layouts() {
        check_ajax_referer('yap_flexible_nonce', 'nonce');
        
        $group_name = sanitize_text_field($_POST['group_name'] ?? '');
        $field_name = sanitize_text_field($_POST['field_name'] ?? '');
        
        $layouts = self::get_layouts($group_name, $field_name);
        
        wp_send_json_success([
            'layouts' => $layouts
        ]);
    }
    
    /**
     * AJAX: Save layouts from Visual Builder
     */
    public static function ajax_save_flexible_layouts() {
        check_ajax_referer('yap_flexible_nonce', 'nonce');
        
        error_log('🔵 ajax_save_flexible_layouts called');
        error_log('POST data: ' . print_r($_POST, true));
        
        $group_name = sanitize_text_field($_POST['group_name'] ?? '');
        $field_name = sanitize_text_field($_POST['field_name'] ?? '');
        $layouts = json_decode(stripslashes($_POST['layouts'] ?? '[]'), true);
        
        error_log("Group name: '{$group_name}'");
        error_log("Field name: '{$field_name}'");
        error_log('Layouts: ' . print_r($layouts, true));
        
        if (empty($group_name) || empty($field_name)) {
            $error_msg = "Missing parameters - group_name: '{$group_name}', field_name: '{$field_name}'";
            error_log('❌ ' . $error_msg);
            wp_send_json_error($error_msg);
        }
        
        $result = self::save_layouts($group_name, $field_name, $layouts);
        
        if ($result !== false) {
            wp_send_json_success([
                'message' => 'Layouts saved successfully',
                'layouts' => $layouts
            ]);
        } else {
            wp_send_json_error('Failed to save layouts');
        }
    }
    
    /**
     * Render flexible content field - router for metabox vs builder
     */
    public static function render_field($field, $value, $input_name, $input_id) {
        $group_name = $field['group_name'] ?? '';
        $field_name = $field['name'] ?? '';
        
        error_log("🎨 YAP_Flexible_Content::render_field called - group: {$group_name}, field: {$field_name}");
        
        // Check if we're in visual builder context
        $is_builder = self::is_in_builder_context();
        
        if ($is_builder) {
            // Use Visual Builder renderer
            error_log("🎨 Using BUILDER renderer for flexible content");
            require_once dirname(__FILE__) . '/flexible-content-builder.php';
            YAP_Flexible_Content_Builder_Renderer::render($field, $value, $input_name, $input_id);
            return;
        } else {
            // Use Metabox renderer
            error_log("🎨 Using METABOX renderer for flexible content");
            require_once dirname(__FILE__) . '/flexible-content-metabox.php';
            YAP_Flexible_Content_Metabox_Renderer::render($field, $value, $input_name, $input_id);
            return;
        }
    }
    
    /**
     * Detect if we're in visual builder context
     */
    private static function is_in_builder_context() {
        // Check if visual-builder.php defines a function or constant
        if (defined('YAP_VISUAL_BUILDER_ACTIVE')) {
            return true;
        }
        
        // Check current screen
        $screen = get_current_screen();
        if ($screen) {
            // If on a YAP admin page (not post/page), likely visual builder
            if (strpos($screen->id, 'yap-') !== false || strpos($screen->id, 'yet-another-plugin') !== false) {
                // Check if it's the visual builder page
                if (strpos($screen->id, 'visual-builder') !== false || strpos($screen->id, 'builder') !== false) {
                    return true;
                }
            }
        }
        
        // Check GET/POST parameters
        if (isset($_GET['page']) && (strpos($_GET['page'], 'visual-builder') !== false || strpos($_GET['page'], 'builder') !== false)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Internal method - render flexible content field in metabox (kept for reference)
     */
    private static function render_field_metabox($field, $value, $input_name, $input_id) {
        $group_name = $field['group_name'] ?? '';
        $field_name = $field['name'] ?? '';
        
        error_log("🎨 YAP_Flexible_Content::render_field called - group: {$group_name}, field: {$field_name}");
        
        // Get layouts configuration
        $layouts = self::get_layouts($group_name, $field_name);
        
        error_log("🎨 Layouts found: " . count($layouts));
        
        // Get a unique field ID for conditional logic
        $field_id = 'field_' . time();
        
        // Start wrapper with metadata
        echo '<div class="yap-preview-field-wrapper" data-field-id="' . esc_attr($field_id) . '" data-conditional="false" data-conditional-action="show" data-conditional-field="" data-conditional-operator="==" data-conditional-value="" data-conditional-message="" style="margin-bottom: 25px;">';
        
        // Label
        echo '<label for="' . esc_attr($input_id) . '" style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px;">';
        echo esc_html($field['label'] ?? 'Flexible Content');
        echo '</label>';
        
        if (empty($layouts)) {
            // Show empty state with preview structure
            echo '<div style="border: 2px dashed #0073aa; border-radius: 4px; padding: 15px; background: #f9f9f9;">';
            echo '<div style="background: #f0f0f0; padding: 15px; border-radius: 4px; margin-top: 10px;">';
            echo '<p style="font-weight: 600; margin-bottom: 15px; color: #333;">📋 ' . esc_html($field['label'] ?? 'Flexible Content') . ' 1 (Przykład)</p>';
            
            echo '<div style="margin-bottom: 12px;">';
            echo '<label style="display: block; font-weight: 500; margin-bottom: 6px; font-size: 13px;">';
            echo esc_html($field['label'] ?? 'Flexible Content') . ' 1';
            echo '</label>';
            echo '<div style="border: 2px dashed #999; border-radius: 4px; padding: 15px; text-align: center; background: #f9f9f9;">';
            echo '<p style="color: #666; margin: 0;">⚙️ Kliknij w edytorze aby skonfigurować sekcje</p>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
            echo '<button type="button" style="margin-top: 15px; padding: 8px 15px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">';
            echo '+ Dodaj rząd';
            echo '</button>';
            echo '</div>';
            
            // Conditional message box
            echo '<div class="yap-conditional-message-box" style="display: none; margin-top: 10px; padding: 12px; border-radius: 4px;"></div>';
            
            echo '</div>'; // .yap-preview-field-wrapper
            return;
        }
        
        // Parse value
        if (!is_array($value)) {
            $value = !empty($value) ? json_decode($value, true) : [];
        }
        if (!is_array($value)) {
            $value = [];
        }
        
        $flexible_id = sanitize_key($input_id);
        $field_id = 'field_' . time();
        
        // Start wrapper with metadata (same as empty state)
        echo '<div class="yap-preview-field-wrapper" data-field-id="' . esc_attr($field_id) . '" data-conditional="false" data-conditional-action="show" data-conditional-field="" data-conditional-operator="==" data-conditional-value="" data-conditional-message="" style="margin-bottom: 25px;">';
        
        // Label (same as empty state)
        echo '<label for="' . esc_attr($input_id) . '" style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px;">';
        echo esc_html($field['label'] ?? 'Flexible Content');
        echo '</label>';
        
        echo '<div class="yap-flexible-container" data-flexible-id="' . esc_attr($flexible_id) . '" data-group="' . esc_attr($group_name) . '" data-field="' . esc_attr($field_name) . '" style="border: 2px dashed #0073aa; border-radius: 4px; padding: 15px; background: #f9f9f9;">';
        
        // Hidden input to store JSON value
        echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr(json_encode($value)) . '" class="yap-flexible-value">';
        
        // Usage info
        echo '<div class="yap-flexible-info" style="margin-bottom: 10px; padding: 8px 12px; background: #f0f6fc; border-left: 3px solid #72aee6; font-size: 12px;">';
        echo '<strong>ℹ️ Jak użyć:</strong> <code style="background: white; padding: 2px 6px; border-radius: 3px;">';
        echo htmlspecialchars("<?php yap_flexible('{$group_name}', '{$field_name}'); ?>");
        echo '</code>';
        echo '</div>';
        
        // Sections container
        echo '<div class="yap-flexible-sections" id="' . esc_attr($flexible_id) . '_sections">';
        
        if (empty($value)) {
            // Show example row when no data
            echo '<div style="background: #f0f0f0; padding: 15px; border-radius: 4px; margin-top: 10px;">';
            echo '<p style="font-weight: 600; margin-bottom: 15px; color: #333;">📋 Row 1 (Example)</p>';
            
            foreach ($layouts as $layout) {
                echo '<div style="margin-bottom: 12px;">';
                echo '<label style="display: block; font-weight: 500; margin-bottom: 6px; font-size: 13px;">';
                echo esc_html($layout['label']);
                echo '</label>';
                echo '<div style="border: 2px dashed #999; border-radius: 4px; padding: 15px; text-align: center; background: #f9f9f9;">';
                echo '<p style="color: #666; margin: 0;">⚙️ Kliknij w edytorze aby skonfigurować sekcje</p>';
                echo '</div>';
                echo '</div>';
                break; // Only show first layout as example
            }
            
            echo '</div>';
        } else {
            foreach ($value as $section_index => $section_data) {
                self::render_section($layouts, $section_data, $section_index, $flexible_id, $input_name);
            }
        }
        
        echo '</div>'; // .yap-flexible-sections
        
        // Add section buttons
        echo '<div class="yap-flexible-actions" style="margin-top: 15px; padding: 15px; background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%); border: none; border-radius: 4px; display: flex; flex-wrap: wrap; gap: 8px;">';
        
        foreach ($layouts as $layout) {
            echo '<button type="button" class="button button-primary yap-add-flexible-section" data-flexible-id="' . esc_attr($flexible_id) . '" data-layout="' . esc_attr($layout['name']) . '" style="background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); border: none; color: white; cursor: pointer; font-weight: 600; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,115,170,0.15);">';
            echo '➕ ' . esc_html($layout['label']);
            echo '</button>';
        }
        
        echo '</div>'; // .yap-flexible-actions
        
        echo '</div>'; // .yap-flexible-container
        
        // Conditional message box (same as empty state)
        echo '<div class="yap-conditional-message-box" style="display: none; margin-top: 10px; padding: 12px; border-radius: 4px;"></div>';
        
        echo '</div>'; // .yap-preview-field-wrapper
        
        // Store layouts data in JavaScript
        echo '<script>
            if (typeof yapFlexibleLayouts === "undefined") {
                window.yapFlexibleLayouts = {};
            }
            yapFlexibleLayouts["' . esc_js($flexible_id) . '"] = ' . json_encode($layouts) . ';
        </script>';
    }
    
    /**
     * Render single flexible section
     */
    private static function render_section($layouts, $section_data, $section_index, $flexible_id, $input_name) {
        $layout_type = $section_data['layout'] ?? '';
        $fields_data = $section_data['fields'] ?? [];
        
        // Find layout configuration
        $layout = null;
        foreach ($layouts as $l) {
            if ($l['name'] === $layout_type) {
                $layout = $l;
                break;
            }
        }
        
        if (!$layout) {
            return;
        }
        
        echo '<div class="yap-flexible-section" data-section-index="' . esc_attr($section_index) . '" data-layout="' . esc_attr($layout_type) . '" style="margin-bottom: 16px; padding: 16px; background: linear-gradient(135deg, #ffffff 0%, #f5f7fa 100%); border: 2px solid #0073aa; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,115,170,0.1); transition: all 0.3s ease;">';
        
        // Section header
        echo '<div class="yap-flexible-section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e0e0e0;">';
        echo '<div style="display: flex; align-items: center; gap: 12px; flex-grow: 1;">';
        echo '<span class="dashicons dashicons-menu" style="cursor: move; color: #0073aa; font-size: 20px;"></span>';
        echo '<div>';
        echo '<strong style="font-size: 15px; color: #0073aa; display: block; margin-bottom: 4px;">' . esc_html($layout['label']) . '</strong>';
        echo '<span style="font-size: 11px; color: #666; background: #e8f2f9; padding: 2px 8px; border-radius: 3px; display: inline-block;">' . esc_html($layout_type) . '</span>';
        echo '</div>';
        echo '</div>';
        echo '<div class="yap-flexible-section-actions" style="display: flex; gap: 8px;">';
        echo '<button type="button" class="button button-small yap-collapse-section" title="Zwiń/Rozwiń" style="padding: 6px 10px; border-radius: 4px; transition: all 0.2s ease;">−</button>';
        echo '<button type="button" class="button button-small yap-duplicate-section" title="Duplikuj" style="padding: 6px 10px; border-radius: 4px; transition: all 0.2s ease;">📋</button>';
        echo '<button type="button" class="button button-small yap-remove-section" title="Usuń" style="color: #fff; background: #dc3232; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; transition: all 0.2s ease;">✕</button>';
        echo '</div>';
        echo '</div>';
        
        // Section fields
        echo '<div class="yap-flexible-section-fields" style="display: grid; gap: 16px;">';
        
        foreach ($layout['sub_fields'] as $sub_field) {
            $field_value = $fields_data[$sub_field['name']] ?? '';
            $sub_field_name = $input_name . '[' . $section_index . '][fields][' . $sub_field['name'] . ']';
            $sub_field_id = $flexible_id . '_' . $section_index . '_' . $sub_field['name'];
            
            echo '<div class="yap-field-wrapper" style="padding: 12px; background: white; border: 1px solid #e0e0e0; border-radius: 6px; transition: all 0.2s ease;">';
            echo '<label style="display: block; margin-bottom: 8px; font-weight: 600; color: #0073aa; font-size: 14px;">' . esc_html($sub_field['label']) . '</label>';
            
            // Render field input
            yap_render_simple_field($sub_field, $field_value, $sub_field_name, $sub_field_id);
            
            echo '</div>';
        }
        
        echo '</div>'; // .yap-flexible-section-fields
        
        echo '</div>'; // .yap-flexible-section
    }
}

// Initialize
YAP_Flexible_Content::get_instance();

/**
 * Template function to display flexible content
 */
function yap_flexible($group_name, $field_name, $post_id = null) {
    if ($post_id === null) {
        $post_id = get_the_ID();
    }
    
    $value = yap_get_field($group_name, $field_name, $post_id);
    
    if (!is_array($value)) {
        $value = json_decode($value, true);
    }
    
    if (!is_array($value) || empty($value)) {
        return;
    }
    
    foreach ($value as $section) {
        $layout = $section['layout'] ?? '';
        $fields = $section['fields'] ?? [];
        
        // Allow theme to handle rendering
        $template = locate_template("flexible/{$layout}.php");
        
        if ($template) {
            // Pass fields as variables to template
            set_query_var('flexible_fields', $fields);
            set_query_var('flexible_layout', $layout);
            load_template($template, false);
        } else {
            // Fallback: print structure
            echo '<div class="flexible-section flexible-' . esc_attr($layout) . '">';
            echo '<h3>' . esc_html(ucfirst(str_replace('_', ' ', $layout))) . '</h3>';
            echo '<pre>' . print_r($fields, true) . '</pre>';
            echo '</div>';
        }
    }
}

/**
 * Get flexible content value
 */
function yap_get_flexible($group_name, $field_name, $post_id = null) {
    if ($post_id === null) {
        $post_id = get_the_ID();
    }
    
    $value = yap_get_field($group_name, $field_name, $post_id);
    
    if (!is_array($value)) {
        $value = json_decode($value, true);
    }
    
    return is_array($value) ? $value : [];
}
