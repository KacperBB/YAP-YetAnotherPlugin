<?php
/**
 * YAP Modules System
 * 
 * Extensibility framework for third-party developers.
 * Allows creating custom field types, layouts, sanitizers, and more.
 * 
 * Features:
 * - Module registration and activation
 * - Custom field type registration
 * - Custom sanitizers/transformers
 * - Custom layouts and templates
 * - Module marketplace
 * - Dependency management
 * - Auto-updates
 * 
 * @package YetAnotherPlugin
 * @subpackage Modules
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Modules {
    
    private static $instance = null;
    private $modules = [];
    private $active_modules = [];
    private $module_paths = [];
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->module_paths = [
            WP_CONTENT_DIR . '/yap-modules/',
            plugin_dir_path(__DIR__) . 'modules/'
        ];
        
        // Load active modules
        $this->active_modules = get_option('yap_active_modules', []);
        
        // Admin hooks
        add_action('admin_menu', [$this, 'add_admin_menu'], 100);
        add_action('admin_init', [$this, 'maybe_activate_module']);
        add_action('admin_init', [$this, 'maybe_deactivate_module']);
        
        // AJAX
        add_action('wp_ajax_yap_install_module', [$this, 'ajax_install_module']);
        add_action('wp_ajax_yap_delete_module', [$this, 'ajax_delete_module']);
        add_action('wp_ajax_yap_check_module_updates', [$this, 'ajax_check_updates']);
        
        // Discover and load modules
        $this->discover_modules();
        $this->load_active_modules();
        
        // Module API hooks
        add_filter('yap_module_paths', [$this, 'filter_module_paths']);
    }
    
    /**
     * Discover all available modules
     */
    private function discover_modules() {
        foreach ($this->module_paths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            
            $dirs = scandir($path);
            
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') {
                    continue;
                }
                
                $module_path = $path . $dir;
                $manifest_file = $module_path . '/module.json';
                
                if (is_dir($module_path) && file_exists($manifest_file)) {
                    $manifest = json_decode(file_get_contents($manifest_file), true);
                    
                    if ($manifest && isset($manifest['id'])) {
                        $this->modules[$manifest['id']] = array_merge($manifest, [
                            'path' => $module_path,
                            'main_file' => $module_path . '/module.php'
                        ]);
                    }
                }
            }
        }
    }
    
    /**
     * Load active modules
     */
    private function load_active_modules() {
        foreach ($this->active_modules as $module_id) {
            if (isset($this->modules[$module_id])) {
                $this->load_module($module_id);
            }
        }
    }
    
    /**
     * Load a single module
     */
    private function load_module($module_id) {
        if (!isset($this->modules[$module_id])) {
            return false;
        }
        
        $module = $this->modules[$module_id];
        
        // Check dependencies
        if (!empty($module['dependencies'])) {
            foreach ($module['dependencies'] as $dep_id => $dep_version) {
                if (!in_array($dep_id, $this->active_modules)) {
                    add_action('admin_notices', function() use ($module, $dep_id) {
                        echo '<div class="notice notice-error"><p>';
                        printf(
                            'Module "%s" requires "%s" to be activated.',
                            $module['name'],
                            $dep_id
                        );
                        echo '</p></div>';
                    });
                    return false;
                }
            }
        }
        
        // Load main file
        if (file_exists($module['main_file'])) {
            require_once $module['main_file'];
            
            // Initialize module if it has init function
            $init_function = 'yap_module_' . $module_id . '_init';
            if (function_exists($init_function)) {
                call_user_func($init_function);
            }
            
            // Fire action hook
            do_action('yap_module_loaded', $module_id, $module);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Activate module
     */
    public function activate_module($module_id) {
        if (in_array($module_id, $this->active_modules)) {
            return true;
        }
        
        if ($this->load_module($module_id)) {
            $this->active_modules[] = $module_id;
            update_option('yap_active_modules', $this->active_modules);
            
            do_action('yap_module_activated', $module_id);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Deactivate module
     */
    public function deactivate_module($module_id) {
        $key = array_search($module_id, $this->active_modules);
        
        if ($key !== false) {
            unset($this->active_modules[$key]);
            $this->active_modules = array_values($this->active_modules);
            update_option('yap_active_modules', $this->active_modules);
            
            do_action('yap_module_deactivated', $module_id);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Register a custom field type from a module
     */
    public static function register_field_type($id, $config) {
        add_filter('yap_field_types', function($types) use ($id, $config) {
            $types[$id] = array_merge([
                'label' => '',
                'description' => '',
                'render_callback' => null,
                'sanitize_callback' => null,
                'supports' => []
            ], $config);
            return $types;
        });
    }
    
    /**
     * Register a custom sanitizer from a module
     */
    public static function register_sanitizer($id, $callback, $description = '') {
        add_filter('yap_sanitizers', function($sanitizers) use ($id, $callback, $description) {
            $sanitizers[$id] = [
                'callback' => $callback,
                'description' => $description
            ];
            return $sanitizers;
        });
    }
    
    /**
     * Register a custom transformer from a module
     */
    public static function register_transformer($id, $callback, $description = '') {
        add_filter('yap_transformers', function($transformers) use ($id, $callback, $description) {
            $transformers[$id] = [
                'callback' => $callback,
                'description' => $description
            ];
            return $transformers;
        });
    }
    
    /**
     * Register a custom layout from a module
     */
    public static function register_layout($id, $config) {
        add_filter('yap_layouts', function($layouts) use ($id, $config) {
            $layouts[$id] = array_merge([
                'label' => '',
                'render_callback' => null,
                'css' => '',
                'js' => ''
            ], $config);
            return $layouts;
        });
    }
    
    /**
     * Maybe activate module (from admin action)
     */
    public function maybe_activate_module() {
        if (!isset($_GET['yap_activate_module']) || !current_user_can('manage_options')) {
            return;
        }
        
        check_admin_referer('yap_activate_module');
        
        $module_id = sanitize_key($_GET['yap_activate_module']);
        
        if ($this->activate_module($module_id)) {
            wp_redirect(add_query_arg(['activated' => '1'], admin_url('admin.php?page=yap-modules')));
            exit;
        }
    }
    
    /**
     * Maybe deactivate module (from admin action)
     */
    public function maybe_deactivate_module() {
        if (!isset($_GET['yap_deactivate_module']) || !current_user_can('manage_options')) {
            return;
        }
        
        check_admin_referer('yap_deactivate_module');
        
        $module_id = sanitize_key($_GET['yap_deactivate_module']);
        
        if ($this->deactivate_module($module_id)) {
            wp_redirect(add_query_arg(['deactivated' => '1'], admin_url('admin.php?page=yap-modules')));
            exit;
        }
    }
    
    /**
     * AJAX: Install module from marketplace
     */
    public function ajax_install_module() {
        check_ajax_referer('yap_modules', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $module_url = esc_url_raw($_POST['module_url'] ?? '');
        
        if (empty($module_url)) {
            wp_send_json_error(['message' => 'Module URL required']);
        }
        
        // Download module
        $temp_file = download_url($module_url);
        
        if (is_wp_error($temp_file)) {
            wp_send_json_error(['message' => $temp_file->get_error_message()]);
        }
        
        // Extract to modules directory
        $modules_dir = WP_CONTENT_DIR . '/yap-modules/';
        
        if (!is_dir($modules_dir)) {
            wp_mkdir_p($modules_dir);
        }
        
        $result = unzip_file($temp_file, $modules_dir);
        
        @unlink($temp_file);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        // Re-discover modules
        $this->modules = [];
        $this->discover_modules();
        
        wp_send_json_success(['message' => 'Module installed successfully']);
    }
    
    /**
     * AJAX: Delete module
     */
    public function ajax_delete_module() {
        check_ajax_referer('yap_modules', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $module_id = sanitize_key($_POST['module_id'] ?? '');
        
        if (!isset($this->modules[$module_id])) {
            wp_send_json_error(['message' => 'Module not found']);
        }
        
        // Deactivate first
        $this->deactivate_module($module_id);
        
        // Delete files
        $module_path = $this->modules[$module_id]['path'];
        
        require_once ABSPATH . 'wp-admin/includes/file.php';
        global $wp_filesystem;
        WP_Filesystem();
        
        if ($wp_filesystem->delete($module_path, true)) {
            unset($this->modules[$module_id]);
            wp_send_json_success(['message' => 'Module deleted']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete module files']);
        }
    }
    
    /**
     * AJAX: Check for module updates
     */
    public function ajax_check_updates() {
        check_ajax_referer('yap_modules', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $updates = [];
        
        foreach ($this->modules as $module_id => $module) {
            if (empty($module['update_url'])) {
                continue;
            }
            
            $response = wp_remote_get($module['update_url']);
            
            if (is_wp_error($response)) {
                continue;
            }
            
            $data = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($data && version_compare($data['version'], $module['version'], '>')) {
                $updates[$module_id] = [
                    'current' => $module['version'],
                    'new' => $data['version'],
                    'download_url' => $data['download_url'] ?? ''
                ];
            }
        }
        
        wp_send_json_success(['updates' => $updates]);
    }
    
    /**
     * Filter module paths
     */
    public function filter_module_paths($paths) {
        return array_merge($paths, $this->module_paths);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        $hook = add_submenu_page(
            'yap-admin-page',
            'YAP Modules',
            '🧩 Modules',
            'manage_options',
            'yap-modules',
            [$this, 'render_admin_page']
        );
        
        add_action('admin_print_styles-' . $hook, [$this, 'enqueue_admin_assets']);
    }
    
    public function enqueue_admin_assets() {
        wp_enqueue_style(
            'yap-advanced-features',
            plugin_dir_url(__DIR__) . 'includes/css/yap-advanced-features.css',
            [],
            '1.4.0'
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>🧩 YAP Modules</h1>
            
            <?php if (isset($_GET['activated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Module activated successfully!</p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deactivated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Module deactivated successfully!</p>
                </div>
            <?php endif; ?>
            
            <div class="yap-modules-header" style="margin: 20px 0;">
                <button class="button button-primary" id="yap-install-module-btn">
                    <span class="dashicons dashicons-download"></span> Install Module
                </button>
                
                <button class="button" id="yap-check-updates-btn">
                    <span class="dashicons dashicons-update"></span> Check for Updates
                </button>
            </div>
            
            <div class="yap-modules-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                <?php if (empty($this->modules)): ?>
                    <div class="yap-card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <p>No modules installed yet.</p>
                        <p>Install modules to extend YAP functionality!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($this->modules as $module_id => $module): ?>
                        <?php $is_active = in_array($module_id, $this->active_modules); ?>
                        <div class="yap-module-card" style="background: white; border: 1px solid #ccc; border-radius: 8px; padding: 20px; <?php echo $is_active ? 'border-color: #46b450;' : ''; ?>">
                            <div class="module-header" style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                <div>
                                    <h3 style="margin: 0 0 5px 0;"><?php echo esc_html($module['name']); ?></h3>
                                    <span class="module-version" style="color: #666; font-size: 12px;">v<?php echo esc_html($module['version']); ?></span>
                                </div>
                                
                                <?php if ($is_active): ?>
                                    <span class="badge" style="background: #46b450; color: white; padding: 3px 10px; border-radius: 12px; font-size: 11px;">Active</span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="module-description" style="color: #666; font-size: 14px; margin-bottom: 15px;">
                                <?php echo esc_html($module['description'] ?? 'No description'); ?>
                            </p>
                            
                            <div class="module-meta" style="font-size: 12px; color: #999; margin-bottom: 15px;">
                                <div>By: <strong><?php echo esc_html($module['author'] ?? 'Unknown'); ?></strong></div>
                                <?php if (!empty($module['author_url'])): ?>
                                    <div><a href="<?php echo esc_url($module['author_url']); ?>" target="_blank">Visit website</a></div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($module['provides'])): ?>
                                <div class="module-provides" style="margin-bottom: 15px;">
                                    <strong style="font-size: 12px; color: #666;">Provides:</strong>
                                    <ul style="margin: 5px 0; padding-left: 20px; font-size: 12px;">
                                        <?php foreach ($module['provides'] as $feature): ?>
                                            <li><?php echo esc_html($feature); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <div class="module-actions" style="display: flex; gap: 10px;">
                                <?php if ($is_active): ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=yap-modules&yap_deactivate_module=' . $module_id), 'yap_deactivate_module'); ?>" class="button">
                                        Deactivate
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=yap-modules&yap_activate_module=' . $module_id), 'yap_activate_module'); ?>" class="button button-primary">
                                        Activate
                                    </a>
                                <?php endif; ?>
                                
                                <button class="button yap-delete-module-btn" data-module-id="<?php echo esc_attr($module_id); ?>" <?php echo $is_active ? 'disabled' : ''; ?>>
                                    Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="yap-card" style="margin-top: 30px; background: white; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
                <h2>📖 Module Development Guide</h2>
                
                <h3>Creating a YAP Module</h3>
                
                <p>Modules are stored in <code>wp-content/yap-modules/your-module-name/</code></p>
                
                <h4>Required Files:</h4>
                <ul>
                    <li><code>module.json</code> - Module manifest</li>
                    <li><code>module.php</code> - Main module file</li>
                </ul>
                
                <h4>Example module.json:</h4>
                <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;"><code>{
  "id": "weather-field",
  "name": "Weather Field",
  "version": "1.0.0",
  "description": "Adds a weather field type",
  "author": "Your Name",
  "author_url": "https://yoursite.com",
  "provides": [
    "Custom field type: weather",
    "Weather API integration"
  ],
  "dependencies": {},
  "update_url": "https://yoursite.com/modules/weather-field/update.json"
}</code></pre>
                
                <h4>Example module.php:</h4>
                <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;"><code>&lt;?php
function yap_module_weather_field_init() {
    // Register custom field type
    YAP_Modules::register_field_type('weather', [
        'label' => 'Weather',
        'description' => 'Display current weather',
        'render_callback' => 'weather_field_render',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
}

function weather_field_render($field, $value) {
    // Your field rendering logic
    echo '&lt;div class="weather-field"&gt;...&lt;/div&gt;';
}
</code></pre>
                
                <h3>Available APIs:</h3>
                <ul>
                    <li><code>YAP_Modules::register_field_type($id, $config)</code> - Register custom field</li>
                    <li><code>YAP_Modules::register_sanitizer($id, $callback)</code> - Register sanitizer</li>
                    <li><code>YAP_Modules::register_transformer($id, $callback)</code> - Register transformer</li>
                    <li><code>YAP_Modules::register_layout($id, $config)</code> - Register layout</li>
                </ul>
                
                <h3>Hooks:</h3>
                <ul>
                    <li><code>yap_module_loaded</code> - Fires when module is loaded</li>
                    <li><code>yap_module_activated</code> - Fires when module is activated</li>
                    <li><code>yap_module_deactivated</code> - Fires when module is deactivated</li>
                </ul>
                
                <h3>Example Modules to Build:</h3>
                <ul>
                    <li>🌤️ <strong>Weather Field</strong> - Display weather data</li>
                    <li>🗺️ <strong>Maps Field</strong> - Google Maps integration</li>
                    <li>📊 <strong>Charts Field</strong> - Data visualization with Chart.js</li>
                    <li>🎨 <strong>Color Picker</strong> - Advanced color selection</li>
                    <li>📅 <strong>Date Range</strong> - From/To date picker</li>
                    <li>💳 <strong>Payment Field</strong> - Stripe/PayPal integration</li>
                    <li>📱 <strong>QR Code</strong> - Generate QR codes</li>
                    <li>🔗 <strong>Related Posts</strong> - Post relationship picker</li>
                </ul>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Install module
            $('#yap-install-module-btn').on('click', function() {
                const url = prompt('Enter module download URL (.zip):');
                if (!url) return;
                
                $.post(ajaxurl, {
                    action: 'yap_install_module',
                    nonce: '<?php echo wp_create_nonce('yap_modules'); ?>',
                    module_url: url
                }, function(response) {
                    if (response.success) {
                        alert('Module installed! Refreshing...');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                });
            });
            
            // Delete module
            $('.yap-delete-module-btn').on('click', function() {
                if (!confirm('Delete this module? This cannot be undone.')) return;
                
                const moduleId = $(this).data('module-id');
                
                $.post(ajaxurl, {
                    action: 'yap_delete_module',
                    nonce: '<?php echo wp_create_nonce('yap_modules'); ?>',
                    module_id: moduleId
                }, function(response) {
                    if (response.success) {
                        alert('Module deleted!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                });
            });
            
            // Check updates
            $('#yap-check-updates-btn').on('click', function() {
                const $btn = $(this);
                $btn.prop('disabled', true).text('Checking...');
                
                $.post(ajaxurl, {
                    action: 'yap_check_module_updates',
                    nonce: '<?php echo wp_create_nonce('yap_modules'); ?>'
                }, function(response) {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Check for Updates');
                    
                    if (response.success) {
                        const updates = response.data.updates;
                        
                        if (Object.keys(updates).length === 0) {
                            alert('All modules are up to date!');
                        } else {
                            let message = 'Updates available:\n\n';
                            for (const [id, info] of Object.entries(updates)) {
                                message += `${id}: ${info.current} → ${info.new}\n`;
                            }
                            alert(message);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
}

// Initialize
YAP_Modules::get_instance();
