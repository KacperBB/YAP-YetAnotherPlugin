<?php
/**
 * YAP Webhooks System
 * 
 * System webhooków i eventów dla integracji z systemami zewnętrznymi:
 * - Zapier, Make.com, n8n
 * - CRM (HubSpot, Salesforce)
 * - Headless frontends
 * - Custom API integrations
 * 
 * @package YetAnotherPlugin
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Webhooks {
    
    private static $instance = null;
    private $webhooks_table;
    private $logs_table;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        
        $this->webhooks_table = $wpdb->prefix . 'yap_webhooks';
        $this->logs_table = $wpdb->prefix . 'yap_webhook_logs';
        
        $this->create_tables();
        
        // Hook do field changes
        add_action('yap_field_updated', [$this, 'trigger_field_webhooks'], 10, 4);
        add_action('yap_group_saved', [$this, 'trigger_group_webhooks'], 10, 3);
        
        // REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    /**
     * Tworzy tabele dla webhooków
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabela webhooków
        $sql_webhooks = "CREATE TABLE IF NOT EXISTS {$this->webhooks_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            url varchar(500) NOT NULL,
            event varchar(100) NOT NULL,
            group_name varchar(255) DEFAULT NULL,
            field_name varchar(255) DEFAULT NULL,
            method varchar(10) DEFAULT 'POST',
            headers longtext,
            active tinyint(1) DEFAULT 1,
            secret_key varchar(255) DEFAULT NULL,
            retry_count int(11) DEFAULT 3,
            timeout int(11) DEFAULT 30,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event (event),
            KEY group_name (group_name),
            KEY active (active)
        ) $charset_collate;";
        
        // Tabela logów
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$this->logs_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            webhook_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL,
            status_code int(11) DEFAULT NULL,
            request_payload longtext,
            response_body longtext,
            error_message text,
            execution_time float DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY webhook_id (webhook_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_webhooks);
        dbDelta($sql_logs);
    }
    
    /**
     * Rejestruje webhook
     * 
     * @param array $args Parametry webhooka
     * @return int|false ID webhooka lub false
     */
    public function register_webhook($args) {
        global $wpdb;
        
        $defaults = [
            'name' => '',
            'url' => '',
            'event' => 'field_updated',
            'group_name' => null,
            'field_name' => null,
            'method' => 'POST',
            'headers' => [],
            'active' => 1,
            'secret_key' => wp_generate_password(32, false),
            'retry_count' => 3,
            'timeout' => 30,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Walidacja
        if (empty($args['url']) || !filter_var($args['url'], FILTER_VALIDATE_URL)) {
            error_log('YAP Webhooks: Invalid URL');
            return false;
        }
        
        // Konwertuj headers na JSON
        if (is_array($args['headers'])) {
            $args['headers'] = json_encode($args['headers']);
        }
        
        $result = $wpdb->insert(
            $this->webhooks_table,
            [
                'name' => $args['name'],
                'url' => $args['url'],
                'event' => $args['event'],
                'group_name' => $args['group_name'],
                'field_name' => $args['field_name'],
                'method' => $args['method'],
                'headers' => $args['headers'],
                'active' => $args['active'],
                'secret_key' => $args['secret_key'],
                'retry_count' => $args['retry_count'],
                'timeout' => $args['timeout'],
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Trigger webhooks dla field update
     */
    public function trigger_field_webhooks($field_name, $old_value, $new_value, $post_id) {
        global $wpdb;
        
        // Znajdź webhooks dla tego pola lub wszystkich pól
        $webhooks = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->webhooks_table} 
            WHERE active = 1 
            AND event = 'field_updated'
            AND (field_name = %s OR field_name IS NULL)",
            $field_name
        ));
        
        $payload = [
            'event' => 'field_updated',
            'field' => $field_name,
            'post_id' => $post_id,
            'old_value' => $old_value,
            'new_value' => $new_value,
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
        ];
        
        foreach ($webhooks as $webhook) {
            $this->send_webhook($webhook, $payload);
        }
    }
    
    /**
     * Trigger webhooks dla group save
     */
    public function trigger_group_webhooks($group_name, $post_id, $fields) {
        global $wpdb;
        
        $webhooks = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->webhooks_table} 
            WHERE active = 1 
            AND event = 'group_saved'
            AND (group_name = %s OR group_name IS NULL)",
            $group_name
        ));
        
        $payload = [
            'event' => 'group_saved',
            'group' => $group_name,
            'post_id' => $post_id,
            'fields' => $fields,
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
        ];
        
        foreach ($webhooks as $webhook) {
            $this->send_webhook($webhook, $payload);
        }
    }
    
    /**
     * Wysyła webhook
     */
    private function send_webhook($webhook, $payload, $attempt = 1) {
        $start_time = microtime(true);
        
        // Przygotuj headers
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'YAP-Webhooks/1.0',
            'X-YAP-Event' => $webhook->event,
            'X-YAP-Signature' => $this->generate_signature($payload, $webhook->secret_key),
        ];
        
        // Dodaj custom headers
        if (!empty($webhook->headers)) {
            $custom_headers = json_decode($webhook->headers, true);
            if (is_array($custom_headers)) {
                $headers = array_merge($headers, $custom_headers);
            }
        }
        
        // Wyślij request
        $response = wp_remote_request($webhook->url, [
            'method' => $webhook->method,
            'headers' => $headers,
            'body' => json_encode($payload),
            'timeout' => $webhook->timeout,
            'blocking' => true,
        ]);
        
        $execution_time = microtime(true) - $start_time;
        
        // Loguj wynik
        $log_data = [
            'webhook_id' => $webhook->id,
            'request_payload' => json_encode($payload),
            'execution_time' => $execution_time,
        ];
        
        if (is_wp_error($response)) {
            $log_data['status'] = 'error';
            $log_data['error_message'] = $response->get_error_message();
            
            // Retry jeśli dozwolone
            if ($attempt < $webhook->retry_count) {
                sleep(pow(2, $attempt)); // Exponential backoff
                return $this->send_webhook($webhook, $payload, $attempt + 1);
            }
            
        } else {
            $status_code = wp_remote_retrieve_response_code($response);
            $log_data['status_code'] = $status_code;
            $log_data['response_body'] = wp_remote_retrieve_body($response);
            
            if ($status_code >= 200 && $status_code < 300) {
                $log_data['status'] = 'success';
            } else {
                $log_data['status'] = 'failed';
                
                // Retry dla 5xx errors
                if ($status_code >= 500 && $attempt < $webhook->retry_count) {
                    sleep(pow(2, $attempt));
                    return $this->send_webhook($webhook, $payload, $attempt + 1);
                }
            }
        }
        
        // Zapisz log
        $this->log_webhook($log_data);
        
        return $log_data['status'] === 'success';
    }
    
    /**
     * Generuje sygnaturę dla weryfikacji
     */
    private function generate_signature($payload, $secret_key) {
        return hash_hmac('sha256', json_encode($payload), $secret_key);
    }
    
    /**
     * Loguje webhook execution
     */
    private function log_webhook($data) {
        global $wpdb;
        
        $wpdb->insert(
            $this->logs_table,
            $data,
            ['%d', '%s', '%s', '%d', '%s', '%s', '%f']
        );
        
        // Czyść stare logi (starsze niż 30 dni)
        $wpdb->query(
            "DELETE FROM {$this->logs_table} 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
    }
    
    /**
     * Pobiera logi dla webhooka
     */
    public function get_logs($webhook_id, $limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->logs_table} 
            WHERE webhook_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d",
            $webhook_id,
            $limit
        ));
    }
    
    /**
     * Rejestruje REST API endpoints
     */
    public function register_rest_routes() {
        // Endpoint do odbierania webhooków (incoming)
        register_rest_route('yap/v1', '/webhook/(?P<key>[a-zA-Z0-9]+)', [
            'methods' => ['POST', 'GET'],
            'callback' => [$this, 'handle_incoming_webhook'],
            'permission_callback' => '__return_true',
        ]);
        
        // Endpoint do testowania webhooka
        register_rest_route('yap/v1', '/webhooks/(?P<id>\d+)/test', [
            'methods' => 'POST',
            'callback' => [$this, 'test_webhook'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
        
        // Lista webhooków
        register_rest_route('yap/v1', '/webhooks', [
            'methods' => 'GET',
            'callback' => [$this, 'list_webhooks'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }
    
    /**
     * Obsługuje incoming webhook
     */
    public function handle_incoming_webhook($request) {
        $key = $request->get_param('key');
        $body = $request->get_json_params();
        
        // Waliduj klucz
        $webhook_key = get_option('yap_incoming_webhook_key');
        
        if ($key !== $webhook_key) {
            return new WP_Error('invalid_key', 'Invalid webhook key', ['status' => 403]);
        }
        
        // Trigger action dla custom handling
        do_action('yap_incoming_webhook', $body, $request);
        
        return [
            'success' => true,
            'message' => 'Webhook received',
            'timestamp' => current_time('mysql'),
        ];
    }
    
    /**
     * Testuje webhook
     */
    public function test_webhook($request) {
        global $wpdb;
        
        $webhook_id = $request->get_param('id');
        
        $webhook = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->webhooks_table} WHERE id = %d",
            $webhook_id
        ));
        
        if (!$webhook) {
            return new WP_Error('not_found', 'Webhook not found', ['status' => 404]);
        }
        
        $test_payload = [
            'event' => 'test',
            'message' => 'This is a test webhook from YAP',
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
        ];
        
        $success = $this->send_webhook($webhook, $test_payload);
        
        return [
            'success' => $success,
            'message' => $success ? 'Test webhook sent successfully' : 'Test webhook failed',
        ];
    }
    
    /**
     * Lista webhooków (REST API)
     */
    public function list_webhooks($request) {
        global $wpdb;
        
        $webhooks = $wpdb->get_results(
            "SELECT * FROM {$this->webhooks_table} ORDER BY created_at DESC"
        );
        
        return $webhooks;
    }
    
    /**
     * Usuwa webhook
     */
    public function delete_webhook($webhook_id) {
        global $wpdb;
        
        // Usuń logi
        $wpdb->delete(
            $this->logs_table,
            ['webhook_id' => $webhook_id],
            ['%d']
        );
        
        // Usuń webhook
        $wpdb->delete(
            $this->webhooks_table,
            ['id' => $webhook_id],
            ['%d']
        );
        
        return true;
    }
    
    /**
     * Aktualizuje webhook
     */
    public function update_webhook($webhook_id, $args) {
        global $wpdb;
        
        if (isset($args['headers']) && is_array($args['headers'])) {
            $args['headers'] = json_encode($args['headers']);
        }
        
        $wpdb->update(
            $this->webhooks_table,
            $args,
            ['id' => $webhook_id],
            null,
            ['%d']
        );
        
        return true;
    }
}

// Helper functions
function yap_register_webhook($args) {
    return YAP_Webhooks::get_instance()->register_webhook($args);
}

function yap_trigger_webhook($event, $payload) {
    do_action('yap_webhook_' . $event, $payload);
}

function yap_get_webhook_logs($webhook_id, $limit = 50) {
    return YAP_Webhooks::get_instance()->get_logs($webhook_id, $limit);
}

// Przykładowe użycie:
/*
// Rejestracja webhooka dla Zapier
yap_register_webhook([
    'name' => 'Zapier - New Hero Title',
    'url' => 'https://hooks.zapier.com/hooks/catch/xxxxx/yyyyy/',
    'event' => 'field_updated',
    'field_name' => 'hero_title',
    'headers' => [
        'X-Custom-Header' => 'value'
    ]
]);

// Webhook dla Make.com na całą grupę
yap_register_webhook([
    'name' => 'Make.com - Products Updated',
    'url' => 'https://hook.eu1.make.com/xxxxx',
    'event' => 'group_saved',
    'group_name' => 'products',
]);

// Custom webhook trigger
add_action('woocommerce_order_status_completed', function($order_id) {
    $order = wc_get_order($order_id);
    
    yap_trigger_webhook('order_completed', [
        'order_id' => $order_id,
        'total' => $order->get_total(),
        'customer_email' => $order->get_billing_email(),
    ]);
});
*/
