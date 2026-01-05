<?php
/**
 * YAP Field Sanitizers
 * Data cleaning system before database save
 */

class YAP_Sanitizers {
    private static $instance = null;
    private $sanitizers = [];
    private $field_sanitizers = [];
    private $type_sanitizers = [];

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->register_default_sanitizers();
        $this->register_hooks();
    }

    /**
     * Register hooks to sanitize on save
     */
    private function register_hooks() {
        add_filter('yap_before_save_field', [$this, 'auto_sanitize'], 10, 3);
    }

    /**
     * Register global sanitizer
     * 
     * @param string $name Sanitizer name
     * @param callable $callback Sanitization function
     */
    public function register_global($name, $callback) {
        if (!is_callable($callback)) {
            error_log("YAP Sanitizers: Invalid callback for sanitizer '{$name}'");
            return false;
        }

        $this->sanitizers[$name] = $callback;
        error_log("✅ Registered global sanitizer: {$name}");
        return true;
    }

    /**
     * Register sanitizer for specific field
     * 
     * @param string $field_name Field user_name or generated_name
     * @param callable $callback Sanitization function
     */
    public function register_for_field($field_name, $callback) {
        if (!is_callable($callback)) {
            error_log("YAP Sanitizers: Invalid callback for field '{$field_name}'");
            return false;
        }

        $this->field_sanitizers[$field_name] = $callback;
        error_log("✅ Registered field sanitizer: {$field_name}");
        return true;
    }

    /**
     * Register sanitizer for field type
     * 
     * @param string $field_type Field type
     * @param callable $callback Sanitization function
     */
    public function register_for_type($field_type, $callback) {
        if (!is_callable($callback)) {
            error_log("YAP Sanitizers: Invalid callback for type '{$field_type}'");
            return false;
        }

        $this->type_sanitizers[$field_type] = $callback;
        error_log("✅ Registered type sanitizer: {$field_type}");
        return true;
    }

    /**
     * Apply sanitization to value
     * Priority: field-specific > type-specific > global
     * 
     * @param mixed $value Value to sanitize
     * @param string $field_name Field name
     * @param string $field_type Field type
     * @return mixed Sanitized value
     */
    public function sanitize($value, $field_name = '', $field_type = '') {
        // Priority 1: Field-specific sanitizer
        if (!empty($field_name) && isset($this->field_sanitizers[$field_name])) {
            return call_user_func($this->field_sanitizers[$field_name], $value);
        }

        // Priority 2: Type-specific sanitizer
        if (!empty($field_type) && isset($this->type_sanitizers[$field_type])) {
            return call_user_func($this->type_sanitizers[$field_type], $value);
        }

        // Default: WordPress sanitize_text_field
        return sanitize_text_field($value);
    }

    /**
     * Apply named sanitizer
     * 
     * @param string $name Sanitizer name
     * @param mixed $value Value to sanitize
     * @return mixed Sanitized value
     */
    public function apply($name, $value) {
        if (!isset($this->sanitizers[$name])) {
            error_log("YAP Sanitizers: Sanitizer '{$name}' not found");
            return sanitize_text_field($value);
        }

        return call_user_func($this->sanitizers[$name], $value);
    }

    /**
     * Auto-sanitize hook callback
     */
    public function auto_sanitize($value, $field_name, $field_type) {
        return $this->sanitize($value, $field_name, $field_type);
    }

    /**
     * Register default sanitizers
     */
    private function register_default_sanitizers() {
        // Phone number (digits only)
        $this->register_global('phone', function($value) {
            return preg_replace('/\D/', '', $value);
        });

        // Email
        $this->register_global('email', function($value) {
            return sanitize_email($value);
        });

        // URL
        $this->register_global('url', function($value) {
            return esc_url_raw($value);
        });

        // Slug
        $this->register_global('slug', function($value) {
            return sanitize_title($value);
        });

        // Integer
        $this->register_global('int', function($value) {
            return intval($value);
        });

        // Float
        $this->register_global('float', function($value) {
            return floatval($value);
        });

        // Boolean
        $this->register_global('bool', function($value) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        });

        // HTML (safe)
        $this->register_global('html', function($value) {
            return wp_kses_post($value);
        });

        // Text (no HTML)
        $this->register_global('text', function($value) {
            return sanitize_text_field($value);
        });

        // Textarea
        $this->register_global('textarea', function($value) {
            return sanitize_textarea_field($value);
        });

        // Alphanumeric only
        $this->register_global('alnum', function($value) {
            return preg_replace('/[^a-zA-Z0-9]/', '', $value);
        });

        // Lowercase alphanumeric
        $this->register_global('alnum_lower', function($value) {
            return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $value));
        });

        // Hex color
        $this->register_global('color', function($value) {
            return sanitize_hex_color($value);
        });

        // File name (safe)
        $this->register_global('filename', function($value) {
            return sanitize_file_name($value);
        });

        // Key (for array keys, option names)
        $this->register_global('key', function($value) {
            return sanitize_key($value);
        });

        // Price (2 decimals)
        $this->register_global('price', function($value) {
            return number_format(floatval($value), 2, '.', '');
        });

        // Trim whitespace
        $this->register_global('trim', function($value) {
            return trim($value);
        });

        // Strip all tags
        $this->register_global('strip_tags', function($value) {
            return strip_tags($value);
        });

        // SQL-safe string
        $this->register_global('sql', function($value) {
            global $wpdb;
            return esc_sql($value);
        });

        // JSON decode/encode (validate JSON)
        $this->register_global('json', function($value) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return '';
            }
            return json_encode($decoded);
        });

        error_log("✅ YAP Sanitizers: Registered 20 default sanitizers");
    }

    /**
     * Get all registered sanitizers
     * 
     * @return array
     */
    public function get_all_sanitizers() {
        return [
            'global' => array_keys($this->sanitizers),
            'field' => array_keys($this->field_sanitizers),
            'type' => array_keys($this->type_sanitizers)
        ];
    }
}

/**
 * Helper function: Register global sanitizer
 */
function yap_register_sanitizer($name, $callback) {
    return YAP_Sanitizers::get_instance()->register_global($name, $callback);
}

/**
 * Helper function: Register field sanitizer
 */
function yap_register_field_sanitizer($field_name, $callback) {
    return YAP_Sanitizers::get_instance()->register_for_field($field_name, $callback);
}

/**
 * Helper function: Register type sanitizer
 */
function yap_register_type_sanitizer($field_type, $callback) {
    return YAP_Sanitizers::get_instance()->register_for_type($field_type, $callback);
}

/**
 * Helper function: Sanitize value
 */
function yap_sanitize($value, $field_name = '', $field_type = '') {
    return YAP_Sanitizers::get_instance()->sanitize($value, $field_name, $field_type);
}

/**
 * Helper function: Apply named sanitizer
 */
function yap_apply_sanitizer($name, $value) {
    return YAP_Sanitizers::get_instance()->apply($name, $value);
}
