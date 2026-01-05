<?php
/**
 * Przykłady rejestracji stron opcji (Options Pages)
 * 
 * Umieść ten kod w functions.php swojego motywu lub w pluginie
 */

// Przykład 1: Główna strona opcji motywu
yap_register_options_page('theme_settings', [
    'page_title' => 'Ustawienia motywu',
    'menu_title' => 'Ustawienia motywu',
    'icon' => 'dashicons-admin-settings',
    'capability' => 'manage_options',
    'position' => 60
]);

// Przykład 2: Submenu pod "Wygląd"
yap_register_options_page('header_footer_options', [
    'page_title' => 'Nagłówek i stopka',
    'menu_title' => 'Nagłówek i stopka',
    'parent' => 'themes.php', // Pod "Wygląd"
    'capability' => 'edit_theme_options'
]);

// Przykład 3: Submenu pod "Ustawienia"
yap_register_options_page('site_general_options', [
    'page_title' => 'Opcje strony',
    'menu_title' => 'Opcje strony',
    'parent' => 'options-general.php', // Pod "Ustawienia"
]);

// Przykład 4: Opcje dla e-commerce
yap_register_options_page('shop_settings', [
    'page_title' => 'Ustawienia sklepu',
    'menu_title' => 'Sklep',
    'icon' => 'dashicons-cart',
    'capability' => 'manage_woocommerce',
    'position' => 56
]);

// Przykład 5: Opcje SEO
yap_register_options_page('seo_settings', [
    'page_title' => 'Ustawienia SEO',
    'menu_title' => 'SEO',
    'icon' => 'dashicons-search',
    'capability' => 'manage_options',
    'position' => 70
]);

// Przykład 6: Submenu - Social Media
yap_register_options_page('social_media_settings', [
    'page_title' => 'Media społecznościowe',
    'menu_title' => 'Social Media',
    'parent' => 'theme_settings' // Pod "Ustawienia motywu"
]);

// Przykład 7: Opcje API
yap_register_options_page('api_integrations', [
    'page_title' => 'Integracje API',
    'menu_title' => 'API',
    'icon' => 'dashicons-admin-plugins',
    'capability' => 'manage_options'
]);

/**
 * Po zarejestrowaniu stron opcji:
 * 
 * 1. Przejdź do YAP -> Grupy pól
 * 2. Utwórz nową grupę pól
 * 3. Dodaj pola (logo, kolory, teksty, itp.)
 * 4. W regułach lokalizacji wybierz:
 *    - Typ: "Strona opcji"
 *    - Operator: "jest równe"
 *    - Wartość: wybierz swoją stronę opcji (np. "theme_settings")
 * 5. Zapisz grupę
 * 
 * Teraz w menu pojawi się nowa strona z polami!
 */

/**
 * Przykłady użycia w motywie:
 */

// Logo w nagłówku
$logo_id = yap_get_option('theme_settings', 'site_logo');
if ($logo_id) {
    echo '<img src="' . wp_get_attachment_url($logo_id) . '" alt="Logo">';
}

// Kolor główny
$primary_color = yap_get_option('theme_settings', 'primary_color', '#0073aa');
echo '<style>:root { --primary-color: ' . esc_attr($primary_color) . '; }</style>';

// Tekst w stopce
$footer_text = yap_get_option('header_footer_options', 'footer_copyright');
echo '<p class="copyright">' . wp_kses_post($footer_text) . '</p>';

// Social media links
$facebook = yap_get_option('social_media_settings', 'facebook_url');
$twitter = yap_get_option('social_media_settings', 'twitter_url');
$instagram = yap_get_option('social_media_settings', 'instagram_url');

if ($facebook) {
    echo '<a href="' . esc_url($facebook) . '" target="_blank">Facebook</a>';
}

// Google Analytics ID
$ga_id = yap_get_option('seo_settings', 'google_analytics_id');
if ($ga_id) {
    echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr($ga_id) . '"></script>';
}

// API Keys
$api_key = yap_get_option('api_integrations', 'mailchimp_api_key');
if ($api_key) {
    // Użyj klucza API...
}

/**
 * Dostępne rodzaje stron nadrzędnych (parent):
 * 
 * 'index.php'              - Kokpit
 * 'edit.php'               - Wpisy
 * 'upload.php'             - Media
 * 'edit.php?post_type=page' - Strony
 * 'edit-comments.php'      - Komentarze
 * 'themes.php'             - Wygląd
 * 'plugins.php'            - Wtyczki
 * 'users.php'              - Użytkownicy
 * 'tools.php'              - Narzędzia
 * 'options-general.php'    - Ustawienia
 * 
 * Lub slug własnej strony (np. 'theme_settings')
 */

/**
 * Dostępne ikony (icon):
 * 
 * dashicons-admin-appearance
 * dashicons-admin-comments
 * dashicons-admin-home
 * dashicons-admin-media
 * dashicons-admin-page
 * dashicons-admin-plugins
 * dashicons-admin-settings
 * dashicons-admin-site
 * dashicons-admin-tools
 * dashicons-admin-users
 * dashicons-cart
 * dashicons-search
 * dashicons-star-filled
 * dashicons-heart
 * dashicons-email
 * dashicons-phone
 * dashicons-location
 * 
 * Pełna lista: https://developer.wordpress.org/resource/dashicons/
 */
