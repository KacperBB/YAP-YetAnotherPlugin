<?php
/**
 * Przykłady użycia nowych funkcji YAP
 * Rejestracja pól w PHP, JSON, REST API, Hooki
 */

// ============================================
// 1. REJESTRACJA POLA W PHP (bez klikania w panelu)
// ============================================

/**
 * Programistyczna rejestracja grupy pól
 * Dodaj ten kod do functions.php swojego motywu
 */
add_action('init', 'register_my_product_fields');

function register_my_product_fields() {
    yap_register_field_group([
        'group_name' => 'product_details',
        'title' => 'Szczegóły produktu',
        'fields' => [
            [
                'name' => 'price',
                'label' => 'Cena',
                'type' => 'number',
                'options' => [
                    'min' => 0,
                    'step' => 0.01,
                    'placeholder' => '0.00'
                ],
                'validation' => [
                    'required' => true,
                    'min_value' => 0
                ]
            ],
            [
                'name' => 'sku',
                'label' => 'SKU',
                'type' => 'short_text',
                'validation' => [
                    'required' => true,
                    'pattern' => '^[A-Z0-9-]+$'
                ]
            ],
            [
                'name' => 'stock',
                'label' => 'Stan magazynowy',
                'type' => 'number',
                'options' => [
                    'min' => 0,
                    'default_value' => 0
                ]
            ],
            [
                'name' => 'product_gallery',
                'label' => 'Galeria produktu',
                'type' => 'gallery'
            ],
            [
                'name' => 'features',
                'label' => 'Cechy produktu',
                'type' => 'repeater',
                'is_repeater' => true,
                'repeater_min' => 1,
                'repeater_max' => 10,
                'sub_fields' => [
                    [
                        'name' => 'feature_name',
                        'label' => 'Nazwa cechy',
                        'type' => 'short_text'
                    ],
                    [
                        'name' => 'feature_value',
                        'label' => 'Wartość',
                        'type' => 'short_text'
                    ]
                ]
            ]
        ],
        'location' => [
            [
                ['type' => 'post_type', 'operator' => '==', 'value' => 'product']
            ]
        ]
    ]);
}

/**
 * Rejestracja strony opcji w PHP
 */
add_action('init', 'register_theme_options_page');

function register_theme_options_page() {
    yap_register_options_page([
        'page_slug' => 'theme-settings',
        'page_title' => 'Ustawienia motywu',
        'menu_title' => 'Motyw',
        'capability' => 'manage_options',
        'icon' => 'dashicons-admin-appearance',
        'position' => 60
    ]);
    
    // Rejestruj pola dla tej strony
    yap_register_field_group([
        'group_name' => 'theme_options',
        'title' => 'Opcje motywu',
        'fields' => [
            [
                'name' => 'logo',
                'label' => 'Logo',
                'type' => 'image'
            ],
            [
                'name' => 'primary_color',
                'label' => 'Kolor główny',
                'type' => 'color',
                'options' => [
                    'default_value' => '#0073aa'
                ]
            ],
            [
                'name' => 'footer_text',
                'label' => 'Tekst w stopce',
                'type' => 'long_text'
            ]
        ],
        'location' => [
            [
                ['type' => 'options_page', 'operator' => '==', 'value' => 'theme-settings']
            ]
        ]
    ]);
}

// ============================================
// 2. JSON EXPORT/IMPORT (Local JSON)
// ============================================

/**
 * Ustaw własną ścieżkę dla plików JSON
 * Domyślnie: wp-content/uploads/yap-json/
 */
add_filter('yap/json_save_path', function($path) {
    return get_stylesheet_directory() . '/yap-json/';
});

/**
 * Eksport grupy do JSON ręcznie
 */
add_action('admin_init', 'my_export_groups_to_json');

function my_export_groups_to_json() {
    // Tylko raz, potem zakomentuj
    // yap_export_all_groups();
}

/**
 * Import z JSON ręcznie
 */
function my_import_from_json() {
    $imported = yap_import_all_json();
    // $imported zawiera nazwy zaimportowanych grup
}

/**
 * Auto-sync działa automatycznie
 * Jeśli zmienisz plik JSON w folderze, YAP automatycznie zaktualizuje bazę
 */

// ============================================
// 3. REST API
// ============================================

/**
 * Pobierz wszystkie grupy pól:
 * GET /wp-json/yap/v1/field-groups
 */

/**
 * Pobierz konkretną grupę:
 * GET /wp-json/yap/v1/field-groups/product_details
 */

/**
 * Pobierz pola dla konkretnego posta:
 * GET /wp-json/yap/v1/fields/post/123
 * 
 * Odpowiedź:
 * {
 *   "product_details": {
 *     "price": 99.99,
 *     "sku": "PROD-001",
 *     "stock": 50
 *   }
 * }
 */

/**
 * Aktualizuj pola posta:
 * POST /wp-json/yap/v1/fields/post/123
 * Body:
 * {
 *   "product_details": {
 *     "price": 109.99,
 *     "stock": 45
 *   }
 * }
 */

/**
 * Pobierz pola użytkownika:
 * GET /wp-json/yap/v1/fields/user/1
 */

/**
 * Pobierz opcje strony:
 * GET /wp-json/yap/v1/options/theme-settings
 */

/**
 * Aktualizuj opcje:
 * POST /wp-json/yap/v1/options/theme-settings
 * Body:
 * {
 *   "logo": 123,
 *   "primary_color": "#ff0000"
 * }
 */

/**
 * Pola są też dostępne w standardowym REST API dla postów:
 * GET /wp-json/wp/v2/posts/123
 * 
 * Odpowiedź zawiera:
 * {
 *   "id": 123,
 *   "title": {...},
 *   "yap_product_details": {
 *     "price": 99.99,
 *     "sku": "PROD-001"
 *   }
 * }
 */

// ============================================
// 4. SYSTEM HOOKÓW
// ============================================

/**
 * Formatuj cenę z walutą
 */
add_filter('yap/format_value/name=price', function($value, $post_id, $field) {
    return number_format($value, 2, ',', ' ') . ' PLN';
}, 10, 3);

/**
 * Automatycznie UPPERCASE dla SKU
 */
add_filter('yap/update_value/name=sku', function($value, $post_id, $field) {
    return strtoupper($value);
}, 10, 3);

/**
 * Walidacja niestandardowa dla SKU
 */
add_filter('yap/validate_value/name=sku', function($valid, $value, $field, $input) {
    // Sprawdź czy SKU nie istnieje już w bazie
    global $wpdb;
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}group_product_details_data 
        WHERE value = %s AND post_id != %d",
        $value,
        $_POST['post_ID'] ?? 0
    ));
    
    if ($exists > 0) {
        return 'Ten SKU jest już używany przez inny produkt';
    }
    
    return $valid;
}, 10, 4);

/**
 * Załaduj domyślną wartość dla nowych postów
 */
add_filter('yap/load_value/name=stock', function($value, $post_id, $field) {
    if (empty($value) && get_post_status($post_id) === 'auto-draft') {
        return 0; // Domyślny stan: 0
    }
    return $value;
}, 10, 3);

/**
 * Wszystkie wartości typu 'number' jako liczby (nie string)
 */
add_filter('yap/format_value/type=number', function($value, $post_id, $field) {
    return (float)$value;
}, 10, 3);

/**
 * Automatyczne kompresowanie obrazów przy zapisie
 */
add_filter('yap/update_value/type=image', function($value, $post_id, $field) {
    if (is_numeric($value)) {
        // Tu możesz dodać kompresję obrazu
        // np. za pomocą wtyczki lub własnej funkcji
    }
    return $value;
}, 10, 3);

/**
 * Hook przed renderowaniem pola w adminpanelu
 */
add_action('yap/render_field/type=wysiwyg', function($field) {
    echo '<div class="custom-wysiwyg-wrapper">';
});

/**
 * Hook po zapisaniu posta z polami YAP
 */
add_action('yap/save_post', function($post_id) {
    // Wyczyść cache
    wp_cache_delete('product_' . $post_id, 'yap');
    
    // Wyślij powiadomienie
    if (get_post_type($post_id) === 'product') {
        // do_action('send_stock_notification', $post_id);
    }
});

/**
 * Modyfikuj konfigurację pola przed załadowaniem
 */
add_filter('yap/load_field/name=price', function($field) {
    // Dodaj sufiks waluty do placeholder
    $field['options']['placeholder'] = '0.00 PLN';
    return $field;
});

/**
 * Formatuj wszystkie pola z grupy 'product_details'
 */
add_filter('yap/format_value/group=product_details', function($value, $post_id, $field) {
    // Loguj każde pobranie wartości
    error_log("YAP: Pobrano {$field['name']} dla posta {$post_id}");
    return $value;
}, 10, 3);

// ============================================
// 5. PRZYKŁAD PEŁNEJ INTEGRACJI
// ============================================

/**
 * Kompletny przykład: Produkt z REST API + Hookami
 */

// Użycie w szablonie
/*
<?php
$product_id = get_the_ID();

// Pobierz cenę (sformatowaną przez hook)
$price = yap_get_field_with_hooks('price', $product_id, 'product_details');
echo '<div class="price">' . $price . '</div>';

// Galeria
$gallery = yap_get_gallery('product_gallery', $product_id, 'product_details');
foreach ($gallery as $image) {
    echo '<img src="' . $image['url'] . '" alt="' . $image['alt'] . '">';
}

// Repeater z cechami
if (yap_have_rows('features', $product_id, 'product_details')) {
    echo '<ul class="features">';
    while (yap_have_rows('features', $product_id, 'product_details')) {
        $feature_name = yap_get_field('feature_name', $product_id, 'product_details');
        $feature_value = yap_get_field('feature_value', $product_id, 'product_details');
        echo '<li><strong>' . $feature_name . ':</strong> ' . $feature_value . '</li>';
    }
    echo '</ul>';
}
?>
*/

/**
 * Użycie przez REST API (JavaScript)
 */
/*
// Pobierz produkt
fetch('/wp-json/yap/v1/fields/post/123')
    .then(response => response.json())
    .then(data => {
        console.log('Cena:', data.product_details.price);
        console.log('SKU:', data.product_details.sku);
        console.log('Stan:', data.product_details.stock);
    });

// Aktualizuj produkt
fetch('/wp-json/yap/v1/fields/post/123', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        product_details: {
            stock: 45
        }
    })
})
.then(response => response.json())
.then(data => console.log('Zapisano!', data));
*/

/**
 * Rejestracja z JSON (functions.php)
 */
/*
// 1. Eksportuj grupę do JSON (jednorazowo w admin_init)
yap_save_group_to_json('product_details');

// 2. Plik JSON zostanie zapisany w:
// wp-content/uploads/yap-json/product-details.json
// (lub w lokalizacji określonej filtrem yap/json_save_path)

// 3. Skopiuj plik do motywu:
// themes/twoj-motyw/yap-json/product-details.json

// 4. Ustaw ścieżkę (w functions.php):
add_filter('yap/json_save_path', function($path) {
    return get_stylesheet_directory() . '/yap-json/';
});

// 5. Teraz przy każdej zmianie JSON, YAP automatycznie zsynchronizuje bazę!
// Local JSON działa jak w ACF - edytuj JSON w edytorze, YAP automatycznie załaduje zmiany
*/
