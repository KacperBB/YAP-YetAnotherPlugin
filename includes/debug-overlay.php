<?php
/**
 * YAP Debug Overlay
 * Developer panel showing field information in WordPress editor
 */

class YAP_Debug_Overlay {
    private static $instance = null;
    private $enabled = false;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Check if debug mode is enabled first
        if (!defined('YAP_DEBUG') || !YAP_DEBUG) {
            return;
        }
        
        // Delay user capability check until WordPress is fully loaded
        add_action('init', function() {
            $this->enabled = function_exists('current_user_can') && current_user_can('manage_options');
            
            if ($this->enabled) {
                add_action('admin_footer', [$this, 'render_overlay']);
                add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
                add_action('wp_ajax_yap_debug_get_field_info', [$this, 'ajax_get_field_info']);
            }
        });
    }

    /**
     * Enqueue CSS and JS
     */
    public function enqueue_assets() {
        if (!$this->should_show()) {
            return;
        }

        wp_enqueue_style('yap-debug-overlay', plugin_dir_url(__FILE__) . '../assets/debug-overlay.css', [], '1.0');
        wp_enqueue_script('yap-debug-overlay', plugin_dir_url(__FILE__) . '../assets/debug-overlay.js', ['jquery'], '1.0', true);
        
        wp_localize_script('yap-debug-overlay', 'yapDebug', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yap_debug_nonce'),
            'post_id' => get_the_ID()
        ]);
    }

    /**
     * Check if overlay should be shown
     */
    private function should_show() {
        global $pagenow;
        return in_array($pagenow, ['post.php', 'post-new.php']);
    }

    /**
     * Render debug overlay
     */
    public function render_overlay() {
        if (!$this->should_show()) {
            return;
        }

        $post_id = get_the_ID();
        $groups = $this->get_post_groups($post_id);
        
        ?>
        <div id="yap-debug-overlay" class="yap-debug-overlay">
            <div class="yap-debug-header">
                <h3>🔧 YAP Debug Panel</h3>
                <button class="yap-debug-toggle" onclick="yapDebugToggle()">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>
            </div>
            
            <div class="yap-debug-content">
                <div class="yap-debug-tabs">
                    <button class="yap-debug-tab active" data-tab="fields">Fields</button>
                    <button class="yap-debug-tab" data-tab="groups">Groups</button>
                    <button class="yap-debug-tab" data-tab="hooks">Hooks</button>
                    <button class="yap-debug-tab" data-tab="cache">Cache</button>
                    <button class="yap-debug-tab" data-tab="queries">Queries</button>
                </div>

                <!-- Fields Tab -->
                <div class="yap-debug-tab-content active" id="yap-tab-fields">
                    <h4>Post Fields (ID: <?php echo $post_id; ?>)</h4>
                    <?php $this->render_fields_table($post_id); ?>
                </div>

                <!-- Groups Tab -->
                <div class="yap-debug-tab-content" id="yap-tab-groups">
                    <h4>Field Groups</h4>
                    <?php $this->render_groups_table($groups); ?>
                </div>

                <!-- Hooks Tab -->
                <div class="yap-debug-tab-content" id="yap-tab-hooks">
                    <h4>Registered Hooks</h4>
                    <?php $this->render_hooks_info(); ?>
                </div>

                <!-- Cache Tab -->
                <div class="yap-debug-tab-content" id="yap-tab-cache">
                    <h4>Cache Statistics</h4>
                    <?php $this->render_cache_stats(); ?>
                </div>

                <!-- Queries Tab -->
                <div class="yap-debug-tab-content" id="yap-tab-queries">
                    <h4>Database Queries</h4>
                    <?php $this->render_queries_info(); ?>
                </div>
            </div>
        </div>

        <style>
            .yap-debug-overlay {
                position: fixed;
                bottom: 0;
                right: 20px;
                width: 600px;
                max-height: 80vh;
                background: #1e1e1e;
                color: #d4d4d4;
                border-radius: 8px 8px 0 0;
                box-shadow: 0 -5px 20px rgba(0,0,0,0.3);
                z-index: 99999;
                font-family: 'Courier New', monospace;
                font-size: 12px;
            }
            .yap-debug-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 15px;
                background: #2d2d2d;
                border-bottom: 2px solid #007cba;
                cursor: move;
            }
            .yap-debug-header h3 {
                margin: 0;
                font-size: 14px;
                color: #007cba;
            }
            .yap-debug-toggle {
                background: none;
                border: none;
                color: #d4d4d4;
                cursor: pointer;
                padding: 5px;
            }
            .yap-debug-content {
                max-height: 600px;
                overflow-y: auto;
                padding: 15px;
            }
            .yap-debug-tabs {
                display: flex;
                gap: 5px;
                margin-bottom: 15px;
                border-bottom: 1px solid #3d3d3d;
            }
            .yap-debug-tab {
                background: none;
                border: none;
                color: #888;
                padding: 8px 15px;
                cursor: pointer;
                border-bottom: 2px solid transparent;
                transition: all 0.2s;
            }
            .yap-debug-tab:hover {
                color: #d4d4d4;
            }
            .yap-debug-tab.active {
                color: #007cba;
                border-bottom-color: #007cba;
            }
            .yap-debug-tab-content {
                display: none;
            }
            .yap-debug-tab-content.active {
                display: block;
            }
            .yap-debug-table {
                width: 100%;
                border-collapse: collapse;
            }
            .yap-debug-table th {
                background: #2d2d2d;
                padding: 8px;
                text-align: left;
                border-bottom: 1px solid #3d3d3d;
                color: #007cba;
            }
            .yap-debug-table td {
                padding: 8px;
                border-bottom: 1px solid #3d3d3d;
            }
            .yap-debug-table tr:hover {
                background: #2d2d2d;
            }
            .yap-debug-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: bold;
            }
            .yap-debug-badge.success { background: #46b450; color: white; }
            .yap-debug-badge.warning { background: #ffb900; color: black; }
            .yap-debug-badge.error { background: #dc3232; color: white; }
            .yap-debug-badge.info { background: #007cba; color: white; }
            .yap-debug-code {
                background: #1a1a1a;
                padding: 10px;
                border-radius: 4px;
                overflow-x: auto;
                margin: 10px 0;
            }
            .yap-debug-stat {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #3d3d3d;
            }
            .yap-debug-stat strong {
                color: #007cba;
            }
        </style>
        <?php
    }

    /**
     * Render fields table
     */
    private function render_fields_table($post_id) {
        global $wpdb;
        
        $groups = $this->get_post_groups($post_id);
        
        if (empty($groups)) {
            echo '<p>No fields found for this post.</p>';
            return;
        }

        echo '<table class="yap-debug-table">';
        echo '<thead><tr>';
        echo '<th>Field Name</th>';
        echo '<th>Type</th>';
        echo '<th>Group</th>';
        echo '<th>Value</th>';
        echo '<th>Source</th>';
        echo '<th>Hooks</th>';
        echo '</tr></thead><tbody>';

        foreach ($groups as $group_name) {
            $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
            $safe_table = esc_sql($data_table);
            
            $fields = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM `{$safe_table}` WHERE associated_id = %d LIMIT 20",
                $post_id
            ));

            foreach ($fields as $field) {
                $hooks = yap_get_field_hooks($field->user_name);
                $hook_count = array_sum($hooks);
                
                $value_preview = is_serialized($field->field_value) 
                    ? 'Serialized data' 
                    : substr($field->field_value, 0, 50);
                
                echo '<tr>';
                echo '<td><strong>' . esc_html($field->user_name) . '</strong><br><small>' . esc_html($field->generated_name) . '</small></td>';
                echo '<td><span class="yap-debug-badge info">' . esc_html($field->field_type) . '</span></td>';
                echo '<td>' . esc_html($group_name) . '</td>';
                echo '<td><code>' . esc_html($value_preview) . '</code></td>';
                echo '<td><span class="yap-debug-badge success">DB</span></td>';
                echo '<td>' . ($hook_count > 0 ? "<span class='yap-debug-badge warning'>{$hook_count}</span>" : '-') . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
    }

    /**
     * Render groups table
     */
    private function render_groups_table($groups) {
        global $wpdb;

        echo '<table class="yap-debug-table">';
        echo '<thead><tr>';
        echo '<th>Group Name</th>';
        echo '<th>Pattern Table</th>';
        echo '<th>Data Table</th>';
        echo '<th>Field Count</th>';
        echo '</tr></thead><tbody>';

        foreach ($groups as $group_name) {
            $pattern_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
            $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
            
            $safe_pattern = esc_sql($pattern_table);
            $field_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$safe_pattern}`");

            echo '<tr>';
            echo '<td><strong>' . esc_html($group_name) . '</strong></td>';
            echo '<td><code>' . esc_html($pattern_table) . '</code></td>';
            echo '<td><code>' . esc_html($data_table) . '</code></td>';
            echo '<td>' . $field_count . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Render hooks info
     */
    private function render_hooks_info() {
        $hooks = yap_get_field_hooks();
        
        if (empty($hooks)) {
            echo '<p>No field hooks registered.</p>';
            return;
        }

        foreach ($hooks as $event => $fields) {
            echo '<h5>' . esc_html(ucwords(str_replace('_', ' ', $event))) . '</h5>';
            echo '<ul>';
            foreach ($fields as $field_name => $callbacks) {
                echo '<li><strong>' . esc_html($field_name) . '</strong>: ' . count($callbacks) . ' callback(s)</li>';
            }
            echo '</ul>';
        }
    }

    /**
     * Render cache stats
     */
    private function render_cache_stats() {
        $stats = yap_cache_stats();
        
        echo '<div class="yap-debug-stat"><span>Cache Driver:</span><strong>' . $stats['driver'] . '</strong></div>';
        echo '<div class="yap-debug-stat"><span>Cache Hits:</span><strong>' . $stats['hits'] . '</strong></div>';
        echo '<div class="yap-debug-stat"><span>Cache Misses:</span><strong>' . $stats['misses'] . '</strong></div>';
        echo '<div class="yap-debug-stat"><span>Hit Rate:</span><strong>' . $stats['hit_rate'] . '</strong></div>';
        echo '<div class="yap-debug-stat"><span>Memory Items:</span><strong>' . $stats['memory_items'] . '</strong></div>';
        echo '<div class="yap-debug-stat"><span>Sets:</span><strong>' . $stats['sets'] . '</strong></div>';
        
        echo '<button onclick="yapFlushCache()" class="button button-secondary" style="margin-top: 15px;">Flush Cache</button>';
    }

    /**
     * Render queries info
     */
    private function render_queries_info() {
        if (defined('SAVEQUERIES') && SAVEQUERIES) {
            global $wpdb;
            
            $yap_queries = array_filter($wpdb->queries, function($query) {
                return strpos($query[0], 'yap_') !== false || strpos($query[0], '_group_') !== false;
            });

            echo '<p>Total YAP Queries: <strong>' . count($yap_queries) . '</strong></p>';
            
            foreach ($yap_queries as $query) {
                echo '<div class="yap-debug-code">';
                echo '<strong>Query:</strong> ' . esc_html($query[0]) . '<br>';
                echo '<strong>Time:</strong> ' . $query[1] . 's';
                echo '</div>';
            }
        } else {
            echo '<p>Enable SAVEQUERIES in wp-config.php to see query information:</p>';
            echo '<div class="yap-debug-code">define(\'SAVEQUERIES\', true);</div>';
        }
    }

    /**
     * Get groups for post
     */
    private function get_post_groups($post_id) {
        global $wpdb;
        
        $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}group_%_pattern'");
        $groups = [];
        
        foreach ($tables as $table) {
            $table_name = current((array)$table);
            $group_name = str_replace([$wpdb->prefix . 'group_', '_pattern'], '', $table_name);
            $groups[] = $group_name;
        }
        
        return $groups;
    }

    /**
     * AJAX: Get field info
     */
    public function ajax_get_field_info() {
        check_ajax_referer('yap_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $field_name = sanitize_text_field($_POST['field_name'] ?? '');
        $post_id = intval($_POST['post_id'] ?? 0);

        // Get field data
        $field_data = $this->get_field_details($field_name, $post_id);
        
        wp_send_json_success($field_data);
    }

    /**
     * Get detailed field information
     */
    private function get_field_details($field_name, $post_id) {
        global $wpdb;
        
        // Search across all groups
        $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}group_%_data'");
        
        foreach ($tables as $table) {
            $table_name = current((array)$table);
            $safe_table = esc_sql($table_name);
            
            $field = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM `{$safe_table}` WHERE user_name = %s AND associated_id = %d",
                $field_name,
                $post_id
            ));
            
            if ($field) {
                return [
                    'field' => $field,
                    'table' => $table_name,
                    'hooks' => yap_get_field_hooks($field_name),
                    'transformers' => YAP_Transformers::get_instance()->get_all_transformers(),
                    'sanitizers' => YAP_Sanitizers::get_instance()->get_all_sanitizers()
                ];
            }
        }
        
        return null;
    }
}

// Initialize if debug mode enabled
if (defined('YAP_DEBUG') && YAP_DEBUG) {
    YAP_Debug_Overlay::get_instance();
}
