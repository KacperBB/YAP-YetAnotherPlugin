<?php
/**
 * YAP Field Transformers
 * Automatic value formatting system
 */

class YAP_Transformers {
    private static $instance = null;
    private $transformers = [];
    private $field_transformers = [];
    private $type_transformers = [];

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->register_default_transformers();
    }

    /**
     * Register global transformer
     * 
     * @param string $name Transformer name
     * @param callable $callback Transformation function
     */
    public function register_global($name, $callback) {
        if (!is_callable($callback)) {
            error_log("YAP Transformers: Invalid callback for transformer '{$name}'");
            return false;
        }

        $this->transformers[$name] = $callback;
        error_log("✅ Registered global transformer: {$name}");
        return true;
    }

    /**
     * Register transformer for specific field
     * 
     * @param string $field_name Field user_name or generated_name
     * @param callable $callback Transformation function
     */
    public function register_for_field($field_name, $callback) {
        if (!is_callable($callback)) {
            error_log("YAP Transformers: Invalid callback for field '{$field_name}'");
            return false;
        }

        $this->field_transformers[$field_name] = $callback;
        error_log("✅ Registered field transformer: {$field_name}");
        return true;
    }

    /**
     * Register transformer for field type
     * 
     * @param string $field_type Field type (text, number, email, etc.)
     * @param callable $callback Transformation function
     */
    public function register_for_type($field_type, $callback) {
        if (!is_callable($callback)) {
            error_log("YAP Transformers: Invalid callback for type '{$field_type}'");
            return false;
        }

        $this->type_transformers[$field_type] = $callback;
        error_log("✅ Registered type transformer: {$field_type}");
        return true;
    }

    /**
     * Apply transformation to value
     * Priority: field-specific > type-specific > global
     * 
     * @param mixed $value Value to transform
     * @param string $field_name Field name
     * @param string $field_type Field type
     * @param string $transformer_name Optional transformer name
     * @return mixed Transformed value
     */
    public function transform($value, $field_name = '', $field_type = '', $transformer_name = '') {
        // If specific transformer requested
        if (!empty($transformer_name) && isset($this->transformers[$transformer_name])) {
            return call_user_func($this->transformers[$transformer_name], $value);
        }

        // Priority 1: Field-specific transformer
        if (!empty($field_name) && isset($this->field_transformers[$field_name])) {
            return call_user_func($this->field_transformers[$field_name], $value);
        }

        // Priority 2: Type-specific transformer
        if (!empty($field_type) && isset($this->type_transformers[$field_type])) {
            return call_user_func($this->type_transformers[$field_type], $value);
        }

        // No transformation
        return $value;
    }

    /**
     * Apply named transformer
     * 
     * @param string $name Transformer name
     * @param mixed $value Value to transform
     * @return mixed Transformed value
     */
    public function apply($name, $value) {
        if (!isset($this->transformers[$name])) {
            error_log("YAP Transformers: Transformer '{$name}' not found");
            return $value;
        }

        return call_user_func($this->transformers[$name], $value);
    }

    /**
     * Register default transformers
     */
    private function register_default_transformers() {
        // Price formatter (Polish format)
        $this->register_global('price', function($value) {
            if (!is_numeric($value)) {
                return $value;
            }
            return number_format($value, 2, ',', ' ') . ' zł';
        });

        // Phone number formatter
        $this->register_global('phone', function($value) {
            $clean = preg_replace('/\D/', '', $value);
            if (strlen($clean) === 9) {
                return preg_replace('/(\d{3})(\d{3})(\d{3})/', '$1 $2 $3', $clean);
            }
            return $value;
        });

        // Date formatter (Polish)
        $this->register_global('date_pl', function($value) {
            if (empty($value)) return $value;
            $timestamp = is_numeric($value) ? $value : strtotime($value);
            return date('d.m.Y', $timestamp);
        });

        // DateTime formatter (Polish)
        $this->register_global('datetime_pl', function($value) {
            if (empty($value)) return $value;
            $timestamp = is_numeric($value) ? $value : strtotime($value);
            return date('d.m.Y H:i', $timestamp);
        });

        // URL slugify
        $this->register_global('slug', function($value) {
            return sanitize_title($value);
        });

        // Truncate text
        $this->register_global('excerpt', function($value, $length = 150) {
            if (strlen($value) <= $length) {
                return $value;
            }
            return substr($value, 0, $length) . '...';
        });

        // Uppercase
        $this->register_global('uppercase', function($value) {
            return mb_strtoupper($value, 'UTF-8');
        });

        // Lowercase
        $this->register_global('lowercase', function($value) {
            return mb_strtolower($value, 'UTF-8');
        });

        // Capitalize
        $this->register_global('capitalize', function($value) {
            return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
        });

        // File size formatter
        $this->register_global('filesize', function($value) {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $bytes = intval($value);
            for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                $bytes /= 1024;
            }
            return round($bytes, 2) . ' ' . $units[$i];
        });

        // Number formatting
        $this->register_global('number', function($value, $decimals = 0) {
            return number_format($value, $decimals, ',', ' ');
        });

        // Percentage
        $this->register_global('percentage', function($value) {
            return number_format($value, 2, ',', ' ') . '%';
        });

        // Strip HTML tags
        $this->register_global('strip_tags', function($value) {
            return strip_tags($value);
        });

        // URL encode
        $this->register_global('urlencode', function($value) {
            return urlencode($value);
        });

        error_log("✅ YAP Transformers: Registered 14 default transformers");
    }

    /**
     * Get all registered transformers
     * 
     * @return array
     */
    public function get_all_transformers() {
        return [
            'global' => array_keys($this->transformers),
            'field' => array_keys($this->field_transformers),
            'type' => array_keys($this->type_transformers)
        ];
    }
}

/**
 * Helper function: Register global transformer
 */
function yap_register_transformer($name, $callback) {
    return YAP_Transformers::get_instance()->register_global($name, $callback);
}

/**
 * Helper function: Register field transformer
 */
function yap_register_field_transformer($field_name, $callback) {
    return YAP_Transformers::get_instance()->register_for_field($field_name, $callback);
}

/**
 * Helper function: Register type transformer
 */
function yap_register_type_transformer($field_type, $callback) {
    return YAP_Transformers::get_instance()->register_for_type($field_type, $callback);
}

/**
 * Helper function: Transform value
 */
function yap_transform($value, $field_name = '', $field_type = '', $transformer = '') {
    return YAP_Transformers::get_instance()->transform($value, $field_name, $field_type, $transformer);
}

/**
 * Helper function: Apply named transformer
 */
function yap_apply_transformer($name, $value) {
    return YAP_Transformers::get_instance()->apply($name, $value);
}
