<?php
/**
 * YAP CLI Tools
 * WP-CLI commands for developers
 * 
 * Usage:
 * wp yap:list                           # List all field groups
 * wp yap:clear-cache                    # Clear YAP cache
 * wp yap:export-json <group_name>       # Export group to JSON
 * wp yap:import-json <file_path>        # Import group from JSON
 * wp yap:migrate                        # Run migrations
 * wp yap:debug                          # Show debug info
 * 
 * @package YAP
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WP_CLI') || !WP_CLI) {
    return; // Only load if WP-CLI is available
}

class YAP_CLI_Tools {
    
    /**
     * List all field groups
     * 
     * ## OPTIONS
     * 
     * [--format=<format>]
     * : Output format (table, json, csv). Default: table
     * 
     * ## EXAMPLES
     * 
     *     wp yap:list
     *     wp yap:list --format=json
     * 
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        global $wpdb;
        
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';
        
        $groups = $wpdb->get_results("SELECT * FROM yap_groups ORDER BY group_name ASC", ARRAY_A);
        
        if (empty($groups)) {
            WP_CLI::success('No field groups found.');
            return;
        }
        
        // Format field counts
        foreach ($groups as &$group) {
            $table_name = sanitize_key($group['group_name']);
            $fields = $wpdb->get_results("DESCRIBE {$table_name}");
            $group['field_count'] = count($fields) - 1; // Exclude ID column
        }
        
        if ($format === 'table') {
            WP_CLI\Utils\format_items('table', $groups, ['id', 'group_name', 'field_count']);
        } else {
            WP_CLI\Utils\format_items($format, $groups, ['id', 'group_name', 'field_count']);
        }
        
        WP_CLI::success(sprintf('Found %d field groups.', count($groups)));
    }
    
    /**
     * Clear YAP cache
     * 
     * ## OPTIONS
     * 
     * [--group=<group_name>]
     * : Clear cache for specific group only
     * 
     * ## EXAMPLES
     * 
     *     wp yap:clear-cache
     *     wp yap:clear-cache --group=products
     * 
     * @when after_wp_load
     */
    public function clear_cache($args, $assoc_args) {
        $cache = YAP_Cache::get_instance();
        
        if (isset($assoc_args['group'])) {
            $group_name = $assoc_args['group'];
            
            // Clear specific group cache
            $cache->clear_group($group_name);
            
            WP_CLI::success(sprintf('Cache cleared for group: %s', $group_name));
        } else {
            // Clear all cache
            $cache->clear_all();
            
            WP_CLI::success('All YAP cache cleared.');
        }
        
        // Also clear WordPress transients
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_yap_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_yap_%'");
        
        WP_CLI::line('WordPress transients cleared.');
    }
    
    /**
     * Export field group to JSON
     * 
     * ## OPTIONS
     * 
     * <group_name>
     * : Name of the group to export
     * 
     * [--output=<file_path>]
     * : Output file path (default: wp-content/yap-exports/{group_name}.json)
     * 
     * [--format=<format>]
     * : Export format (json, php). Default: json
     * 
     * ## EXAMPLES
     * 
     *     wp yap:export-json products
     *     wp yap:export-json products --output=/tmp/products.json
     *     wp yap:export-json products --format=php
     * 
     * @when after_wp_load
     */
    public function export_json($args, $assoc_args) {
        global $wpdb;
        
        if (empty($args[0])) {
            WP_CLI::error('Group name is required.');
        }
        
        $group_name = sanitize_key($args[0]);
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'json';
        
        // Check if group exists
        $group = $wpdb->get_row($wpdb->prepare("SELECT * FROM yap_groups WHERE group_name = %s", $group_name));
        
        if (!$group) {
            WP_CLI::error(sprintf('Group not found: %s', $group_name));
        }
        
        // Get group schema
        $fields = $wpdb->get_results("DESCRIBE {$group_name}");
        
        $schema = [
            'name' => $group_name,
            'id' => $group->id,
            'fields' => []
        ];
        
        foreach ($fields as $field) {
            if ($field->Field === 'id') continue;
            
            $schema['fields'][] = [
                'name' => $field->Field,
                'type' => $this->parse_mysql_type($field->Type),
                'null' => $field->Null === 'YES',
                'default' => $field->Default,
                'key' => $field->Key
            ];
        }
        
        // Set output path
        if (isset($assoc_args['output'])) {
            $output_path = $assoc_args['output'];
        } else {
            $export_dir = WP_CONTENT_DIR . '/yap-exports';
            if (!file_exists($export_dir)) {
                mkdir($export_dir, 0755, true);
            }
            $output_path = $export_dir . '/' . $group_name . '.' . $format;
        }
        
        // Export based on format
        if ($format === 'php') {
            $content = "<?php\nreturn " . var_export($schema, true) . ";\n";
        } else {
            $content = json_encode($schema, JSON_PRETTY_PRINT);
        }
        
        file_put_contents($output_path, $content);
        
        WP_CLI::success(sprintf('Group exported to: %s', $output_path));
    }
    
    /**
     * Import field group from JSON
     * 
     * ## OPTIONS
     * 
     * <file_path>
     * : Path to JSON file
     * 
     * [--overwrite]
     * : Overwrite existing group
     * 
     * ## EXAMPLES
     * 
     *     wp yap:import-json /tmp/products.json
     *     wp yap:import-json /tmp/products.json --overwrite
     * 
     * @when after_wp_load
     */
    public function import_json($args, $assoc_args) {
        global $wpdb;
        
        if (empty($args[0])) {
            WP_CLI::error('File path is required.');
        }
        
        $file_path = $args[0];
        
        if (!file_exists($file_path)) {
            WP_CLI::error(sprintf('File not found: %s', $file_path));
        }
        
        $content = file_get_contents($file_path);
        
        // Parse based on file extension
        if (pathinfo($file_path, PATHINFO_EXTENSION) === 'php') {
            $schema = include $file_path;
        } else {
            $schema = json_decode($content, true);
        }
        
        if (!$schema || !isset($schema['name'])) {
            WP_CLI::error('Invalid schema format.');
        }
        
        $group_name = sanitize_key($schema['name']);
        
        // Check if group exists
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM yap_groups WHERE group_name = %s", $group_name));
        
        if ($exists && !isset($assoc_args['overwrite'])) {
            WP_CLI::error(sprintf('Group already exists: %s. Use --overwrite to replace it.', $group_name));
        }
        
        if ($exists) {
            // Drop existing table
            $wpdb->query("DROP TABLE IF EXISTS {$group_name}");
            $wpdb->delete('yap_groups', ['group_name' => $group_name]);
        }
        
        // Create new group
        yap_add_group($group_name);
        
        // Add fields
        foreach ($schema['fields'] as $field) {
            yap_add_field($group_name, $field['name'], $field['type']);
        }
        
        WP_CLI::success(sprintf('Group imported: %s (%d fields)', $group_name, count($schema['fields'])));
    }
    
    /**
     * Run migrations
     * 
     * ## OPTIONS
     * 
     * [--up]
     * : Run pending migrations
     * 
     * [--down]
     * : Rollback last migration batch
     * 
     * [--status]
     * : Show migration status
     * 
     * [--create=<name>]
     * : Create new migration file
     * 
     * ## EXAMPLES
     * 
     *     wp yap:migrate --status
     *     wp yap:migrate --up
     *     wp yap:migrate --down
     *     wp yap:migrate --create=add_products_table
     * 
     * @when after_wp_load
     */
    public function migrate($args, $assoc_args) {
        $migrations = YAP_Migrations::get_instance();
        
        if (isset($assoc_args['status'])) {
            $status = $migrations->get_migration_status();
            
            WP_CLI::line('Migration Status:');
            WP_CLI::line('');
            
            WP_CLI::line(sprintf('Pending migrations: %d', count($status['pending'])));
            if (!empty($status['pending'])) {
                foreach ($status['pending'] as $migration) {
                    WP_CLI::line('  - ' . $migration);
                }
            }
            
            WP_CLI::line('');
            WP_CLI::line(sprintf('Applied migrations: %d', count($status['applied'])));
            if (!empty($status['applied'])) {
                foreach ($status['applied'] as $migration) {
                    WP_CLI::line('  - ' . $migration);
                }
            }
            
        } elseif (isset($assoc_args['up'])) {
            WP_CLI::line('Running migrations...');
            
            $result = $migrations->run_migrations();
            
            if ($result['success']) {
                WP_CLI::success(sprintf('Ran %d migrations.', count($result['migrations'])));
                
                foreach ($result['migrations'] as $migration) {
                    WP_CLI::line('  ✓ ' . $migration);
                }
            } else {
                WP_CLI::error($result['message']);
            }
            
        } elseif (isset($assoc_args['down'])) {
            WP_CLI::line('Rolling back last migration batch...');
            
            $result = $migrations->rollback_migration();
            
            if ($result['success']) {
                WP_CLI::success(sprintf('Rolled back %d migrations.', count($result['migrations'])));
                
                foreach ($result['migrations'] as $migration) {
                    WP_CLI::line('  ✓ ' . $migration);
                }
            } else {
                WP_CLI::error($result['message']);
            }
            
        } elseif (isset($assoc_args['create'])) {
            $name = sanitize_file_name($assoc_args['create']);
            
            $result = $migrations->create_migration($name);
            
            if ($result['success']) {
                WP_CLI::success(sprintf('Created migration: %s', $result['file']));
            } else {
                WP_CLI::error($result['message']);
            }
            
        } else {
            WP_CLI::error('Please specify an option: --status, --up, --down, or --create=<name>');
        }
    }
    
    /**
     * Show debug information
     * 
     * ## OPTIONS
     * 
     * [--group=<group_name>]
     * : Show debug info for specific group
     * 
     * [--verbose]
     * : Show detailed information
     * 
     * ## EXAMPLES
     * 
     *     wp yap:debug
     *     wp yap:debug --group=products
     *     wp yap:debug --verbose
     * 
     * @when after_wp_load
     */
    public function debug($args, $assoc_args) {
        global $wpdb;
        
        WP_CLI::line('=== YAP Debug Information ===');
        WP_CLI::line('');
        
        // Plugin version
        WP_CLI::line('Plugin Version: 1.3.0');
        WP_CLI::line('');
        
        // Groups count
        $groups_count = $wpdb->get_var("SELECT COUNT(*) FROM yap_groups");
        WP_CLI::line(sprintf('Total Groups: %d', $groups_count));
        WP_CLI::line('');
        
        // Cache stats
        if (class_exists('YAP_Cache')) {
            $cache = YAP_Cache::get_instance();
            $stats = $cache->get_stats();
            
            WP_CLI::line('Cache Statistics:');
            WP_CLI::line(sprintf('  - Hits: %d', $stats['hits']));
            WP_CLI::line(sprintf('  - Misses: %d', $stats['misses']));
            WP_CLI::line(sprintf('  - Hit Rate: %.2f%%', $stats['hit_rate']));
            WP_CLI::line('');
        }
        
        // Database size
        $db_size = $wpdb->get_var("
            SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb
            FROM information_schema.TABLES
            WHERE table_schema = '{$wpdb->dbname}'
            AND table_name LIKE 'yap_%'
        ");
        WP_CLI::line(sprintf('Database Size: %.2f MB', $db_size));
        WP_CLI::line('');
        
        // Group-specific debug
        if (isset($assoc_args['group'])) {
            $group_name = $assoc_args['group'];
            
            $group = $wpdb->get_row($wpdb->prepare("SELECT * FROM yap_groups WHERE group_name = %s", $group_name));
            
            if (!$group) {
                WP_CLI::error(sprintf('Group not found: %s', $group_name));
            }
            
            WP_CLI::line(sprintf('Group Debug: %s', $group_name));
            WP_CLI::line('');
            
            // Fields
            $fields = $wpdb->get_results("DESCRIBE {$group_name}");
            WP_CLI::line(sprintf('Total Fields: %d', count($fields) - 1));
            
            if (isset($assoc_args['verbose'])) {
                WP_CLI::line('');
                WP_CLI::line('Fields:');
                foreach ($fields as $field) {
                    if ($field->Field === 'id') continue;
                    WP_CLI::line(sprintf('  - %s (%s)', $field->Field, $field->Type));
                }
            }
            WP_CLI::line('');
            
            // Records count
            $records = $wpdb->get_var("SELECT COUNT(*) FROM {$group_name}");
            WP_CLI::line(sprintf('Total Records: %d', $records));
            WP_CLI::line('');
            
            // Table size
            $table_size = $wpdb->get_var($wpdb->prepare("
                SELECT (data_length + index_length) / 1024 / 1024 AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = %s
                AND table_name = %s
            ", $wpdb->dbname, $group_name));
            WP_CLI::line(sprintf('Table Size: %.2f MB', $table_size));
        }
        
        // Verbose system info
        if (isset($assoc_args['verbose'])) {
            WP_CLI::line('');
            WP_CLI::line('System Information:');
            WP_CLI::line(sprintf('  - PHP Version: %s', PHP_VERSION));
            WP_CLI::line(sprintf('  - WordPress Version: %s', get_bloginfo('version')));
            WP_CLI::line(sprintf('  - MySQL Version: %s', $wpdb->db_version()));
            WP_CLI::line(sprintf('  - Memory Limit: %s', ini_get('memory_limit')));
            WP_CLI::line(sprintf('  - Max Execution Time: %s', ini_get('max_execution_time')));
        }
        
        WP_CLI::success('Debug information displayed.');
    }
    
    /**
     * Parse MySQL type to simple type
     */
    private function parse_mysql_type($mysql_type) {
        if (strpos($mysql_type, 'varchar') !== false) return 'text';
        if (strpos($mysql_type, 'text') !== false) return 'textarea';
        if (strpos($mysql_type, 'int') !== false) return 'number';
        if (strpos($mysql_type, 'decimal') !== false) return 'number';
        if (strpos($mysql_type, 'date') !== false) return 'date';
        if (strpos($mysql_type, 'datetime') !== false) return 'datetime';
        return 'text';
    }
}

// Register WP-CLI commands
WP_CLI::add_command('yap:list', ['YAP_CLI_Tools', 'list']);
WP_CLI::add_command('yap:clear-cache', ['YAP_CLI_Tools', 'clear_cache']);
WP_CLI::add_command('yap:export-json', ['YAP_CLI_Tools', 'export_json']);
WP_CLI::add_command('yap:import-json', ['YAP_CLI_Tools', 'import_json']);
WP_CLI::add_command('yap:migrate', ['YAP_CLI_Tools', 'migrate']);
WP_CLI::add_command('yap:debug', ['YAP_CLI_Tools', 'debug']);
