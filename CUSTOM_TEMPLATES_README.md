## 🎨 YetAnotherPlugin v1.4.6 - Custom Templates System Ready!

Właśnie dodaliśmy **Custom Templates System** - kompleksowy system do tworzenia i ponownego użytku własnych szablonów pól!

### ✨ Co nowego?

#### 🎨 Custom Templates System (v1.0.0)
- **Tworzenie szablonów** - Kliknij "🎨 Stwórz Template" w modalu ustawień pola
- **Drag & Drop** - Przeciąg szablony na kanwę
- **Kategoria Custom** - Nowa sekcja "Custom Templates 🎨" w polu selektora
- **Historia zmian** - Szablony w pełni wspierają undo/redo
- **localStorage** - Szablony zapisywane lokalnie (szybko, bez synku)

### 🚀 Szybki start

#### Krok 1: Tworzenie szablonu
1. Otwórz **Visual Builder**
2. Kliknij na pole w kanwie
3. Kliknij **🎨 Stwórz Template** w modalu
4. Uzupełnij formularz:
   - **Nazwa szablonu** (required)
   - **Etykieta** (required, może mieć emoji!)
   - **Opis** (optional)
5. Kliknij **➕ Stwórz Template**

#### Krok 2: Używanie szablonu
**Opcja A - Drag & Drop (Najszybsza):**
1. Znajdź szablon w "Custom Templates 🎨"
2. Przeciągnij na kanwę
3. Done! Pola pojawią się jako nowa grupa

**Opcja B - Kod:**
```javascript
CustomTemplates.addToSchema('nazwa_szablonu');
YAPBuilder.refreshCanvas();
```

### 📚 Dokumentacja

Pełna dokumentacja: [CUSTOM_TEMPLATES_GUIDE.md](./CUSTOM_TEMPLATES_GUIDE.md)

Changelog: [CUSTOM_TEMPLATES_CHANGELOG.md](./CUSTOM_TEMPLATES_CHANGELOG.md)

### 💡 Przykłady szablonów

#### Szablon: Dane Osobowe 👤
```javascript
CustomTemplates.save('tpl_personal', {
    name: 'personal_data',
    label: '👤 Dane Osobowe',
    fields: [
        { name: 'first_name', label: 'Imię', type: 'text', required: true },
        { name: 'last_name', label: 'Nazwisko', type: 'text', required: true },
        { name: 'email', label: 'Email', type: 'email', required: true }
    ]
});
```

#### Szablon: Adres 📍
```javascript
CustomTemplates.save('tpl_address', {
    name: 'address_group',
    label: '📍 Pełny Adres',
    fields: [
        { name: 'street', label: 'Ulica', type: 'text', required: true },
        { name: 'city', label: 'Miasto', type: 'text', required: true },
        { name: 'postal_code', label: 'Kod pocztowy', type: 'text' }
    ]
});
```

### 🔍 Testowanie

W konsoli F12:
```javascript
// Sprawdź wszystkie szablony
CustomTemplates.getAll();

// Dodaj szablon do kanwy
CustomTemplates.addToSchema('tpl_address');

// Usuń szablon
CustomTemplates.delete('tpl_address');

// Wyświetl ten plik testowy:
// test-custom-templates.php (otwórz go w konsoli)
```

### 🔧 Gdzie szukać plików?

- **JavaScript:** `/includes/js/custom-templates.js` (nowy!)
- **CSS:** `/includes/css/admin/admin-style.css` (dodane style)
- **Dokumentacja:** `CUSTOM_TEMPLATES_GUIDE.md` (nowa)
- **Changelog:** `CUSTOM_TEMPLATES_CHANGELOG.md` (nowy)
- **Testy:** `test-custom-templates.php` (nowy)

### ⚙️ Integracja

System jest zintegrowany z:
- ✅ **Visual Builder** - Full support
- ✅ **Field History** - Undo/Redo działa!
- ✅ **Field Presets** - Komplementarne systemy
- ✅ **Canvas refresh** - Auto-update pola

### 📋 FAQ

**P: Gdzie szablony są przechowywane?**
O: W localStorage przeglądarki (klucz: `yap_custom_templates`)

**P: Czy szablony są serwere?**
O: Nie - są w localStorage (szybko, offline). Future: sync z bazą danych

**P: Czy mogę edytować szablon?**
O: Nie dedykowanej funkcji edit() - usuń i stwórz nowy

**P: Czy szablony są backupowane?**
O: Nie automatycznie. Future: export/import JSON

**P: Limit szablonów?**
O: localStorage limit (~5MB) - typowo ok 100+ szablonów

### 🐛 Troubleshooting

**Szablon nie pojawia się w kategorii:**
```javascript
// Odśwież selektor:
CustomTemplates.refreshFieldSelector();
```

**Pola nie pojawiają się na kanwie:**
```javascript
// Odśwież kanwę:
YAPBuilder.refreshCanvas();
```

**Szablony zniknęły:**
```javascript
// localStorage może być wyczyszczony
// Sprawdź:
localStorage.getItem('yap_custom_templates');

// Jeśli null - szablony zniknęły
```

### 🚀 Next steps

- [ ] Backend storage (zamiast localStorage)
- [ ] Export/import szablonów
- [ ] Share szablonów między użytkownikami
- [ ] Categories dla szablonów
- [ ] Search/filter
- [ ] Clone szablonu

### 📞 Support

Wszystko działa? Sprawdzaj konsolę F12 za błędami!

### 📦 Zmiany w v1.4.6

```
visual-builder.php:
  + Dodano enqueue dla yap-custom-templates (1.0.0)

visual-builder.js (1.4.6):
  + Przycisk "🎨 Stwórz Template" w footer modalu
  + Obsługa CustomTemplates.createFromSelection()

custom-templates.js (NEW):
  + 700+ linii kodu
  + localStorage integration
  + Drag & drop support
  + Modal creation form

admin-style.css:
  + 200+ linii CSS
  + Animacje, responsive, dark mode friendly

Dokumentacja:
  + CUSTOM_TEMPLATES_GUIDE.md
  + CUSTOM_TEMPLATES_CHANGELOG.md
```

---

**Happy templating! 🎨**
