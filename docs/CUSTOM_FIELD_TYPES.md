# Custom Field Types Guide

> Komplety przewodnik do tworzenia niestandardowych typów pól w YAPe

**Wersja:** 1.0  
**Data:** 2024  
**Język:** JavaScript + PHP

---

## 📋 Spis Treści

1. [Podstawy](#podstawy)
2. [Struktura Field Type](#struktura-field-type)
3. [Metody Wymagane](#metody-wymagane)
4. [Praktyczne Przykłady](#praktyczne-przykłady)
5. [Walidacja i Sanityzacja](#walidacja-i-sanityzacja)
6. [Zaawansowane Funkcje](#zaawansowane-funkcje)
7. [Rejestracja Typu](#rejestracja-typu)
8. [Best Practices](#best-practices)

---

## Podstawy

### Co to jest Field Type?

Field Type to klasa definiująca zachowanie pojedynczego typu pola w YAPie:
- **Konfiguracja domyślna** (defaults)
- **Schemat ustawień UI** (settingsSchema)
- **Renderowanie w Visual Builderze** (renderPreview)
- **Renderowanie w admin panelu** (renderAdmin)
- **Walidacja wartości** (validate)
- **Oczyszczanie danych** (sanitize)
- **Formatowanie** (format)

### Dlaczego Custom Field Types?

- ✅ Skalowalne - dodawaj nowe typy bez zmiany kodu core
- ✅ Bezpieczne - każdy typ ma własną walidację
- ✅ Elastyczne - dostosuj dokładnie do swoich potrzeb
- ✅ Testowalny - każdy typ jest niezależną klasą

---

## Struktura Field Type

### Template Klasy

```javascript
class MyFieldType extends BaseFieldType {
    // Obligatoryjne
    static get type() { return 'mytype'; }
    static defaults() { /* ... */ }
    static settingsSchema() { /* ... */ }
    static renderAdmin(field, value) { /* ... */ }
    
    // Opcjonalne
    static renderPreview(field) { /* ... */ }
    static validate(value, field) { /* ... */ }
    static sanitize(value) { /* ... */ }
    static format(value, field) { /* ... */ }
}
```

### Dziedziczenie

```javascript
// Prosty typ (rozszerza BaseFieldType)
class MyType extends BaseFieldType { }

// Złożony typ (rozszerza inny typ)
class MyEmailType extends EmailFieldType {
    static validate(value, field) {
        const result = super.validate(value, field);
        if (!result.valid) return result;
        
        // Dodatkowa walidacja
        if (value.endsWith('@example.com')) {
            return { valid: false, error: 'Nie akceptujemy example.com' };
        }
        return { valid: true };
    }
}
```

---

## Metody Wymagane

### 1. `static get type()`

```javascript
static get type() { 
    return 'color'; 
}
```

**Opis:** Zwraca unikatowy identyfikator typu  
**Wymagana:** ✅ TAK  
**Format:** lowercase, kebab-case, bez spacji  
**Używane do:** Rejestracji w FieldTypeRegistry

---

### 2. `static defaults()`

```javascript
static defaults() {
    return {
        // Klucze ze super.defaults()
        type: 'color',
        name: '',
        label: '',
        description: '',
        required: false,
        default_value: '#000000',
        
        // Typ-specyficzne
        enable_alpha: false,
        picker_format: 'hex'
    };
}
```

**Opis:** Zwraca domyślną konfigurację pola  
**Wymagana:** ✅ TAK  
**Zawsze:** Zaczynaj od `super.defaults()`  
**Zwracaj:** Obiekt z pełną konfiguracją

---

### 3. `static settingsSchema()`

```javascript
static settingsSchema() {
    const base = super.settingsSchema();
    
    base[1].fields.push({
        name: 'enable_alpha',
        label: 'Włącz Alpha Channel',
        type: 'checkbox',
        hint: 'Obsługuje przezroczystość',
        group: 'Wygląd'
    });
    
    return base;
}
```

**Struktura:**

```javascript
[
    // Panel 0: Podstawowe ustawienia
    {
        label: 'Podstawowe',
        fields: [
            { name: 'label', label: 'Nazwa', type: 'text', required: true },
            { name: 'name', label: 'ID Pola', type: 'text', required: true },
            // ...
        ]
    },
    // Panel 1: Typ-specyficzne
    {
        label: 'Typ Pola',
        fields: [
            { name: 'my_setting', label: 'My Setting', type: 'text' },
        ]
    }
]
```

**Pola Ustawień:**

```javascript
{
    name: 'field_name',           // Klucz w konfiguracji
    label: 'Display Name',        // Etykieta w UI
    type: 'text|select|checkbox', // Typ input
    required: true,               // Czy obowiązkowe
    default: 'value',             // Wartość domyślna
    options: [],                  // Dla select/radio
    hint: 'Pomocy text',          // Wskazówka
    group: 'Validation',          // Sekcja UI
    validate: (val) => ({})       // Walidacja settingów
}
```

---

### 4. `static renderAdmin(field, value = '')`

```javascript
static renderAdmin(field, value = '') {
    return `
        <div class="yap-field-input">
            <label for="${field.name}">
                ${field.label}
                ${field.required ? '<span class="required">*</span>' : ''}
            </label>
            <input 
                type="color" 
                name="${field.name}" 
                id="${field.name}"
                value="${value}"
                ${field.required ? 'required' : ''}
            >
            ${field.description ? `<p class="help">${field.description}</p>` : ''}
        </div>
    `;
}
```

**Opis:** Renderuje pole w meta boxie / edytorze  
**Wymagana:** ✅ TAK  
**Parametry:**
- `field` - Obiekt konfiguracji pola
- `value` - Bieżąca wartość (domyślnie '')

**Best Practice:**
- Zawsze opakowuj w `<div class="yap-field-input">`
- Użyj `field.name` dla `name` i `id`
- Dodaj `[field.required ? 'required' : '']`
- Dodaj help text jeśli istnieje `field.description`

---

### 5. `static renderPreview(field)`

```javascript
static renderPreview(field) {
    return `
        <div class="yap-field-preview">
            <label>${field.label}</label>
            <div class="preview-content">
                Przykład: <strong>#000000</strong>
            </div>
            ${field.required ? '<span class="required">*</span>' : ''}
        </div>
    `;
}
```

**Opis:** Renderuje podgląd w Visual Builderze  
**Wymagana:** ❌ NIE (ale zalecane)  
**Zwracaj:** HTML string lub fragment  
**Cel:** Pokazać edytorowi jak będzie wyglądać pole

---

### 6. `static validate(value, field)`

```javascript
static validate(value, field) {
    // Zawsze zacznij od super
    const base = super.validate(value, field);
    if (!base.valid) return base;
    
    // Typ-specyficzna walidacja
    if (value.length > 7) {
        return { 
            valid: false, 
            error: 'Kolor musi być krótszy niż 7 znaków' 
        };
    }
    
    return { valid: true };
}
```

**Zwracaj:**

```javascript
// Sukces
{ valid: true }

// Błąd
{ valid: false, error: 'Opisz co jest nie tak' }
```

**Rodzaje Walidacji:**

```javascript
// 1. Długość
if (value.length < field.min_length) {
    return { valid: false, error: 'Za krótkie' };
}

// 2. Format (regex)
if (!field.pattern.test(value)) {
    return { valid: false, error: 'Zły format' };
}

// 3. Zakres
if (value < field.min || value > field.max) {
    return { valid: false, error: 'Poza zakresem' };
}

// 4. Lista
if (!field.options.includes(value)) {
    return { valid: false, error: 'Nieznaną opcja' };
}

// 5. Typ
if (typeof value !== 'string') {
    return { valid: false, error: 'Musi być tekst' };
}
```

---

### 7. `static sanitize(value)`

```javascript
static sanitize(value) {
    // Konwertuj na string
    value = String(value || '');
    
    // Usuń białe znaki
    value = value.trim();
    
    // Konwertuj na lowercase
    value = value.toLowerCase();
    
    // Usuń znaki specjalne
    value = value.replace(/[^a-z0-9-]/g, '');
    
    return value;
}
```

**Opis:** Oczyszcza/normalizuje wartość  
**Wymagana:** ❌ NIE (ale zalecane dla bezpieczeństwa)  
**Wykorzystane:** Przed zapisaniem do bazy  
**Cel:** Zapobiec XSS i złośliwym danym

---

### 8. `static format(value, field)`

```javascript
static format(value, field) {
    // Formatuj dla wyświetlenia
    if (field.date_format === 'YYYY-MM-DD') {
        return value; // już sformatowany
    }
    
    // Konwertuj format
    const date = new Date(value);
    return date.toLocaleDateString('pl-PL');
}
```

**Opis:** Formatuje wartość do wyświetlenia  
**Wymagana:** ❌ NIE  
**Używane do:** Wyświetlania w frontendzie

---

## Praktyczne Przykłady

### Przykład 1: Field Type - Wideo (URL)

```javascript
class VideoFieldType extends BaseFieldType {
    static get type() { return 'video'; }
    
    static defaults() {
        return {
            ...super.defaults(),
            type: 'video',
            allowed_sources: ['youtube', 'vimeo'],
            aspect_ratio: '16:9'
        };
    }
    
    static settingsSchema() {
        const base = super.settingsSchema();
        base[1].fields.push(
            {
                name: 'allowed_sources',
                label: 'Dozwolone źródła',
                type: 'select',
                options: [
                    { value: 'youtube', label: 'YouTube' },
                    { value: 'vimeo', label: 'Vimeo' }
                ],
                multiple: true
            },
            {
                name: 'aspect_ratio',
                label: 'Proporcje',
                type: 'select',
                options: [
                    { value: '16:9', label: '16:9 (Wstęga)' },
                    { value: '4:3', label: '4:3 (Standard)' },
                    { value: '1:1', label: '1:1 (Kwadrat)' }
                ]
            }
        );
        return base;
    }
    
    static renderAdmin(field, value = '') {
        return `
            <div class="yap-field-input">
                <label for="${field.name}">${field.label}</label>
                <input 
                    type="url" 
                    name="${field.name}" 
                    id="${field.name}"
                    value="${value}"
                    placeholder="https://youtube.com/watch?v=..."
                    ${field.required ? 'required' : ''}
                >
                <p class="help">Wklej URL do wideo z: ${field.allowed_sources.join(', ')}</p>
            </div>
        `;
    }
    
    static validate(value, field) {
        const base = super.validate(value, field);
        if (!base.valid) return base;
        
        // Sprawdź czy to URL
        try {
            new URL(value);
        } catch {
            return { valid: false, error: 'Niepoprawny URL' };
        }
        
        // Sprawdź źródło
        const source = this.getSource(value);
        if (!field.allowed_sources.includes(source)) {
            return { 
                valid: false, 
                error: `Źródło musi być: ${field.allowed_sources.join(', ')}` 
            };
        }
        
        return { valid: true };
    }
    
    static getSource(url) {
        if (url.includes('youtube')) return 'youtube';
        if (url.includes('vimeo')) return 'vimeo';
        return null;
    }
}
```

### Przykład 2: Field Type - Telefon

```javascript
class PhoneFieldType extends TextFieldType {
    static get type() { return 'phone'; }
    
    static defaults() {
        return {
            ...super.defaults(),
            type: 'phone',
            country_code: '+48',
            pattern: '^[0-9+\\-\\s()]{9,}$'
        };
    }
    
    static renderAdmin(field, value = '') {
        return `
            <div class="yap-field-input">
                <label for="${field.name}">${field.label}</label>
                <div class="phone-input-group">
                    <span class="country-code">${field.country_code}</span>
                    <input 
                        type="tel" 
                        name="${field.name}" 
                        id="${field.name}"
                        value="${value}"
                        placeholder="500 123 456"
                        ${field.required ? 'required' : ''}
                    >
                </div>
            </div>
        `;
    }
    
    static validate(value, field) {
        const base = super.validate(value, field);
        if (!base.valid) return base;
        
        // Usuń spacje, myślniki, nawiasy
        const clean = value.replace(/[\s\-()]/g, '');
        
        if (!/^\d{9,}$/.test(clean)) {
            return { valid: false, error: 'Numer musi mieć min. 9 cyfr' };
        }
        
        return { valid: true };
    }
    
    static sanitize(value) {
        // Zachowaj tylko numery i znaki specjalne
        return value.replace(/[^\d+\-\s()]/g, '');
    }
}
```

### Przykład 3: Field Type - Adres (Zaawansowany)

```javascript
class AddressFieldType extends BaseFieldType {
    static get type() { return 'address'; }
    
    static defaults() {
        return {
            ...super.defaults(),
            type: 'address',
            components: ['street', 'number', 'city', 'postal_code', 'country'],
            enable_google_maps: false,
            format: 'full' // full, street_city, compact
        };
    }
    
    static renderAdmin(field, value = {}) {
        const addr = typeof value === 'object' ? value : {};
        
        return `
            <div class="yap-field-input address-field">
                <label>${field.label}</label>
                <div class="address-inputs">
                    ${field.components.includes('street') ? `
                        <input type="text" name="${field.name}[street]" 
                               placeholder="Ulica" value="${addr.street || ''}">
                    ` : ''}
                    ${field.components.includes('number') ? `
                        <input type="text" name="${field.name}[number]" 
                               placeholder="Numer" value="${addr.number || ''}" style="width: 100px;">
                    ` : ''}
                    ${field.components.includes('city') ? `
                        <input type="text" name="${field.name}[city]" 
                               placeholder="Miasto" value="${addr.city || ''}">
                    ` : ''}
                    ${field.components.includes('postal_code') ? `
                        <input type="text" name="${field.name}[postal_code]" 
                               placeholder="Kod" value="${addr.postal_code || ''}" style="width: 120px;">
                    ` : ''}
                    ${field.components.includes('country') ? `
                        <input type="text" name="${field.name}[country]" 
                               placeholder="Kraj" value="${addr.country || ''}">
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    static validate(value, field) {
        const addr = typeof value === 'object' ? value : {};
        
        for (const component of field.components) {
            if (field.required && !addr[component]) {
                return { 
                    valid: false, 
                    error: `${component} jest wymagane` 
                };
            }
        }
        
        return { valid: true };
    }
    
    static format(value, field) {
        const addr = typeof value === 'object' ? value : {};
        
        if (field.format === 'full') {
            return `${addr.street} ${addr.number}, ${addr.postal_code} ${addr.city}`;
        } else if (field.format === 'street_city') {
            return `${addr.street} ${addr.number}, ${addr.city}`;
        } else {
            return `${addr.city}`;
        }
    }
}
```

---

## Walidacja i Sanityzacja

### Bezpieczna Sanityzacja

```javascript
static sanitize(value) {
    // 1. String konwersja
    value = String(value || '');
    
    // 2. HTML escape (bardzo ważne!)
    value = value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    
    // 3. Trim
    value = value.trim();
    
    return value;
}
```

### Zaawansowana Walidacja

```javascript
static validate(value, field) {
    const result = { valid: true };
    
    // 1. Walidacja wymagane
    if (field.required && !value) {
        return { valid: false, error: 'To pole jest wymagane' };
    }
    
    // 2. Walidacja typu
    if (typeof value !== 'string') {
        return { valid: false, error: 'Musi być tekst' };
    }
    
    // 3. Długość
    if (field.min_length && value.length < field.min_length) {
        return { 
            valid: false, 
            error: `Min. ${field.min_length} znaków` 
        };
    }
    
    if (field.max_length && value.length > field.max_length) {
        return { 
            valid: false, 
            error: `Max. ${field.max_length} znaków` 
        };
    }
    
    // 4. Pattern (regex)
    if (field.pattern) {
        const regex = new RegExp(field.pattern);
        if (!regex.test(value)) {
            return { 
                valid: false, 
                error: field.pattern_message || 'Format jest nieprawidłowy' 
            };
        }
    }
    
    // 5. Custom validator
    if (field.custom_validator && typeof field.custom_validator === 'function') {
        const customResult = field.custom_validator(value);
        if (!customResult.valid) return customResult;
    }
    
    return { valid: true };
}
```

---

## Zaawansowane Funkcje

### 1. Statyczne Metamasowe Informacje

```javascript
class MyFieldType extends BaseFieldType {
    static get type() { return 'mytype'; }
    
    // Metadata
    static get description() { 
        return 'Opis dla administratora'; 
    }
    
    static get category() { 
        return 'basic'; // basic, content, advanced
    }
    
    static get icon() { 
        return '📝'; // Emoji lub CSS class
    }
    
    static get version() { 
        return '1.0.0'; 
    }
}
```

### 2. Async Walidacja

```javascript
static async validate(value, field) {
    const base = super.validate(value, field);
    if (!base.valid) return base;
    
    // Async sprawdzenie na serwerze
    try {
        const response = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=check_email&email=${encodeURIComponent(value)}`
        });
        
        const data = await response.json();
        
        if (!data.available) {
            return { valid: false, error: 'Email już zarejestrowany' };
        }
    } catch (error) {
        return { valid: false, error: 'Błąd sprawdzenia email' };
    }
    
    return { valid: true };
}
```

### 3. Warunkowe Rendery

```javascript
static renderAdmin(field, value = '') {
    // Różne renderowanie zależnie od ustawienia
    if (field.input_style === 'textarea') {
        return `<textarea name="${field.name}">${value}</textarea>`;
    } else if (field.input_style === 'password') {
        return `<input type="password" name="${field.name}" value="${value}">`;
    } else {
        return `<input type="text" name="${field.name}" value="${value}">`;
    }
}
```

### 4. Integracja z Pluginami

```javascript
class WooProductFieldType extends BaseFieldType {
    static get type() { return 'woo_product'; }
    
    // Sprawdzenie czy WooCommerce jest zainstalowany
    static isAvailable() {
        return typeof window.wc !== 'undefined';
    }
    
    static renderAdmin(field, value = '') {
        if (!this.isAvailable()) {
            return '<p>⚠️ WooCommerce musi być zainstalowany</p>';
        }
        
        // Użyj WC selectów
        return `
            <select name="${field.name}" class="wc-product-search">
                <option value="${value}">Ładowanie...</option>
            </select>
        `;
    }
}
```

---

## Rejestracja Typu

### Metoda 1: Automatycznie (w field-types.js)

```javascript
// Na koniec pliku field-types.js
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        FieldTypeRegistry.register('mytype', MyFieldType);
    });
} else {
    FieldTypeRegistry.register('mytype', MyFieldType);
}
```

### Metoda 2: Ręcznie (w custom pliku)

```javascript
// custom-field-types.js
class MyCustomType extends BaseFieldType {
    // ...
}

// Rejestracja
FieldTypeRegistry.register('custom', MyCustomType);
```

### Metoda 3: Z PHP

```php
// W plugin file lub custom plugin
function register_custom_field_types() {
    wp_enqueue_script(
        'my-custom-types',
        plugin_dir_url(__FILE__) . 'js/custom-types.js',
        ['yap-field-registry'],
        filemtime(plugin_dir_path(__FILE__) . 'js/custom-types.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'register_custom_field_types');
add_action('admin_enqueue_scripts', 'register_custom_field_types');
```

---

## Best Practices

### ✅ Dobrze

```javascript
class GoodFieldType extends BaseFieldType {
    // 1. Jasna nazwa
    static get type() { return 'my_field'; }
    
    // 2. Zawsze rozszerz super
    static defaults() {
        return { ...super.defaults(), ... };
    }
    
    // 3. Dokumentuj publiczne metody
    /**
     * Renderuje pole w admin panelu
     * @param {Object} field - Konfiguracja pola
     * @param {*} value - Bieżąca wartość
     * @returns {string} HTML
     */
    static renderAdmin(field, value) { ... }
    
    // 4. Zawsze waliduj
    static validate(value, field) {
        const result = super.validate(value, field);
        if (!result.valid) return result;
        // Typ-specyficzna walidacja
        return { valid: true };
    }
    
    // 5. Sanityzuj dla bezpieczeństwa
    static sanitize(value) { ... }
}
```

### ❌ Źle

```javascript
class BadFieldType {
    // ❌ Brak dziedziczenia
    
    defaults() { // ❌ Powinno być static
        return { /* brak super */ };
    }
    
    renderAdmin(field, value) { // ❌ Powinno być static
        // ❌ Brak required check
        return `<input value="${value}">`;
    }
    
    // ❌ Brak walidacji
    // ❌ Brak sanityzacji
}
```

---

## Checklist: Twój Field Type

Zanim puszczysz typ do produkcji:

- [ ] Rozszerza `BaseFieldType` lub inny typ
- [ ] Ma statyczną metodę `type`
- [ ] `defaults()` zaczyna od `super.defaults()`
- [ ] `settingsSchema()` rozszerza `super.settingsSchema()`
- [ ] `renderAdmin()` zawiera label, input, help
- [ ] `validate()` najpierw sprawdza `super.validate()`
- [ ] `sanitize()` usuwa niebezpieczne znaki
- [ ] Przetestowany w Visual Builderze
- [ ] Przetestowany w admin panelu
- [ ] Przetestowany na frontendzie
- [ ] Dokumentacja dla użytkowników

---

## Testowanie Custom Field Types

```javascript
// test-custom-type.php
// W teście sprawdź:

// 1. Rejestracja
console.assert(FieldTypeRegistry.has('mytype'), 'Type registered');

// 2. Defaults
const field = FieldTypeRegistry.createField('mytype');
console.assert(field.type === 'mytype', 'Type set correctly');

// 3. Walidacja
const validResult = MyType.validate('test', field);
console.assert(validResult.valid === true, 'Valid input passed');

const invalidResult = MyType.validate('', field);
console.assert(invalidResult.valid === false, 'Invalid input failed');

// 4. Render
const html = MyType.renderAdmin(field, 'test');
console.assert(html.includes('name="'), 'HTML contains input');
```

---

## Pytania Часто Zadawane

**P: Czy mogę rozszerzyć istniejący typ?**  
O: Tak! Dziedzicz z `EmailFieldType extends TextFieldType` itd.

**P: Jak dodać asynchroniczną walidację?**  
O: Zwróć `Promise` z `validate()` - TestRunner czeka na Promises.

**P: Czy mogę ukryć typ z Visual Buildera?**  
O: Tak - dodaj `static get hidden() { return true; }`

**P: Jak tworzyć kompleksowe typy?**  
O: Patrz Przykład 3 (Address) - rozdzielaj komponenty.

**P: Gdzie przechowywać wartości typów złożonych?**  
O: Jako JSON string w postmeta, deserializuj w `format()`.

---

## Zasoby

- [Registry API](./registry.js) - Dokumentacja systemu rejestracji
- [Built-in Types](./field-types.js) - Przykłady wbudowanych typów
- [Examples](./examples.js) - Color, Date, Video, Phone, Address
- [Base Class](./registry.js#L1-L60) - `BaseFieldType` template

---

## Wsparcie

Masz pytania? Stwórz issue z tagiem `field-type` lub obejrzyj przykłady w `examples.js`.

Happy coding! 🚀
