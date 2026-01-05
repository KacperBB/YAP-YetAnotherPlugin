<?php
/**
 * Field Renderers Loader
 * 
 * System do automatycznego załadowania odpowiednich renderers
 * na podstawie typu pola i kontekstu (builder/preview/display)
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Renderers_Loader {
    
    /**
     * Załaduj renderer dla danego pola i kontekstu
     */
    public static function get_renderer($context, $field_type, $field_config, $value = '', $extra_param = '') {
        $class_name = self::get_class_name($context, $field_type);
        
        // Jeśli klasa nie istnieje w pamięci, załaduj plik
        if (!class_exists($class_name)) {
            $file = self::get_file_path($context, $field_type);
            
            if (!file_exists($file)) {
                error_log("🚨 Field renderer file not found: {$file}");
                return null;
            }
            
            require_once $file;
        }
        
        // Sprawdź czy klasa istnieje po załadowaniu
        if (!class_exists($class_name)) {
            error_log("🚨 Field renderer class not found: {$class_name}");
            return null;
        }
        
        // Stwórz instancję renderer
        switch ($context) {
            case 'builder':
                return new $class_name($field_config, $value, '', '');
            case 'preview':
                return new $class_name($field_config, $value);
            case 'display':
                $post_id = is_numeric($extra_param) ? $extra_param : 0;
                return new $class_name($field_config, $value, $post_id);
            default:
                return null;
        }
    }
    
    /**
     * Renderuj pole w danym kontekście
     */
    public static function render($context, $field_type, $field_config, $value = '', $extra_param = '') {
        $renderer = self::get_renderer($context, $field_type, $field_config, $value, $extra_param);
        
        if ($renderer === null) {
            error_log("🚨 Failed to create renderer for type: {$field_type}");
            return;
        }
        
        // Ustaw input_name i input_id dla buildrera
        if ($context === 'builder') {
            $renderer->input_name = $field_config['name'] ?? '';
            $renderer->input_id = 'field_' . ($field_config['name'] ?? uniqid());
        }
        
        $renderer->render();
    }
    
    /**
     * Pobierz nazwę klasy na podstawie kontekstu i typu pola
     */
    private static function get_class_name($context, $field_type) {
        $type_class = ucfirst(str_replace('-', '_', $field_type));
        
        switch ($context) {
            case 'builder':
                return "YAP_Field_{$type_class}_Builder";
            case 'preview':
                return "YAP_Field_{$type_class}_Preview";
            case 'display':
                return "YAP_Field_{$type_class}_Display";
            default:
                return '';
        }
    }
    
    /**
     * Pobierz ścieżkę do pliku renderer
     */
    private static function get_file_path($context, $field_type) {
        $base_path = dirname(__FILE__) . '/field-renderers/' . $context . '/';
        $filename = strtolower(str_replace('_', '-', $field_type)) . '.php';
        return $base_path . $filename;
    }
    
    /**
     * Lista dostępnych renderers dla danego kontekstu
     */
    public static function get_available_renderers($context) {
        $path = dirname(__FILE__) . '/field-renderers/' . $context . '/';
        
        if (!is_dir($path)) {
            return [];
        }
        
        $files = scandir($path);
        $renderers = [];
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '_base.php') {
                continue;
            }
            
            if (substr($file, -4) === '.php') {
                $type = str_replace('.php', '', $file);
                $renderers[] = $type;
            }
        }
        
        return $renderers;
    }
    
    /**
     * Debug - sprawdź dostępne renderers
     */
    public static function debug_available() {
        $contexts = ['builder', 'preview', 'display'];
        $debug = [];
        
        foreach ($contexts as $context) {
            $debug[$context] = self::get_available_renderers($context);
        }
        
        return $debug;
    }
}
