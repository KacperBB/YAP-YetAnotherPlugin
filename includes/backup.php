<?php
/**
 * YAP Backup & Restore System
 * Backup and restore field groups, data, and configurations
 */

class YAP_Backup {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'add_backup_page'], 20);
        add_action('admin_post_yap_export_backup', [$this, 'handle_export']);
        add_action('admin_post_yap_import_backup', [$this, 'handle_import']);
    }
    
    /**
     * Add backup page to admin menu
     */
    public function add_backup_page() {
        add_submenu_page(
            'yap-admin-page',
            'Backup & Restore',
            '💾 Backup',
            'manage_options',
            'yap-backup',
            [$this, 'render_backup_page']
        );
    }
    
    /**
     * Render backup page
     */
    public function render_backup_page() {
        ?>
        <div class="wrap">
            <h1>YAP Backup & Restore</h1>
            
            <!-- Export Section -->
            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2>Export Backup</h2>
                <p>Export field groups and their data to a backup file.</p>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="yap_export_backup">
                    <?php wp_nonce_field('yap_backup_export'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Include Schema</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="include_schema" value="1" checked>
                                    Export field group definitions
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Include Data</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="include_data" value="1" checked>
                                    Export all field values
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Include Options</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="include_options" value="1" checked>
                                    Export options pages data
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Include Location Rules</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="include_locations" value="1" checked>
                                    Export location rules
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Select Groups</th>
                            <td>
                                <?php
                                $groups = $this->get_all_groups();
                                foreach ($groups as $group): ?>
                                    <label style="display: block; margin: 5px 0;">
                                        <input type="checkbox" name="groups[]" value="<?php echo esc_attr($group); ?>" checked>
                                        <?php echo esc_html($group); ?>
                                    </label>
                                <?php endforeach; ?>
                                <p class="description">Select which groups to export</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Export Backup', 'primary', 'submit', false); ?>
                </form>
            </div>
            
            <!-- Import Section -->
            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2>Import Backup</h2>
                <p>Import field groups and data from a backup file.</p>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="yap_import_backup">
                    <?php wp_nonce_field('yap_backup_import'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Backup File</th>
                            <td>
                                <input type="file" name="backup_file" accept=".json" required>
                                <p class="description">Select a YAP backup JSON file</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Import Mode</th>
                            <td>
                                <label style="display: block; margin: 5px 0;">
                                    <input type="radio" name="import_mode" value="merge" checked>
                                    <strong>Merge:</strong> Keep existing data, add new groups
                                </label>
                                <label style="display: block; margin: 5px 0;">
                                    <input type="radio" name="import_mode" value="replace">
                                    <strong>Replace:</strong> Delete existing groups and data, import new
                                </label>
                                <label style="display: block; margin: 5px 0;">
                                    <input type="radio" name="import_mode" value="skip">
                                    <strong>Skip Existing:</strong> Only import new groups, skip duplicates
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Import Backup', 'primary', 'submit', false); ?>
                </form>
            </div>
            
            <!-- Quick Actions -->
            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2>Quick Actions</h2>
                
                <p>
                    <a href="<?php echo admin_url('admin-post.php?action=yap_export_schema_only&' . wp_create_nonce('yap_export')); ?>" 
                       class="button">
                        Export Schema Only (Groups & Fields)
                    </a>
                </p>
                
                <p>
                    <a href="<?php echo admin_url('admin-post.php?action=yap_export_data_only&' . wp_create_nonce('yap_export')); ?>" 
                       class="button">
                        Export Data Only (Field Values)
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle export
     */
    public function handle_export() {
        check_admin_referer('yap_backup_export');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $include_schema = isset($_POST['include_schema']);
        $include_data = isset($_POST['include_data']);
        $include_options = isset($_POST['include_options']);
        $include_locations = isset($_POST['include_locations']);
        $selected_groups = $_POST['groups'] ?? [];
        
        $backup = $this->create_backup([
            'include_schema' => $include_schema,
            'include_data' => $include_data,
            'include_options' => $include_options,
            'include_locations' => $include_locations,
            'groups' => $selected_groups
        ]);
        
        // Output as JSON file
        $filename = 'yap-backup-' . date('Y-m-d-His') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        echo json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Handle import
     */
    public function handle_import() {
        check_admin_referer('yap_backup_import');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die('No file uploaded or upload error');
        }
        
        $file_content = file_get_contents($_FILES['backup_file']['tmp_name']);
        $backup_data = json_decode($file_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_die('Invalid JSON file: ' . json_last_error_msg());
        }
        
        $import_mode = $_POST['import_mode'] ?? 'merge';
        
        $result = $this->restore_backup($backup_data, $import_mode);
        
        if (is_wp_error($result)) {
            wp_die('Import failed: ' . $result->get_error_message());
        }
        
        wp_redirect(add_query_arg([
            'page' => 'yap-backup',
            'message' => 'imported'
        ], admin_url('admin.php')));
        exit;
    }
    
    /**
     * Create backup
     */
    public function create_backup($options = []) {
        $backup = [
            'version' => '1.0',
            'created' => current_time('mysql'),
            'site_url' => get_site_url(),
            'yap_version' => '1.1',
            'groups' => []
        ];
        
        $groups = !empty($options['groups']) ? $options['groups'] : $this->get_all_groups();
        
        foreach ($groups as $group_name) {
            $group_data = ['name' => $group_name];
            
            // Export schema
            if ($options['include_schema']) {
                $json_manager = YAP_JSON_Manager::get_instance();
                $group_data['schema'] = $json_manager->export_group($group_name);
            }
            
            // Export location rules
            if ($options['include_locations']) {
                $group_data['locations'] = $this->export_location_rules($group_name);
            }
            
            // Export data
            if ($options['include_data']) {
                $group_data['data'] = $this->export_group_data($group_name);
            }
            
            // Export options
            if ($options['include_options']) {
                $group_data['options'] = $this->export_options_data($group_name);
            }
            
            $backup['groups'][] = $group_data;
        }
        
        return $backup;
    }
    
    /**
     * Restore backup
     */
    public function restore_backup($backup_data, $mode = 'merge') {
        global $wpdb;
        
        if (empty($backup_data['groups'])) {
            return new WP_Error('empty_backup', 'Backup contains no groups');
        }
        
        foreach ($backup_data['groups'] as $group_data) {
            $group_name = $group_data['name'];
            
            // Check if group exists
            $exists = $this->group_exists($group_name);
            
            if ($exists && $mode === 'skip') {
                continue;
            }
            
            if ($exists && $mode === 'replace') {
                $this->delete_group($group_name);
            }
            
            // Import schema
            if (!empty($group_data['schema'])) {
                $json_manager = YAP_JSON_Manager::get_instance();
                $json_manager->import_group($group_data['schema']);
            }
            
            // Import location rules
            if (!empty($group_data['locations'])) {
                $this->import_location_rules($group_name, $group_data['locations']);
            }
            
            // Import data
            if (!empty($group_data['data'])) {
                $this->import_group_data($group_name, $group_data['data'], $mode);
            }
            
            // Import options
            if (!empty($group_data['options'])) {
                $this->import_options_data($group_name, $group_data['options']);
            }
        }
        
        return true;
    }
    
    /**
     * Export location rules
     */
    private function export_location_rules($group_name) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'yap_location_rules';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE group_name = %s",
            $group_name
        ), ARRAY_A);
    }
    
    /**
     * Import location rules
     */
    private function import_location_rules($group_name, $rules) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'yap_location_rules';
        
        // Delete existing
        $wpdb->delete($table, ['group_name' => $group_name]);
        
        // Insert new
        foreach ($rules as $rule) {
            unset($rule['id']); // Remove old ID
            $rule['group_name'] = $group_name;
            $wpdb->insert($table, $rule);
        }
    }
    
    /**
     * Export group data
     */
    private function export_group_data($group_name) {
        global $wpdb;
        
        $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
        
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $data_table)) !== $data_table) {
            return [];
        }
        
        $safe_table = esc_sql($data_table);
        return $wpdb->get_results("SELECT * FROM `{$safe_table}`", ARRAY_A);
    }
    
    /**
     * Import group data
     */
    private function import_group_data($group_name, $data, $mode) {
        global $wpdb;
        
        $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
        
        foreach ($data as $row) {
            unset($row['id']); // Remove old ID
            
            if ($mode === 'merge') {
                // Check if exists using correct schema: generated_name + associated_id
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $data_table WHERE generated_name = %s AND associated_id = %d",
                    $row['generated_name'],
                    $row['associated_id']
                ));
                
                if ($exists) {
                    $wpdb->update($data_table, $row, ['id' => $exists]);
                    continue;
                }
            }
            
            $wpdb->insert($data_table, $row);
        }
    }
    
    /**
     * Export options data
     */
    private function export_options_data($group_name) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'yap_options';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE option_page LIKE %s",
            '%' . $group_name . '%'
        ), ARRAY_A);
    }
    
    /**
     * Import options data
     */
    private function import_options_data($group_name, $options) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'yap_options';
        
        foreach ($options as $option) {
            unset($option['id']);
            
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE option_page = %s AND field_name = %s",
                $option['option_page'],
                $option['field_name']
            ));
            
            if ($exists) {
                $wpdb->update($table, $option, ['id' => $exists]);
            } else {
                $wpdb->insert($table, $option);
            }
        }
    }
    
    /**
     * Check if group exists
     */
    private function group_exists($group_name) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table;
    }
    
    /**
     * Delete group completely
     */
    private function delete_group($group_name) {
        global $wpdb;
        
        $pattern_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
        $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
        
        $safe_pattern = esc_sql($pattern_table);
        $safe_data = esc_sql($data_table);
        $wpdb->query("DROP TABLE IF EXISTS `{$safe_pattern}`");
        $wpdb->query("DROP TABLE IF EXISTS `{$safe_data}`");
    }
    
    /**
     * Get all groups
     */
    private function get_all_groups() {
        global $wpdb;
        
        $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}group_%_pattern'");
        $groups = [];
        
        foreach ($tables as $table) {
            $table_name = reset($table);
            $group_name = str_replace(
                [$wpdb->prefix . 'group_', '_pattern'],
                '',
                $table_name
            );
            $groups[] = $group_name;
        }
        
        return $groups;
    }
}

// Helper functions
function yap_export_backup($options = []) {
    return YAP_Backup::get_instance()->create_backup($options);
}

function yap_import_backup($backup_data, $mode = 'merge') {
    return YAP_Backup::get_instance()->restore_backup($backup_data, $mode);
}
