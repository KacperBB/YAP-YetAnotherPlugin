<?php
/**
 * YAP Advanced Query Fields
 * Fields powered by custom SQL queries
 * 
 * Features:
 * - Define fields with custom SQL
 * - Dynamic values from database
 * - Cached for performance
 * - Use post_id, user_id, and other variables
 * - Perfect for dashboards and analytics
 * 
 * Examples:
 * - ORDER COUNT: SELECT COUNT(*) FROM wp_orders WHERE customer_id = {post_id}
 * - TOTAL REVENUE: SELECT SUM(total) FROM wp_orders WHERE user_id = {user_id}
 * - LAST LOGIN: SELECT login_date FROM wp_user_sessions WHERE user_id = {user_id} ORDER BY login_date DESC LIMIT 1
 * 
 * @package YAP
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Advanced_Query_Fields {
    private static $instance = null;
    private $cache_ttl = 300; // 5 minutes
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Register query field type
        add_filter('yap_field_types', [$this, 'register_query_field_type']);
        
        // Render query field
        add_filter('yap_render_field_query', [$this, 'render_query_field'], 10, 3);
        
        // Get query field value
        add_filter('yap_get_field_value_query', [$this, 'get_query_field_value'], 10, 3);
        
        // Admin UI
        add_action('admin_menu', [$this, 'add_query_fields_page'], 100);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_query_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_yap_test_query', [$this, 'ajax_test_query']);
        add_action('wp_ajax_yap_save_query_field', [$this, 'ajax_save_query_field']);
        add_action('wp_ajax_yap_refresh_query_cache', [$this, 'ajax_refresh_query_cache']);
        
        // WP-CLI command
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('yap:query', [$this, 'cli_query_command']);
        }
    }
    
    /**
     * Register query field type
     */
    public function register_query_field_type($types) {
        $types['query'] = [
            'label' => 'SQL Query Field',
            'description' => 'Dynamic field powered by custom SQL',
            'icon' => '⚡',
            'category' => 'advanced'
        ];
        
        return $types;
    }
    
    /**
     * Render query field (read-only display)
     */
    public function render_query_field($output, $field, $value) {
        $query_result = $this->execute_query($field['query'], $field['context']);
        
        $format = $field['format'] ?? 'raw';
        $formatted_value = $this->format_query_result($query_result, $format);
        
        $output = '<div class="yap-query-field" data-field="' . esc_attr($field['name']) . '">';
        $output .= '<label>' . esc_html($field['label']) . '</label>';
        $output .= '<div class="yap-query-result">';
        $output .= '<span class="yap-query-value">' . $formatted_value . '</span>';
        
        if (current_user_can('manage_options')) {
            $output .= ' <button type="button" class="button button-small yap-refresh-query" data-field="' . esc_attr($field['name']) . '">';
            $output .= '<span class="dashicons dashicons-update"></span> Refresh';
            $output .= '</button>';
        }
        
        $output .= '</div>';
        
        if (!empty($field['description'])) {
            $output .= '<p class="description">' . esc_html($field['description']) . '</p>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Get query field value
     */
    public function get_query_field_value($value, $field, $context) {
        return $this->execute_query($field['query'], $context);
    }
    
    /**
     * Execute SQL query with variable replacement
     */
    public function execute_query($query, $context = []) {
        global $wpdb;
        
        // Check cache first
        $cache_key = 'yap_query_' . md5($query . serialize($context));
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Replace variables in query
        $processed_query = $this->process_query_variables($query, $context);
        
        // Validate query (security check)
        if (!$this->is_query_safe($processed_query)) {
            return new WP_Error('unsafe_query', 'Query contains unsafe operations');
        }
        
        // Execute query
        $result = $wpdb->get_var($processed_query);
        
        if ($wpdb->last_error) {
            return new WP_Error('query_error', $wpdb->last_error);
        }
        
        // Cache result
        set_transient($cache_key, $result, $this->cache_ttl);
        
        return $result;
    }
    
    /**
     * Process query variables
     */
    private function process_query_variables($query, $context = []) {
        global $wpdb;
        
        // Available variables
        $variables = [
            '{post_id}' => get_the_ID(),
            '{user_id}' => get_current_user_id(),
            '{site_id}' => get_current_blog_id(),
            '{current_date}' => current_time('Y-m-d'),
            '{current_datetime}' => current_time('Y-m-d H:i:s'),
            '{current_year}' => current_time('Y'),
            '{current_month}' => current_time('m'),
            '{prefix}' => $wpdb->prefix
        ];
        
        // Add context variables
        foreach ($context as $key => $value) {
            $variables['{' . $key . '}'] = $value;
        }
        
        // Replace variables
        foreach ($variables as $var => $value) {
            $query = str_replace($var, $wpdb->prepare('%s', $value), $query);
        }
        
        return $query;
    }
    
    /**
     * Check if query is safe (basic validation)
     */
    private function is_query_safe($query) {
        $query_upper = strtoupper($query);
        
        // Must be SELECT only
        if (strpos($query_upper, 'SELECT') !== 0) {
            return false;
        }
        
        // No dangerous operations
        $dangerous = ['DROP', 'DELETE', 'TRUNCATE', 'ALTER', 'CREATE', 'INSERT', 'UPDATE', 'REPLACE', 'GRANT', 'REVOKE'];
        
        foreach ($dangerous as $keyword) {
            if (strpos($query_upper, $keyword) !== false) {
                return false;
            }
        }
        
        // No INTO OUTFILE or similar
        if (preg_match('/INTO\s+(OUTFILE|DUMPFILE)/i', $query)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Format query result
     */
    private function format_query_result($result, $format = 'raw') {
        if (is_wp_error($result)) {
            return '<span class="yap-query-error">Error: ' . $result->get_error_message() . '</span>';
        }
        
        if ($result === null) {
            return '<span class="yap-query-null">No data</span>';
        }
        
        switch ($format) {
            case 'number':
                return number_format((float)$result);
                
            case 'currency':
                return '$' . number_format((float)$result, 2);
                
            case 'percentage':
                return number_format((float)$result, 2) . '%';
                
            case 'date':
                return date('F j, Y', strtotime($result));
                
            case 'datetime':
                return date('F j, Y g:i A', strtotime($result));
                
            case 'filesize':
                return size_format((int)$result);
                
            default:
                return esc_html($result);
        }
    }
    
    /**
     * Add query fields admin page
     */
    public function add_query_fields_page() {
        add_submenu_page(
            'yap-admin-page',
            'Query Fields',
            '⚡ Query Fields',
            'manage_options',
            'yap-query-fields',
            [$this, 'render_query_fields_page']
        );
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_query_assets($hook) {
        if ($hook !== 'yap_groups_page_yap-query-fields') return;
        
        wp_enqueue_style('yap-advanced-features', plugin_dir_url(__DIR__) . 'includes/css/yap-advanced-features.css', [], '1.4.0');
        wp_enqueue_code_editor(['type' => 'application/x-sql']);
        wp_enqueue_script('yap-query-js', plugin_dir_url(__DIR__) . 'includes/js/query-fields.js', ['jquery', 'wp-codemirror'], '1.0', true);
        
        wp_localize_script('yap-query-js', 'yapQuery', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yap_query_nonce')
        ]);
    }
    
    /**
     * Render query fields page
     */
    public function render_query_fields_page() {
        global $wpdb;
        
        $query_fields = $this->get_all_query_fields();
        
        ?>
        <div class="wrap yap-query-wrap">
            <h1>⚡ Advanced Query Fields - SQL-Powered Fields</h1>
            <p class="description">Create dynamic fields powered by custom SQL queries. Perfect for dashboards and analytics.</p>
            
            <div class="yap-query-builder">
                <h2>Create Query Field</h2>
                
                <table class="form-table">
                    <tr>
                        <th><label for="query-field-name">Field Name</label></th>
                        <td>
                            <input type="text" id="query-field-name" class="regular-text" placeholder="order_count">
                            <p class="description">Internal field identifier</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="query-field-label">Field Label</label></th>
                        <td>
                            <input type="text" id="query-field-label" class="regular-text" placeholder="Total Orders">
                            <p class="description">Display label</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="query-field-group">Field Group</label></th>
                        <td>
                            <select id="query-field-group">
                                <?php
                                $groups = $wpdb->get_results("SELECT * FROM yap_groups");
                                foreach ($groups as $group): ?>
                                    <option value="<?php echo esc_attr($group->group_name); ?>">
                                        <?php echo esc_html($group->group_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="query-field-sql">SQL Query</label></th>
                        <td>
                            <textarea id="query-field-sql" rows="10" class="large-text code"></textarea>
                            <p class="description">
                                <strong>Available variables:</strong> 
                                {post_id}, {user_id}, {site_id}, {current_date}, {current_datetime}, {prefix}
                            </p>
                            <p class="description">
                                <strong>Security:</strong> Only SELECT queries allowed. No DROP, DELETE, INSERT, UPDATE.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="query-field-format">Display Format</label></th>
                        <td>
                            <select id="query-field-format">
                                <option value="raw">Raw</option>
                                <option value="number">Number (1,234)</option>
                                <option value="currency">Currency ($1,234.56)</option>
                                <option value="percentage">Percentage (12.34%)</option>
                                <option value="date">Date (January 1, 2024)</option>
                                <option value="datetime">DateTime (January 1, 2024 3:45 PM)</option>
                                <option value="filesize">File Size (1.2 MB)</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="query-field-cache">Cache Duration</label></th>
                        <td>
                            <input type="number" id="query-field-cache" value="300" min="0" step="60"> seconds
                            <p class="description">How long to cache query results (0 = no cache)</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="button" id="yap-test-query" class="button">Test Query</button>
                    <button type="button" id="yap-save-query-field" class="button button-primary">Save Query Field</button>
                </p>
                
                <div id="yap-query-test-result" style="display:none; margin-top: 20px;"></div>
            </div>
            
            <div class="yap-query-examples">
                <h2>Query Examples</h2>
                
                <div class="yap-example-grid">
                    <div class="yap-example-card">
                        <h3>📦 Order Count</h3>
                        <pre>SELECT COUNT(*) 
FROM {prefix}orders 
WHERE customer_id = {post_id}</pre>
                    </div>
                    
                    <div class="yap-example-card">
                        <h3>💰 Total Revenue</h3>
                        <pre>SELECT SUM(total) 
FROM {prefix}orders 
WHERE user_id = {user_id} 
AND status = 'completed'</pre>
                    </div>
                    
                    <div class="yap-example-card">
                        <h3>📊 Average Rating</h3>
                        <pre>SELECT AVG(rating) 
FROM {prefix}reviews 
WHERE product_id = {post_id}</pre>
                    </div>
                    
                    <div class="yap-example-card">
                        <h3>🕒 Last Login</h3>
                        <pre>SELECT login_date 
FROM {prefix}user_sessions 
WHERE user_id = {user_id} 
ORDER BY login_date DESC 
LIMIT 1</pre>
                    </div>
                    
                    <div class="yap-example-card">
                        <h3>📈 Post Views</h3>
                        <pre>SELECT meta_value 
FROM {prefix}postmeta 
WHERE post_id = {post_id} 
AND meta_key = 'views'</pre>
                    </div>
                    
                    <div class="yap-example-card">
                        <h3>👥 Follower Count</h3>
                        <pre>SELECT COUNT(*) 
FROM {prefix}followers 
WHERE user_id = {post_id}</pre>
                    </div>
                </div>
            </div>
            
            <div class="yap-query-list">
                <h2>Existing Query Fields</h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Field Name</th>
                            <th>Label</th>
                            <th>Group</th>
                            <th>Query Preview</th>
                            <th>Format</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($query_fields)): ?>
                            <tr><td colspan="6">No query fields created yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($query_fields as $field): ?>
                                <tr>
                                    <td><code><?php echo esc_html($field->field_name); ?></code></td>
                                    <td><?php echo esc_html($field->label); ?></td>
                                    <td><?php echo esc_html($field->group_name); ?></td>
                                    <td><code><?php echo esc_html($this->truncate_query($field->query)); ?></code></td>
                                    <td><?php echo esc_html($field->format); ?></td>
                                    <td>
                                        <button class="button button-small yap-edit-query" data-id="<?php echo $field->id; ?>">Edit</button>
                                        <button class="button button-small yap-delete-query" data-id="<?php echo $field->id; ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <style>
        .yap-query-wrap { margin: 20px; }
        .yap-query-builder,
        .yap-query-examples,
        .yap-query-list {
            background: #fff;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .yap-example-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .yap-example-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #2271b1;
        }
        .yap-example-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        .yap-example-card pre {
            background: #fff;
            padding: 10px;
            border-radius: 4px;
            font-size: 12px;
            overflow-x: auto;
            margin: 0;
        }
        #yap-query-test-result {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
            border-left: 4px solid #46b450;
        }
        #yap-query-test-result.error {
            border-left-color: #dc3232;
            background: #ffebee;
        }
        </style>
        <?php
    }
    
    /**
     * Truncate query for display
     */
    private function truncate_query($query, $length = 50) {
        $query = preg_replace('/\s+/', ' ', trim($query));
        return strlen($query) > $length ? substr($query, 0, $length) . '...' : $query;
    }
    
    /**
     * Get all query fields
     */
    private function get_all_query_fields() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'yap_query_fields';
        
        return $wpdb->get_results("SELECT * FROM $table ORDER BY group_name, field_name");
    }
    
    /**
     * AJAX: Test query
     */
    public function ajax_test_query() {
        check_ajax_referer('yap_query_nonce', 'nonce');
        
        $query = stripslashes($_POST['query']);
        $format = sanitize_text_field($_POST['format']);
        
        // Test with sample context
        $context = [
            'post_id' => 1,
            'user_id' => get_current_user_id()
        ];
        
        $result = $this->execute_query($query, $context);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
        }
        
        $formatted = $this->format_query_result($result, $format);
        
        wp_send_json_success([
            'raw' => $result,
            'formatted' => $formatted
        ]);
    }
    
    /**
     * AJAX: Save query field
     */
    public function ajax_save_query_field() {
        check_ajax_referer('yap_query_nonce', 'nonce');
        
        global $wpdb;
        
        $data = [
            'field_name' => sanitize_key($_POST['field_name']),
            'label' => sanitize_text_field($_POST['label']),
            'group_name' => sanitize_key($_POST['group_name']),
            'query' => stripslashes($_POST['query']),
            'format' => sanitize_text_field($_POST['format']),
            'cache_ttl' => intval($_POST['cache_ttl'])
        ];
        
        // Validate query
        if (!$this->is_query_safe($data['query'])) {
            wp_send_json_error(['message' => 'Query contains unsafe operations']);
        }
        
        $table = $wpdb->prefix . 'yap_query_fields';
        
        // Check if exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE group_name = %s AND field_name = %s",
            $data['group_name'], $data['field_name']
        ));
        
        if ($exists) {
            $wpdb->update($table, $data, ['id' => $exists]);
        } else {
            $wpdb->insert($table, $data);
        }
        
        wp_send_json_success(['message' => 'Query field saved successfully']);
    }
    
    /**
     * AJAX: Refresh query cache
     */
    public function ajax_refresh_query_cache() {
        check_ajax_referer('yap_query_nonce', 'nonce');
        
        $field_name = sanitize_text_field($_POST['field_name']);
        
        // Clear cache for this field
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '%yap_query_' . $field_name . '%'
        ));
        
        wp_send_json_success(['message' => 'Cache refreshed']);
    }
    
    /**
     * WP-CLI: Query command
     */
    public function cli_query_command($args, $assoc_args) {
        if (empty($args[0])) {
            WP_CLI::error('Query required. Usage: wp yap:query "SELECT COUNT(*) FROM wp_posts"');
        }
        
        $query = $args[0];
        $context = [
            'post_id' => isset($assoc_args['post-id']) ? intval($assoc_args['post-id']) : 1,
            'user_id' => isset($assoc_args['user-id']) ? intval($assoc_args['user-id']) : get_current_user_id()
        ];
        
        $result = $this->execute_query($query, $context);
        
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        }
        
        WP_CLI::success('Query result: ' . $result);
    }
}

YAP_Advanced_Query_Fields::get_instance();
