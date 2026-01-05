# 🌍 Wielojęzykowość (i18n) - Yet Another Plugin

## ✅ Aktualny Setup

Plugin ma już:
- ✅ `Text Domain: yap` w header
- ✅ `Domain Path: /languages` 
- ✅ `load_plugin_textdomain()` w `yetanotherplugin.php`
- ✅ `__('text', 'yap')` w całym kodzie PHP
- ✅ Folder `/languages` ze szablonami

## 📦 Dostępne Narzędzia

### Opcja 1: Poedit (Desktop App)
**Najłatwiejsze dla początkujących**

1. Pobierz: https://poedit.net/download
2. Otwórz `languages/yap.pot`
3. Tłumacz (lub import istniejącej `.po`)
4. Zapisz jako `yap-pl_PL.po` (PL)
5. Poedit automatycznie tworzy `.mo`

### Opcja 2: Online Translator
**Dla małych projektów**

- https://poeditor.com (FREE tier: 5 projektów)
- https://crowdin.com (open-source friendly)
- https://lokalise.com (30 dni FREE trial)

**Zaletę:** 
- Zespołowe tłumaczenia
- Automatyczne synchronizacje
- Historia zmian

### Opcja 3: WP CLI (Command Line)
**Dla developerów**

```bash
# Zainstaluj WP CLI
wp i18n make-pot /path/to/yap languages/yap.pot

# Kompiluj .po do .mo
wp i18n make-mo languages/yap-pl_PL.po
```

### Opcja 4: npm / Webpack
**Dla zaawansowanych**

```json
{
  "scripts": {
    "i18n:pot": "wp i18n make-pot . languages/yap.pot",
    "i18n:mo": "wp i18n make-mo languages/ --mo-location=languages/"
  }
}
```

## 🎯 Dostępne Języki

Szablon `.pot` jest gotowy dla wszystkich języków!

**Wspierane lokal:**
- `yap-pl_PL.po` / `yap-pl_PL.mo` - Polski
- `yap-de_DE.po` / `yap-de_DE.mo` - Niemiecki
- `yap-fr_FR.po` / `yap-fr_FR.mo` - Francuski
- `yap-es_ES.po` / `yap-es_ES.mo` - Hiszpański
- `yap-en_GB.po` / `yap-en_GB.mo` - Angielski (UK)

Patern: `yap-{lang_COUNTRY}.po` (WordPress standard)

## 🔄 Workflow Tłumaczeń

1. **Developer** dodaje nowy string:
   ```php
   __('New feature', 'yap')
   ```

2. **Regenerujesz .pot:**
   ```bash
   wp i18n make-pot . languages/yap.pot
   ```

3. **Tłumacz** otwiera `.pot` w Poedicie i tłumaczy

4. **Kompiluje się** `.mo` (binarny format dla WordPress)

5. **Upload** na serwer: `/languages/yap-pl_PL.mo`

## 📱 JavaScript Tłumaczenia

Już masz `wp_localize_script()`:

```php
wp_localize_script('yap-visual-builder', 'yapBuilder', [
    'i18n' => [
        'addField' => __('Add Field', 'yap'),
        'editField' => __('Edit Field', 'yap'),
        'deleteField' => __('Delete Field', 'yap'),
    ]
]);
```

W JavaScript:
```javascript
console.log(yapBuilder.i18n.addField); // "Dodaj pole" (jeśli PL)
```

## 🚀 Recommendation

**Dla Twojego projektu:**

1. ✅ **Teraz:** Poedit (desktop) - łatwo, szybko
2. 📈 **Jak rośnie projekt:** Crowdin/Lokalise (wielojęzyczność, zespół)
3. 🤖 **CI/CD:** WP CLI + GitHub Actions (automatycznie)

## 📂 Bieżące Pliki

```
languages/
├── yap.pot           ← Szablon (aktualizuj regularnie!)
├── yap-pl_PL.po      ← Polski tłumacz (edytowalny)
└── yap-pl_PL.mo      ← Binarny (WordPress używa tego)
```

**Który plik edytujesz?** → `.po`
**Który plik WordPress czyta?** → `.mo`

---

**Pytania? Mogę Ci pomóc ustawić Crowdin lub Lokalise!** 🌐
