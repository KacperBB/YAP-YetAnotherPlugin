# 🎨 Custom Templates System - Kompletny Przewodnik

## Przegląd

Nowy system **Custom Templates** pozwala użytkownikom na tworzenie i ponowne użycie własnych szablonów pól (grup pól) w Visual Builderze.

### Cechy (v1.1.0)
✅ Tworzenie szablonów z istniejących pól  
✅ **NOWE: Wybór ikony emoji dla szablonu**  
✅ **NOWE: Szybki selektor ikon (40+ emoji)**  
✅ **NOWE: Podgląd ikony w realu**  
✅ Magazyn w localStorage (szybki, brak synku serwera)  
✅ Drag & drop do kanwy  
✅ Kategoria "Custom Templates 🎨" w selektorze pól  
✅ Edycja i usuwanie szablonów  
✅ Historia zmian (undo/redo) dla szablonów  

## Jak używać

### 1️⃣ Tworzenie szablonu z pola

#### Przez UI (Rekomendowane)
1. Otwórz **Visual Builder**
2. Kliknij na pole lub grupę pól w kanwie
3. W modalu ustawień kliknij przycisk **🎨 Stwórz Template**
4. Uzupełnij:
   - **Nazwa szablonu** (wymagane) - wewnętrzna nazwa ID
   - **Ikona** (wymagane) - emoji lub symbol, będzie wyświetlany obok szablonu
     - Możesz wybrać z siatki szybkiego wyboru (40+ ikon)
     - Lub wpisać własne emoji w pole input
     - Live preview pokazuje wybraną ikonę
   - **Etykieta** (wymagane) - nazwa wyświetlana w selektorze (bez emoji)
   - **Opis** (opcjonalnie) - pojawia się w tooltipie
5. Kliknij **➕ Stwórz Template**
6. Template pojawia się w kategorii "Custom Templates 🎨" z Twoją ikoną

#### Przez kod/konsolę
```javascript
// Przygotuj dane pola(ów)
const fieldData = {
    name: 'my_template',
    label: '📋 Mój Custom Template',
    type: 'group',
    description: 'Szybki dostęp do takich pól',
    fields: [
        {
            name: 'first_name',
            label: 'Imię',
            type: 'text',
            required: true
        },
        {
            name: 'last_name',
            label: 'Nazwisko',
            type: 'text',
            required: true
        }
    ]
};

// Stwórz template
CustomTemplates.save('custom_' + Date.now(), fieldData);

// Odśwież selektor pól
CustomTemplates.refreshFieldSelector();
```

### 📍 Dostępne Ikony w Szybkim Selectorze

**Biuro & Narzędzia:**
- 🎨 (paleta) - projektowanie
- 📝 (notatnik) - tekst
- 📋 (clipboard) - listy
- ⚙️ (koła zębate) - ustawienia
- 🛠️ (narzędzia) - naprawa

**Ludzie & Kontakt:**
- 👤 (osoba) - profil
- 📧 (email) - wiadomości
- 📞 (telefon) - kontakt
- 🌐 (świat) - online

**Miejsca:**
- 🏢 (biuro) - firma
- 🏭 (fabryka) - produkcja
- 🏠 (dom) - dom, adres

**Dane & Finanse:**
- 📊 (wykres) - statystyki
- 📈 (trend up) - wzrost
- 📉 (trend down) - spadek
- 💰 (pieniądze) - kwota
- 💳 (karta) - płatność

**Status:**
- ⭐ (gwiazdka) - ocena
- ✅ (haczyk) - gotowe
- ❌ (krzyżyk) - błąd
- ⚠️ (ostrzeżenie) - uwaga
- 🔔 (dzwonok) - powiadomienie

**Technologia:**
- 📱 (telefon) - mobile
- 💻 (laptop) - komputer
- ⌨️ (klawiatura) - wpisywanie
- 🖱️ (mysz) - klikanie

**Logistyka:**
- 📦 (paczka) - przesyłka
- 🚚 (ciężarówka) - dostawa
- 📌 (pinezka) - lokacja
- 🗺️ (mapa) - trasa

**Media:**
- 🎯 (cel) - wideowyjazd
- 🎪 (cyrk) - event
- 🎭 (maska) - entertainment
- 🎬 (kamera) - film

### 2️⃣ Dodawanie szablonu do kanwy

#### Opcja A: Drag & Drop (Najszybciej!)
1. Znajdź szablon w kategorii "Custom Templates 🎨"
2. Przeciągnij na kanwę
3. Pola pojawią się jako nowa grupa

#### Opcja B: Przez kod
```javascript
// Dodaj szablon do schematu
const result = CustomTemplates.addToSchema('custom_1234567890');

// Wynik zawiera:
// - success: true/false
// - field: newly created group field
// - template: templateId
// - fieldCount: number of fields in template

if (result.success) {
    console.log(`Dodano ${result.fieldCount} pól z szablonu!`);
}
```

### 3️⃣ Zarządzanie szablonami

#### Wyświetl wszystkie szablony
```javascript
const allTemplates = CustomTemplates.getAll();
console.log(allTemplates);
// Output: { custom_123: {...}, custom_456: {...} }
```

#### Pobierz pojedynczy szablon
```javascript
const template = CustomTemplates.getTemplate('custom_123');
console.log(template);
// Output: {
//   id: 'custom_123',
//   name: 'my_template',
//   label: '📋 Mój Template',
//   description: 'Opis szablonu',
//   fields: [...],
//   created_at: 1234567890,
//   updated_at: 1234567890
// }
```

#### Usuń szablon
```javascript
CustomTemplates.delete('custom_123');
CustomTemplates.refreshFieldSelector(); // Odśwież UI
```

#### Edytuj szablon
```javascript
// Templates se nie mają dedykowanej funkcji edit()
// Zamiast tego: Usuń stary → Stwórz nowy
CustomTemplates.delete('custom_123');
CustomTemplates.save('custom_456', updatedTemplateData);
CustomTemplates.refreshFieldSelector();
```

## Dane przechowywania

### Lokalizacja: localStorage
- **Klucz:** `yap_custom_templates`
- **Format:** JSON
- **Zakres:** Per domena
- **Vida:** Dopóki user nie wyczyści cache/localStorage

### Struktura szablonu
```json
{
  "custom_1234567890": {
    "id": "custom_1234567890",
    "name": "contact_form",
    "label": "📞 Formularz Kontaktowy",
    "description": "Standardowy formularz do zbierania kontaktów",
    "fields": [
      {
        "name": "email",
        "label": "Email",
        "type": "email",
        "required": true,
        "placeholder": "twoj@email.com"
      },
      {
        "name": "message",
        "label": "Wiadomość",
        "type": "textarea",
        "required": true
      }
    ],
    "created_at": 1234567890,
    "updated_at": 1234567890
  }
}
```

## Integracja z historią

Custom templates są **w pełni zintegrowane** z systemem historii:

```javascript
// Gdy dodasz template, automatycznie:
// 1. Pola są dodane do schematu
// 2. Operacja jest zapisywana w FieldHistory.recordAdd()
// 3. Możesz cofnąć CTRL+Z
// 4. Możesz ponowić CTRL+Y
```

### Historia edytów szablonów
Gdy edytujesz szablon po jego dodaniu:
- Edycje pól są normalne śledzone (jak każde pole)
- Usunięcie szablonu = usunięcie grupy pól (śledzone)

## Przykłady szablonów

### Szablon: Dane Osobowe 👤
```javascript
CustomTemplates.save('tpl_personal_data', {
    name: 'personal_data_group',
    label: '👤 Dane Osobowe',
    description: 'Podstawowe dane kontaktowe',
    fields: [
        { name: 'first_name', label: 'Imię', type: 'text', required: true },
        { name: 'last_name', label: 'Nazwisko', type: 'text', required: true },
        { name: 'email', label: 'Email', type: 'email', required: true },
        { name: 'phone', label: 'Telefon', type: 'text' }
    ]
});
```

### Szablon: Adres 📍
```javascript
CustomTemplates.save('tpl_address', {
    name: 'address_group',
    label: '📍 Pełny Adres',
    description: 'Kompletne dane adresowe',
    fields: [
        { name: 'street', label: 'Ulica', type: 'text', required: true },
        { name: 'city', label: 'Miasto', type: 'text', required: true },
        { name: 'postal_code', label: 'Kod pocztowy', type: 'text' },
        { name: 'country', label: 'Kraj', type: 'select' }
    ]
});
```

### Szablon: Szczegóły Produktu 📦
```javascript
CustomTemplates.save('tpl_product', {
    name: 'product_details',
    label: '📦 Szczegóły Produktu',
    description: 'Informacje o produkcie',
    fields: [
        { name: 'sku', label: 'SKU', type: 'text', required: true },
        { name: 'price', label: 'Cena', type: 'number', required: true },
        { name: 'stock', label: 'Stan magazynu', type: 'number' },
        { name: 'description', label: 'Opis', type: 'textarea' }
    ]
});
```

## Troubleshooting

### ❌ "Custom Templates system nie jest załadowany!"
- Upewnij się, że `yap-custom-templates` script jest załadowany
- Sprawdź: DevTools → Network → czy `custom-templates.js` załadował się?
- Sprawdź konsolę za 404 błędami

### ❌ Template nie pojawia się w kategorii
```javascript
// Ręcznie odśwież UI:
CustomTemplates.refreshFieldSelector();

// Sprawdź czy szablon istnieje:
console.log(CustomTemplates.getTemplate('custom_123'));
```

### ❌ Szablon dodany ale pola nie widać
- Sprawdź konsolę (F12) za błędami
- Spróbuj ręcznie: `YAPBuilder.refreshCanvas()`
- Upewnij się, że FieldHistory jest załadowany

### ❌ localStorage jest pełny
```javascript
// Sprawdź rozmiar:
console.log(localStorage.getItem('yap_custom_templates').length);

// Usuń stare szablony:
CustomTemplates.delete('old_template_id');
CustomTemplates.delete('another_old_id');
```

## API Reference

### CustomTemplates.getAll()
```javascript
Returns: Object<templateId, TemplateData>
Example: { custom_123: {...}, custom_456: {...} }
```

### CustomTemplates.getTemplate(templateId)
```javascript
Returns: TemplateData | null
Parameters:
  - templateId (string): ID szablonu
```

### CustomTemplates.save(templateId, templateData)
```javascript
Parameters:
  - templateId (string): Unikatowy ID
  - templateData (object): { name, label, description?, fields }
Returns: TemplateData (saved object)
```

### CustomTemplates.delete(templateId)
```javascript
Parameters:
  - templateId (string): ID do usunięcia
Returns: void
```

### CustomTemplates.addToSchema(templateId)
```javascript
Parameters:
  - templateId (string): ID szablonu do dodania
Returns: { success, field, template, fieldCount, error? }
```

### CustomTemplates.createFromSelection(fields)
```javascript
Parameters:
  - fields (array): Tablica pól do wstawienia w template
Returns: void (opens modal)
```

### CustomTemplates.showCreationModal(templateId, fieldsToUse)
```javascript
Parameters:
  - templateId (string): ID dla nowego szablonu
  - fieldsToUse (array): Pola do wyświetlenia w modalu
Returns: void (displays modal)
```

### CustomTemplates.refreshFieldSelector()
```javascript
Updates UI to show all templates
Parameters: none
Returns: void
```

## Notatki dla developerów

### Zapamiętywanie w bazie (Future feature)
Aby przenieść szablony na serwer:

```javascript
// 1. Zmodyfikuj CustomTemplates.save():
// Add AJAX call to save w wp_options / custom tabeli

// 2. Zmień retrieve w getAll():
// Fetch z serwera zamiast localStorage

// 3. Rozważ synchronizację:
// Multi-device sync
// Sharing templates między użytkownikami
// Backup/import-export
```

### Bez emoji w labelach
```javascript
// Jeśli system nie wspiera emoji:
const safeLabel = templateData.label.replace(/[^\w\s-]/g, '');
```

### Typowanie (TypeScript - Future)
```typescript
interface CustomTemplate {
    id: string;
    name: string;
    label: string;
    description?: string;
    fields: FieldData[];
    created_at: number;
    updated_at: number;
}

interface TemplateAddResult {
    success: boolean;
    field?: GroupField;
    template?: string;
    fieldCount?: number;
    error?: string;
}
```

## Roadmap

- [ ] Eksport/import szablonów (JSON file)
- [ ] Udostępnianie szablonów między użytkownikami
- [ ] Szablony w bazie danych (zamiast localStorage)
- [ ] Ustawienia szablonów per-group
- [ ] Clone istniejącego szablonu
- [ ] Categories dla szablonów (Personal, Team, Public)
- [ ] Search/filter szablonów

## Zmienności

- **v1.0.0** - Initial release
  - localStorage storage
  - UI modal creation
  - Drag & drop support
  - History integration
  - Comprehensive CSS styling
