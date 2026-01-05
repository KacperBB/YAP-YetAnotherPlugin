<?php
/**
 * YAP Computed Fields
 * 
 * System pól wyliczanych automatycznie na podstawie innych pól lub callbacków.
 * Idealne dla: kalkulatorów, logiki biznesowej, pól zależnych, e-commerce.
 * 
 * @package YetAnotherPlugin
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Computed_Fields {
    
    private static $instance = null;
    private $computed_fields = [];
    private $dependencies = [];
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook do obliczania przy zapisie posta
        add_action('save_post', [$this, 'calculate_on_save'], 20);
        add_action('yap_field_updated', [$this, 'recalculate_dependencies'], 10, 4);
        
        // Filter dla get_field - zwraca obliczoną wartość
        add_filter('yap_get_field_value', [$this, 'get_computed_value'], 10, 3);
    }
    
    /**
     * Rejestruje computed field
     * 
     * @param string $field_name Nazwa pola do wyliczenia
     * @param callable $callback Funkcja obliczająca wartość
     * @param array $dependencies Pola od których zależy to pole
     * @param array $args Dodatkowe opcje
     */
    public function register($field_name, $callback, $dependencies = [], $args = []) {
        $defaults = [
            'cache' => true,              // Cachowanie wyniku
            'cache_ttl' => 3600,          // TTL cache (1 godzina)
            'group_name' => null,         // Grupa pól (opcjonalne)
            'recalculate_on_read' => false, // Przelicz przy każdym odczycie
            'store_in_db' => true,        // Zapisz do bazy jako normalne pole
            'format' => null,             // Format wyjściowy (number, price, date)
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $this->computed_fields[$field_name] = [
            'callback' => $callback,
            'dependencies' => $dependencies,
            'args' => $args,
        ];
        
        // Zapisz zależności dla szybkiego lookup
        foreach ($dependencies as $dep) {
            if (!isset($this->dependencies[$dep])) {
                $this->dependencies[$dep] = [];
            }
            $this->dependencies[$dep][] = $field_name;
        }
        
        error_log("YAP Computed: Registered '{$field_name}' with dependencies: " . implode(', ', $dependencies));
    }
    
    /**
     * Oblicza wartość computed field
     * 
     * @param string $field_name Nazwa pola
     * @param int $post_id ID posta
     * @param string $group_name Nazwa grupy (opcjonalne)
     * @return mixed Obliczona wartość
     */
    public function calculate($field_name, $post_id, $group_name = null) {
        if (!isset($this->computed_fields[$field_name])) {
            return null;
        }
        
        $config = $this->computed_fields[$field_name];
        
        // Sprawdź cache
        if ($config['args']['cache'] && function_exists('yap_cache_get')) {
            $cache_key = "computed_{$field_name}_{$post_id}";
            $cached = yap_cache_get($cache_key, 'computed_fields');
            
            if ($cached !== false) {
                return $cached;
            }
        }
        
        // Przygotuj kontekst dla callbacka
        $context = [
            'post_id' => $post_id,
            'group_name' => $group_name ?: $config['args']['group_name'],
            'field_name' => $field_name,
        ];
        
        // Pobierz wartości zależnych pól
        $dependency_values = [];
        foreach ($config['dependencies'] as $dep) {
            $dependency_values[$dep] = yap_get_field($dep, $post_id, $context['group_name']);
        }
        
        // Wykonaj callback
        try {
            $value = call_user_func($config['callback'], $dependency_values, $context);
            
            // Formatowanie
            if ($config['args']['format']) {
                $value = $this->format_value($value, $config['args']['format']);
            }
            
            // Cache
            if ($config['args']['cache'] && function_exists('yap_cache_set')) {
                yap_cache_set($cache_key, $value, 'computed_fields', $config['args']['cache_ttl']);
            }
            
            // Zapisz do bazy jeśli włączone
            if ($config['args']['store_in_db'] && $context['group_name']) {
                $this->store_computed_value($field_name, $value, $post_id, $context['group_name']);
            }
            
            return $value;
            
        } catch (Exception $e) {
            error_log("YAP Computed Error ({$field_name}): " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Formatuje wartość wg typu
     */
    private function format_value($value, $format) {
        switch ($format) {
            case 'number':
                return number_format($value, 2, ',', ' ');
                
            case 'price':
                return number_format($value, 2, ',', ' ') . ' zł';
                
            case 'percentage':
                return number_format($value, 2, ',', '') . '%';
                
            case 'date':
                return date('d.m.Y', is_numeric($value) ? $value : strtotime($value));
                
            case 'datetime':
                return date('d.m.Y H:i', is_numeric($value) ? $value : strtotime($value));
                
            case 'boolean':
                return $value ? 'Tak' : 'Nie';
                
            default:
                return $value;
        }
    }
    
    /**
     * Zapisuje obliczoną wartość do bazy
     */
    private function store_computed_value($field_name, $value, $post_id, $group_name) {
        global $wpdb;
        
        $data_table = $wpdb->prefix . 'yap_' . $group_name . '_data';
        
        // Sprawdź czy tabela istnieje
        if ($wpdb->get_var("SHOW TABLES LIKE '{$data_table}'") !== $data_table) {
            return false;
        }
        
        // Sprawdź czy pole już istnieje
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$data_table} WHERE post_id = %d AND user_field_name = %s",
            $post_id,
            $field_name
        ));
        
        if ($exists) {
            // Update
            $wpdb->update(
                $data_table,
                ['field_value' => $value],
                ['post_id' => $post_id, 'user_field_name' => $field_name],
                ['%s'],
                ['%d', '%s']
            );
        } else {
            // Insert
            $wpdb->insert(
                $data_table,
                [
                    'post_id' => $post_id,
                    'user_field_name' => $field_name,
                    'field_value' => $value,
                ],
                ['%d', '%s', '%s']
            );
        }
    }
    
    /**
     * Przelicza wszystkie computed fields dla posta
     */
    public function calculate_all($post_id, $group_name = null) {
        $results = [];
        
        foreach ($this->computed_fields as $field_name => $config) {
            // Skip jeśli nie pasuje grupa
            if ($group_name && $config['args']['group_name'] && $config['args']['group_name'] !== $group_name) {
                continue;
            }
            
            $value = $this->calculate($field_name, $post_id, $group_name ?: $config['args']['group_name']);
            $results[$field_name] = $value;
        }
        
        return $results;
    }
    
    /**
     * Hook: oblicz przy zapisie posta
     */
    public function calculate_on_save($post_id) {
        // Skip autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Przelicz wszystkie computed fields
        $this->calculate_all($post_id);
    }
    
    /**
     * Hook: przelicz zależne pola gdy pole się zmienia
     */
    public function recalculate_dependencies($field_name, $old_value, $new_value, $post_id) {
        // Sprawdź czy jakieś pola zależą od tego pola
        if (!isset($this->dependencies[$field_name])) {
            return;
        }
        
        // Przelicz wszystkie zależne pola
        foreach ($this->dependencies[$field_name] as $computed_field) {
            $config = $this->computed_fields[$computed_field];
            
            // Wyczyść cache
            if ($config['args']['cache'] && function_exists('yap_cache_delete')) {
                $cache_key = "computed_{$computed_field}_{$post_id}";
                yap_cache_delete($cache_key, 'computed_fields');
            }
            
            // Przelicz
            $this->calculate($computed_field, $post_id, $config['args']['group_name']);
            
            error_log("YAP Computed: Recalculated '{$computed_field}' due to '{$field_name}' change");
        }
    }
    
    /**
     * Filter: zwróć computed value przy get_field
     */
    public function get_computed_value($value, $field_name, $post_id) {
        if (!isset($this->computed_fields[$field_name])) {
            return $value;
        }
        
        $config = $this->computed_fields[$field_name];
        
        // Jeśli recalculate_on_read, przelicz za każdym razem
        if ($config['args']['recalculate_on_read']) {
            return $this->calculate($field_name, $post_id, $config['args']['group_name']);
        }
        
        // Jeśli store_in_db, zwróć wartość z bazy (już obliczona)
        if ($config['args']['store_in_db']) {
            return $value;
        }
        
        // W przeciwnym razie oblicz teraz
        return $this->calculate($field_name, $post_id, $config['args']['group_name']);
    }
    
    /**
     * Zwraca listę computed fields
     */
    public function get_all() {
        return $this->computed_fields;
    }
    
    /**
     * Usuwa computed field
     */
    public function unregister($field_name) {
        if (isset($this->computed_fields[$field_name])) {
            // Usuń zależności
            foreach ($this->computed_fields[$field_name]['dependencies'] as $dep) {
                if (isset($this->dependencies[$dep])) {
                    $this->dependencies[$dep] = array_diff($this->dependencies[$dep], [$field_name]);
                }
            }
            
            unset($this->computed_fields[$field_name]);
        }
    }
    
    // Legacy compatibility
    public function register_computed_field($field_name, $callback, $dependencies = []) {
        return $this->register($field_name, $callback, $dependencies);
    }
    
    public function compute_field_value($value, $post_id, $field) {
        if (is_array($field)) {
            $field_name = $field['name'] ?? $field['user_name'] ?? $field['generated_name'] ?? '';
            $group_name = $field['group'] ?? '';
        } else {
            $field_name = $field->name ?? $field->user_name ?? $field->generated_name ?? '';
            $group_name = $field->group ?? '';
        }
        
        if (!isset($this->computed_callbacks[$field_name])) {
            return $value;
        }
        
        // Check cache first
        $cached = $this->get_cached_value($post_id, $field_name);
        if ($cached !== false) {
            return $cached;
        }
        
        $computed = $this->computed_callbacks[$field_name];
        
        // Execute callback
        try {
            $computed_value = call_user_func($computed['callback'], $post_id, $group_name, $field);
            
            // Cache the result
            $this->cache_computed_value($post_id, $field_name, $computed_value);
            
            return $computed_value;
        } catch (Exception $e) {
            error_log('YAP Computed Field Error: ' . $e->getMessage());
            return $value;
        }
    }
    
    /**
     * Cache computed value
     */
    private function cache_computed_value($post_id, $field_name, $value) {
        $cache_key = "yap_computed_{$post_id}_{$field_name}";
        wp_cache_set($cache_key, $value, 'yap_computed', HOUR_IN_SECONDS);
    }
    
    /**
     * Get cached computed value
     */
    private function get_cached_value($post_id, $field_name) {
        $cache_key = "yap_computed_{$post_id}_{$field_name}";
        return wp_cache_get($cache_key, 'yap_computed');
    }
    
    /**
     * Clear computed cache for post
     */
    public function clear_computed_cache($post_id) {
        foreach ($this->computed_callbacks as $field_name => $computed) {
            $cache_key = "yap_computed_{$post_id}_{$field_name}";
            wp_cache_delete($cache_key, 'yap_computed');
        }
    }
    
    /**
     * Get all computed fields
     */
    public function get_computed_fields() {
        return array_keys($this->computed_callbacks);
    }
    
    /**
     * Check if field is computed
     */
    public function is_computed_field($field_name) {
        return isset($this->computed_callbacks[$field_name]);
    }
}

// Helper functions

/**
 * Register a computed field
 * 
 * @param string $field_name Field name
 * @param callable $callback Callback function
 * @param array $dependencies Dependencies
 */
function yap_register_computed_field($field_name, $callback, $dependencies = []) {
    return YAP_Computed_Fields::get_instance()->register_computed_field($field_name, $callback, $dependencies);
}

/**
 * Clear computed cache
 */
function yap_clear_computed_cache($post_id) {
    return YAP_Computed_Fields::get_instance()->clear_computed_cache($post_id);
}

/**
 * Check if field is computed
 */
function yap_is_computed_field($field_name) {
    return YAP_Computed_Fields::get_instance()->is_computed_field($field_name);
}

// Auto-clear cache on post save
add_action('save_post', function($post_id) {
    yap_clear_computed_cache($post_id);
});
