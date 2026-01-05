<?php
/**
 * YAP Field Hooks
 * Event system for field-level changes
 */

class YAP_Field_Hooks {
    private static $instance = null;
    private $hooks = [
        'on_update' => [],
        'on_empty' => [],
        'on_first_save' => [],
        'on_delete' => [],
        'on_change' => []
    ];

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->register_wordpress_hooks();
    }

    /**
     * Register WordPress hooks
     */
    private function register_wordpress_hooks() {
        add_action('yap_field_updated', [$this, 'trigger_update'], 10, 4);
        add_action('yap_field_saved', [$this, 'check_first_save'], 10, 3);
    }

    /**
     * Register hook for field update
     * 
     * @param string $field_name Field user_name or generated_name
     * @param callable $callback Function to call
     */
    public function on_update($field_name, $callback) {
        if (!is_callable($callback)) {
            error_log("YAP Field Hooks: Invalid callback for field '{$field_name}'");
            return false;
        }

        if (!isset($this->hooks['on_update'][$field_name])) {
            $this->hooks['on_update'][$field_name] = [];
        }

        $this->hooks['on_update'][$field_name][] = $callback;
        error_log("✅ Registered on_update hook for field: {$field_name}");
        return true;
    }

    /**
     * Register hook for when field becomes empty
     * 
     * @param string $field_name Field name
     * @param callable $callback Function to call
     */
    public function on_empty($field_name, $callback) {
        if (!is_callable($callback)) {
            error_log("YAP Field Hooks: Invalid callback for field '{$field_name}'");
            return false;
        }

        if (!isset($this->hooks['on_empty'][$field_name])) {
            $this->hooks['on_empty'][$field_name] = [];
        }

        $this->hooks['on_empty'][$field_name][] = $callback;
        error_log("✅ Registered on_empty hook for field: {$field_name}");
        return true;
    }

    /**
     * Register hook for first save
     * 
     * @param string $field_name Field name
     * @param callable $callback Function to call
     */
    public function on_first_save($field_name, $callback) {
        if (!is_callable($callback)) {
            error_log("YAP Field Hooks: Invalid callback for field '{$field_name}'");
            return false;
        }

        if (!isset($this->hooks['on_first_save'][$field_name])) {
            $this->hooks['on_first_save'][$field_name] = [];
        }

        $this->hooks['on_first_save'][$field_name][] = $callback;
        error_log("✅ Registered on_first_save hook for field: {$field_name}");
        return true;
    }

    /**
     * Register hook for field deletion
     * 
     * @param string $field_name Field name
     * @param callable $callback Function to call
     */
    public function on_delete($field_name, $callback) {
        if (!is_callable($callback)) {
            error_log("YAP Field Hooks: Invalid callback for field '{$field_name}'");
            return false;
        }

        if (!isset($this->hooks['on_delete'][$field_name])) {
            $this->hooks['on_delete'][$field_name] = [];
        }

        $this->hooks['on_delete'][$field_name][] = $callback;
        error_log("✅ Registered on_delete hook for field: {$field_name}");
        return true;
    }

    /**
     * Register hook for any field change
     * 
     * @param string $field_name Field name
     * @param callable $callback Function to call
     */
    public function on_change($field_name, $callback) {
        if (!is_callable($callback)) {
            error_log("YAP Field Hooks: Invalid callback for field '{$field_name}'");
            return false;
        }

        if (!isset($this->hooks['on_change'][$field_name])) {
            $this->hooks['on_change'][$field_name] = [];
        }

        $this->hooks['on_change'][$field_name][] = $callback;
        error_log("✅ Registered on_change hook for field: {$field_name}");
        return true;
    }

    /**
     * Trigger update hooks
     */
    public function trigger_update($field_name, $old_value, $new_value, $post_id) {
        // Trigger on_update hooks
        if (isset($this->hooks['on_update'][$field_name])) {
            foreach ($this->hooks['on_update'][$field_name] as $callback) {
                call_user_func($callback, $old_value, $new_value, $post_id);
            }
        }

        // Trigger on_change hooks if value actually changed
        if ($old_value !== $new_value && isset($this->hooks['on_change'][$field_name])) {
            foreach ($this->hooks['on_change'][$field_name] as $callback) {
                call_user_func($callback, $old_value, $new_value, $post_id);
            }
        }

        // Trigger on_empty hooks if new value is empty
        if (empty($new_value) && !empty($old_value) && isset($this->hooks['on_empty'][$field_name])) {
            foreach ($this->hooks['on_empty'][$field_name] as $callback) {
                call_user_func($callback, $old_value, $post_id);
            }
        }
    }

    /**
     * Check if this is first save
     */
    public function check_first_save($field_name, $value, $post_id) {
        global $wpdb;

        // Check if field existed before
        $data_table = $wpdb->prefix . 'group_' . sanitize_title($field_name) . '_data';
        $safe_table = esc_sql($data_table);
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM `{$safe_table}` WHERE user_name = %s AND associated_id = %d",
            $field_name,
            $post_id
        ));

        if ($exists == 0 && isset($this->hooks['on_first_save'][$field_name])) {
            foreach ($this->hooks['on_first_save'][$field_name] as $callback) {
                call_user_func($callback, $value, $post_id);
            }
        }
    }

    /**
     * Trigger delete hooks
     */
    public function trigger_delete($field_name, $value, $post_id) {
        if (isset($this->hooks['on_delete'][$field_name])) {
            foreach ($this->hooks['on_delete'][$field_name] as $callback) {
                call_user_func($callback, $value, $post_id);
            }
        }
    }

    /**
     * Get all registered hooks
     */
    public function get_all_hooks() {
        return $this->hooks;
    }

    /**
     * Get hooks for specific field
     */
    public function get_field_hooks($field_name) {
        $result = [];
        foreach ($this->hooks as $event => $fields) {
            if (isset($fields[$field_name])) {
                $result[$event] = count($fields[$field_name]);
            }
        }
        return $result;
    }
}

/**
 * Helper functions
 */
function yap_on_field_update($field_name, $callback) {
    return YAP_Field_Hooks::get_instance()->on_update($field_name, $callback);
}

function yap_on_field_empty($field_name, $callback) {
    return YAP_Field_Hooks::get_instance()->on_empty($field_name, $callback);
}

function yap_on_field_first_save($field_name, $callback) {
    return YAP_Field_Hooks::get_instance()->on_first_save($field_name, $callback);
}

function yap_on_field_delete($field_name, $callback) {
    return YAP_Field_Hooks::get_instance()->on_delete($field_name, $callback);
}

function yap_on_field_change($field_name, $callback) {
    return YAP_Field_Hooks::get_instance()->on_change($field_name, $callback);
}

/**
 * Get field hooks info
 */
function yap_get_field_hooks($field_name = null) {
    $hooks = YAP_Field_Hooks::get_instance();
    return $field_name ? $hooks->get_field_hooks($field_name) : $hooks->get_all_hooks();
}
