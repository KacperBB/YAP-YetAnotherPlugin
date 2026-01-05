<?php
/**
 * YAP Field Migrations System
 * 
 * System migracji struktury pól w stylu Laravel.
 * Kontrola wersji schematu, rollback, historia zmian.
 * 
 * @package YetAnotherPlugin
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Migrations {
    
    private static $instance = null;
    private $migrations_dir;
    private $migrations_table;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        
        $this->migrations_table = $wpdb->prefix . 'yap_migrations';
        $this->migrations_dir = WP_CONTENT_DIR . '/yap-migrations/';
        
        // Utwórz folder migracji jeśli nie istnieje
        if (!file_exists($this->migrations_dir)) {
            wp_mkdir_p($this->migrations_dir);
        }
        
        // Utwórz tabelę migracji
        $this->create_migrations_table();
        
        // WP-CLI commands
        if (defined('WP_CLI') && WP_CLI) {
            $this->register_wp_cli_commands();
        }
    }
    
    /**
     * Tworzy tabelę do śledzenia migracji
     */
    private function create_migrations_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrations_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            migration varchar(255) NOT NULL,
            batch int(11) NOT NULL,
            executed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY migration (migration)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Tworzy nową migrację
     * 
     * @param string $name Nazwa migracji
     * @param callable $up Funkcja up (stosuje zmiany)
     * @param callable $down Funkcja down (cofa zmiany)
     * @return string Ścieżka do pliku migracji
     */
    public function make_migration($name, $up = null, $down = null) {
        $timestamp = date('Y_m_d_His');
        $filename = $timestamp . '_' . $name . '.php';
        $filepath = $this->migrations_dir . $filename;
        
        // Szablon migracji
        $template = "<?php\n";
        $template .= "/**\n";
        $template .= " * Migration: {$name}\n";
        $template .= " * Created at: " . date('Y-m-d H:i:s') . "\n";
        $template .= " */\n\n";
        $template .= "return [\n";
        $template .= "    'up' => function() {\n";
        $template .= "        // Dodaj tutaj zmiany (np. yap_add_field)\n";
        
        if (is_callable($up)) {
            $template .= "        // Auto-generated\n";
            $template .= "    },\n\n";
        } else {
            $template .= "        \n";
            $template .= "    },\n\n";
        }
        
        $template .= "    'down' => function() {\n";
        $template .= "        // Dodaj tutaj cofnięcie zmian (np. yap_remove_field)\n";
        
        if (is_callable($down)) {
            $template .= "        // Auto-generated\n";
            $template .= "    },\n";
        } else {
            $template .= "        \n";
            $template .= "    },\n";
        }
        
        $template .= "];\n";
        
        // Zapisz plik
        file_put_contents($filepath, $template);
        
        // Jeśli podano callbacki, wykonaj je i zapisz do pliku
        if (is_callable($up) || is_callable($down)) {
            $this->register_migration($filename, $up, $down);
        }
        
        return $filepath;
    }
    
    /**
     * Rejestruje migrację z callbackami
     */
    private function register_migration($filename, $up, $down) {
        $filepath = $this->migrations_dir . $filename;
        
        // Upewnij się, że plik istnieje
        if (!file_exists($filepath)) {
            return false;
        }
        
        // Zapisz callbacki do cache
        $migrations_cache = get_option('yap_migrations_cache', []);
        $migrations_cache[$filename] = [
            'up' => $up,
            'down' => $down,
        ];
        update_option('yap_migrations_cache', $migrations_cache);
        
        return true;
    }
    
    /**
     * Wykonuje wszystkie nieuruchomione migracje
     * 
     * @return array Wyniki migracji
     */
    public function migrate() {
        global $wpdb;
        
        $migrations = $this->get_migration_files();
        $executed = $this->get_executed_migrations();
        $batch = $this->get_next_batch_number();
        
        $results = [];
        
        foreach ($migrations as $migration) {
            if (in_array($migration, $executed)) {
                continue; // Już wykonana
            }
            
            try {
                $this->run_migration($migration, 'up');
                
                // Zapisz do bazy
                $wpdb->insert(
                    $this->migrations_table,
                    [
                        'migration' => $migration,
                        'batch' => $batch,
                    ],
                    ['%s', '%d']
                );
                
                $results[] = [
                    'migration' => $migration,
                    'status' => 'success',
                    'message' => 'Migration executed successfully'
                ];
                
            } catch (Exception $e) {
                $results[] = [
                    'migration' => $migration,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                
                error_log("YAP Migration Error ({$migration}): " . $e->getMessage());
                break; // Zatrzymaj na pierwszym błędzie
            }
        }
        
        return $results;
    }
    
    /**
     * Cofa ostatnią batch migracji
     * 
     * @return array Wyniki rollbacku
     */
    public function rollback() {
        global $wpdb;
        
        $last_batch = $wpdb->get_var(
            "SELECT MAX(batch) FROM {$this->migrations_table}"
        );
        
        if (!$last_batch) {
            return [
                ['status' => 'info', 'message' => 'No migrations to rollback']
            ];
        }
        
        $migrations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->migrations_table} WHERE batch = %d ORDER BY id DESC",
                $last_batch
            )
        );
        
        $results = [];
        
        foreach ($migrations as $migration) {
            try {
                $this->run_migration($migration->migration, 'down');
                
                // Usuń z bazy
                $wpdb->delete(
                    $this->migrations_table,
                    ['id' => $migration->id],
                    ['%d']
                );
                
                $results[] = [
                    'migration' => $migration->migration,
                    'status' => 'success',
                    'message' => 'Migration rolled back successfully'
                ];
                
            } catch (Exception $e) {
                $results[] = [
                    'migration' => $migration->migration,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                
                error_log("YAP Rollback Error ({$migration->migration}): " . $e->getMessage());
                break;
            }
        }
        
        return $results;
    }
    
    /**
     * Reset wszystkich migracji (rollback all + migrate)
     */
    public function reset() {
        global $wpdb;
        
        // Pobierz wszystkie migracje w odwrotnej kolejności
        $migrations = $wpdb->get_results(
            "SELECT * FROM {$this->migrations_table} ORDER BY id DESC"
        );
        
        $results = [];
        
        // Rollback wszystkich
        foreach ($migrations as $migration) {
            try {
                $this->run_migration($migration->migration, 'down');
                
                $results[] = [
                    'migration' => $migration->migration,
                    'status' => 'rolled_back',
                ];
                
            } catch (Exception $e) {
                error_log("YAP Reset Error ({$migration->migration}): " . $e->getMessage());
            }
        }
        
        // Wyczyść tabelę migracji
        $wpdb->query("TRUNCATE TABLE {$this->migrations_table}");
        
        // Uruchom ponownie
        $migrate_results = $this->migrate();
        
        return array_merge($results, $migrate_results);
    }
    
    /**
     * Zwraca status migracji
     */
    public function status() {
        $migrations = $this->get_migration_files();
        $executed = $this->get_executed_migrations();
        
        $status = [];
        
        foreach ($migrations as $migration) {
            $status[] = [
                'migration' => $migration,
                'executed' => in_array($migration, $executed),
            ];
        }
        
        return $status;
    }
    
    /**
     * Uruchamia pojedynczą migrację
     */
    private function run_migration($filename, $direction = 'up') {
        $filepath = $this->migrations_dir . $filename;
        
        if (!file_exists($filepath)) {
            throw new Exception("Migration file not found: {$filename}");
        }
        
        // Sprawdź cache
        $migrations_cache = get_option('yap_migrations_cache', []);
        
        if (isset($migrations_cache[$filename][$direction])) {
            $callback = $migrations_cache[$filename][$direction];
            
            if (is_callable($callback)) {
                return call_user_func($callback);
            }
        }
        
        // Wczytaj z pliku
        $migration = include $filepath;
        
        if (!is_array($migration) || !isset($migration[$direction])) {
            throw new Exception("Invalid migration structure in {$filename}");
        }
        
        if (!is_callable($migration[$direction])) {
            throw new Exception("Migration {$direction} is not callable in {$filename}");
        }
        
        return call_user_func($migration[$direction]);
    }
    
    /**
     * Pobiera listę plików migracji
     */
    private function get_migration_files() {
        $files = glob($this->migrations_dir . '*.php');
        $migrations = [];
        
        foreach ($files as $file) {
            $migrations[] = basename($file);
        }
        
        sort($migrations);
        
        return $migrations;
    }
    
    /**
     * Pobiera wykonane migracje z bazy
     */
    private function get_executed_migrations() {
        global $wpdb;
        
        return $wpdb->get_col(
            "SELECT migration FROM {$this->migrations_table} ORDER BY id ASC"
        );
    }
    
    /**
     * Pobiera numer następnej batch
     */
    private function get_next_batch_number() {
        global $wpdb;
        
        $max_batch = $wpdb->get_var(
            "SELECT MAX(batch) FROM {$this->migrations_table}"
        );
        
        return $max_batch ? $max_batch + 1 : 1;
    }
    
    /**
     * Rejestruje komendy WP-CLI
     */
    private function register_wp_cli_commands() {
        if (!class_exists('WP_CLI')) {
            return;
        }
        
        WP_CLI::add_command('yap:migrate', function() {
            WP_CLI::line('Running migrations...');
            
            $results = $this->migrate();
            
            foreach ($results as $result) {
                if ($result['status'] === 'success') {
                    WP_CLI::success($result['migration'] . ' - ' . $result['message']);
                } else {
                    WP_CLI::error($result['migration'] . ' - ' . $result['message']);
                }
            }
            
            if (empty($results)) {
                WP_CLI::success('Nothing to migrate.');
            }
        });
        
        WP_CLI::add_command('yap:rollback', function() {
            WP_CLI::line('Rolling back migrations...');
            
            $results = $this->rollback();
            
            foreach ($results as $result) {
                if ($result['status'] === 'success') {
                    WP_CLI::success($result['migration'] . ' - ' . $result['message']);
                } elseif ($result['status'] === 'info') {
                    WP_CLI::line($result['message']);
                } else {
                    WP_CLI::error($result['migration'] . ' - ' . $result['message']);
                }
            }
        });
        
        WP_CLI::add_command('yap:reset', function() {
            WP_CLI::line('Resetting all migrations...');
            
            $results = $this->reset();
            
            WP_CLI::success('Migrations reset complete.');
        });
        
        WP_CLI::add_command('yap:status', function() {
            $status = $this->status();
            
            $table = [];
            foreach ($status as $item) {
                $table[] = [
                    'Migration' => $item['migration'],
                    'Status' => $item['executed'] ? 'Executed' : 'Pending',
                ];
            }
            
            WP_CLI\Utils\format_items('table', $table, ['Migration', 'Status']);
        });
        
        WP_CLI::add_command('yap:make:migration', function($args, $assoc_args) {
            if (empty($args[0])) {
                WP_CLI::error('Migration name is required.');
            }
            
            $name = $args[0];
            $filepath = $this->make_migration($name);
            
            WP_CLI::success("Migration created: {$filepath}");
        });
    }
}

// Helper functions dla migracji
function yap_make_migration($name, $up = null, $down = null) {
    return YAP_Migrations::get_instance()->make_migration($name, $up, $down);
}

function yap_add_field($group_name, $field_data) {
    global $wpdb;
    
    $defaults = [
        'name' => '',
        'type' => 'text',
        'label' => '',
        'default_value' => '',
        'required' => false,
        'order' => 0,
    ];
    
    $field_data = wp_parse_args($field_data, $defaults);
    
    $table_name = $wpdb->prefix . 'yap_' . $group_name . '_pattern';
    
    // Wygeneruj unique generated_name
    $generated_name = 'yap_' . $group_name . '_' . $field_data['name'] . '_' . substr(md5(uniqid()), 0, 8);
    
    $wpdb->insert(
        $table_name,
        [
            'user_field_name' => $field_data['name'],
            'generated_field_name' => $generated_name,
            'field_type' => $field_data['type'],
            'field_order' => $field_data['order'],
        ],
        ['%s', '%s', '%s', '%d']
    );
    
    error_log("YAP Migration: Added field '{$field_data['name']}' to group '{$group_name}'");
    
    return $wpdb->insert_id;
}

function yap_remove_field($group_name, $field_name) {
    global $wpdb;
    
    $pattern_table = $wpdb->prefix . 'yap_' . $group_name . '_pattern';
    $data_table = $wpdb->prefix . 'yap_' . $group_name . '_data';
    
    // Usuń z pattern
    $wpdb->delete(
        $pattern_table,
        ['user_field_name' => $field_name],
        ['%s']
    );
    
    // Usuń dane
    $wpdb->delete(
        $data_table,
        ['user_field_name' => $field_name],
        ['%s']
    );
    
    error_log("YAP Migration: Removed field '{$field_name}' from group '{$group_name}'");
}

function yap_add_group($group_name) {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Tabela pattern
    $pattern_table = $wpdb->prefix . 'yap_' . $group_name . '_pattern';
    $sql_pattern = "CREATE TABLE IF NOT EXISTS {$pattern_table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_field_name varchar(255) NOT NULL,
        generated_field_name varchar(255) NOT NULL UNIQUE,
        field_type varchar(50) NOT NULL DEFAULT 'text',
        field_order int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        KEY user_field_name (user_field_name)
    ) $charset_collate;";
    
    // Tabela data
    $data_table = $wpdb->prefix . 'yap_' . $group_name . '_data';
    $sql_data = "CREATE TABLE IF NOT EXISTS {$data_table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        post_id bigint(20) unsigned NOT NULL,
        user_field_name varchar(255) NOT NULL,
        field_value longtext,
        PRIMARY KEY  (id),
        UNIQUE KEY post_field (post_id, user_field_name),
        KEY post_id (post_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_pattern);
    dbDelta($sql_data);
    
    error_log("YAP Migration: Created group '{$group_name}'");
}

function yap_remove_group($group_name) {
    global $wpdb;
    
    $pattern_table = $wpdb->prefix . 'yap_' . $group_name . '_pattern';
    $data_table = $wpdb->prefix . 'yap_' . $group_name . '_data';
    
    $wpdb->query("DROP TABLE IF EXISTS {$pattern_table}");
    $wpdb->query("DROP TABLE IF EXISTS {$data_table}");
    
    error_log("YAP Migration: Removed group '{$group_name}'");
}

function yap_rename_field($group_name, $old_name, $new_name) {
    global $wpdb;
    
    $pattern_table = $wpdb->prefix . 'yap_' . $group_name . '_pattern';
    $data_table = $wpdb->prefix . 'yap_' . $group_name . '_data';
    
    // Rename w pattern
    $wpdb->update(
        $pattern_table,
        ['user_field_name' => $new_name],
        ['user_field_name' => $old_name],
        ['%s'],
        ['%s']
    );
    
    // Rename w data
    $wpdb->update(
        $data_table,
        ['user_field_name' => $new_name],
        ['user_field_name' => $old_name],
        ['%s'],
        ['%s']
    );
    
    error_log("YAP Migration: Renamed field '{$old_name}' to '{$new_name}' in group '{$group_name}'");
}
