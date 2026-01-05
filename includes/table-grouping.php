<?php
/**
 * YAP Table Grouping
 * 
 * Grupuje tabele YAP w MySQL poprzez dodanie komentarzy.
 * W phpMyAdmin tabele będą wyświetlane uporządkowane.
 * 
 * @package YetAnotherPlugin
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Table_Grouping {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Grupuj tabele przy aktywacji
        add_action('admin_init', [$this, 'maybe_group_tables']);
        
        // Dodaj submenu
        add_action('admin_menu', [$this, 'add_admin_menu'], 100);
    }
    
    /**
     * Grupuje tabele jeśli jeszcze nie były grupowane
     */
    public function maybe_group_tables() {
        if (get_option('yap_tables_grouped') !== 'yes') {
            $this->group_all_tables();
            update_option('yap_tables_grouped', 'yes');
        }
    }
    
    /**
     * Dodaje submenu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'yap-admin-page',
            'Database Tables',
            '🗄️ Database Tables',
            'manage_options',
            'yap-tables',
            [$this, 'render_page']
        );
    }
    
    /**
     * Renderuje stronę
     */
    public function render_page() {
        global $wpdb;
        
        // Jeśli formularz został wysłany
        if (isset($_POST['yap_regroup_tables']) && check_admin_referer('yap_regroup_tables')) {
            $this->group_all_tables();
            echo '<div class="notice notice-success"><p>✅ Tables regrouped successfully!</p></div>';
        }
        
        // Pobierz wszystkie tabele YAP
        $tables = $this->get_yap_tables();
        ?>
        <div class="wrap">
            <h1>🗄️ YAP Database Tables</h1>
            <p>This page shows all YAP-related database tables grouped by category.</p>
            
            <form method="post">
                <?php wp_nonce_field('yap_regroup_tables'); ?>
                <button type="submit" name="yap_regroup_tables" class="button button-primary">
                    🔄 Regroup All Tables
                </button>
            </form>
            
            <h2 style="margin-top: 30px;">📊 Tables Overview</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                
                <!-- Core System Tables -->
                <div class="yap-table-group" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3>🔧 Core System</h3>
                    <ul>
                        <?php foreach ($tables['core'] as $table): ?>
                            <li><code><?php echo esc_html($table); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Field Groups -->
                <div class="yap-table-group" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3>📝 Field Groups (<?php echo count($tables['groups']); ?>)</h3>
                    <ul style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($tables['groups'] as $group => $group_tables): ?>
                            <li><strong><?php echo esc_html($group); ?></strong>
                                <ul style="margin-left: 15px; font-size: 12px;">
                                    <?php foreach ($group_tables as $table): ?>
                                        <li><code><?php echo esc_html($table); ?></code></li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Advanced Features -->
                <div class="yap-table-group" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3>⚡ Advanced Features</h3>
                    <ul>
                        <?php foreach ($tables['advanced'] as $table): ?>
                            <li><code><?php echo esc_html($table); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
            </div>
            
            <h2 style="margin-top: 40px;">💡 About Table Grouping</h2>
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <p><strong>Table grouping adds comments to your database tables to organize them in phpMyAdmin.</strong></p>
                <ul style="margin-left: 20px;">
                    <li><strong>Core System:</strong> location_rules, options, field_metadata, sync_log</li>
                    <li><strong>Field Groups:</strong> wp_yap_GROUPNAME_pattern and wp_yap_GROUPNAME_data</li>
                    <li><strong>Advanced Features:</strong> data_history, query_fields, automations, automation_log</li>
                </ul>
                <p><strong>Old format tables</strong> (wp_group_*) are <strong>deprecated</strong> and will be migrated automatically.</p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Pobiera wszystkie tabele YAP
     */
    private function get_yap_tables() {
        global $wpdb;
        
        $all_tables = $wpdb->get_col("SHOW TABLES");
        
        $tables = [
            'core' => [],
            'groups' => [],
            'advanced' => [],
        ];
        
        foreach ($all_tables as $table) {
            // Core system tables
            if (preg_match('/_yap_(location_rules|options|field_metadata|sync_log)$/', $table)) {
                $tables['core'][] = $table;
            }
            // Advanced features
            elseif (preg_match('/_yap_(data_history|query_fields|automations|automation_log)$/', $table)) {
                $tables['advanced'][] = $table;
            }
            // Field groups (wp_yap_GROUPNAME_pattern / _data)
            elseif (preg_match('/^' . $wpdb->prefix . 'yap_(.+)_(pattern|data)$/', $table, $matches)) {
                $group_name = $matches[1];
                if (!isset($tables['groups'][$group_name])) {
                    $tables['groups'][$group_name] = [];
                }
                $tables['groups'][$group_name][] = $table;
            }
        }
        
        return $tables;
    }
    
    /**
     * Grupuje wszystkie tabele YAP
     */
    public function group_all_tables() {
        global $wpdb;
        
        $prefix = $wpdb->prefix;
        
        // Core System Tables
        $core_tables = [
            "{$prefix}yap_location_rules" => "YAP Core: Location Rules - Assigns field groups to posts/pages",
            "{$prefix}yap_options" => "YAP Core: Options - Stores options page values",
            "{$prefix}yap_field_metadata" => "YAP Core: Field Metadata - Visual Builder field definitions",
            "{$prefix}yap_sync_log" => "YAP Core: Sync Log - Environment synchronization history",
        ];
        
        foreach ($core_tables as $table => $comment) {
            $this->add_table_comment($table, $comment);
        }
        
        // Advanced Features Tables
        $advanced_tables = [
            "{$prefix}yap_data_history" => "YAP Advanced: Data History - Git-like version control for field data",
            "{$prefix}yap_query_fields" => "YAP Advanced: Query Fields - SQL-powered dynamic fields",
            "{$prefix}yap_automations" => "YAP Advanced: Automations - Airtable-style automation rules",
            "{$prefix}yap_automation_log" => "YAP Advanced: Automation Log - Execution history for automations",
        ];
        
        foreach ($advanced_tables as $table => $comment) {
            $this->add_table_comment($table, $comment);
        }
        
        // Field Group Tables
        $group_tables = $wpdb->get_col("SHOW TABLES LIKE '{$prefix}yap_%'");
        foreach ($group_tables as $table) {
            if (preg_match('/^' . $prefix . 'yap_(.+)_(pattern|data)$/', $table, $matches)) {
                $group_name = $matches[1];
                $type = $matches[2];
                
                // Skip core and advanced tables
                if (in_array($group_name, ['location', 'options', 'field', 'sync', 'data', 'query', 'automations', 'automation'])) {
                    continue;
                }
                
                if ($type === 'pattern') {
                    $comment = "YAP Group: {$group_name} - Field definitions (pattern)";
                } else {
                    $comment = "YAP Group: {$group_name} - Field values (data)";
                }
                
                $this->add_table_comment($table, $comment);
            }
        }
        
        error_log("✅ YAP: All tables grouped successfully");
    }
    
    /**
     * Dodaje komentarz do tabeli
     */
    private function add_table_comment($table, $comment) {
        global $wpdb;
        
        // Sprawdź czy tabela istnieje
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        
        if ($exists) {
            $wpdb->query($wpdb->prepare(
                "ALTER TABLE `%s` COMMENT = %s",
                $table,
                $comment
            ));
        }
    }
}

// Initialize
YAP_Table_Grouping::get_instance();
