<?php
/**
 * YAP Theme.json Integration
 * Integration with WordPress theme.json for colors, spacing, typography
 */

class YAP_Theme_JSON {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_filter('yap/block/supports', [$this, 'add_theme_supports'], 10, 2);
        add_action('after_setup_theme', [$this, 'register_block_patterns']);
        add_action('enqueue_block_assets', [$this, 'enqueue_theme_styles']);
    }
    
    /**
     * Add theme.json supports to YAP blocks
     */
    public function add_theme_supports($supports, $block_config) {
        $theme_json = $this->get_theme_json();
        
        if (empty($theme_json)) {
            return $supports;
        }
        
        // Add color support
        if (!empty($theme_json['settings']['color'])) {
            $supports['color'] = [
                'background' => true,
                'text' => true,
                'gradients' => !empty($theme_json['settings']['color']['gradients']),
                'duotone' => !empty($theme_json['settings']['color']['duotone'])
            ];
        }
        
        // Add spacing support
        if (!empty($theme_json['settings']['spacing'])) {
            $supports['spacing'] = [
                'margin' => true,
                'padding' => true,
                'blockGap' => !empty($theme_json['settings']['spacing']['blockGap'])
            ];
        }
        
        // Add typography support
        if (!empty($theme_json['settings']['typography'])) {
            $supports['typography'] = [
                'fontSize' => true,
                'lineHeight' => !empty($theme_json['settings']['typography']['lineHeight']),
                'fontWeight' => !empty($theme_json['settings']['typography']['fontWeight']),
                'fontStyle' => !empty($theme_json['settings']['typography']['fontStyle']),
                'textTransform' => !empty($theme_json['settings']['typography']['textTransform']),
                'letterSpacing' => !empty($theme_json['settings']['typography']['letterSpacing'])
            ];
        }
        
        return $supports;
    }
    
    /**
     * Get theme.json data
     */
    private function get_theme_json() {
        $theme_json_path = get_stylesheet_directory() . '/theme.json';
        
        if (!file_exists($theme_json_path)) {
            $theme_json_path = get_template_directory() . '/theme.json';
        }
        
        if (!file_exists($theme_json_path)) {
            return null;
        }
        
        $json_content = file_get_contents($theme_json_path);
        return json_decode($json_content, true);
    }
    
    /**
     * Get theme color palette
     */
    public function get_color_palette() {
        $theme_json = $this->get_theme_json();
        
        if (empty($theme_json['settings']['color']['palette'])) {
            return [];
        }
        
        return $theme_json['settings']['color']['palette'];
    }
    
    /**
     * Get theme font sizes
     */
    public function get_font_sizes() {
        $theme_json = $this->get_theme_json();
        
        if (empty($theme_json['settings']['typography']['fontSizes'])) {
            return [];
        }
        
        return $theme_json['settings']['typography']['fontSizes'];
    }
    
    /**
     * Get theme spacing scale
     */
    public function get_spacing_scale() {
        $theme_json = $this->get_theme_json();
        
        if (empty($theme_json['settings']['spacing']['spacingSizes'])) {
            return [];
        }
        
        return $theme_json['settings']['spacing']['spacingSizes'];
    }
    
    /**
     * Register block patterns for YAP blocks
     */
    public function register_block_patterns() {
        // Check if block patterns are supported
        if (!function_exists('register_block_pattern_category')) {
            return;
        }
        
        // Register YAP patterns category
        register_block_pattern_category('yap-patterns', [
            'label' => __('YAP Patterns', 'yap')
        ]);
        
        // Allow themes to register patterns
        do_action('yap/register_block_patterns');
    }
    
    /**
     * Enqueue theme styles for blocks
     */
    public function enqueue_theme_styles() {
        $theme_json = $this->get_theme_json();
        
        if (empty($theme_json)) {
            return;
        }
        
        // Generate CSS from theme.json
        $css = $this->generate_theme_css($theme_json);
        
        if (!empty($css)) {
            wp_add_inline_style('yap-blocks-style', $css);
        }
    }
    
    /**
     * Generate CSS from theme.json
     */
    private function generate_theme_css($theme_json) {
        $css = '';
        
        // Color palette CSS variables
        if (!empty($theme_json['settings']['color']['palette'])) {
            $css .= ':root {';
            foreach ($theme_json['settings']['color']['palette'] as $color) {
                $slug = sanitize_title($color['slug']);
                $css .= "--wp--preset--color--{$slug}: {$color['color']};";
            }
            $css .= '}';
        }
        
        // Font sizes CSS variables
        if (!empty($theme_json['settings']['typography']['fontSizes'])) {
            $css .= ':root {';
            foreach ($theme_json['settings']['typography']['fontSizes'] as $size) {
                $slug = sanitize_title($size['slug']);
                $css .= "--wp--preset--font-size--{$slug}: {$size['size']};";
            }
            $css .= '}';
        }
        
        // Spacing scale CSS variables
        if (!empty($theme_json['settings']['spacing']['spacingSizes'])) {
            $css .= ':root {';
            foreach ($theme_json['settings']['spacing']['spacingSizes'] as $spacing) {
                $slug = sanitize_title($spacing['slug']);
                $css .= "--wp--preset--spacing--{$slug}: {$spacing['size']};";
            }
            $css .= '}';
        }
        
        return $css;
    }
    
    /**
     * Get block style variations from theme.json
     */
    public function get_block_style_variations($block_name) {
        $theme_json = $this->get_theme_json();
        
        if (empty($theme_json['styles']['blocks'][$block_name]['variations'])) {
            return [];
        }
        
        return $theme_json['styles']['blocks'][$block_name]['variations'];
    }
    
    /**
     * Apply theme.json settings to block config
     */
    public function apply_theme_settings($block_config) {
        $theme_json = $this->get_theme_json();
        
        if (empty($theme_json)) {
            return $block_config;
        }
        
        // Add color palette to color fields
        if (!empty($block_config['fields'])) {
            foreach ($block_config['fields'] as &$field) {
                if ($field['type'] === 'color') {
                    $field['options']['palette'] = $this->get_color_palette();
                }
            }
        }
        
        return $block_config;
    }
}

// Helper functions
function yap_get_theme_colors() {
    return YAP_Theme_JSON::get_instance()->get_color_palette();
}

function yap_get_theme_font_sizes() {
    return YAP_Theme_JSON::get_instance()->get_font_sizes();
}

function yap_get_theme_spacing() {
    return YAP_Theme_JSON::get_instance()->get_spacing_scale();
}

/**
 * Register a block pattern
 * 
 * Example:
 * yap_register_block_pattern('yap/hero-section', [
 *     'title' => 'Hero Section',
 *     'description' => 'A hero section with image and text',
 *     'content' => '<!-- wp:yap/hero --><!-- /wp:yap/hero -->',
 *     'categories' => ['yap-patterns']
 * ]);
 */
function yap_register_block_pattern($pattern_name, $pattern_properties) {
    if (!function_exists('register_block_pattern')) {
        return false;
    }
    
    return register_block_pattern($pattern_name, $pattern_properties);
}

/**
 * Unregister a block pattern
 */
function yap_unregister_block_pattern($pattern_name) {
    if (!function_exists('unregister_block_pattern')) {
        return false;
    }
    
    return unregister_block_pattern($pattern_name);
}

/**
 * Get CSS variable value from theme.json
 * 
 * Example:
 * $primary_color = yap_get_theme_var('color', 'primary');
 * $large_font = yap_get_theme_var('font-size', 'large');
 * $spacing_medium = yap_get_theme_var('spacing', 'medium');
 */
function yap_get_theme_var($type, $slug) {
    return "var(--wp--preset--{$type}--{$slug})";
}

/**
 * Generate theme.json for YAP blocks
 * This allows themes to style YAP blocks via theme.json
 */
function yap_generate_theme_json_config() {
    $blocks = yap_get_registered_blocks();
    $config = [
        'version' => 2,
        'settings' => [
            'blocks' => []
        ],
        'styles' => [
            'blocks' => []
        ]
    ];
    
    foreach ($blocks as $block_name => $block_config) {
        $block_settings = [
            'color' => true,
            'spacing' => true,
            'typography' => true
        ];
        
        $config['settings']['blocks'][$block_name] = $block_settings;
        
        // Add default styles
        $config['styles']['blocks'][$block_name] = [
            'spacing' => [
                'padding' => '1em',
                'margin' => '0'
            ]
        ];
    }
    
    return $config;
}
