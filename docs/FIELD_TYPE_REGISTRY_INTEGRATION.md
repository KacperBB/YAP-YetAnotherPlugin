# Field Type Registry - Integration Guide

> Jak zintegrować profesjonalny system typów pól z Visual Builderem

**Status:** ✅ Production Ready  
**Wersja:** 1.0.0  
**Data:** 2024

---

## 📋 Spis Treści

1. [Szybki Start](#szybki-start)
2. [Architektura Systemu](#architektura-systemu)
3. [Integracja z Visual Builderem](#integracja-z-visual-builderem)
4. [Migracja Istniejącego Kodu](#migracja-istniejącego-kodu)
5. [Testing & Debugging](#testing--debugging)
6. [Performance](#performance)
7. [FAQ](#faq)

---

## Szybki Start

### 1. System jest Już Zainstalowany

Registry system jest już ładowany w `enqueue.php`:

```php
wp_enqueue_script('yap-field-type-registry', '.../field-types/registry.js');
wp_enqueue_script('yap-field-types', '.../field-types/field-types.js', ['yap-field-type-registry']);
```

### 2. Sprawdzenie czy System Działa

W konsoli przeglądarki:

```javascript
// Sprawdź czy registry jest dostępny
console.log(FieldTypeRegistry); // ✅ Object

// Sprawdź czy typy są zarejestrowane
console.log(FieldTypeRegistry.getAll());
// {
//   'text': TextFieldType,
//   'textarea': TextareaFieldType,
//   'select': SelectFieldType,
//   ...
// }

// Stwórz pole
const field = FieldTypeRegistry.createField('text', { label: 'Test' });
console.log(field); // ✅ {type: 'text', label: 'Test', ...}
```

### 3. Użyj w Swoim Kodzie

```javascript
// Pobierz typ
const TextType = FieldTypeRegistry.get('text');

// Stwórz field
const field = FieldTypeRegistry.createField('text', {
    name: 'first_name',
    label: 'Imię'
});

// Render w UI
const html = TextType.renderAdmin(field, 'John');
document.getElementById('form').innerHTML = html;

// Waliduj
const valid = TextType.validate('John', field);
if (valid.valid) {
    // OK
} else {
    console.error(valid.error);
}
```

---

## Architektura Systemu

### Pliki

```
includes/js/field-types/
├── registry.js          # Core system (FieldTypeRegistry + BaseFieldType)
├── field-types.js       # 7 Built-in types (text, textarea, select, ...)
└── examples.js          # Custom type examples (color, date, video, ...)
```

### Strumień Ładowania

```
1. registry.js ładuje się pierwszy
   └─ Definiuje FieldTypeRegistry
   └─ Definiuje BaseFieldType
   
2. field-types.js ładuje się drugi
   └─ Rozszerza BaseFieldType
   └─ Tworzy 7 built-in typów
   └─ Rejestruje je automatycznie
   
3. Opcjonalnie: examples.js
   └─ Dodatkowe przykłady
   └─ Ręczna rejestracja
```

### Klasy

```
BaseFieldType (Abstract)
├── defaults()              - Domyślna konfiguracja
├── settingsSchema()        - Schema dla modala ustawień
├── renderPreview()         - Podgląd w Visual Builderze
├── renderAdmin()           - Wyświetlanie w meta boxie
├── validate()              - Walidacja wartości
├── sanitize()              - Oczyszczanie danych
└── format()                - Formatowanie do wyświetlenia

TextFieldType extends BaseFieldType
├── defaults() + min/max length, pattern
├── renderAdmin() → <input type="text" />
├── validate() → sprawdza length + pattern
└── ... (9 innych typów)
```

---

## Integracja z Visual Builderem

### Scenario 1: Pobierz Typ i Stwórz Field

```javascript
// visual-builder.js
function addNewFieldToBuilder(fieldType, fieldName) {
    // Pobierz typ z registry
    const TypeClass = FieldTypeRegistry.get(fieldType);
    if (!TypeClass) {
        console.error(`Unknown field type: ${fieldType}`);
        return;
    }
    
    // Stwórz field z domyślnymi wartościami
    const newField = FieldTypeRegistry.createField(fieldType, {
        name: fieldName,
        label: fieldName.charAt(0).toUpperCase() + fieldName.slice(1)
    });
    
    // Dodaj do schematu
    window.yapBuilder.schema.fields.push(newField);
    
    // Renderuj podgląd
    const preview = TypeClass.renderPreview(newField);
    document.getElementById('preview').innerHTML += preview;
}
```

### Scenario 2: Edytuj Field - Dynamiczne Ustawienia

```javascript
// Zamiast hardcoded HTML w modalu
function showFieldSettingsModal(field) {
    const TypeClass = FieldTypeRegistry.get(field.type);
    if (!TypeClass) return;
    
    // Pobierz schema ustawień z typu
    const schema = TypeClass.settingsSchema();
    
    // Renderuj dynamicznie
    const modalContent = renderSettingsForm(schema, field);
    
    showModal('Field Settings', modalContent);
}

function renderSettingsForm(schema, fieldData) {
    let html = '<form id="field-settings-form">';
    
    // Iterate over schema panels
    schema.forEach(panel => {
        html += `<fieldset><legend>${panel.label}</legend>`;
        
        panel.fields.forEach(setting => {
            const value = fieldData[setting.name] || setting.default || '';
            
            if (setting.type === 'text') {
                html += `
                    <div class="form-group">
                        <label>${setting.label}</label>
                        <input type="text" name="${setting.name}" value="${value}">
                        ${setting.hint ? `<small>${setting.hint}</small>` : ''}
                    </div>
                `;
            } else if (setting.type === 'checkbox') {
                html += `
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="${setting.name}" ${value ? 'checked' : ''}>
                            ${setting.label}
                        </label>
                    </div>
                `;
            } else if (setting.type === 'select') {
                html += `
                    <div class="form-group">
                        <label>${setting.label}</label>
                        <select name="${setting.name}" ${setting.multiple ? 'multiple' : ''}>
                            ${setting.options.map(opt => `
                                <option value="${opt.value}" ${value === opt.value ? 'selected' : ''}>
                                    ${opt.label}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                `;
            }
        });
        
        html += '</fieldset>';
    });
    
    html += '</form>';
    return html;
}
```

### Scenario 3: Waliduj i Zapisz

```javascript
// Gdy użytkownik kliknie "Save Settings"
function saveFieldSettings(fieldId) {
    const form = document.getElementById('field-settings-form');
    const formData = new FormData(form);
    
    // Pobierz istniejące pole
    const field = window.yapBuilder.schema.fields.find(f => f.id === fieldId);
    if (!field) return;
    
    const TypeClass = FieldTypeRegistry.get(field.type);
    
    // Aktualizuj pole z formularza
    formData.forEach((value, key) => {
        field[key] = value;
    });
    
    // Waliduj całe pole
    const result = TypeClass.validate(field.default_value || '', field);
    if (!result.valid) {
        alert('Validation error: ' + result.error);
        return;
    }
    
    // Sanityzuj
    if (field.default_value) {
        field.default_value = TypeClass.sanitize(field.default_value);
    }
    
    // Zapisz
    console.log('Field saved:', field);
    closeModal();
}
```

---

## Migracja Istniejącego Kodu

### Przed (Hardcoded)

```javascript
// visual-builder.js (old)
const FIELD_TYPES = {
    'text': {
        defaults: { type: 'text', min_length: 0, max_length: 255 },
        renderAdmin: function(field, value) {
            return `<input type="text" value="${value}">`;
        },
        validate: function(value, field) {
            if (value.length < field.min_length) return { valid: false };
            if (value.length > field.max_length) return { valid: false };
            return { valid: true };
        }
    },
    'select': { /* ... */ },
    'checkbox': { /* ... */ }
    // ... 50 lines more
};

// Użycie
const type = FIELD_TYPES[fieldType];
const rendered = type.renderAdmin(field, value);
```

### Po (Registry)

```javascript
// Now: just use registry
const TypeClass = FieldTypeRegistry.get(fieldType);
const rendered = TypeClass.renderAdmin(field, value);

// 30 linii mniej kodu!
// Zysk: type-safe, extensible, testable
```

### Kroki Migracji

1. **Usuń hardcoded typów z visual-builder.js**

```diff
- const FIELD_TYPES = { ... }
```

2. **Zamień referencje na registry**

```diff
- const type = FIELD_TYPES[fieldType];
- const rendered = type.renderAdmin(field, value);

+ const TypeClass = FieldTypeRegistry.get(fieldType);
+ const rendered = TypeClass.renderAdmin(field, value);
```

3. **Przetestuj każdy typ**

```javascript
// test-migration.php
YAPBuilderTests.testMigration = function() {
    const types = ['text', 'textarea', 'select', 'checkbox'];
    
    types.forEach(type => {
        const TypeClass = FieldTypeRegistry.get(type);
        const field = FieldTypeRegistry.createField(type);
        
        // Render test
        const html = TypeClass.renderAdmin(field, 'test');
        console.assert(html.includes('input'), `${type} renders`);
    });
};
```

4. **Usuń duplikaty z kodu**

Jeśli masz gdzieś indziej definicje typów, usuń je - teraz jest centralne źródło.

---

## Testing & Debugging

### Debug Mode

```javascript
// W konsoli przeglądarki
FieldTypeRegistry.debug = true;

// Teraz każda operacja loguje się:
// [REGISTRY] Registering type: text
// [REGISTRY] Creating field of type: text
// [TYPE:text] Validating: "hello"
// [TYPE:text] Validation result: {valid: true}
```

### Sprawdzenie Typów

```javascript
// Czy typ istnieje?
if (FieldTypeRegistry.has('text')) { ... }

// Pobierz wszystkie typy
const allTypes = FieldTypeRegistry.getAll();
Object.keys(allTypes); // ['text', 'textarea', 'select', ...]

// Pobierz defaults
const defaults = FieldTypeRegistry.getDefaults('email');
console.log(defaults);
// { type: 'email', pattern: '...', ... }
```

### Testowanie Custom Typu

```javascript
// 1. Zdefiniuj typ
class MyCustomType extends BaseFieldType {
    static get type() { return 'custom'; }
    // ... implementacja
}

// 2. Zarejestruj
FieldTypeRegistry.register('custom', MyCustomType);

// 3. Testuj
const field = FieldTypeRegistry.createField('custom');
const valid = MyCustomType.validate('test', field);
console.log(valid);

// 4. Renderuj
const html = MyCustomType.renderAdmin(field, 'value');
console.log(html);
```

---

## Performance

### Benchmarking

```javascript
// Ile czasu zajmuje stworzenie pola?
console.time('createField');
for (let i = 0; i < 1000; i++) {
    FieldTypeRegistry.createField('text');
}
console.timeEnd('createField');
// Expected: < 10ms

// Ile czasu zajmuje walidacja?
const field = FieldTypeRegistry.createField('text');
console.time('validate');
for (let i = 0; i < 1000; i++) {
    TextFieldType.validate('test value', field);
}
console.timeEnd('validate');
// Expected: < 5ms
```

### Optymalizacja

1. **Cachowanie typów**

```javascript
// Pobierz raz, użyj wiele razy
const TextType = FieldTypeRegistry.get('text');
// ... use TextType many times

// Nie:
FieldTypeRegistry.get('text').renderAdmin(...); // lookup każdy raz
```

2. **Lazy Loading**

```javascript
// Ładuj custom typy tylko gdy potrzebne
if (window.yapFeatures.includes('video')) {
    wp_enqueue_script('yap-video-field', '...video-type.js');
}
```

---

## FAQ

**P: Mogę edytować built-in typy?**

O: Nie bezpośrednio. Zamiast tego stwórz własny typ:

```javascript
class MyTextType extends TextFieldType {
    static defaults() {
        const base = super.defaults();
        base.max_length = 100; // custom maksimum
        return base;
    }
}

FieldTypeRegistry.register('my-text', MyTextType);
```

---

**P: Jak dodać nowy typ do registry?**

O: Stwórz plik `custom-type.js`:

```javascript
class DateRangeFieldType extends BaseFieldType {
    static get type() { return 'date_range'; }
    // ... implementacja
}

FieldTypeRegistry.register('date_range', DateRangeFieldType);
```

Załaduj w `enqueue.php`:

```php
wp_enqueue_script('yap-date-range-type', 'path/to/custom-type.js', ['yap-field-types']);
```

---

**P: Jaka jest różnica między defaults a settingsSchema?**

O:

- **defaults()** - Zwraca domyślne wartości dla UŻYTKOWNIKA (label, name, min_length)
- **settingsSchema()** - Zwraca formularz KONFIGURACJI dla administratora

```javascript
defaults() → {
    type: 'text',
    label: '',       // ← Użytkownik widzi to
    min_length: 0    // ← Użytkownik konfiguruje to
}

settingsSchema() → [
    { label: 'Ustawienia', fields: [
        { name: 'min_length', label: 'Min Długość', type: 'number' }
    ]}
]
```

---

**P: Czy mogę tworzyć pola bez użycia registry?**

O: Technicznie tak, ale nie powinieneś:

```javascript
// ❌ Nie:
const field = { type: 'text', name: 'test' };

// ✅ Tak:
const field = FieldTypeRegistry.createField('text', { name: 'test' });
// To gwarantuje poprawne defaults i strukturę
```

---

**P: Jak debugować złe renderowanie?**

O:

```javascript
// 1. Sprawdź czy typ istnieje
console.log(FieldTypeRegistry.get('mytype')); // undefined? ❌

// 2. Sprawdź czy field jest poprawny
console.log(field); // Ma wszystkie wymagane pola?

// 3. Renderuj do string i sprawdź
const html = MyType.renderAdmin(field, 'value');
console.log(html); // Czy ma <input> itd?

// 4. Wstaw do DOM i sprawdź w DevTools
document.body.innerHTML = html;
// Czy HTML jest poprawny?
```

---

**P: Jak walidować ustawienia field type'a?**

O:

```javascript
class StrictType extends BaseFieldType {
    static settingsSchema() {
        return [{
            label: 'Settings',
            fields: [{
                name: 'min_length',
                type: 'number',
                validate: (value) => {
                    if (value < 0) {
                        return { valid: false, error: 'Must be positive' };
                    }
                    return { valid: true };
                }
            }]
        }];
    }
}
```

---

## Podsumowanie

| Część | Status | Plik |
|-------|--------|------|
| Registry | ✅ Ready | `registry.js` |
| Built-in Types | ✅ Ready | `field-types.js` |
| Examples | ✅ Ready | `examples.js` |
| Enqueue Integration | ✅ Ready | `enqueue.php` |
| Custom Types Guide | ✅ Ready | `CUSTOM_FIELD_TYPES.md` |
| Migration Path | ✅ Documented | This file |

---

## Następne Kroki

1. ✅ Zintegruj registry z Visual Builderem
2. ✅ Migruj hardcoded typy na registry
3. ✅ Dodaj custom field types
4. ⬜ Dokumentuj custom typy dla team

Happy coding! 🚀
