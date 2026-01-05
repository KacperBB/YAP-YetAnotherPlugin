<?php

/**
 * Pobiera wartość pojedynczego pola z grupy dla danego posta
 * 
 * @param string $field_name Nazwa pola (user_name)
 * @param int $post_id ID posta
 * @param string $group_name Nazwa grupy (bez prefiksu wp_group_ i sufiksu _pattern/_data)
 * @param bool $return_url Dla pól typu image, czy zwrócić URL zamiast ID (domyślnie false)
 * @return string|null Wartość pola lub null jeśli nie znaleziono
 */
function yap_get_field($field_name, $post_id, $group_name, $return_url = false) {
    global $wpdb;
    $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
    
    $field = $wpdb->get_row($wpdb->prepare(
        "SELECT field_value, field_type FROM {$data_table} WHERE user_name = %s AND associated_id = %d",
        $field_name,
        $post_id
    ));
    
    if (!$field) {
        return null;
    }
    
    // Jeśli to pole obrazu i chcemy URL
    if ($field->field_type === 'image' && $return_url && is_numeric($field->field_value)) {
        return wp_get_attachment_url($field->field_value);
    }
    
    return $field->field_value;
}

/**
 * Aktualizuje wartość pojedynczego pola
 * 
 * @param string $field_name Nazwa pola
 * @param mixed $value Nowa wartość
 * @param int $post_id ID posta
 * @param string $group_name Nazwa grupy
 * @return bool True on success, false on failure
 */
function yap_update_field($field_name, $value, $post_id, $group_name) {
    global $wpdb;
    
    $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
    $pattern_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
    
    // Get field definition
    $field_def = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$pattern_table} WHERE user_name = %s OR generated_name = %s LIMIT 1",
        $field_name,
        $field_name
    ));
    
    if (!$field_def) {
        return false;
    }
    
    // Serialize complex values
    if (is_array($value) || is_object($value)) {
        $value = maybe_serialize($value);
    }
    
    // Check if field value exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$data_table} WHERE generated_name = %s AND associated_id = %d",
        $field_def->generated_name,
        $post_id
    ));
    
    if ($existing) {
        // Update existing
        return $wpdb->update(
            $data_table,
            [
                'field_value' => $value,
                'user_name' => $field_def->user_name,
                'field_type' => $field_def->field_type
            ],
            ['id' => $existing]
        ) !== false;
    } else {
        // Insert new
        return $wpdb->insert(
            $data_table,
            [
                'generated_name' => $field_def->generated_name,
                'user_name' => $field_def->user_name,
                'field_type' => $field_def->field_type,
                'field_value' => $value,
                'associated_id' => $post_id
            ]
        ) !== false;
    }
}

/**
 * Pobiera URL obrazu z pola typu image
 * 
 * @param string $field_name Nazwa pola
 * @param int $post_id ID posta
 * @param string $group_name Nazwa grupy
 * @param string $size Rozmiar obrazu (thumbnail, medium, large, full)
 * @return string|false URL obrazu lub false jeśli nie znaleziono
 */
function yap_get_image($field_name, $post_id, $group_name, $size = 'full') {
    $image_id = yap_get_field($field_name, $post_id, $group_name);
    
    if (!$image_id || !is_numeric($image_id)) {
        return false;
    }
    
    $image = wp_get_attachment_image_src($image_id, $size);
    return $image ? $image[0] : false;
}

/**
 * Pobiera wszystkie pola z grupy dla danego posta
 * 
 * @param int $post_id ID posta
 * @param string $group_name Nazwa grupy
 * @return array Tablica pól z kluczami: label, value, type
 */
function yap_get_all_fields($post_id, $group_name) {
    global $wpdb;
    $data_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_data';
    
    $fields = $wpdb->get_results($wpdb->prepare(
        "SELECT user_name, field_value, field_type FROM {$data_table} WHERE associated_id = %d",
        $post_id
    ));
    
    $result = [];
    foreach ($fields as $field) {
        $result[] = [
            'label' => $field->user_name,
            'value' => $field->field_value,
            'type' => $field->field_type
        ];
    }
    
    return $result;
}

/**
 * Pobiera zagnieżdżoną grupę
 * 
 * @param string $nested_field_name Nazwa pola typu nested_group
 * @param int $post_id ID posta
 * @param string $group_name Nazwa grupy głównej
 * @return array Tablica zagnieżdżonych wartości
 */
function yap_get_nested_group($nested_field_name, $post_id, $group_name) {
    global $wpdb;
    $pattern_table = $wpdb->prefix . 'group_' . sanitize_title($group_name) . '_pattern';
    
    // Znajdź pole nested_group
    $nested_field = $wpdb->get_row($wpdb->prepare(
        "SELECT nested_field_ids FROM {$pattern_table} WHERE user_name = %s AND field_type = 'nested_group'",
        $nested_field_name
    ));
    
    if (!$nested_field || empty($nested_field->nested_field_ids)) {
        return [];
    }
    
    $nested_tables = json_decode($nested_field->nested_field_ids, true);
    $result = [];
    
    foreach ($nested_tables as $nested_table) {
        $data_table = str_replace('_pattern', '_data', $nested_table);
        $fields = $wpdb->get_results($wpdb->prepare(
            "SELECT user_name, field_value FROM {$data_table} WHERE associated_id = %d",
            $post_id
        ));
        
        $item = [];
        foreach ($fields as $field) {
            $item[$field->user_name] = $field->field_value;
        }
        if (!empty($item)) {
            $result[] = $item;
        }
    }
    
    return $result;
}

/**
 * Wyświetla niestandardowe pola (opcjonalne - nie dodaje automatycznie do treści)
 * Używaj powyższych funkcji w swoim motywie zamiast tego filtra
 */
function yap_display_custom_fields($content) {
    // Ta funkcja jest obecnie wyłączona - używaj funkcji yap_get_field() w swoim motywie
    return $content;
}
// add_filter('the_content', 'yap_display_custom_fields'); // Zakomentowane - odkomentuj jeśli chcesz automatyczne wyświetlanie

/**
 * PRZYKŁADY UŻYCIA ZAAWANSOWANYCH TYPÓW PÓL:
 * 
 * === POLA WYBORU ===
 * 
 * Select:
 * $value = yap_get_select('kraj', get_the_ID(), 'dane_kontaktowe');
 * echo $value; // np. "Polska"
 * 
 * Checkbox (wielokrotny wybór):
 * $values = yap_get_checkbox('zainteresowania', get_the_ID(), 'profil_uzytkownika');
 * foreach ($values as $value) {
 *     echo $value . '<br>';
 * }
 * 
 * Radio:
 * $value = yap_get_radio('plec', get_the_ID(), 'dane_osobowe');
 * echo $value; // np. "Kobieta"
 * 
 * True/False:
 * $is_active = yap_get_true_false('aktywny', get_the_ID(), 'ustawienia');
 * if ($is_active) {
 *     echo 'Aktywny';
 * }
 * 
 * === DATA I CZAS ===
 * 
 * Date:
 * $date = yap_get_date('data_urodzenia', get_the_ID(), 'profil', 'd.m.Y');
 * echo $date; // np. "15.03.1990"
 * 
 * DateTime:
 * $datetime = yap_get_datetime('data_wydarzenia', get_the_ID(), 'wydarzenia', 'd.m.Y H:i');
 * echo $datetime; // np. "15.03.2024 18:30"
 * 
 * Time:
 * $time = yap_get_time('godzina_otwarcia', get_the_ID(), 'restauracja', 'H:i');
 * echo $time; // np. "09:00"
 * 
 * === MEDIA ===
 * 
 * File (plik):
 * $file_url = yap_get_file('broszura', get_the_ID(), 'dokumenty', true);
 * echo '<a href="' . $file_url . '">Pobierz PDF</a>';
 * 
 * // Lub z metadanymi:
 * $file = yap_get_file_array('umowa', get_the_ID(), 'dokumenty');
 * echo 'Nazwa: ' . $file['filename'];
 * echo 'Rozmiar: ' . $file['filesize'];
 * echo 'Typ: ' . $file['mime_type'];
 * 
 * Gallery (galeria):
 * $images = yap_get_gallery('zdjecia_produktu', get_the_ID(), 'produkty', true);
 * foreach ($images as $image_url) {
 *     echo '<img src="' . $image_url . '" alt="">';
 * }
 * 
 * === WYSIWYG I OEMBED ===
 * 
 * WYSIWYG:
 * $content = yap_get_wysiwyg('opis_szczegolowy', get_the_ID(), 'produkty');
 * echo wpautop($content); // Automatyczne akapity
 * 
 * oEmbed:
 * $video = yap_get_oembed('film_youtube', get_the_ID(), 'multimedia');
 * echo $video; // Osadzony odtwarzacz YouTube/Vimeo
 * 
 * === RELACJE ===
 * 
 * Post Object (pojedynczy post):
 * $related_post = yap_get_post_object('powiazany_artykul', get_the_ID(), 'artykuly');
 * if ($related_post) {
 *     echo '<a href="' . get_permalink($related_post->ID) . '">' . $related_post->post_title . '</a>';
 * }
 * 
 * Relationship (wiele postów):
 * $related_posts = yap_get_relationship('powiazane_produkty', get_the_ID(), 'produkty', true);
 * foreach ($related_posts as $post) {
 *     echo '<h3>' . $post->post_title . '</h3>';
 *     echo '<p>' . wp_trim_words($post->post_content, 20) . '</p>';
 * }
 * 
 * Taxonomy (termin taksonomii):
 * $term = yap_get_taxonomy('kategoria_produktu', get_the_ID(), 'produkty', true);
 * if ($term) {
 *     echo $term->name;
 * }
 * 
 * User (użytkownik):
 * $author = yap_get_user('dodatkowy_autor', get_the_ID(), 'artykuly');
 * if ($author) {
 *     echo 'Autor: ' . $author->display_name;
 * }
 * 
 * === ZAAWANSOWANE ===
 * 
 * Color Picker:
 * $color = yap_get_color('kolor_tla', get_the_ID(), 'ustawienia_wizualne');
 * echo '<div style="background-color: ' . $color . '">Treść</div>';
 * 
 * Range (suwak):
 * $value = yap_get_range('priorytet', get_the_ID(), 'zadania');
 * echo 'Priorytet: ' . $value . '/100';
 * 
 * Google Map:
 * $map = yap_get_google_map('lokalizacja', get_the_ID(), 'miejsca');
 * echo 'Adres: ' . $map['address'];
 * echo 'Współrzędne: ' . $map['lat'] . ', ' . $map['lng'];
 * 
 * // Lub renderuj mapę:
 * echo yap_render_google_map('lokalizacja', get_the_ID(), 'miejsca', '100%', '400px', 15);
 * 
 * === REPEATER (POWTARZALNE POLA) ===
 * 
 * Sprawdź czy są rzędy:
 * if (yap_have_rows('team_members', get_the_ID(), 'about_page')) {
 *     $rows = yap_get_repeater('team_members', get_the_ID(), 'about_page');
 *     foreach ($rows as $index => $row) {
 *         echo '<div class="team-member">';
 *         echo '<h3>' . $row['name'] . '</h3>';
 *         echo '<p>' . $row['position'] . '</p>';
 *         echo '<img src="' . $row['photo'] . '">';
 *         echo '</div>';
 *     }
 * }
 * 
 * Liczba rzędów:
 * $count = yap_count_rows('team_members', get_the_ID(), 'about_page');
 * echo "Liczba członków zespołu: {$count}";
 * 
 * Dodaj rząd (programowo):
 * yap_add_row('team_members', get_the_ID(), 'about_page', [
 *     'name' => 'Jan Kowalski',
 *     'position' => 'Developer',
 *     'photo' => 123
 * ]);
 * 
 * === FLEXIBLE CONTENT (ELASTYCZNE UKŁADY) ===
 * 
 * $layouts = yap_get_flexible_content('page_builder', get_the_ID(), 'landing_page');
 * foreach ($layouts as $layout) {
 *     switch ($layout['type']) {
 *         case 'hero_section':
 *             echo '<section class="hero">';
 *             echo '<h1>' . $layout['data']['title'] . '</h1>';
 *             echo '<p>' . $layout['data']['subtitle'] . '</p>';
 *             echo '<a href="' . $layout['data']['button_url'] . '">' . $layout['data']['button_text'] . '</a>';
 *             echo '</section>';
 *             break;
 *             
 *         case 'text_block':
 *             echo '<section class="text">';
 *             echo wpautop($layout['data']['content']);
 *             echo '</section>';
 *             break;
 *             
 *         case 'image_gallery':
 *             echo '<section class="gallery">';
 *             foreach ($layout['data']['images'] as $image_id) {
 *                 echo wp_get_attachment_image($image_id, 'large');
 *             }
 *             echo '</section>';
 *             break;
 *             
 *         case 'call_to_action':
 *             echo '<section class="cta" style="background-color: ' . $layout['data']['bg_color'] . '">';
 *             echo '<h2>' . $layout['data']['heading'] . '</h2>';
 *             echo '<a class="button" href="' . $layout['data']['link'] . '">Dowiedz się więcej</a>';
 *             echo '</section>';
 *             break;
 *     }
 * }
 * 
 * Filtrowanie po typie layoutu:
 * $hero_sections = yap_get_layouts_by_type('page_builder', get_the_ID(), 'landing_page', 'hero_section');
 * 
 * === WALIDACJA ===
 * 
 * Walidacja pola przed zapisem:
 * $rules = [
 *     'required' => true,
 *     'min_length' => 5,
 *     'max_length' => 100,
 *     'pattern' => '/^[a-zA-Z\s]+$/',
 *     'pattern_message' => 'Tylko litery i spacje są dozwolone'
 * ];
 * 
 * if (yap_validate_field($_POST['field_value'], $rules, 'Nazwa')) {
 *     // Zapisz wartość
 * } else {
 *     $errors = yap_get_validation_errors();
 *     foreach ($errors as $error) {
 *         echo '<p class="error">' . $error . '</p>';
 *     }
 * }
 * 
 * Dostępne reguły walidacji:
 * - required: Pole wymagane
 * - min_length: Minimalna długość
 * - max_length: Maksymalna długość
 * - min_value: Minimalna wartość (liczby)
 * - max_value: Maksymalna wartość (liczby)
 * - pattern: Regex pattern
 * - email: Walidacja email
 * - url: Walidacja URL
 * - date_format: Format daty (np. 'Y-m-d')
 * 
 * === LOGIKA WARUNKOWA ===
 * 
 * Sprawdź czy pole powinno być widoczne:
 * $conditions = [
 *     'logic' => 'and', // lub 'or'
 *     'rules' => [
 *         ['field' => 'show_details', 'operator' => '==', 'value' => '1'],
 *         ['field' => 'user_role', 'operator' => 'in', 'value' => ['admin', 'editor']]
 *     ]
 * ];
 * 
 * $field_values = [
 *     'show_details' => '1',
 *     'user_role' => 'admin'
 * ];
 * 
 * if (yap_check_conditional($conditions, $field_values)) {
 *     // Wyświetl pole
 *     echo yap_get_field('detailed_info', get_the_ID(), 'settings');
 * }
 * 
 * Dostępne operatory:
 * - == (równe)
 * - != (różne)
 * - > (większe)
 * - < (mniejsze)
 * - >= (większe lub równe)
 * - <= (mniejsze lub równe)
 * - contains (zawiera)
 * - not_contains (nie zawiera)
 * - starts_with (zaczyna się od)
 * - ends_with (kończy się na)
 * - empty (puste)
 * - not_empty (niepuste)
 * - in (w tablicy)
 * - not_in (nie w tablicy)
 * 
 * === STRONY OPCJI (OPTIONS PAGES) ===
 * 
 * Rejestracja strony opcji:
 * yap_register_options_page('theme_settings', [
 *     'page_title' => 'Ustawienia motywu',
 *     'menu_title' => 'Ustawienia',
 *     'icon' => 'dashicons-admin-settings',
 *     'capability' => 'manage_options'
 * ]);
 * 
 * // Submenu pod istniejącą stroną:
 * yap_register_options_page('footer_options', [
 *     'page_title' => 'Opcje stopki',
 *     'menu_title' => 'Stopka',
 *     'parent' => 'themes.php' // Pod "Wygląd"
 * ]);
 * 
 * Pobieranie opcji:
 * $logo = yap_get_option('theme_settings', 'logo');
 * $footer_text = yap_get_option('theme_settings', 'footer_text', 'Domyślny tekst');
 * $contact_email = yap_get_option('theme_settings', 'contact_email');
 * 
 * Przykład użycia w motywie:
 * // header.php
 * $logo_id = yap_get_option('theme_settings', 'site_logo');
 * if ($logo_id) {
 *     echo '<img src="' . wp_get_attachment_url($logo_id) . '" alt="Logo">';
 * }
 * 
 * // footer.php
 * $footer_columns = yap_get_option('theme_settings', 'footer_columns', '3');
 * $copyright = yap_get_option('theme_settings', 'copyright_text');
 * echo '<p>' . $copyright . '</p>';
 * 
 * === REGUŁY LOKALIZACJI ===
 * 
 * Dostępne lokalizacje dla przypisania grup:
 * 
 * 1. POST TYPE - Typ posta (post, page, custom post type)
 * 2. POST - Konkretny post (ID)
 * 3. PAGE - Konkretna strona (ID)
 * 4. PAGE TEMPLATE - Szablon strony (template-name.php)
 * 5. TAXONOMY - Taksonomia (category, post_tag, custom taxonomy)
 * 6. TAXONOMY TERM - Konkretny term (ID)
 * 7. USER ROLE - Rola użytkownika (administrator, editor, etc.)
 * 8. USER - Konkretny użytkownik (ID)
 * 9. ATTACHMENT - Załączniki/Media
 * 10. COMMENT - Komentarze
 * 11. WIDGET - Widgety
 * 12. NAV MENU - Menu nawigacyjne
 * 13. OPTIONS PAGE - Strona opcji
 * 
 * Sprawdzanie czy grupa powinna być wyświetlona:
 * if (yap_should_show_group('product_details', ['post_type' => 'product'])) {
 *     // Wyświetl pola grupy
 * }
 * 
 * Pobieranie grup dla lokalizacji:
 * $groups = yap_get_groups_for_location([
 *     'post_type' => 'page',
 *     'post_id' => get_the_ID()
 * ]);
 * 
 * foreach ($groups as $group_name) {
 *     $fields = yap_get_all_fields(get_the_ID(), $group_name);
 *     // Wyświetl pola
 * }
 * 
 * === PRZYKŁADY UŻYCIA W RÓŻNYCH KONTEKSTACH ===
 * 
 * PROFIL UŻYTKOWNIKA:
 * // Dodaj pola do profilu użytkownika
 * add_action('show_user_profile', function($user) {
 *     $groups = yap_get_groups_for_location(['user_id' => $user->ID]);
 *     foreach ($groups as $group_name) {
 *         // Renderuj pola
 *     }
 * });
 * 
 * EDYCJA TERMINU TAKSONOMII:
 * add_action('category_edit_form_fields', function($term) {
 *     $groups = yap_get_groups_for_location([
 *         'taxonomy' => 'category',
 *         'term_id' => $term->term_id
 *     ]);
 *     // Renderuj pola
 * });
 * 
 * WIDGET:
 * class My_Custom_Widget extends WP_Widget {
 *     public function form($instance) {
 *         $groups = yap_get_groups_for_location(['widget' => 'all']);
 *         // Renderuj pola
 *     }
 * }
 * 
 * KOMENTARZ:
 * add_action('comment_form_logged_in_after', function() {
 *     $groups = yap_get_groups_for_location(['comment_id' => 'all']);
 *     // Renderuj pola
 * });
 */
?>
