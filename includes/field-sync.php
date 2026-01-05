<?php
/**
 * YAP Field Sync
 * Synchronize field groups between environments (DEV → STAGE → PROD)
 * Visual diff viewer and conflict resolution
 * 
 * Features:
 * - Compare schemas across environments
 * - Visual diff highlighting (added/removed/modified)
 * - One-click sync
 * - Rollback capability
 * - Export/import between servers
 * 
 * @package YAP
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Sync {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'add_sync_page'], 100);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_sync_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_yap_sync_compare', [$this, 'ajax_compare_schemas']);
        add_action('wp_ajax_yap_sync_push', [$this, 'ajax_push_schema']);
        add_action('wp_ajax_yap_sync_pull', [$this, 'ajax_pull_schema']);
        add_action('wp_ajax_yap_sync_export_package', [$this, 'ajax_export_package']);
        add_action('wp_ajax_yap_sync_import_package', [$this, 'ajax_import_package']);
    }
    
    /**
     * Add sync admin page
     */
    public function add_sync_page() {
        add_submenu_page(
            'yap-admin-page',
            'Field Sync',
            '🔄 Field Sync',
            'manage_options',
            'yap-field-sync',
            [$this, 'render_sync_page']
        );
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_sync_assets($hook) {
        if ($hook !== 'yet-another-plugin_page_yap-field-sync') return;
        
        wp_enqueue_style('yap-advanced-features', plugin_dir_url(__DIR__) . 'includes/css/yap-advanced-features.css', [], '1.4.0');
        wp_enqueue_script('yap-sync-js', plugin_dir_url(__DIR__) . 'includes/js/field-sync.js', ['jquery'], '1.0', true);
        
        wp_localize_script('yap-sync-js', 'yapSync', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yap_sync_nonce')
        ]);
    }
    
    /**
     * Render sync page
     */
    public function render_sync_page() {
        global $wpdb;
        
        $groups = $wpdb->get_results("SELECT * FROM yap_groups ORDER BY group_name ASC");
        
        ?>
        <div class="wrap yap-sync-wrap">
            <h1>🔄 Field Sync - Environment Synchronization</h1>
            <p class="description">Synchronize field groups between development, staging, and production environments.</p>
            
            <div class="yap-sync-container">
                <!-- Environment Selection -->
                <div class="yap-sync-environments">
                    <div class="yap-environment-card yap-env-local active">
                        <h3>🖥️ Local (Current)</h3>
                        <p><?php echo home_url(); ?></p>
                        <span class="yap-env-badge yap-env-development">DEVELOPMENT</span>
                    </div>
                    
                    <div class="yap-environment-card yap-env-remote">
                        <h3>🌐 Remote Environment</h3>
                        <div class="yap-remote-config">
                            <input type="text" id="yap-remote-url" placeholder="https://staging.example.com" class="regular-text">
                            <input type="text" id="yap-remote-key" placeholder="API Key" class="regular-text">
                            <button type="button" id="yap-connect-remote" class="button">Connect</button>
                        </div>
                        <div id="yap-remote-status"></div>
                    </div>
                </div>
                
                <!-- Group Selection -->
                <div class="yap-sync-group-selection">
                    <h2>Select Field Group to Sync</h2>
                    <select id="yap-sync-group-select" class="widefat">
                        <option value="">-- Select Group --</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo esc_attr($group->group_name); ?>">
                                <?php echo esc_html($group->group_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div class="yap-sync-actions">
                        <button type="button" id="yap-compare-schemas" class="button button-primary">
                            <span class="dashicons dashicons-visibility"></span> Compare Schemas
                        </button>
                        
                        <button type="button" id="yap-export-package" class="button">
                            <span class="dashicons dashicons-download"></span> Export Package
                        </button>
                        
                        <label for="yap-import-file" class="button">
                            <span class="dashicons dashicons-upload"></span> Import Package
                            <input type="file" id="yap-import-file" accept=".json" style="display:none;">
                        </label>
                    </div>
                </div>
                
                <!-- Diff Viewer -->
                <div id="yap-diff-viewer" style="display:none;">
                    <h2>Schema Comparison</h2>
                    
                    <div class="yap-diff-stats">
                        <div class="yap-stat yap-stat-added">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <strong id="yap-added-count">0</strong> Added
                        </div>
                        <div class="yap-stat yap-stat-modified">
                            <span class="dashicons dashicons-edit"></span>
                            <strong id="yap-modified-count">0</strong> Modified
                        </div>
                        <div class="yap-stat yap-stat-removed">
                            <span class="dashicons dashicons-minus"></span>
                            <strong id="yap-removed-count">0</strong> Removed
                        </div>
                    </div>
                    
                    <div class="yap-diff-container">
                        <div class="yap-diff-column">
                            <h3>Local Schema</h3>
                            <div id="yap-local-schema" class="yap-schema-view"></div>
                        </div>
                        
                        <div class="yap-diff-column">
                            <h3>Remote Schema</h3>
                            <div id="yap-remote-schema" class="yap-schema-view"></div>
                        </div>
                    </div>
                    
                    <div class="yap-sync-direction">
                        <button type="button" id="yap-pull-schema" class="button button-secondary">
                            <span class="dashicons dashicons-download"></span> Pull from Remote (Import)
                        </button>
                        
                        <button type="button" id="yap-push-schema" class="button button-primary">
                            <span class="dashicons dashicons-upload"></span> Push to Remote (Export)
                        </button>
                    </div>
                </div>
                
                <!-- Sync History -->
                <div class="yap-sync-history">
                    <h2>Sync History</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Group</th>
                                <th>Action</th>
                                <th>Environment</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="yap-sync-history-body">
                            <?php echo $this->get_sync_history_html(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <style>
        .yap-sync-wrap {
            margin: 20px;
        }
        
        .yap-sync-container {
            background: #fff;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .yap-sync-environments {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .yap-environment-card {
            border: 2px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .yap-environment-card.active {
            border-color: #2271b1;
            background: #f0f6fc;
        }
        
        .yap-env-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .yap-env-development {
            background: #ffc107;
            color: #000;
        }
        
        .yap-env-staging {
            background: #2196f3;
            color: #fff;
        }
        
        .yap-env-production {
            background: #f44336;
            color: #fff;
        }
        
        .yap-remote-config {
            margin: 15px 0;
        }
        
        .yap-remote-config input {
            margin-bottom: 10px;
            width: 100%;
        }
        
        .yap-sync-group-selection {
            margin-bottom: 30px;
        }
        
        .yap-sync-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        #yap-diff-viewer {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #ddd;
        }
        
        .yap-diff-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .yap-stat {
            flex: 1;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .yap-stat-added {
            background: #d4edda;
            border: 2px solid #28a745;
        }
        
        .yap-stat-modified {
            background: #fff3cd;
            border: 2px solid #ffc107;
        }
        
        .yap-stat-removed {
            background: #f8d7da;
            border: 2px solid #dc3545;
        }
        
        .yap-diff-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .yap-schema-view {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            max-height: 400px;
            overflow-y: auto;
            background: #f9f9f9;
            font-family: monospace;
            font-size: 12px;
        }
        
        .yap-field-diff {
            padding: 8px;
            margin: 4px 0;
            border-radius: 4px;
        }
        
        .yap-field-added {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        
        .yap-field-modified {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        .yap-field-removed {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        
        .yap-field-unchanged {
            background: #fff;
            border-left: 4px solid #6c757d;
        }
        
        .yap-sync-direction {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        
        .yap-sync-history {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #ddd;
        }
        </style>
        <?php
    }
    
    /**
     * AJAX: Compare schemas
     */
    public function ajax_compare_schemas() {
        check_ajax_referer('yap_sync_nonce', 'nonce');
        
        $group_name = sanitize_key($_POST['group_name']);
        $remote_url = esc_url_raw($_POST['remote_url']);
        
        $local_schema = $this->get_local_schema($group_name);
        $remote_schema = $this->get_remote_schema($remote_url, $group_name);
        
        $diff = $this->compare_schemas($local_schema, $remote_schema);
        
        wp_send_json_success([
            'local' => $local_schema,
            'remote' => $remote_schema,
            'diff' => $diff
        ]);
    }
    
    /**
     * AJAX: Push schema to remote
     */
    public function ajax_push_schema() {
        check_ajax_referer('yap_sync_nonce', 'nonce');
        
        $group_name = sanitize_key($_POST['group_name']);
        $remote_url = esc_url_raw($_POST['remote_url']);
        $remote_key = sanitize_text_field($_POST['remote_key']);
        
        $schema = $this->get_local_schema($group_name);
        
        // Push to remote (requires REST API endpoint)
        $response = wp_remote_post($remote_url . '/wp-json/yap/v1/sync/import', [
            'body' => json_encode(['schema' => $schema]),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $remote_key
            ]
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }
        
        $this->log_sync_action($group_name, 'push', $remote_url, 'success');
        
        wp_send_json_success(['message' => 'Schema pushed successfully']);
    }
    
    /**
     * AJAX: Pull schema from remote
     */
    public function ajax_pull_schema() {
        check_ajax_referer('yap_sync_nonce', 'nonce');
        
        $group_name = sanitize_key($_POST['group_name']);
        $remote_url = esc_url_raw($_POST['remote_url']);
        
        $remote_schema = $this->get_remote_schema($remote_url, $group_name);
        
        if (!$remote_schema) {
            wp_send_json_error(['message' => 'Failed to fetch remote schema']);
        }
        
        // Apply remote schema locally
        $this->apply_schema($remote_schema);
        
        $this->log_sync_action($group_name, 'pull', $remote_url, 'success');
        
        wp_send_json_success(['message' => 'Schema pulled successfully']);
    }
    
    /**
     * AJAX: Export sync package
     */
    public function ajax_export_package() {
        check_ajax_referer('yap_sync_nonce', 'nonce');
        
        $group_name = sanitize_key($_POST['group_name']);
        $schema = $this->get_local_schema($group_name);
        
        $package = [
            'version' => '1.0',
            'timestamp' => current_time('timestamp'),
            'source' => home_url(),
            'group' => $group_name,
            'schema' => $schema
        ];
        
        wp_send_json_success([
            'package' => json_encode($package, JSON_PRETTY_PRINT),
            'filename' => 'yap-sync-' . $group_name . '-' . date('Y-m-d-H-i-s') . '.json'
        ]);
    }
    
    /**
     * AJAX: Import sync package
     */
    public function ajax_import_package() {
        check_ajax_referer('yap_sync_nonce', 'nonce');
        
        if (!isset($_FILES['package'])) {
            wp_send_json_error(['message' => 'No file uploaded']);
        }
        
        $file = $_FILES['package'];
        $content = file_get_contents($file['tmp_name']);
        $package = json_decode($content, true);
        
        if (!$package || !isset($package['schema'])) {
            wp_send_json_error(['message' => 'Invalid package format']);
        }
        
        $this->apply_schema($package['schema']);
        
        $this->log_sync_action($package['group'], 'import', $package['source'], 'success');
        
        wp_send_json_success(['message' => 'Package imported successfully']);
    }
    
    /**
     * Get local schema
     */
    private function get_local_schema($group_name) {
        global $wpdb;
        
        $fields = $wpdb->get_results("DESCRIBE {$group_name}");
        
        $schema = [
            'name' => $group_name,
            'fields' => []
        ];
        
        foreach ($fields as $field) {
            if ($field->Field === 'id') continue;
            
            $schema['fields'][] = [
                'name' => $field->Field,
                'type' => $field->Type,
                'null' => $field->Null,
                'default' => $field->Default,
                'key' => $field->Key
            ];
        }
        
        return $schema;
    }
    
    /**
     * Get remote schema
     */
    private function get_remote_schema($remote_url, $group_name) {
        $response = wp_remote_get($remote_url . '/wp-json/yap/v1/sync/export/' . $group_name);
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Compare schemas and generate diff
     */
    private function compare_schemas($local, $remote) {
        $diff = [
            'added' => [],
            'modified' => [],
            'removed' => [],
            'unchanged' => []
        ];
        
        $local_fields = array_column($local['fields'], null, 'name');
        $remote_fields = array_column($remote['fields'], null, 'name');
        
        // Find added fields (in remote but not local)
        foreach ($remote_fields as $name => $field) {
            if (!isset($local_fields[$name])) {
                $diff['added'][] = $field;
            }
        }
        
        // Find removed fields (in local but not remote)
        foreach ($local_fields as $name => $field) {
            if (!isset($remote_fields[$name])) {
                $diff['removed'][] = $field;
            }
        }
        
        // Find modified fields
        foreach ($local_fields as $name => $local_field) {
            if (isset($remote_fields[$name])) {
                $remote_field = $remote_fields[$name];
                
                if ($local_field['type'] !== $remote_field['type'] || 
                    $local_field['null'] !== $remote_field['null']) {
                    $diff['modified'][] = [
                        'local' => $local_field,
                        'remote' => $remote_field
                    ];
                } else {
                    $diff['unchanged'][] = $local_field;
                }
            }
        }
        
        return $diff;
    }
    
    /**
     * Apply schema locally
     */
    private function apply_schema($schema) {
        global $wpdb;
        
        $group_name = $schema['name'];
        
        // Backup existing table
        $this->backup_table($group_name);
        
        // Drop and recreate
        $wpdb->query("DROP TABLE IF EXISTS {$group_name}");
        
        yap_add_group($group_name);
        
        foreach ($schema['fields'] as $field) {
            yap_add_field($group_name, $field['name'], $this->mysql_to_yap_type($field['type']));
        }
    }
    
    /**
     * Backup table before sync
     */
    private function backup_table($table_name) {
        global $wpdb;
        
        $backup_name = $table_name . '_backup_' . time();
        $wpdb->query("CREATE TABLE {$backup_name} LIKE {$table_name}");
        $wpdb->query("INSERT INTO {$backup_name} SELECT * FROM {$table_name}");
        
        update_option('yap_sync_backup_' . $table_name, $backup_name);
    }
    
    /**
     * Convert MySQL type to YAP type
     */
    private function mysql_to_yap_type($mysql_type) {
        if (strpos($mysql_type, 'varchar') !== false) return 'text';
        if (strpos($mysql_type, 'text') !== false) return 'textarea';
        if (strpos($mysql_type, 'int') !== false) return 'number';
        return 'text';
    }
    
    /**
     * Log sync action
     */
    private function log_sync_action($group, $action, $environment, $status) {
        global $wpdb;
        
        $wpdb->insert('yap_sync_log', [
            'group_name' => $group,
            'action' => $action,
            'environment' => $environment,
            'status' => $status,
            'timestamp' => current_time('mysql')
        ]);
    }
    
    /**
     * Get sync history HTML
     */
    private function get_sync_history_html() {
        global $wpdb;
        
        $logs = $wpdb->get_results("SELECT * FROM yap_sync_log ORDER BY timestamp DESC LIMIT 20");
        
        if (empty($logs)) {
            return '<tr><td colspan="6">No sync history yet.</td></tr>';
        }
        
        $html = '';
        foreach ($logs as $log) {
            $html .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td><code>%s</code></td>
                    <td><span class="yap-sync-action">%s</span></td>
                    <td>%s</td>
                    <td><span class="yap-status-%s">%s</span></td>
                    <td><button class="button button-small yap-rollback" data-id="%d">Rollback</button></td>
                </tr>',
                esc_html($log->timestamp),
                esc_html($log->group_name),
                esc_html($log->action),
                esc_html($log->environment),
                esc_attr($log->status),
                esc_html($log->status),
                $log->id
            );
        }
        
        return $html;
    }
}

YAP_Field_Sync::get_instance();
