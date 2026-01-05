<?php
/**
 * YAP Dynamic API Fields
 * 
 * Najbardziej zaawansowana funkcja w pluginie!
 * Pola dynamiczne pobierające dane z zewnętrznych API w czasie rzeczywistym.
 * 
 * Features:
 * - GET/POST requests
 * - Authentication (Bearer, Basic, API Key, OAuth)
 * - Data mapping do select/radio/checkbox
 * - Response caching
 * - Retry logic
 * - Transformacje danych
 * - Webhooks dla aktualizacji
 * 
 * @package YetAnotherPlugin
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_API_Fields {
    
    private static $instance = null;
    private $api_fields = [];
    private $api_table;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        
        $this->api_table = $wpdb->prefix . 'yap_api_fields';
        
        $this->create_table();
        
        // AJAX endpoints dla admin
        add_action('wp_ajax_yap_test_api_field', [$this, 'test_api_connection']);
        add_action('wp_ajax_yap_refresh_api_data', [$this, 'refresh_api_data']);
        
        // Cron dla auto-refresh
        add_action('yap_refresh_api_fields', [$this, 'refresh_all_api_fields']);
        
        if (!wp_next_scheduled('yap_refresh_api_fields')) {
            wp_schedule_event(time(), 'hourly', 'yap_refresh_api_fields');
        }
    }
    
    /**
     * Tworzy tabelę dla konfiguracji API fields
     */
    private function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->api_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            field_name varchar(255) NOT NULL UNIQUE,
            group_name varchar(255) NOT NULL,
            api_url varchar(500) NOT NULL,
            method varchar(10) DEFAULT 'GET',
            auth_type varchar(50) DEFAULT 'none',
            auth_credentials longtext,
            headers longtext,
            request_body longtext,
            response_path varchar(255) DEFAULT '',
            value_key varchar(100) DEFAULT 'id',
            label_key varchar(100) DEFAULT 'name',
            cache_duration int(11) DEFAULT 3600,
            retry_count int(11) DEFAULT 3,
            timeout int(11) DEFAULT 30,
            transform_callback varchar(255) DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            last_sync datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY group_name (group_name),
            KEY active (active)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Rejestruje API field
     * 
     * @param array $args Konfiguracja API field
     * @return int|false ID pola lub false
     */
    public function register_api_field($args) {
        global $wpdb;
        
        $defaults = [
            'field_name' => '',
            'group_name' => '',
            'api_url' => '',
            'method' => 'GET',
            'auth_type' => 'none',          // none, bearer, basic, api_key, oauth
            'auth_credentials' => [],        // Credentials w zależności od typu
            'headers' => [],
            'request_body' => [],
            'response_path' => '',           // JSONPath do danych, np. "data.users"
            'value_key' => 'id',             // Klucz wartości w obiekcie
            'label_key' => 'name',           // Klucz etykiety
            'cache_duration' => 3600,        // Czas cache w sekundach
            'retry_count' => 3,
            'timeout' => 30,
            'transform_callback' => null,    // Funkcja transformująca dane
            'active' => 1,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Walidacja
        if (empty($args['field_name']) || empty($args['api_url'])) {
            error_log('YAP API Fields: field_name and api_url are required');
            return false;
        }
        
        if (!filter_var($args['api_url'], FILTER_VALIDATE_URL)) {
            error_log('YAP API Fields: Invalid API URL');
            return false;
        }
        
        // Konwertuj arrays na JSON
        $args['auth_credentials'] = is_array($args['auth_credentials']) ? json_encode($args['auth_credentials']) : $args['auth_credentials'];
        $args['headers'] = is_array($args['headers']) ? json_encode($args['headers']) : $args['headers'];
        $args['request_body'] = is_array($args['request_body']) ? json_encode($args['request_body']) : $args['request_body'];
        
        $result = $wpdb->insert(
            $this->api_table,
            $args,
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d']
        );
        
        if ($result) {
            // Pierwsze pobranie danych
            $this->fetch_api_data($args['field_name']);
            
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Pobiera dane z API
     * 
     * @param string $field_name Nazwa pola
     * @param bool $force Wymuś pobranie (ignoruj cache)
     * @return array|false Dane z API lub false
     */
    public function fetch_api_data($field_name, $force = false) {
        global $wpdb;
        
        // Pobierz konfigurację
        $config = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->api_table} WHERE field_name = %s AND active = 1",
            $field_name
        ), ARRAY_A);
        
        if (!$config) {
            error_log("YAP API Fields: Configuration not found for '{$field_name}'");
            return false;
        }
        
        // Sprawdź cache
        if (!$force && function_exists('yap_cache_get')) {
            $cache_key = "api_field_{$field_name}";
            $cached = yap_cache_get($cache_key, 'api_fields');
            
            if ($cached !== false) {
                return $cached;
            }
        }
        
        // Przygotuj request
        $headers = $this->prepare_headers($config);
        $body = $config['method'] === 'POST' ? $this->prepare_body($config) : null;
        
        // Wykonaj request z retry
        $response = $this->make_request(
            $config['api_url'],
            $config['method'],
            $headers,
            $body,
            $config['timeout'],
            $config['retry_count']
        );
        
        if (is_wp_error($response)) {
            error_log("YAP API Fields Error ({$field_name}): " . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code < 200 || $status_code >= 300) {
            error_log("YAP API Fields Error ({$field_name}): HTTP {$status_code}");
            return false;
        }
        
        // Parse response
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("YAP API Fields Error ({$field_name}): Invalid JSON response");
            return false;
        }
        
        // Extract data z JSONPath
        if (!empty($config['response_path'])) {
            $data = $this->extract_from_path($data, $config['response_path']);
        }
        
        // Transform callback
        if (!empty($config['transform_callback']) && is_callable($config['transform_callback'])) {
            $data = call_user_func($config['transform_callback'], $data);
        }
        
        // Map do value/label format
        $mapped_data = $this->map_data($data, $config['value_key'], $config['label_key']);
        
        // Cache
        if (function_exists('yap_cache_set')) {
            yap_cache_set("api_field_{$field_name}", $mapped_data, 'api_fields', $config['cache_duration']);
        }
        
        // Update last_sync
        $wpdb->update(
            $this->api_table,
            ['last_sync' => current_time('mysql')],
            ['field_name' => $field_name],
            ['%s'],
            ['%s']
        );
        
        return $mapped_data;
    }
    
    /**
     * Przygotowuje headers z autentykacją
     */
    private function prepare_headers($config) {
        $headers = [];
        
        // Custom headers
        if (!empty($config['headers'])) {
            $custom_headers = json_decode($config['headers'], true);
            if (is_array($custom_headers)) {
                $headers = $custom_headers;
            }
        }
        
        // Authentication
        $auth_creds = json_decode($config['auth_credentials'], true);
        
        switch ($config['auth_type']) {
            case 'bearer':
                $headers['Authorization'] = 'Bearer ' . $auth_creds['token'];
                break;
                
            case 'basic':
                $headers['Authorization'] = 'Basic ' . base64_encode($auth_creds['username'] . ':' . $auth_creds['password']);
                break;
                
            case 'api_key':
                $key = $auth_creds['key'];
                $value = $auth_creds['value'];
                $headers[$key] = $value;
                break;
                
            case 'oauth':
                // TODO: OAuth flow implementation
                break;
        }
        
        $headers['Content-Type'] = 'application/json';
        $headers['User-Agent'] = 'YAP-API-Fields/1.0';
        
        return $headers;
    }
    
    /**
     * Przygotowuje body dla POST request
     */
    private function prepare_body($config) {
        if (empty($config['request_body'])) {
            return null;
        }
        
        $body = json_decode($config['request_body'], true);
        
        return is_array($body) ? json_encode($body) : $config['request_body'];
    }
    
    /**
     * Wykonuje HTTP request z retry logic
     */
    private function make_request($url, $method, $headers, $body, $timeout, $retry_count, $attempt = 1) {
        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => $timeout,
            'blocking' => true,
        ];
        
        if ($body && $method === 'POST') {
            $args['body'] = $body;
        }
        
        $response = wp_remote_request($url, $args);
        
        // Retry na error
        if (is_wp_error($response) && $attempt < $retry_count) {
            sleep(pow(2, $attempt)); // Exponential backoff
            return $this->make_request($url, $method, $headers, $body, $timeout, $retry_count, $attempt + 1);
        }
        
        // Retry na 5xx errors
        if (!is_wp_error($response)) {
            $status_code = wp_remote_retrieve_response_code($response);
            
            if ($status_code >= 500 && $attempt < $retry_count) {
                sleep(pow(2, $attempt));
                return $this->make_request($url, $method, $headers, $body, $timeout, $retry_count, $attempt + 1);
            }
        }
        
        return $response;
    }
    
    /**
     * Ekstraktuje dane z JSONPath (simplified)
     */
    private function extract_from_path($data, $path) {
        $keys = explode('.', $path);
        
        foreach ($keys as $key) {
            if (is_array($data) && isset($data[$key])) {
                $data = $data[$key];
            } else {
                return [];
            }
        }
        
        return $data;
    }
    
    /**
     * Mapuje dane do formatu value/label
     */
    private function map_data($data, $value_key, $label_key) {
        if (!is_array($data)) {
            return [];
        }
        
        $mapped = [];
        
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }
            
            $value = isset($item[$value_key]) ? $item[$value_key] : '';
            $label = isset($item[$label_key]) ? $item[$label_key] : $value;
            
            $mapped[] = [
                'value' => $value,
                'label' => $label,
                'data' => $item, // Zapisz cały obiekt dla advanced usage
            ];
        }
        
        return $mapped;
    }
    
    /**
     * Pobiera opcje dla pola (używane w admin)
     */
    public function get_field_options($field_name) {
        $data = $this->fetch_api_data($field_name);
        
        if (!$data) {
            return [];
        }
        
        $options = [];
        
        foreach ($data as $item) {
            $options[$item['value']] = $item['label'];
        }
        
        return $options;
    }
    
    /**
     * Odświeża wszystkie API fields (cron)
     */
    public function refresh_all_api_fields() {
        global $wpdb;
        
        $fields = $wpdb->get_results(
            "SELECT field_name FROM {$this->api_table} WHERE active = 1"
        );
        
        foreach ($fields as $field) {
            $this->fetch_api_data($field->field_name, true);
            error_log("YAP API Fields: Refreshed '{$field->field_name}'");
        }
    }
    
    /**
     * AJAX: Test API connection
     */
    public function test_api_connection() {
        check_ajax_referer('yap_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $field_name = sanitize_text_field($_POST['field_name']);
        
        $data = $this->fetch_api_data($field_name, true);
        
        if ($data === false) {
            wp_send_json_error([
                'message' => 'API connection failed. Check logs for details.'
            ]);
        }
        
        wp_send_json_success([
            'message' => 'API connection successful!',
            'items_count' => count($data),
            'sample' => array_slice($data, 0, 5),
        ]);
    }
    
    /**
     * AJAX: Refresh API data
     */
    public function refresh_api_data() {
        check_ajax_referer('yap_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $field_name = sanitize_text_field($_POST['field_name']);
        
        // Wyczyść cache
        if (function_exists('yap_cache_delete')) {
            yap_cache_delete("api_field_{$field_name}", 'api_fields');
        }
        
        $data = $this->fetch_api_data($field_name, true);
        
        if ($data === false) {
            wp_send_json_error(['message' => 'Refresh failed']);
        }
        
        wp_send_json_success([
            'message' => 'Data refreshed successfully',
            'items_count' => count($data),
        ]);
    }
    
    /**
     * Webhook handler dla auto-update
     */
    public function handle_api_webhook($field_name, $data) {
        // Invalidate cache
        if (function_exists('yap_cache_delete')) {
            yap_cache_delete("api_field_{$field_name}", 'api_fields');
        }
        
        // Trigger refresh
        $this->fetch_api_data($field_name, true);
        
        do_action('yap_api_field_updated', $field_name, $data);
    }
}

// Helper functions
function yap_register_api_field($args) {
    return YAP_API_Fields::get_instance()->register_api_field($args);
}

function yap_get_api_field_options($field_name) {
    return YAP_API_Fields::get_instance()->get_field_options($field_name);
}

function yap_refresh_api_field($field_name) {
    return YAP_API_Fields::get_instance()->fetch_api_data($field_name, true);
}

// Przykładowe użycie:
/*
// 1. Pobieranie użytkowników z API (Bearer auth)
yap_register_api_field([
    'field_name' => 'external_users',
    'group_name' => 'integration',
    'api_url' => 'https://api.example.com/users',
    'method' => 'GET',
    'auth_type' => 'bearer',
    'auth_credentials' => [
        'token' => 'your-secret-token-here'
    ],
    'response_path' => 'data.users',
    'value_key' => 'id',
    'label_key' => 'full_name',
    'cache_duration' => 1800, // 30 minut
]);

// 2. API z Basic Auth
yap_register_api_field([
    'field_name' => 'crm_companies',
    'group_name' => 'crm',
    'api_url' => 'https://crm.example.com/api/companies',
    'auth_type' => 'basic',
    'auth_credentials' => [
        'username' => 'api_user',
        'password' => 'api_password'
    ],
    'value_key' => 'company_id',
    'label_key' => 'company_name',
]);

// 3. API Key w header
yap_register_api_field([
    'field_name' => 'products_from_api',
    'group_name' => 'products',
    'api_url' => 'https://api.shop.com/products',
    'auth_type' => 'api_key',
    'auth_credentials' => [
        'key' => 'X-API-Key',
        'value' => 'your-api-key-here'
    ],
    'response_path' => 'products',
    'value_key' => 'sku',
    'label_key' => 'name',
    'cache_duration' => 3600,
]);

// 4. POST request z body
yap_register_api_field([
    'field_name' => 'search_results',
    'group_name' => 'search',
    'api_url' => 'https://api.search.com/query',
    'method' => 'POST',
    'request_body' => [
        'query' => 'wordpress plugins',
        'limit' => 50,
    ],
    'auth_type' => 'bearer',
    'auth_credentials' => ['token' => 'token123'],
    'response_path' => 'results',
    'value_key' => 'id',
    'label_key' => 'title',
]);

// 5. Custom transform callback
yap_register_api_field([
    'field_name' => 'currencies',
    'group_name' => 'finance',
    'api_url' => 'https://api.nbp.pl/api/exchangerates/tables/A?format=json',
    'transform_callback' => function($data) {
        // NBP API returns nested structure
        $rates = $data[0]['rates'];
        
        $transformed = [];
        foreach ($rates as $rate) {
            $transformed[] = [
                'code' => $rate['code'],
                'currency' => $rate['currency'],
                'mid' => $rate['mid'],
            ];
        }
        
        return $transformed;
    },
    'value_key' => 'code',
    'label_key' => 'currency',
    'cache_duration' => 86400, // 24 godziny
]);

// 6. Użycie w szablonie
$users = yap_get_api_field_options('external_users');

echo '<select name="user_id">';
foreach ($users as $value => $label) {
    echo '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
}
echo '</select>';

// 7. Webhook dla auto-update (gdy API powiadomi o zmianach)
add_action('yap_incoming_webhook', function($data, $request) {
    if (isset($data['event']) && $data['event'] === 'users_updated') {
        yap_refresh_api_field('external_users');
    }
}, 10, 2);

// 8. Manual refresh z kodu
yap_refresh_api_field('crm_companies'); // Force refresh now
*/
