<?php
/**
 * Przykłady użycia YAP w różnych lokalizacjach
 */

// ============================================
// 1. PROFIL UŻYTKOWNIKA
// ============================================

/**
 * Dodaj pola YAP do profilu użytkownika
 */
add_action('show_user_profile', 'yap_render_user_profile_fields');
add_action('edit_user_profile', 'yap_render_user_profile_fields');

function yap_render_user_profile_fields($user) {
    // Pobierz grupy przypisane do profilu użytkownika
    $groups = yap_get_groups_for_location([
        'user_id' => $user->ID,
        'user_role' => !empty($user->roles) ? $user->roles[0] : ''
    ]);
    
    if (empty($groups)) {
        return;
    }
    
    echo '<h2>Dodatkowe informacje</h2>';
    echo '<table class="form-table">';
    
    foreach ($groups as $group_name) {
        $fields = yap_get_all_fields($user->ID, $group_name);
        
        foreach ($fields as $field) {
            echo '<tr>';
            echo '<th><label>' . esc_html($field['label']) . '</label></th>';
            echo '<td>';
            echo '<input type="text" name="yap_user_field[' . esc_attr($field['label']) . ']" ';
            echo 'value="' . esc_attr($field['value']) . '" class="regular-text">';
            echo '</td>';
            echo '</tr>';
        }
    }
    
    echo '</table>';
}

/**
 * Zapisz pola użytkownika
 */
add_action('personal_options_update', 'yap_save_user_profile_fields');
add_action('edit_user_profile_update', 'yap_save_user_profile_fields');

function yap_save_user_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }
    
    if (isset($_POST['yap_user_field'])) {
        foreach ($_POST['yap_user_field'] as $field_name => $value) {
            update_user_meta($user_id, 'yap_' . $field_name, sanitize_text_field($value));
        }
    }
}

// ============================================
// 2. EDYCJA TERMINU TAKSONOMII (KATEGORIA)
// ============================================

/**
 * Dodaj pola do edycji kategorii
 */
add_action('category_edit_form_fields', 'yap_render_taxonomy_fields', 10, 2);
add_action('post_tag_edit_form_fields', 'yap_render_taxonomy_fields', 10, 2);

function yap_render_taxonomy_fields($term, $taxonomy) {
    $groups = yap_get_groups_for_location([
        'taxonomy' => $taxonomy,
        'term_id' => $term->term_id
    ]);
    
    if (empty($groups)) {
        return;
    }
    
    echo '<tr class="form-field">';
    echo '<th colspan="2"><h2>Dodatkowe pola</h2></th>';
    echo '</tr>';
    
    foreach ($groups as $group_name) {
        $fields = yap_get_all_fields($term->term_id, $group_name);
        
        foreach ($fields as $field) {
            echo '<tr class="form-field">';
            echo '<th scope="row"><label>' . esc_html($field['label']) . '</label></th>';
            echo '<td>';
            
            switch ($field['type']) {
                case 'long_text':
                    echo '<textarea name="yap_term_field[' . esc_attr($field['label']) . ']" ';
                    echo 'rows="5" class="large-text">' . esc_textarea($field['value']) . '</textarea>';
                    break;
                    
                case 'image':
                    echo '<input type="hidden" name="yap_term_field[' . esc_attr($field['label']) . ']" ';
                    echo 'value="' . esc_attr($field['value']) . '" class="yap-image-id">';
                    echo '<button type="button" class="button yap-upload-image">Wybierz obraz</button>';
                    if ($field['value']) {
                        echo '<img src="' . wp_get_attachment_url($field['value']) . '" ';
                        echo 'style="display:block;max-width:150px;margin-top:10px;">';
                    }
                    break;
                    
                default:
                    echo '<input type="text" name="yap_term_field[' . esc_attr($field['label']) . ']" ';
                    echo 'value="' . esc_attr($field['value']) . '" class="regular-text">';
            }
            
            echo '</td>';
            echo '</tr>';
        }
    }
}

/**
 * Zapisz pola taksonomii
 */
add_action('edited_category', 'yap_save_taxonomy_fields', 10, 2);
add_action('edited_post_tag', 'yap_save_taxonomy_fields', 10, 2);

function yap_save_taxonomy_fields($term_id, $tt_id) {
    if (isset($_POST['yap_term_field'])) {
        foreach ($_POST['yap_term_field'] as $field_name => $value) {
            update_term_meta($term_id, 'yap_' . $field_name, sanitize_text_field($value));
        }
    }
}

// ============================================
// 3. WIDGET
// ============================================

/**
 * Custom widget z polami YAP
 */
class YAP_Custom_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'yap_custom_widget',
            'Widget z polami YAP',
            ['description' => 'Widget z dodatkowymi polami']
        );
    }
    
    public function form($instance) {
        $groups = yap_get_groups_for_location(['widget' => 'all']);
        
        if (empty($groups)) {
            echo '<p>Brak grup pól przypisanych do widgetów.</p>';
            return;
        }
        
        foreach ($groups as $group_name) {
            $pattern_table = $GLOBALS['wpdb']->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
            $fields = $GLOBALS['wpdb']->get_results("SELECT * FROM {$pattern_table}");
            
            foreach ($fields as $field) {
                $value = isset($instance[$field->user_name]) ? $instance[$field->user_name] : '';
                
                echo '<p>';
                echo '<label for="' . $this->get_field_id($field->user_name) . '">';
                echo esc_html($field->user_name) . ':</label>';
                echo '<input class="widefat" type="text" ';
                echo 'id="' . $this->get_field_id($field->user_name) . '" ';
                echo 'name="' . $this->get_field_name($field->user_name) . '" ';
                echo 'value="' . esc_attr($value) . '">';
                echo '</p>';
            }
        }
    }
    
    public function update($new_instance, $old_instance) {
        return $new_instance;
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        foreach ($instance as $key => $value) {
            echo '<p>' . esc_html($key) . ': ' . esc_html($value) . '</p>';
        }
        
        echo $args['after_widget'];
    }
}

// Rejestruj widget
add_action('widgets_init', function() {
    register_widget('YAP_Custom_Widget');
});

// ============================================
// 4. KOMENTARZE
// ============================================

/**
 * Dodaj pola do formularza komentarza
 */
add_action('comment_form_logged_in_after', 'yap_render_comment_fields');
add_action('comment_form_after_fields', 'yap_render_comment_fields');

function yap_render_comment_fields() {
    $groups = yap_get_groups_for_location(['comment_id' => 'all']);
    
    if (empty($groups)) {
        return;
    }
    
    foreach ($groups as $group_name) {
        $pattern_table = $GLOBALS['wpdb']->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
        $fields = $GLOBALS['wpdb']->get_results("SELECT * FROM {$pattern_table}");
        
        foreach ($fields as $field) {
            echo '<p class="comment-form-' . esc_attr($field->generated_name) . '">';
            echo '<label for="' . esc_attr($field->generated_name) . '">' . esc_html($field->user_name) . '</label>';
            echo '<input type="text" name="yap_comment_field[' . esc_attr($field->user_name) . ']" ';
            echo 'id="' . esc_attr($field->generated_name) . '">';
            echo '</p>';
        }
    }
}

/**
 * Zapisz pola komentarza
 */
add_action('comment_post', 'yap_save_comment_fields');

function yap_save_comment_fields($comment_id) {
    if (isset($_POST['yap_comment_field'])) {
        foreach ($_POST['yap_comment_field'] as $field_name => $value) {
            add_comment_meta($comment_id, 'yap_' . $field_name, sanitize_text_field($value));
        }
    }
}

// ============================================
// 5. MENU NAWIGACYJNE
// ============================================

/**
 * Dodaj pola do elementów menu
 */
add_action('wp_nav_menu_item_custom_fields', 'yap_render_menu_item_fields', 10, 4);

function yap_render_menu_item_fields($item_id, $item, $depth, $args) {
    $groups = yap_get_groups_for_location(['nav_menu' => $args->menu]);
    
    if (empty($groups)) {
        return;
    }
    
    foreach ($groups as $group_name) {
        $pattern_table = $GLOBALS['wpdb']->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
        $fields = $GLOBALS['wpdb']->get_results("SELECT * FROM {$pattern_table}");
        
        foreach ($fields as $field) {
            $value = get_post_meta($item_id, 'yap_' . $field->user_name, true);
            
            echo '<p class="description description-wide">';
            echo '<label>' . esc_html($field->user_name) . '<br>';
            echo '<input type="text" name="yap_menu_field[' . $item_id . '][' . esc_attr($field->user_name) . ']" ';
            echo 'value="' . esc_attr($value) . '" class="widefat">';
            echo '</label>';
            echo '</p>';
        }
    }
}

/**
 * Zapisz pola menu
 */
add_action('wp_update_nav_menu_item', 'yap_save_menu_item_fields', 10, 2);

function yap_save_menu_item_fields($menu_id, $menu_item_db_id) {
    if (isset($_POST['yap_menu_field'][$menu_item_db_id])) {
        foreach ($_POST['yap_menu_field'][$menu_item_db_id] as $field_name => $value) {
            update_post_meta($menu_item_db_id, 'yap_' . $field_name, sanitize_text_field($value));
        }
    }
}

// ============================================
// 6. ZAŁĄCZNIKI (MEDIA)
// ============================================

/**
 * Dodaj pola do załączników
 */
add_filter('attachment_fields_to_edit', 'yap_render_attachment_fields', 10, 2);

function yap_render_attachment_fields($form_fields, $post) {
    $groups = yap_get_groups_for_location(['attachment' => 'all']);
    
    if (empty($groups)) {
        return $form_fields;
    }
    
    foreach ($groups as $group_name) {
        $fields = yap_get_all_fields($post->ID, $group_name);
        
        foreach ($fields as $field) {
            $form_fields['yap_' . $field['label']] = [
                'label' => $field['label'],
                'input' => 'text',
                'value' => $field['value']
            ];
        }
    }
    
    return $form_fields;
}

/**
 * Zapisz pola załącznika
 */
add_filter('attachment_fields_to_save', 'yap_save_attachment_fields', 10, 2);

function yap_save_attachment_fields($post, $attachment) {
    foreach ($attachment as $key => $value) {
        if (strpos($key, 'yap_') === 0) {
            update_post_meta($post['ID'], $key, sanitize_text_field($value));
        }
    }
    
    return $post;
}

// ============================================
// 7. SZABLON STRONY (PAGE TEMPLATE)
// ============================================

/**
 * W szablonie strony możesz sprawdzić grupy:
 */

// W pliku template-landing.php
/*
<?php
// Template Name: Landing Page

$groups = yap_get_groups_for_location([
    'page_template' => 'template-landing.php',
    'post_id' => get_the_ID()
]);

foreach ($groups as $group_name) {
    $hero_title = yap_get_field('hero_title', get_the_ID(), $group_name);
    $hero_subtitle = yap_get_field('hero_subtitle', get_the_ID(), $group_name);
    $cta_button = yap_get_field('cta_button_text', get_the_ID(), $group_name);
    
    echo '<section class="hero">';
    echo '<h1>' . esc_html($hero_title) . '</h1>';
    echo '<p>' . esc_html($hero_subtitle) . '</p>';
    echo '<button>' . esc_html($cta_button) . '</button>';
    echo '</section>';
}
?>
*/
