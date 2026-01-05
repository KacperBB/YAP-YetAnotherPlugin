<?php
/**
 * YAP Data History
 * Version control for field data changes - like Git for data
 * 
 * Features:
 * - Track all field changes with diff
 * - Show who changed what and when
 * - Rollback to any previous version
 * - Compare versions side-by-side
 * - Audit trail for compliance
 * 
 * @package YAP
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Data_History {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into field updates
        add_action('yap_after_update_field', [$this, 'track_field_change'], 10, 5);
        
        // Admin UI
        add_action('admin_menu', [$this, 'add_history_page'], 100);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_history_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_yap_get_field_history', [$this, 'ajax_get_field_history']);
        add_action('wp_ajax_yap_compare_versions', [$this, 'ajax_compare_versions']);
        add_action('wp_ajax_yap_rollback_version', [$this, 'ajax_rollback_version']);
        add_action('wp_ajax_yap_restore_version', [$this, 'ajax_restore_version']);
        
        // Meta box for post editor
        add_action('add_meta_boxes', [$this, 'add_history_meta_box']);
    }
    
    /**
     * Track field change
     */
    public function track_field_change($group_name, $field_name, $old_value, $new_value, $record_id) {
        global $wpdb;
        
        // Skip if values are the same
        if ($old_value === $new_value) {
            return;
        }
        
        // Get current user
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $user_name = $user ? $user->display_name : 'System';
        
        // Calculate diff
        $diff = $this->calculate_diff($old_value, $new_value);
        
        // Insert history record
        $wpdb->insert(
            $wpdb->prefix . 'yap_data_history',
            [
                'group_name' => $group_name,
                'field_name' => $field_name,
                'record_id' => $record_id,
                'old_value' => $this->serialize_value($old_value),
                'new_value' => $this->serialize_value($new_value),
                'diff' => json_encode($diff),
                'user_id' => $user_id,
                'user_name' => $user_name,
                'user_ip' => $this->get_client_ip(),
                'changed_at' => current_time('mysql')
            ]
        );
        
        // Cleanup old history (keep last 100 versions per field)
        $this->cleanup_old_history($group_name, $field_name, $record_id);
    }
    
    /**
     * Calculate diff between old and new values
     */
    private function calculate_diff($old_value, $new_value) {
        $diff = [
            'type' => 'change',
            'old' => $old_value,
            'new' => $new_value
        ];
        
        // String diff
        if (is_string($old_value) && is_string($new_value)) {
            $diff['type'] = 'text';
            $diff['changes'] = $this->text_diff($old_value, $new_value);
        }
        
        // Numeric diff
        elseif (is_numeric($old_value) && is_numeric($new_value)) {
            $diff['type'] = 'numeric';
            $diff['delta'] = $new_value - $old_value;
            $diff['percent'] = $old_value != 0 ? (($new_value - $old_value) / $old_value * 100) : 0;
        }
        
        // Array/JSON diff
        elseif (is_array($old_value) && is_array($new_value)) {
            $diff['type'] = 'array';
            $diff['added'] = array_diff_assoc($new_value, $old_value);
            $diff['removed'] = array_diff_assoc($old_value, $new_value);
        }
        
        return $diff;
    }
    
    /**
     * Simple text diff
     */
    private function text_diff($old, $new) {
        $old_words = explode(' ', $old);
        $new_words = explode(' ', $new);
        
        return [
            'removed_words' => array_diff($old_words, $new_words),
            'added_words' => array_diff($new_words, $old_words),
            'old_length' => strlen($old),
            'new_length' => strlen($new)
        ];
    }
    
    /**
     * Serialize value for storage
     */
    private function serialize_value($value) {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        return $value;
    }
    
    /**
     * Unserialize value from storage
     */
    private function unserialize_value($value) {
        $decoded = json_decode($value, true);
        return $decoded !== null ? $decoded : $value;
    }
    
    /**
     * Cleanup old history entries
     */
    private function cleanup_old_history($group_name, $field_name, $record_id, $keep = 100) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'yap_data_history';
        
        // Count existing records
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
            WHERE group_name = %s AND field_name = %s AND record_id = %d",
            $group_name, $field_name, $record_id
        ));
        
        if ($count > $keep) {
            $delete_count = $count - $keep;
            
            // Delete oldest records
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $table 
                WHERE group_name = %s AND field_name = %s AND record_id = %d
                ORDER BY changed_at ASC
                LIMIT %d",
                $group_name, $field_name, $record_id, $delete_count
            ));
        }
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }
    
    /**
     * Add history admin page
     */
    public function add_history_page() {
        add_submenu_page(
            'yap-admin-page',
            'Data History',
            '📜 Data History',
            'manage_options',
            'yap-data-history',
            [$this, 'render_history_page']
        );
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_history_assets($hook) {
        if ($hook !== 'yap_groups_page_yap-data-history' && $hook !== 'post.php') {
            return;
        }
        
        wp_enqueue_style('yap-advanced-features', plugin_dir_url(__DIR__) . 'includes/css/yap-advanced-features.css', [], '1.4.0');
        // TODO: Create data-history.js file
        // wp_enqueue_script('yap-history-js', plugin_dir_url(__DIR__) . 'includes/js/data-history.js', ['jquery'], '1.0', true);
        
        wp_localize_script('yap-history-js', 'yapHistory', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yap_history_nonce')
        ]);
    }
    
    /**
     * Add history meta box to post editor
     */
    public function add_history_meta_box() {
        $post_types = get_post_types(['public' => true]);
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'yap_field_history',
                '📜 Field History',
                [$this, 'render_history_meta_box'],
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    /**
     * Render history meta box
     */
    public function render_history_meta_box($post) {
        global $wpdb;
        
        // Get recent changes for this post
        $changes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}yap_data_history 
            WHERE record_id = %d 
            ORDER BY changed_at DESC 
            LIMIT 10",
            $post->ID
        ));
        
        if (empty($changes)) {
            echo '<p>No history yet.</p>';
            return;
        }
        
        echo '<div class="yap-history-widget">';
        foreach ($changes as $change) {
            $diff = json_decode($change->diff, true);
            $time_ago = human_time_diff(strtotime($change->changed_at), current_time('timestamp'));
            
            echo '<div class="yap-history-item">';
            echo '<div class="yap-history-field"><strong>' . esc_html($change->field_name) . '</strong></div>';
            echo '<div class="yap-history-change">';
            echo $this->format_change_display($diff);
            echo '</div>';
            echo '<div class="yap-history-meta">';
            echo '<span class="yap-history-user">' . esc_html($change->user_name) . '</span> • ';
            echo '<span class="yap-history-time">' . $time_ago . ' ago</span>';
            echo '</div>';
            echo '<button class="button button-small yap-restore-version" data-id="' . $change->id . '">Restore</button>';
            echo '</div>';
        }
        echo '</div>';
        
        echo '<style>
        .yap-history-widget { margin-top: 10px; }
        .yap-history-item { 
            padding: 10px; 
            margin-bottom: 10px; 
            background: #f9f9f9; 
            border-left: 3px solid #2271b1;
            font-size: 12px;
        }
        .yap-history-field { font-weight: 600; margin-bottom: 5px; }
        .yap-history-change { margin-bottom: 5px; }
        .yap-history-meta { color: #666; font-size: 11px; margin-bottom: 5px; }
        .yap-old-value { 
            text-decoration: line-through; 
            color: #dc3232; 
            background: #ffebee;
            padding: 2px 4px;
            border-radius: 2px;
        }
        .yap-new-value { 
            color: #46b450; 
            background: #e7f7e9;
            padding: 2px 4px;
            border-radius: 2px;
            font-weight: 600;
        }
        .yap-restore-version { margin-top: 5px; }
        </style>';
    }
    
    /**
     * Format change display
     */
    private function format_change_display($diff) {
        if (!$diff) return 'Changed';
        
        $type = $diff['type'] ?? 'change';
        
        switch ($type) {
            case 'numeric':
                $delta = $diff['delta'];
                $arrow = $delta > 0 ? '↑' : '↓';
                $color = $delta > 0 ? 'green' : 'red';
                return sprintf(
                    '<span class="yap-old-value">%s</span> → <span class="yap-new-value">%s</span> <span style="color:%s">%s%+.2f</span>',
                    $diff['old'],
                    $diff['new'],
                    $color,
                    $arrow,
                    $delta
                );
                
            case 'text':
                $old_len = $diff['changes']['old_length'] ?? 0;
                $new_len = $diff['changes']['new_length'] ?? 0;
                return sprintf(
                    '<span class="yap-old-value">%d chars</span> → <span class="yap-new-value">%d chars</span>',
                    $old_len,
                    $new_len
                );
                
            default:
                return sprintf(
                    '<span class="yap-old-value">%s</span> → <span class="yap-new-value">%s</span>',
                    $this->truncate($diff['old'], 30),
                    $this->truncate($diff['new'], 30)
                );
        }
    }
    
    /**
     * Truncate string
     */
    private function truncate($str, $length) {
        $str = is_string($str) ? $str : json_encode($str);
        return strlen($str) > $length ? substr($str, 0, $length) . '...' : $str;
    }
    
    /**
     * Render history page
     */
    public function render_history_page() {
        global $wpdb;
        
        $group_name = isset($_GET['group']) ? sanitize_key($_GET['group']) : '';
        $field_name = isset($_GET['field']) ? sanitize_text_field($_GET['field']) : '';
        
        $groups = $wpdb->get_results("SELECT DISTINCT group_name FROM {$wpdb->prefix}yap_data_history ORDER BY group_name");
        
        ?>
        <div class="wrap yap-history-wrap">
            <h1>📜 Data History - Version Control for Fields</h1>
            <p class="description">Track all field changes with full audit trail and rollback capability.</p>
            
            <div class="yap-history-filters">
                <form method="get">
                    <input type="hidden" name="post_type" value="yap_groups">
                    <input type="hidden" name="page" value="yap-data-history">
                    
                    <select name="group" id="yap-filter-group">
                        <option value="">-- Select Group --</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo esc_attr($group->group_name); ?>" <?php selected($group_name, $group->group_name); ?>>
                                <?php echo esc_html($group->group_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <?php if ($group_name): ?>
                        <input type="text" name="field" placeholder="Field name" value="<?php echo esc_attr($field_name); ?>">
                    <?php endif; ?>
                    
                    <button type="submit" class="button button-primary">Filter</button>
                    <a href="?post_type=yap_groups&page=yap-data-history" class="button">Clear</a>
                </form>
            </div>
            
            <div class="yap-history-timeline">
                <?php echo $this->render_history_timeline($group_name, $field_name); ?>
            </div>
        </div>
        
        <style>
        .yap-history-wrap { margin: 20px; }
        .yap-history-filters { 
            background: #fff; 
            padding: 20px; 
            margin: 20px 0; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .yap-history-filters select,
        .yap-history-filters input[type="text"] {
            margin-right: 10px;
            min-width: 200px;
        }
        .yap-history-timeline {
            background: #fff;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .yap-timeline-item {
            position: relative;
            padding-left: 40px;
            padding-bottom: 30px;
            border-left: 2px solid #ddd;
        }
        .yap-timeline-item:last-child {
            border-left: none;
        }
        .yap-timeline-marker {
            position: absolute;
            left: -8px;
            top: 0;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #2271b1;
            border: 2px solid #fff;
        }
        .yap-timeline-content {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #2271b1;
        }
        .yap-timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .yap-timeline-field {
            font-weight: 600;
            font-size: 14px;
        }
        .yap-timeline-meta {
            color: #666;
            font-size: 12px;
        }
        .yap-timeline-change {
            margin: 10px 0;
            padding: 10px;
            background: #fff;
            border-radius: 4px;
        }
        .yap-timeline-actions {
            margin-top: 10px;
        }
        .yap-version-badge {
            display: inline-block;
            padding: 2px 8px;
            background: #2271b1;
            color: #fff;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        </style>
        <?php
    }
    
    /**
     * Render history timeline
     */
    private function render_history_timeline($group_name = '', $field_name = '') {
        global $wpdb;
        
        $where = '1=1';
        $params = [];
        
        if ($group_name) {
            $where .= ' AND group_name = %s';
            $params[] = $group_name;
        }
        
        if ($field_name) {
            $where .= ' AND field_name = %s';
            $params[] = $field_name;
        }
        
        $query = "SELECT * FROM {$wpdb->prefix}yap_data_history WHERE $where ORDER BY changed_at DESC LIMIT 100";
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $changes = $wpdb->get_results($query);
        
        if (empty($changes)) {
            return '<p>No history found. Make some field changes to start tracking.</p>';
        }
        
        $output = '';
        $version = count($changes);
        
        foreach ($changes as $change) {
            $diff = json_decode($change->diff, true);
            $time = date('Y-m-d H:i:s', strtotime($change->changed_at));
            $time_ago = human_time_diff(strtotime($change->changed_at), current_time('timestamp'));
            
            $output .= '<div class="yap-timeline-item">';
            $output .= '<div class="yap-timeline-marker"></div>';
            $output .= '<div class="yap-timeline-content">';
            
            $output .= '<div class="yap-timeline-header">';
            $output .= '<div>';
            $output .= '<span class="yap-version-badge">v' . $version . '</span> ';
            $output .= '<span class="yap-timeline-field">' . esc_html($change->group_name) . ' → ' . esc_html($change->field_name) . '</span>';
            $output .= '</div>';
            $output .= '<div class="yap-timeline-meta">';
            $output .= esc_html($change->user_name) . ' • ' . $time_ago . ' ago';
            $output .= '</div>';
            $output .= '</div>';
            
            $output .= '<div class="yap-timeline-change">';
            $output .= $this->format_change_display($diff);
            $output .= '</div>';
            
            $output .= '<div class="yap-timeline-actions">';
            $output .= '<button class="button button-small yap-view-diff" data-id="' . $change->id . '">View Diff</button> ';
            $output .= '<button class="button button-small yap-rollback" data-id="' . $change->id . '">Rollback</button>';
            $output .= '</div>';
            
            $output .= '</div>';
            $output .= '</div>';
            
            $version--;
        }
        
        return $output;
    }
    
    /**
     * AJAX: Get field history
     */
    public function ajax_get_field_history() {
        check_ajax_referer('yap_history_nonce', 'nonce');
        
        global $wpdb;
        
        $group_name = sanitize_key($_POST['group_name']);
        $field_name = sanitize_text_field($_POST['field_name']);
        $record_id = intval($_POST['record_id']);
        
        $history = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}yap_data_history 
            WHERE group_name = %s AND field_name = %s AND record_id = %d 
            ORDER BY changed_at DESC",
            $group_name, $field_name, $record_id
        ));
        
        wp_send_json_success(['history' => $history]);
    }
    
    /**
     * AJAX: Compare versions
     */
    public function ajax_compare_versions() {
        check_ajax_referer('yap_history_nonce', 'nonce');
        
        global $wpdb;
        
        $version1_id = intval($_POST['version1']);
        $version2_id = intval($_POST['version2']);
        
        $v1 = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}yap_data_history WHERE id = %d",
            $version1_id
        ));
        
        $v2 = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}yap_data_history WHERE id = %d",
            $version2_id
        ));
        
        if (!$v1 || !$v2) {
            wp_send_json_error(['message' => 'Versions not found']);
        }
        
        $comparison = [
            'version1' => [
                'value' => $this->unserialize_value($v1->new_value),
                'changed_at' => $v1->changed_at,
                'user' => $v1->user_name
            ],
            'version2' => [
                'value' => $this->unserialize_value($v2->new_value),
                'changed_at' => $v2->changed_at,
                'user' => $v2->user_name
            ]
        ];
        
        wp_send_json_success(['comparison' => $comparison]);
    }
    
    /**
     * AJAX: Rollback to version
     */
    public function ajax_rollback_version() {
        check_ajax_referer('yap_history_nonce', 'nonce');
        
        global $wpdb;
        
        $version_id = intval($_POST['version_id']);
        
        $version = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}yap_data_history WHERE id = %d",
            $version_id
        ));
        
        if (!$version) {
            wp_send_json_error(['message' => 'Version not found']);
        }
        
        // Restore old value
        $old_value = $this->unserialize_value($version->old_value);
        
        // Update field in database
        $table_name = $version->group_name;
        $wpdb->update(
            $table_name,
            [$version->field_name => $old_value],
            ['id' => $version->record_id]
        );
        
        // Track this rollback as a new change
        $current_value = $wpdb->get_var($wpdb->prepare(
            "SELECT {$version->field_name} FROM {$table_name} WHERE id = %d",
            $version->record_id
        ));
        
        $this->track_field_change(
            $version->group_name,
            $version->field_name,
            $current_value,
            $old_value,
            $version->record_id
        );
        
        wp_send_json_success([
            'message' => 'Rolled back successfully',
            'value' => $old_value
        ]);
    }
    
    /**
     * AJAX: Restore specific version
     */
    public function ajax_restore_version() {
        check_ajax_referer('yap_history_nonce', 'nonce');
        
        global $wpdb;
        
        $version_id = intval($_POST['version_id']);
        
        $version = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}yap_data_history WHERE id = %d",
            $version_id
        ));
        
        if (!$version) {
            wp_send_json_error(['message' => 'Version not found']);
        }
        
        // Restore to new value (the value at that point in time)
        $restore_value = $this->unserialize_value($version->new_value);
        
        // Update field
        $table_name = $version->group_name;
        $wpdb->update(
            $table_name,
            [$version->field_name => $restore_value],
            ['id' => $version->record_id]
        );
        
        wp_send_json_success([
            'message' => 'Version restored successfully',
            'value' => $restore_value
        ]);
    }
    
    /**
     * Get field history for specific record
     */
    public function get_field_history($group_name, $field_name, $record_id, $limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}yap_data_history 
            WHERE group_name = %s AND field_name = %s AND record_id = %d 
            ORDER BY changed_at DESC 
            LIMIT %d",
            $group_name, $field_name, $record_id, $limit
        ));
    }
    
    /**
     * Get history statistics
     */
    public function get_history_stats() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'yap_data_history';
        
        return [
            'total_changes' => $wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'unique_fields' => $wpdb->get_var("SELECT COUNT(DISTINCT CONCAT(group_name, field_name)) FROM $table"),
            'unique_records' => $wpdb->get_var("SELECT COUNT(DISTINCT record_id) FROM $table"),
            'top_users' => $wpdb->get_results("
                SELECT user_name, COUNT(*) as change_count 
                FROM $table 
                GROUP BY user_name 
                ORDER BY change_count DESC 
                LIMIT 10
            ")
        ];
    }
}

YAP_Data_History::get_instance();
