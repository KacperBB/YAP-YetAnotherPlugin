<?php

/**
 * YAP Location Rules Handler
 * Zarządzanie regułami wyświetlania grup pól w różnych lokalizacjach
 */
class YAP_Location_Rules {
    
    /**
     * Inicjalizacja AJAX handlers
     */
    public static function init() {
        add_action('wp_ajax_yap_get_taxonomy_terms', [__CLASS__, 'ajax_get_taxonomy_terms']);
    }
    
    /**
     * Dostępne typy lokalizacji
     */
    public static function get_location_types() {
        $types = [
            'post_type' => [
                'label' => 'Typ posta',
                'choices' => self::get_post_types()
            ],
            'post' => [
                'label' => 'Konkretny post',
                'choices' => 'ajax' // Będzie ładowane przez AJAX
            ],
            'page' => [
                'label' => 'Konkretna strona',
                'choices' => 'ajax'
            ],
            'page_template' => [
                'label' => 'Szablon strony',
                'choices' => self::get_page_templates()
            ],
        ];
        
        // Dodaj najpopularniejsze taksonomie ręcznie (zawsze dostępne)
        $common_taxonomies = [
            'category' => 'Kategorie',
            'post_tag' => 'Tagi',
            'product_cat' => 'Kategorie produktów (WooCommerce)',
            'product_tag' => 'Tagi produktów (WooCommerce)',
        ];
        
        foreach ($common_taxonomies as $tax_name => $tax_label) {
            if (taxonomy_exists($tax_name)) {
                $terms = self::get_taxonomy_terms($tax_name);
                $types['taxonomy_' . $tax_name] = [
                    'label' => $tax_label,
                    'choices' => !empty($terms) ? $terms : ['no_terms' => 'Brak termów - dodaj najpierw kategorie/tagi'],
                    'taxonomy' => $tax_name
                ];
            }
        }
        
        // Dodaj wszystkie pozostałe taksonomie
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        foreach ($taxonomies as $taxonomy) {
            // Pomiń jeśli już dodana
            if (isset($types['taxonomy_' . $taxonomy->name])) {
                continue;
            }
            
            $terms = self::get_taxonomy_terms($taxonomy->name);
            
            // Dodaj nawet jeśli termy są puste (użytkownik może je dodać później)
            $types['taxonomy_' . $taxonomy->name] = [
                'label' => $taxonomy->label,
                'choices' => !empty($terms) ? $terms : ['no_terms' => 'Brak termów w tej taksonomii'],
                'taxonomy' => $taxonomy->name
            ];
        }
        
        $types += [
            'user_role' => [
                'label' => 'Rola użytkownika',
                'choices' => self::get_user_roles()
            ],
            'user' => [
                'label' => 'Konkretny użytkownik',
                'choices' => 'ajax'
            ],
            'attachment' => [
                'label' => 'Załącznik',
                'choices' => ['all' => 'Wszystkie załączniki']
            ],
            'comment' => [
                'label' => 'Komentarz',
                'choices' => ['all' => 'Wszystkie komentarze']
            ],
            'widget' => [
                'label' => 'Widget',
                'choices' => ['all' => 'Wszystkie widgety']
            ],
            'nav_menu' => [
                'label' => 'Menu',
                'choices' => self::get_nav_menus()
            ],
            'options_page' => [
                'label' => 'Strona opcji',
                'choices' => self::get_options_pages()
            ]
        ];
        
        return $types;
    }
    
    /**
     * Dostępne operatory
     */
    public static function get_operators() {
        return [
            '==' => 'jest równe',
            '!=' => 'nie jest równe'
        ];
    }
    
    /**
     * Pobierz typy postów
     */
    private static function get_post_types() {
        $post_types = get_post_types(['public' => true], 'objects');
        $choices = [];
        foreach ($post_types as $post_type) {
            $choices[$post_type->name] = $post_type->label;
        }
        return $choices;
    }
    
    /**
     * Pobierz szablony stron
     */
    private static function get_page_templates() {
        $templates = wp_get_theme()->get_page_templates();
        $choices = ['default' => 'Domyślny szablon'];
        foreach ($templates as $file => $name) {
            $choices[$file] = $name;
        }
        return $choices;
    }
    
    /**
     * Pobierz taksonomie
     */
    private static function get_taxonomies() {
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        $choices = [];
        foreach ($taxonomies as $taxonomy) {
            $choices[$taxonomy->name] = $taxonomy->label;
        }
        return $choices;
    }
    
    /**
     * Pobierz kategorie postów
     */
    private static function get_categories() {
        $categories = get_categories(['hide_empty' => false]);
        $choices = [];
        foreach ($categories as $category) {
            $choices[$category->term_id] = $category->name;
        }
        return $choices;
    }
    
    /**
     * Pobierz termy dla wybranej taksonomii
     */
    public static function get_taxonomy_terms($taxonomy) {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false
        ]);
        
        if (is_wp_error($terms)) {
            return [];
        }
        
        $choices = [];
        foreach ($terms as $term) {
            $choices[$term->term_id] = $term->name;
        }
        return $choices;
    }
    
    /**
     * AJAX handler - pobierz termy dla taksonomii
     */
    public static function ajax_get_taxonomy_terms() {
        check_ajax_referer('yap_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Brak uprawnień']);
        }
        
        $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
        
        if (empty($taxonomy)) {
            wp_send_json_error(['message' => 'Nie podano taksonomii']);
        }
        
        $terms = self::get_taxonomy_terms($taxonomy);
        wp_send_json_success(['terms' => $terms]);
    }
    
    /**
     * Pobierz role użytkowników
     */
    private static function get_user_roles() {
        global $wp_roles;
        $choices = [];
        foreach ($wp_roles->roles as $key => $role) {
            $choices[$key] = $role['name'];
        }
        return $choices;
    }
    
    /**
     * Pobierz menu nawigacyjne
     */
    private static function get_nav_menus() {
        $menus = wp_get_nav_menus();
        $choices = [];
        foreach ($menus as $menu) {
            $choices[$menu->term_id] = $menu->name;
        }
        return $choices;
    }
    
    /**
     * Pobierz strony opcji (zarejestrowane w pluginie)
     */
    private static function get_options_pages() {
        global $yap_options_pages;
        
        if (!isset($yap_options_pages) || empty($yap_options_pages)) {
            return ['general' => 'Ustawienia ogólne'];
        }
        
        return $yap_options_pages;
    }
    
    /**
     * Zapisz reguły lokalizacji dla grupy
     */
    public static function save_rules($group_name, $rules) {
        global $wpdb;
        $table = $wpdb->prefix . 'yap_location_rules';
        
        // Usuń stare reguły
        $wpdb->delete($table, ['group_name' => $group_name]);
        
        // Zapisz nowe reguły
        if (empty($rules)) {
            return true;
        }
        
        foreach ($rules as $group_index => $rule_group) {
            foreach ($rule_group as $order => $rule) {
                $wpdb->insert($table, [
                    'group_name' => $group_name,
                    'location_type' => $rule['type'],
                    'location_operator' => $rule['operator'] ?? '==',
                    'location_value' => $rule['value'],
                    'rule_group' => $group_index,
                    'rule_order' => $order
                ]);
            }
        }
        
        return true;
    }
    
    /**
     * Pobierz reguły lokalizacji dla grupy
     */
    public static function get_rules($group_name) {
        global $wpdb;
        $table = $wpdb->prefix . 'yap_location_rules';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE group_name = %s ORDER BY rule_group, rule_order",
            $group_name
        ));
        
        if (empty($results)) {
            return [];
        }
        
        $rules = [];
        foreach ($results as $row) {
            $rules[$row->rule_group][] = [
                'type' => $row->location_type,
                'operator' => $row->location_operator,
                'value' => $row->location_value
            ];
        }
        
        return $rules;
    }
    
    /**
     * Sprawdź czy grupa powinna być wyświetlona w danej lokalizacji
     */
    public static function should_show_group($group_name, $context = []) {
        $rules = self::get_rules($group_name);
        
        if (empty($rules)) {
            return false; // Brak reguł = nie pokazuj
        }
        
        // Sprawdź każdą grupę reguł (OR logic między grupami)
        foreach ($rules as $rule_group) {
            $group_match = true;
            
            // Wszystkie reguły w grupie muszą być spełnione (AND logic)
            foreach ($rule_group as $rule) {
                if (!self::check_rule($rule, $context)) {
                    $group_match = false;
                    break;
                }
            }
            
            // Jeśli jedna grupa się zgadza, wyświetl
            if ($group_match) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sprawdź pojedynczą regułę
     */
    private static function check_rule($rule, $context) {
        $type = $rule['type'];
        $operator = $rule['operator'];
        $expected_value = $rule['value'];
        
        // Obsługa taxonomy_* (np. taxonomy_category, taxonomy_post_tag)
        if (strpos($type, 'taxonomy_') === 0) {
            $taxonomy = substr($type, 9); // Usuń prefix "taxonomy_"
            $post_id = $context['post_id'] ?? get_the_ID();
            
            if ($post_id && is_numeric($expected_value)) {
                $terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids']);
                if (!is_wp_error($terms) && is_array($terms)) {
                    if ($operator === '==') {
                        return in_array((int)$expected_value, $terms);
                    } elseif ($operator === '!=') {
                        return !in_array((int)$expected_value, $terms);
                    }
                }
            }
            return false;
        }
        
        $actual_value = self::get_context_value($type, $context);
        
        // Specjalna obsługa dla kategorii (actual_value to tablica)
        if ($type === 'category' && is_array($actual_value)) {
            if ($operator === '==') {
                return in_array($expected_value, $actual_value);
            } elseif ($operator === '!=') {
                return !in_array($expected_value, $actual_value);
            }
        }
        
        // Specjalna obsługa dla taxonomy - gdy wartość to taxonomy:term_id
        if ($type === 'taxonomy' && strpos($expected_value, ':') !== false) {
            list($taxonomy, $term_id) = explode(':', $expected_value, 2);
            $post_id = $context['post_id'] ?? get_the_ID();
            
            if ($post_id && is_numeric($term_id)) {
                $terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids']);
                if (!is_wp_error($terms) && is_array($terms)) {
                    if ($operator === '==') {
                        return in_array((int)$term_id, $terms);
                    } elseif ($operator === '!=') {
                        return !in_array((int)$term_id, $terms);
                    }
                }
            }
            return false;
        }
        
        // Backward compatibility: taxonomy z samym term_id (liczba)
        if ($type === 'taxonomy' && is_numeric($expected_value)) {
            $post_id = $context['post_id'] ?? get_the_ID();
            if ($post_id) {
                $term = get_term($expected_value);
                if (!is_wp_error($term) && $term) {
                    $terms = wp_get_post_terms($post_id, $term->taxonomy, ['fields' => 'ids']);
                    if (!is_wp_error($terms) && is_array($terms)) {
                        if ($operator === '==') {
                            return in_array((int)$expected_value, $terms);
                        } elseif ($operator === '!=') {
                            return !in_array((int)$expected_value, $terms);
                        }
                    }
                }
            }
            return false;
        }
        
        if ($operator === '==') {
            return $actual_value == $expected_value;
        } elseif ($operator === '!=') {
            return $actual_value != $expected_value;
        }
        
        return false;
    }
    
    /**
     * Pobierz wartość z kontekstu
     */
    private static function get_context_value($type, $context) {
        global $post, $current_user;
        
        switch ($type) {
            case 'post_type':
                return $context['post_type'] ?? get_post_type($post);
                
            case 'post':
                return $context['post_id'] ?? get_the_ID();
                
            case 'page':
                return $context['post_id'] ?? get_the_ID();
                
            case 'page_template':
                $post_id = $context['post_id'] ?? get_the_ID();
                return get_page_template_slug($post_id) ?: 'default';
                
            case 'taxonomy':
                if (isset($context['taxonomy'])) {
                    return $context['taxonomy'];
                }
                if (is_tax() || is_category() || is_tag()) {
                    $term = get_queried_object();
                    return $term->taxonomy ?? '';
                }
                return '';
                
            case 'category':
                // Sprawdź czy post ma daną kategorię
                if (isset($context['post_id'])) {
                    $post_id = $context['post_id'];
                } elseif (isset($context['post']) && is_object($context['post'])) {
                    $post_id = $context['post']->ID;
                } else {
                    $post_id = get_the_ID();
                }
                
                if ($post_id) {
                    $categories = wp_get_post_categories($post_id);
                    // Zwróć tablicę ID kategorii
                    return $categories;
                }
                return [];
                
            case 'taxonomy_term':
                if (isset($context['term_id'])) {
                    return $context['term_id'];
                }
                if (is_tax() || is_category() || is_tag()) {
                    $term = get_queried_object();
                    return $term->term_id ?? 0;
                }
                return 0;
                
            case 'user_role':
                if (isset($context['user_role'])) {
                    return $context['user_role'];
                }
                if (isset($current_user->roles) && !empty($current_user->roles)) {
                    return $current_user->roles[0];
                }
                return '';
                
            case 'user':
                return $context['user_id'] ?? get_current_user_id();
                
            case 'attachment':
                return is_attachment() ? 'all' : '';
                
            case 'comment':
                return $context['comment_id'] ?? '';
                
            case 'widget':
                return $context['widget'] ?? '';
                
            case 'nav_menu':
                return $context['menu_id'] ?? '';
                
            case 'options_page':
                return $context['options_page'] ?? '';
                
            default:
                return '';
        }
    }
    
    /**
     * Pobierz wszystkie grupy dla danej lokalizacji
     */
    public static function get_groups_for_location($context = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'yap_location_rules';
        
        // Pobierz wszystkie unikalne nazwy grup
        $group_names = $wpdb->get_col("SELECT DISTINCT group_name FROM {$table}");
        
        $matching_groups = [];
        foreach ($group_names as $group_name) {
            if (self::should_show_group($group_name, $context)) {
                $matching_groups[] = $group_name;
            }
        }
        
        return $matching_groups;
    }
}

/**
 * Options Pages Handler
 * Zarządzanie stronami opcji globalnych
 */
class YAP_Options_Pages {
    
    private static $pages = [];
    
    /**
     * Zarejestruj stronę opcji
     */
    public static function register_options_page($page_slug, $args = []) {
        $defaults = [
            'page_title' => 'Opcje',
            'menu_title' => 'Opcje',
            'menu_slug' => $page_slug,
            'capability' => 'manage_options',
            'icon' => 'dashicons-admin-settings',
            'position' => null,
            'parent' => null, // Jeśli null, to strona główna, jeśli ustawione - submenu
            'redirect' => true
        ];
        
        $args = wp_parse_args($args, $defaults);
        self::$pages[$page_slug] = $args;
        
        global $yap_options_pages;
        $yap_options_pages[$page_slug] = $args['page_title'];
        
        // Dodaj stronę do menu WordPress
        add_action('admin_menu', function() use ($page_slug, $args) {
            if ($args['parent']) {
                add_submenu_page(
                    $args['parent'],
                    $args['page_title'],
                    $args['menu_title'],
                    $args['capability'],
                    $args['menu_slug'],
                    [__CLASS__, 'render_options_page']
                );
            } else {
                add_menu_page(
                    $args['page_title'],
                    $args['menu_title'],
                    $args['capability'],
                    $args['menu_slug'],
                    [__CLASS__, 'render_options_page'],
                    $args['icon'],
                    $args['position']
                );
            }
        });
        
        return $page_slug;
    }
    
    /**
     * Renderuj stronę opcji
     */
    public static function render_options_page() {
        $page_slug = $_GET['page'] ?? '';
        
        if (!isset(self::$pages[$page_slug])) {
            echo '<div class="error"><p>Nieznana strona opcji.</p></div>';
            return;
        }
        
        $page = self::$pages[$page_slug];
        
        // Obsługa zapisu
        if (isset($_POST['yap_save_options']) && check_admin_referer('yap_options_' . $page_slug)) {
            self::save_options($page_slug, $_POST);
            echo '<div class="updated"><p>Opcje zostały zapisane.</p></div>';
        }
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($page['page_title']) . '</h1>';
        
        // Pobierz grupy dla tej strony opcji
        $groups = YAP_Location_Rules::get_groups_for_location(['options_page' => $page_slug]);
        
        if (empty($groups)) {
            echo '<p>Brak grup pól przypisanych do tej strony opcji.</p>';
            echo '<p><a href="' . admin_url('admin.php?page=yap-admin-page') . '">Utwórz grupę pól</a></p>';
        } else {
            echo '<form method="post">';
            wp_nonce_field('yap_options_' . $page_slug);
            
            foreach ($groups as $group_name) {
                self::render_group_fields($page_slug, $group_name);
            }
            
            echo '<p class="submit">';
            echo '<input type="submit" name="yap_save_options" class="button button-primary" value="Zapisz opcje">';
            echo '</p>';
            echo '</form>';
        }
        
        echo '</div>';
    }
    
    /**
     * Renderuj pola grupy
     */
    private static function render_group_fields($page_slug, $group_name) {
        global $wpdb;
        $pattern_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
        $safe_table = esc_sql($pattern_table);
        
        $fields = $wpdb->get_results("SELECT * FROM `{$safe_table}` WHERE field_depth = 0");
        
        echo '<h2>' . esc_html($group_name) . '</h2>';
        echo '<table class="form-table">';
        
        foreach ($fields as $field) {
            if (strpos($field->generated_name, 'group_meta') !== false) {
                continue;
            }
            
            $field_value = self::get_option($page_slug, $field->user_name);
            
            echo '<tr>';
            echo '<th><label for="' . esc_attr($field->generated_name) . '">' . esc_html($field->user_name) . '</label></th>';
            echo '<td>';
            
            self::render_field_input($field, $field_value);
            
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    /**
     * Renderuj pole inputu
     */
    private static function render_field_input($field, $value) {
        $name = 'yap_option[' . esc_attr($field->user_name) . ']';
        
        switch ($field->field_type) {
            case 'short_text':
                echo '<input type="text" name="' . $name . '" value="' . esc_attr($value) . '" class="regular-text">';
                break;
                
            case 'long_text':
                echo '<textarea name="' . $name . '" rows="5" class="large-text">' . esc_textarea($value) . '</textarea>';
                break;
                
            case 'number':
                echo '<input type="number" name="' . $name . '" value="' . esc_attr($value) . '" class="small-text">';
                break;
                
            case 'wysiwyg':
                wp_editor($value, sanitize_key($field->user_name), [
                    'textarea_name' => $name,
                    'textarea_rows' => 10
                ]);
                break;
                
            case 'true_false':
                echo '<label>';
                echo '<input type="checkbox" name="' . $name . '" value="1" ' . checked($value, '1', false) . '>';
                echo ' Tak</label>';
                break;
                
            case 'image':
                echo '<input type="hidden" name="' . $name . '" value="' . esc_attr($value) . '" class="yap-image-id">';
                echo '<button type="button" class="button yap-upload-image-button">Wybierz obraz</button>';
                if ($value) {
                    echo '<img src="' . wp_get_attachment_url($value) . '" style="display:block;max-width:200px;margin-top:10px;">';
                }
                break;
                
            default:
                echo '<input type="text" name="' . $name . '" value="' . esc_attr($value) . '" class="regular-text">';
        }
    }
    
    /**
     * Zapisz opcje
     */
    private static function save_options($page_slug, $post_data) {
        if (!isset($post_data['yap_option'])) {
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'yap_options';
        
        foreach ($post_data['yap_option'] as $field_name => $value) {
            $wpdb->replace($table, [
                'option_page' => $page_slug,
                'field_name' => $field_name,
                'field_value' => is_array($value) ? wp_json_encode($value) : $value
            ]);
        }
    }
    
    /**
     * Pobierz opcję
     */
    public static function get_option($page_slug, $field_name, $default = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'yap_options';
        
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT field_value FROM {$table} WHERE option_page = %s AND field_name = %s",
            $page_slug,
            $field_name
        ));
        
        return $value !== null ? $value : $default;
    }
}

/**
 * Helper Functions
 */

/**
 * Zarejestruj stronę opcji
 */
function yap_register_options_page($page_slug, $args = []) {
    return YAP_Options_Pages::register_options_page($page_slug, $args);
}

/**
 * Pobierz opcję ze strony opcji
 */
function yap_get_option($page_slug, $field_name, $default = '') {
    return YAP_Options_Pages::get_option($page_slug, $field_name, $default);
}

/**
 * Sprawdź czy grupa powinna być wyświetlona
 */
function yap_should_show_group($group_name, $context = []) {
    return YAP_Location_Rules::should_show_group($group_name, $context);
}

/**
 * Pobierz grupy dla lokalizacji
 */
function yap_get_groups_for_location($context = []) {
    return YAP_Location_Rules::get_groups_for_location($context);
}
