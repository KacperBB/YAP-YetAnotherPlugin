<?php
/**
 * YAP Automation Rules
 * Airtable-style automation for field changes
 * 
 * Features:
 * - Trigger actions when field values change
 * - Conditional logic: IF field == value THEN action
 * - Actions: update field, send email, webhook, run function
 * - Scheduled automations (cron-based)
 * - Audit log for all automations
 * 
 * Examples:
 * - When status == "completed" → set completed_at = now()
 * - When stock == 0 → send email to admin
 * - When is_published == true → set published_at = now()
 * - When price > 1000 → set requires_approval = true
 * 
 * @package YAP
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Automation_Rules {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into field updates
        add_action('yap_after_update_field', [$this, 'check_automation_triggers'], 10, 5);
        
        // Scheduled automations
        add_action('yap_run_scheduled_automations', [$this, 'run_scheduled_automations']);
        
        // Admin UI
        add_action('admin_menu', [$this, 'add_automation_page'], 100);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_automation_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_yap_save_automation', [$this, 'ajax_save_automation']);
        add_action('wp_ajax_yap_test_automation', [$this, 'ajax_test_automation']);
        add_action('wp_ajax_yap_toggle_automation', [$this, 'ajax_toggle_automation']);
        add_action('wp_ajax_yap_delete_automation', [$this, 'ajax_delete_automation']);
        
        // Register cron schedule
        if (!wp_next_scheduled('yap_run_scheduled_automations')) {
            wp_schedule_event(time(), 'hourly', 'yap_run_scheduled_automations');
        }
    }
    
    /**
     * Check automation triggers when field is updated
     */
    public function check_automation_triggers($group_name, $field_name, $old_value, $new_value, $record_id) {
        global $wpdb;
        
        // Get all active automations for this field
        $automations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}yap_automations 
            WHERE group_name = %s 
            AND trigger_field = %s 
            AND is_active = 1
            AND trigger_type = 'field_change'",
            $group_name, $field_name
        ));
        
        foreach ($automations as $automation) {
            $conditions = json_decode($automation->conditions, true);
            
            // Check if conditions are met
            if ($this->evaluate_conditions($conditions, $old_value, $new_value)) {
                $this->execute_automation($automation, $group_name, $record_id, [
                    'trigger_field' => $field_name,
                    'old_value' => $old_value,
                    'new_value' => $new_value
                ]);
            }
        }
    }
    
    /**
     * Evaluate conditions
     */
    private function evaluate_conditions($conditions, $old_value, $new_value) {
        if (empty($conditions)) return true;
        
        foreach ($conditions as $condition) {
            $operator = $condition['operator'];
            $compare_value = $condition['value'];
            $target = $condition['target'] ?? 'new'; // 'old' or 'new'
            
            $value_to_check = $target === 'old' ? $old_value : $new_value;
            
            $result = $this->compare_values($value_to_check, $operator, $compare_value);
            
            // If any condition fails and logic is AND, return false
            if (!$result && ($condition['logic'] ?? 'and') === 'and') {
                return false;
            }
            
            // If any condition succeeds and logic is OR, return true
            if ($result && ($condition['logic'] ?? 'and') === 'or') {
                return true;
            }
        }
        
        return true;
    }
    
    /**
     * Compare values with operator
     */
    private function compare_values($value, $operator, $compare_value) {
        switch ($operator) {
            case '==':
            case 'equals':
                return $value == $compare_value;
                
            case '!=':
            case 'not_equals':
                return $value != $compare_value;
                
            case '>':
            case 'greater_than':
                return $value > $compare_value;
                
            case '<':
            case 'less_than':
                return $value < $compare_value;
                
            case '>=':
            case 'greater_or_equal':
                return $value >= $compare_value;
                
            case '<=':
            case 'less_or_equal':
                return $value <= $compare_value;
                
            case 'contains':
                return strpos($value, $compare_value) !== false;
                
            case 'not_contains':
                return strpos($value, $compare_value) === false;
                
            case 'starts_with':
                return strpos($value, $compare_value) === 0;
                
            case 'ends_with':
                return substr($value, -strlen($compare_value)) === $compare_value;
                
            case 'is_empty':
                return empty($value);
                
            case 'is_not_empty':
                return !empty($value);
                
            case 'changed':
                return true; // Already changed if we're here
                
            default:
                return false;
        }
    }
    
    /**
     * Execute automation
     */
    private function execute_automation($automation, $group_name, $record_id, $context = []) {
        $actions = json_decode($automation->actions, true);
        
        if (empty($actions)) return;
        
        foreach ($actions as $action) {
            $action_type = $action['type'];
            
            try {
                switch ($action_type) {
                    case 'update_field':
                        $this->action_update_field($action, $group_name, $record_id, $context);
                        break;
                        
                    case 'send_email':
                        $this->action_send_email($action, $group_name, $record_id, $context);
                        break;
                        
                    case 'webhook':
                        $this->action_webhook($action, $group_name, $record_id, $context);
                        break;
                        
                    case 'run_function':
                        $this->action_run_function($action, $group_name, $record_id, $context);
                        break;
                        
                    case 'create_post':
                        $this->action_create_post($action, $group_name, $record_id, $context);
                        break;
                        
                    case 'add_note':
                        $this->action_add_note($action, $group_name, $record_id, $context);
                        break;
                }
                
                // Log successful execution
                $this->log_automation_execution($automation->id, $record_id, 'success', $action_type);
                
            } catch (Exception $e) {
                // Log failed execution
                $this->log_automation_execution($automation->id, $record_id, 'error', $action_type, $e->getMessage());
            }
        }
    }
    
    /**
     * Action: Update field
     */
    private function action_update_field($action, $group_name, $record_id, $context) {
        global $wpdb;
        
        $target_field = $action['field'];
        $value = $action['value'];
        
        // Process dynamic values
        $value = $this->process_dynamic_value($value, $context);
        
        $wpdb->update(
            $group_name,
            [$target_field => $value],
            ['id' => $record_id]
        );
    }
    
    /**
     * Action: Send email
     */
    private function action_send_email($action, $group_name, $record_id, $context) {
        $to = $this->process_dynamic_value($action['to'], $context);
        $subject = $this->process_dynamic_value($action['subject'], $context);
        $message = $this->process_dynamic_value($action['message'], $context);
        
        wp_mail($to, $subject, $message);
    }
    
    /**
     * Action: Webhook
     */
    private function action_webhook($action, $group_name, $record_id, $context) {
        $url = $action['url'];
        $method = $action['method'] ?? 'POST';
        
        $data = [
            'group_name' => $group_name,
            'record_id' => $record_id,
            'context' => $context,
            'timestamp' => current_time('mysql')
        ];
        
        $args = [
            'method' => $method,
            'body' => json_encode($data),
            'headers' => ['Content-Type' => 'application/json']
        ];
        
        wp_remote_request($url, $args);
    }
    
    /**
     * Action: Run custom function
     */
    private function action_run_function($action, $group_name, $record_id, $context) {
        $function_name = $action['function'];
        
        if (function_exists($function_name)) {
            call_user_func($function_name, $group_name, $record_id, $context);
        }
        
        // Also fire WordPress action
        do_action('yap_automation_function', $function_name, $group_name, $record_id, $context);
    }
    
    /**
     * Action: Create post
     */
    private function action_create_post($action, $group_name, $record_id, $context) {
        $post_data = [
            'post_title' => $this->process_dynamic_value($action['title'], $context),
            'post_content' => $this->process_dynamic_value($action['content'], $context),
            'post_status' => $action['status'] ?? 'draft',
            'post_type' => $action['post_type'] ?? 'post'
        ];
        
        wp_insert_post($post_data);
    }
    
    /**
     * Action: Add note (to post meta)
     */
    private function action_add_note($action, $group_name, $record_id, $context) {
        $note = $this->process_dynamic_value($action['note'], $context);
        $note_data = [
            'note' => $note,
            'timestamp' => current_time('mysql'),
            'user' => get_current_user_id()
        ];
        
        $existing_notes = get_post_meta($record_id, 'yap_automation_notes', true) ?: [];
        $existing_notes[] = $note_data;
        
        update_post_meta($record_id, 'yap_automation_notes', $existing_notes);
    }
    
    /**
     * Process dynamic values (variables)
     */
    private function process_dynamic_value($value, $context) {
        // Available variables
        $variables = [
            '{now}' => current_time('mysql'),
            '{date}' => current_time('Y-m-d'),
            '{time}' => current_time('H:i:s'),
            '{user_id}' => get_current_user_id(),
            '{user_name}' => wp_get_current_user()->display_name,
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => get_site_url()
        ];
        
        // Add context variables
        foreach ($context as $key => $val) {
            $variables['{' . $key . '}'] = $val;
        }
        
        // Replace variables
        foreach ($variables as $var => $replacement) {
            $value = str_replace($var, $replacement, $value);
        }
        
        return $value;
    }
    
    /**
     * Log automation execution
     */
    private function log_automation_execution($automation_id, $record_id, $status, $action_type, $error = null) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'yap_automation_log',
            [
                'automation_id' => $automation_id,
                'record_id' => $record_id,
                'action_type' => $action_type,
                'status' => $status,
                'error_message' => $error,
                'executed_at' => current_time('mysql')
            ]
        );
    }
    
    /**
     * Run scheduled automations
     */
    public function run_scheduled_automations() {
        global $wpdb;
        
        $automations = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}yap_automations 
            WHERE is_active = 1 
            AND trigger_type = 'scheduled'"
        );
        
        foreach ($automations as $automation) {
            $schedule = json_decode($automation->schedule, true);
            
            // Check if should run now
            if ($this->should_run_scheduled($automation->id, $schedule)) {
                // Get records matching conditions
                $records = $this->get_records_for_automation($automation);
                
                foreach ($records as $record) {
                    $this->execute_automation($automation, $automation->group_name, $record->id);
                }
                
                // Update last run
                $wpdb->update(
                    $wpdb->prefix . 'yap_automations',
                    ['last_run' => current_time('mysql')],
                    ['id' => $automation->id]
                );
            }
        }
    }
    
    /**
     * Check if scheduled automation should run
     */
    private function should_run_scheduled($automation_id, $schedule) {
        global $wpdb;
        
        $last_run = $wpdb->get_var($wpdb->prepare(
            "SELECT last_run FROM {$wpdb->prefix}yap_automations WHERE id = %d",
            $automation_id
        ));
        
        if (!$last_run) return true;
        
        $frequency = $schedule['frequency'] ?? 'daily';
        $last_run_time = strtotime($last_run);
        $now = current_time('timestamp');
        
        switch ($frequency) {
            case 'hourly':
                return ($now - $last_run_time) >= 3600;
            case 'daily':
                return ($now - $last_run_time) >= 86400;
            case 'weekly':
                return ($now - $last_run_time) >= 604800;
            case 'monthly':
                return ($now - $last_run_time) >= 2592000;
            default:
                return false;
        }
    }
    
    /**
     * Get records for automation
     */
    private function get_records_for_automation($automation) {
        global $wpdb;
        
        $conditions = json_decode($automation->conditions, true);
        
        $query = "SELECT * FROM {$automation->group_name} WHERE 1=1";
        
        if (!empty($conditions)) {
            foreach ($conditions as $condition) {
                $field = $condition['field'];
                $operator = $condition['operator'];
                $value = $condition['value'];
                
                switch ($operator) {
                    case '==':
                        $query .= $wpdb->prepare(" AND $field = %s", $value);
                        break;
                    case '>':
                        $query .= $wpdb->prepare(" AND $field > %s", $value);
                        break;
                    case '<':
                        $query .= $wpdb->prepare(" AND $field < %s", $value);
                        break;
                }
            }
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Add automation admin page
     */
    public function add_automation_page() {
        add_submenu_page(
            'yap-admin-page',
            'Automation Rules',
            '⚙️ Automations',
            'manage_options',
            'yap-automations',
            [$this, 'render_automation_page']
        );
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_automation_assets($hook) {
        if ($hook !== 'yap_groups_page_yap-automations') return;
        
        wp_enqueue_style('yap-advanced-features', plugin_dir_url(__DIR__) . 'includes/css/yap-advanced-features.css', [], '1.4.0');
        wp_enqueue_script('yap-automation-js', plugin_dir_url(__DIR__) . 'includes/js/automations.js', ['jquery'], '1.0', true);
        
        wp_localize_script('yap-automation-js', 'yapAutomation', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yap_automation_nonce')
        ]);
    }
    
    /**
     * Render automation page
     */
    public function render_automation_page() {
        global $wpdb;
        
        $automations = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}yap_automations ORDER BY created_at DESC"
        );
        
        $groups = $wpdb->get_results("SELECT * FROM yap_groups");
        
        ?>
        <div class="wrap yap-automation-wrap">
            <h1>⚙️ Automation Rules - Airtable-Style Automations</h1>
            <p class="description">Create powerful automations triggered by field changes or on schedule.</p>
            
            <button type="button" class="button button-primary" id="yap-new-automation">
                <span class="dashicons dashicons-plus-alt"></span> New Automation
            </button>
            
            <div class="yap-automations-list">
                <h2>Active Automations</h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="30">✓</th>
                            <th>Name</th>
                            <th>Group</th>
                            <th>Trigger</th>
                            <th>Actions</th>
                            <th>Last Run</th>
                            <th>Executions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($automations)): ?>
                            <tr><td colspan="8">No automations created yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($automations as $auto): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="yap-toggle-automation" 
                                               data-id="<?php echo $auto->id; ?>" 
                                               <?php checked($auto->is_active, 1); ?>>
                                    </td>
                                    <td><strong><?php echo esc_html($auto->name); ?></strong></td>
                                    <td><code><?php echo esc_html($auto->group_name); ?></code></td>
                                    <td>
                                        <?php if ($auto->trigger_type === 'field_change'): ?>
                                            <span class="yap-trigger-badge yap-trigger-change">
                                                📝 <?php echo esc_html($auto->trigger_field); ?> changed
                                            </span>
                                        <?php else: ?>
                                            <span class="yap-trigger-badge yap-trigger-scheduled">
                                                🕒 Scheduled
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo count(json_decode($auto->actions, true)); ?> actions</td>
                                    <td><?php echo $auto->last_run ? human_time_diff(strtotime($auto->last_run)) . ' ago' : 'Never'; ?></td>
                                    <td>
                                        <?php
                                        $exec_count = $wpdb->get_var($wpdb->prepare(
                                            "SELECT COUNT(*) FROM {$wpdb->prefix}yap_automation_log WHERE automation_id = %d",
                                            $auto->id
                                        ));
                                        echo number_format($exec_count);
                                        ?>
                                    </td>
                                    <td>
                                        <button class="button button-small yap-edit-automation" data-id="<?php echo $auto->id; ?>">Edit</button>
                                        <button class="button button-small yap-test-automation" data-id="<?php echo $auto->id; ?>">Test</button>
                                        <button class="button button-small yap-delete-automation" data-id="<?php echo $auto->id; ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="yap-automation-examples">
                <h2>Automation Examples</h2>
                
                <div class="yap-example-grid">
                    <div class="yap-example-card">
                        <h3>📦 Auto-complete orders</h3>
                        <p><strong>When:</strong> status == "completed"</p>
                        <p><strong>Then:</strong> Set completed_at = {now}</p>
                    </div>
                    
                    <div class="yap-example-card">
                        <h3>📧 Low stock alert</h3>
                        <p><strong>When:</strong> stock <= 5</p>
                        <p><strong>Then:</strong> Send email to admin</p>
                    </div>
                    
                    <div class="yap-example-card">
                        <h3>🚀 Auto-publish</h3>
                        <p><strong>When:</strong> is_published == true</p>
                        <p><strong>Then:</strong> Set published_at = {now}</p>
                    </div>
                    
                    <div class="yap-example-card">
                        <h3>💰 Approval required</h3>
                        <p><strong>When:</strong> price > 1000</p>
                        <p><strong>Then:</strong> Set requires_approval = true</p>
                    </div>
                    
                    <div class="yap-example-card">
                        <h3>🔔 New order webhook</h3>
                        <p><strong>When:</strong> order_status changed</p>
                        <p><strong>Then:</strong> Send webhook to Slack</p>
                    </div>
                    
                    <div class="yap-example-card">
                        <h3>📅 Scheduled cleanup</h3>
                        <p><strong>When:</strong> Daily at 2 AM</p>
                        <p><strong>Then:</strong> Delete old drafts</p>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .yap-automation-wrap { margin: 20px; }
        .yap-automations-list,
        .yap-automation-examples {
            background: #fff;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .yap-trigger-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .yap-trigger-change {
            background: #e7f7e9;
            color: #46b450;
        }
        .yap-trigger-scheduled {
            background: #fff3cd;
            color: #f0b849;
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
        .yap-example-card p {
            margin: 5px 0;
            font-size: 12px;
        }
        </style>
        <?php
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_save_automation() {
        check_ajax_referer('yap_automation_nonce', 'nonce');
        
        global $wpdb;
        
        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'group_name' => sanitize_key($_POST['group_name']),
            'trigger_type' => sanitize_text_field($_POST['trigger_type']),
            'trigger_field' => sanitize_text_field($_POST['trigger_field']),
            'conditions' => wp_json_encode($_POST['conditions']),
            'actions' => wp_json_encode($_POST['actions']),
            'is_active' => intval($_POST['is_active'])
        ];
        
        if (isset($_POST['automation_id'])) {
            $wpdb->update(
                $wpdb->prefix . 'yap_automations',
                $data,
                ['id' => intval($_POST['automation_id'])]
            );
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($wpdb->prefix . 'yap_automations', $data);
        }
        
        wp_send_json_success(['message' => 'Automation saved']);
    }
    
    public function ajax_test_automation() {
        check_ajax_referer('yap_automation_nonce', 'nonce');
        
        $automation_id = intval($_POST['automation_id']);
        
        global $wpdb;
        $automation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}yap_automations WHERE id = %d",
            $automation_id
        ));
        
        if (!$automation) {
            wp_send_json_error(['message' => 'Automation not found']);
        }
        
        // Test with first record
        $record = $wpdb->get_row("SELECT * FROM {$automation->group_name} LIMIT 1");
        
        if ($record) {
            $this->execute_automation($automation, $automation->group_name, $record->id, ['test' => true]);
            wp_send_json_success(['message' => 'Test executed successfully']);
        } else {
            wp_send_json_error(['message' => 'No records found to test']);
        }
    }
    
    public function ajax_toggle_automation() {
        check_ajax_referer('yap_automation_nonce', 'nonce');
        
        global $wpdb;
        
        $automation_id = intval($_POST['automation_id']);
        $is_active = intval($_POST['is_active']);
        
        $wpdb->update(
            $wpdb->prefix . 'yap_automations',
            ['is_active' => $is_active],
            ['id' => $automation_id]
        );
        
        wp_send_json_success();
    }
    
    public function ajax_delete_automation() {
        check_ajax_referer('yap_automation_nonce', 'nonce');
        
        global $wpdb;
        
        $automation_id = intval($_POST['automation_id']);
        
        $wpdb->delete(
            $wpdb->prefix . 'yap_automations',
            ['id' => $automation_id]
        );
        
        wp_send_json_success(['message' => 'Automation deleted']);
    }
}

YAP_Automation_Rules::get_instance();
