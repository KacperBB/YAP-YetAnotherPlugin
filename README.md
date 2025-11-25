# YAP - Yet Another Plugin

**WordPress plugin inspirowany ACF (Advanced Custom Fields)** do tworzenia niestandardowych grup pól z nielimitowaną strukturą zagnieżdżeń.

## 🎯 O Pluginie

YAP to potężne narzędzie do zarządzania custom fields w WordPress, oferujące dynamiczny system tabel i pełną kontrolę nad strukturą danych. W przeciwieństwie do tradycyjnych rozwiązań, każda grupa pól generuje własne dedykowane tabele w bazie danych.

## 🏗️ Architektura - System Dynamicznych Tabel

**Kluczowa koncepcja**: Każda grupa pól tworzy DWA tabele MySQL:

- `wp_group_{nazwa}_pattern` - Definicje pól (schemat)
- `wp_group_{nazwa}_data` - Wartości pól dla konkretnych postów (dane)

### Jak to działa?

**Tabela Pattern** przechowuje:
- Metadane pól (nazwa, typ, wartość domyślna)
- `nested_field_ids` - JSON array z nazwami tabel dzieci
- Metadane grupy (post_type, category) w specjalnym wierszu `group_meta`

**Tabela Data** zawiera:
- Wartości pól przypisane do konkretnych postów
- Link do posta przez `associated_id`
- UNIQUE constraint na `(generated_name, associated_id)`

## ✨ Funkcje

### 1. **Nieograniczone Zagnieżdżenia**
Twórz grupy wewnątrz grup bez limitu głębokości. Każda zagnieżdżona grupa otrzymuje własną tabelę i może zawierać kolejne podgrupy.

### 2. **Inteligentne Filtrowanie**
Pokazuj grupy pól tylko tam, gdzie są potrzebne:
- **Post Type**: Wybierz konkretny typ (post, page, CPT) lub "Wszystkie"
- **Kategoria**: Filtruj po standardowych kategoriach WP lub "Wszystkie"
- Filtrowanie działa PRZED dodaniem metaboxów - wydajne rozwiązanie

### 3. **Typy Pól**
- **Krótki tekst** (`short_text`) - jednoliniowe pole tekstowe
- **Długi tekst** (`long_text`) - textarea
- **Liczba** (`number`) - pole numeryczne
- **Obraz** (`image`) - integracja z WordPress Media Library
- **Zagnieżdżona grupa** (`nested_group`) - rekurencyjny kontener pól

### 4. **WordPress Media Library**
Pełna integracja z natywną galerią WordPress:
- Wybór obrazów przez standardowy interfejs WP
- Przechowywanie Attachment ID (nie URL)
- Podgląd wybranych obrazów w edytorze
- Obsługa w metaboxach, formularzu edycji i zagnieżdżonych grupach

### 5. **Lazy Loading**
Pola generują się automatycznie przy pierwszym zapisie posta - optymalizacja wydajności.

## 🚀 Instalacja

1. Skopiuj folder `YetAnotherPlugin` do `wp-content/plugins/`
2. Aktywuj plugin w panelu WordPress
3. Przejdź do **Yet Another Plugin** w menu admina

## 📖 Jak Używać

### Tworzenie Grupy Pól

1. **Yet Another Plugin** → kliknij główne menu
2. Wypełnij formularz:
   - **Nazwa grupy**: Unikalna nazwa (generuje tabele)
   - **Typ posta**: Wybierz gdzie pokazywać lub "Wszystkie typy postów"
   - **Kategoria**: Opcjonalne filtrowanie po kategoriach
3. Kliknij **Zapisz grupę**

### Dodawanie Pól

1. Znajdź swoją grupę na liście i kliknij **Edytuj**
2. Wypełnij formularz dodawania pola:
   - **Nazwa pola**: Etykieta wyświetlana w UI
   - **Typ pola**: Wybierz z listy dostępnych typów
   - **Wartość pola**: Wartość domyślna (automatycznie ukryta dla obrazów/zagnieżdżonych grup)
3. Kliknij **Dodaj Pole**

### Tworzenie Zagnieżdżonych Grup

1. Podczas dodawania pola wybierz typ **Zagnieżdżona grupa**
2. Wartość pola zostanie automatycznie ukryta
3. Po dodaniu pola pojawi się nowa sekcja z formularzem dla zagnieżdżonych pól
4. Dodawaj pola do zagnieżdżonej grupy normalnie
5. Możesz tworzyć kolejne poziomy zagnieżdżeń bez limitu

### Używanie w Szablonach

```php
// Pobierz wartość pojedynczego pola
$value = yap_get_field('nazwa_pola', $post_id, 'nazwa_grupy');

// Pobierz URL obrazu
$image_url = yap_get_image('pole_obrazu', $post_id, 'nazwa_grupy', 'full');

// Pobierz wszystkie pola z grupy
$all_fields = yap_get_all_fields($post_id, 'nazwa_grupy');

// Pobierz zagnieżdżoną grupę
$nested = yap_get_nested_group('nazwa_zagniezdzonej', $post_id, 'nazwa_grupy');
```

**Uwaga**: Używaj nazwy grupy BEZ prefiksów tabel (np. `'produkty'` zamiast `'wp_group_produkty_pattern'`).

## 🔧 Struktura Plików

```
YetAnotherPlugin/
├── yetanotherplugin.php          # Główny plik pluginu
├── db/
│   └── database.php              # Logika tabel i generowanie pól
├── includes/
│   ├── admin.php                 # Hooki, enqueue, metaboxy
│   ├── display.php               # Publiczne API funkcji
│   └── admin/
│       ├── admin-menu.php        # Rejestracja menu
│       ├── admin-page.php        # Formularz tworzenia grup
│       ├── admin-edit-page.php   # Edycja i aktualizacja pól
│       ├── ajax_requests/        # Endpointy AJAX
│       └── views/pattern/        # Szablony UI
├── includes/css/admin/
│   └── admin-style.css           # Style panelu admina
└── includes/js/admin/
    ├── admin.js                  # Główna logika JS + AJAX
    └── includes/                 # Moduły JS
```

## 🛡️ Bezpieczeństwo

- Wszystkie dane sanityzowane przez `sanitize_text_field()`, `sanitize_title()`
- Output escapowany: `esc_html()`, `esc_attr()`, `esc_url()`
- Sprawdzanie uprawnień: `current_user_can('manage_options')`
- Weryfikacja nonce we wszystkich requestach AJAX
- Prepared statements w zapytaniach SQL

## 🐛 Debugging

Włącz WordPress debug mode w `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Sprawdzaj `wp-content/debug.log` - plugin używa emoji w logach:
- 🔵 - AJAX operations
- ✅ - Success messages
- 🚨 - Errors
- 🔄 - Update operations
- ⚙️ - Info

## 📝 Changelog

### v1.0.4 (2025-11-25)
- ✅ Integracja WordPress Media Library dla pól typu obraz
- ✅ Selektor obrazów w metaboxach, formularzu edycji i zagnieżdżonych grupach
- ✅ Automatyczne ukrywanie pola wartości dla typów image/nested_group
- ✅ Opcje "Wszystkie" dla post types i kategorii
- ✅ Poprawiona logika aktualizacji pól (zachowanie nested_field_ids)
- ✅ Ulepszony system logowania z emoji

### v1.0.2
- Podstawowa funkcjonalność zagnieżdżonych grup
- System dynamicznych tabel
- AJAX dodawanie pól

## ⚠️ Znane Ograniczenia

1. **Kategorie** - działa tylko z taxonomy `category` (standardowe kategorie WP)
2. **Reload strony** - po dodaniu pola następuje odświeżenie (nie full-AJAX)
3. **Brak walidacji cykliczności** - możliwe zagnieżdżenie grupy w samej sobie
4. **Pole wartości** - dla typów image/nested_group czyszczone automatycznie przez JS

## 🤝 Contributing

Plugin w fazie rozwoju. Zapraszamy do zgłaszania błędów i sugestii przez Issues.

## 📄 Licencja

MIT License - plugin open source.

## 👨‍💻 Autor

Zbudowane z myślą o elastyczności i wydajności w zarządzaniu custom fields w WordPress.

---

**YAP - Yet Another Plugin**: Bo czasami "kolejny plugin" to właśnie to, czego potrzebujesz. 🚀
