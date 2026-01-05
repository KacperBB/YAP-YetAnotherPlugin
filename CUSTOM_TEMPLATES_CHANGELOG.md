# 🎨 Custom Templates System - Changelog v1.4.5

## ✨ Nowe Funkcjonalności

### Custom Templates System (v1.0.0)
- ✅ **Tworzenie szablonów** - Przycisk "🎨 Stwórz Template" w modalu ustawień pola
- ✅ **Magazyn w localStorage** - Szybkie, trwałe przechowywanie szablonów
- ✅ **Drag & Drop** - Przeciąganie szablonów z selektora pól na kanwę
- ✅ **Nowa kategoria** - "Custom Templates 🎨" w polu selektora typu
- ✅ **Historia zmian** - Szablony integrują się z undo/redo systemem
- ✅ **Modal tworzenia** - Użytkownik może wpisać nazwę, etykietę i opis
- ✅ **API konsoli** - `CustomTemplates.addToSchema()`, `CustomTemplates.save()`, etc.
- ✅ **CSS styling** - Nowoczesny wygląd, animacje, responsywne

### Integracja z Visual Builderem
- ✅ Skrypt `custom-templates.js` załadowany na `yap-visual-builder-ext`
- ✅ Zależy od: jQuery, YAPBuilder, FieldStabilization, FieldHistory
- ✅ Przycisk "Create Template" w modalu ustawień (footer)
- ✅ Obsługa drag & drop dla custom szablonów

## 📝 Zmodyfikowane pliki

### `/includes/visual-builder.php` (v1.4.5)
- ✅ Dodano enqueue dla `yap-custom-templates` (1.0.0)
- ✅ Zależy od: jquery, yap-visual-builder, yap-field-stabilization, yap-field-history

### `/includes/js/visual-builder.js` (v1.4.5)
- ✅ Dodano przycisk "🎨 Stwórz Template" w footer modalu (linia ~1054)
- ✅ Dodano obsługę kliknięcia `.yap-create-template` (linia ~1101)
- ✅ Zbiera dane pola i otwiera CustomTemplates.createFromSelection()
- ✅ Obsługuje zarówno pojedyncze pola jak i grupy pól

### `/includes/css/admin/admin-style.css`
- ✅ Dodano 200+ linii CSS dla custom templates
- ✅ Style dla:
  - `.yap-custom-template` - Item w selektorze
  - `.yap-custom-template-modal` - Modal tworzenia
  - `.yap-template-form` - Formularz
  - `.yap-template-fields-list` - Lista wybranych pól
  - `.yap-create-template` - Przycisk w modalu
  - Drag-over, hover, focus, animation states

### `/includes/js/custom-templates.js` (NEW - v1.0.0)
- ✅ Całkowicie nowy plik (700+ linii)
- ✅ CustomTemplates.getAll() - Pobierz wszystkie szablony
- ✅ CustomTemplates.getTemplate(id) - Pobierz pojedynczy
- ✅ CustomTemplates.save(id, data) - Zapisz/aktualizuj
- ✅ CustomTemplates.delete(id) - Usuń
- ✅ CustomTemplates.addToSchema(templateId) - Dodaj do kanwy
- ✅ CustomTemplates.createFromSelection(fields) - Otwórz modal
- ✅ CustomTemplates.showCreationModal(id, fields) - Pokaz modal
- ✅ CustomTemplates.refreshFieldSelector() - Odśwież UI
- ✅ CustomTemplates.bindCustomTemplateDragHandlers() - Drag & drop
- ✅ localStorage integration
- ✅ FieldHistory integration
- ✅ YAPBuilder.refreshCanvas() integration

## 🚀 Jak to działa

### Przepływ: Tworzenie szablonu
1. User klika na pole w kanwie
2. Otwiera się modal ustawień pola
3. User klika "🎨 Stwórz Template"
4. Otwiera się modal tworzenia szablonu
5. User wpisuje: Nazwę, Etykietę, Opis (opcja)
6. User klika "➕ Stwórz Template"
7. Szablon zapisywany w localStorage
8. UI odświeża - pojawia się w "Custom Templates 🎨"

### Przepływ: Dodawanie szablonu
1. User widzi "Custom Templates 🎨" w polu selektora
2. **Opcja A:** Przeciąga szablon na kanwę (drag & drop)
3. **Opcja B:** Calls `CustomTemplates.addToSchema('template_id')`
4. Pola są dodawane jako nowa grupa
5. Historia rejestruje operację (undo/redo działa!)

## 🔧 Kod przykłady

### Stwórz template programistycznie
```javascript
CustomTemplates.save('tpl_address', {
    name: 'address_group',
    label: '📍 Pełny Adres',
    description: 'Kompletne dane adresowe',
    fields: [
        { name: 'street', label: 'Ulica', type: 'text', required: true },
        { name: 'city', label: 'Miasto', type: 'text', required: true }
    ]
});

CustomTemplates.refreshFieldSelector();
```

### Dodaj template do kanwy
```javascript
const result = CustomTemplates.addToSchema('tpl_address');
if (result.success) {
    console.log(`Added ${result.fieldCount} fields!`);
}
```

### Wyświetl wszystkie
```javascript
const all = CustomTemplates.getAll();
console.table(all);
```

## 📊 Struktura localStorage

```json
{
  "yap_custom_templates": {
    "custom_1234567890": {
      "id": "custom_1234567890",
      "name": "address_group",
      "label": "📍 Pełny Adres",
      "description": "...",
      "fields": [...],
      "created_at": 1234567890,
      "updated_at": 1234567890
    }
  }
}
```

## ✅ Testy i walidacja

### Walidacja poprawnie działa:
- ✅ Nazwa szablonu (required)
- ✅ Etykieta szablonu (required)
- ✅ Opis szablonu (optional)
- ✅ Pola są kopiowane z właściwymi ID
- ✅ FieldStabilization.generateShortId() generuje unikatowe ID
- ✅ Historia rejestruje dodanie/usunięcie szablonów

### Integracja z systemami:
- ✅ FieldHistory.recordAdd() - Operations są śledzone
- ✅ YAPBuilder.refreshCanvas() - Canvas się odświeża
- ✅ yapBuilder.fieldTypes - Ikony pól są dostępne
- ✅ FieldStabilization - ID są unikatowe

## 🎯 Future enhancements

- [ ] Eksport/import szablonów (JSON)
- [ ] Szablony w bazie danych (zamiast localStorage)
- [ ] Share szablonów między użytkownikami
- [ ] Categories/tags dla szablonów
- [ ] Clone istniejącego szablonu
- [ ] Search/filter szablonów
- [ ] Limit rozmiar localStorage warning

## 📚 Dokumentacja

Pełna dokumentacja dostępna w: [CUSTOM_TEMPLATES_GUIDE.md](./CUSTOM_TEMPLATES_GUIDE.md)

## 🐛 Znane problemy

Brak znanych problemów w v1.0.0

## 💬 Feedback

Jeśli masz problemy lub sugestie:
1. Sprawdź konsolę (F12) za błędami
2. Zweryfikuj czy custom-templates.js załadował
3. Spróbuj refreshCanvas() ręcznie
