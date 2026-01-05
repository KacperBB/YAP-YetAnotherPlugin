# 🧪 Jak Załadować Testy - Instrukcja

## Problem: `YAPBuilderTests is not defined`

Błąd pojawia się gdy skrypty testów nie są załadowane w DOM.

---

## ✅ Rozwiązanie 1: Załaduj w Visual Builderze (NAJŁATWIEJ)

1. **Otwórz Visual Builder**
   ```
   WordPress Admin → YetAnotherPlugin → 🎨 Visual Builder
   ```

2. **Otwórz DevTools**
   ```
   Naciśnij: F12
   ```

3. **Przejdź do Console**
   ```
   Kliknij: Console tab
   ```

4. **Uruchom testy**
   ```javascript
   YAPBuilderTests.runAll()
   ```

   lub zaawansowane:
   ```javascript
   YAPAdvancedTests.runAll()
   ```

---

## ✅ Rozwiązanie 2: Auto-loader z Konsoli

1. **Otwórz DevTools**
   ```
   F12 → Console
   ```

2. **Wklej i uruchom loader**
   ```javascript
   // Załaduj testy automatycznie
   (function() {
       const files = [
           'test-config.js',
           'visual-builder-field-editing.test.js',
           'visual-builder-advanced.test.js'
       ];
       
       const url = '/wp-content/plugins/YetAnotherPlugin/includes/js/tests/';
       
       let loaded = 0;
       files.forEach((file, i) => {
           const script = document.createElement('script');
           script.src = url + file;
           script.onload = () => {
               loaded++;
               console.log(`✅ ${file}`);
               if (loaded === files.length) {
                   console.log('✨ All tests loaded! Run: YAPBuilderTests.runAll()');
               }
           };
           setTimeout(() => document.head.appendChild(script), i * 100);
       });
   })();
   ```

3. **Po załadowaniu, uruchom testy**
   ```javascript
   YAPBuilderTests.runAll()
   ```

---

## ✅ Rozwiązanie 3: Kliknij Przycisk w test-runner.html

1. **Otwórz plik HTML**
   ```
   /wp-content/plugins/YetAnotherPlugin/includes/js/tests/test-runner.html
   ```

2. **Kliknij przycisk**
   ```
   "Uruchom Testy Podstawowe"
   lub
   "Uruchom Testy Zaawansowane"
   ```

3. **Obserwuj wyniki**

---

## 🔍 Debugowanie

### Jeśli testy nie się załadują:

**Sprawdź czy jQuery jest dostępny:**
```javascript
typeof jQuery
// Powinno zwrócić: "function"
```

**Sprawdź czy pliki testów istnieją:**
```
/wp-content/plugins/YetAnotherPlugin/includes/js/tests/
├── test-config.js ✓
├── visual-builder-field-editing.test.js ✓
└── visual-builder-advanced.test.js ✓
```

**Sprawdź Network tab (F12 → Network):**
- Czy pliki się ładują?
- Czy status 200 OK?
- Czy nie ma błędów?

**Sprawdzaj logi w Console:**
```
✅ Loaded: test-config.js
✅ Loaded: visual-builder-field-editing.test.js
✅ Loaded: visual-builder-advanced.test.js
✨ All tests loaded!
```

---

## 📋 Polecenia Testów

Gdy testy są załadowane:

```javascript
// Testy Podstawowe - 15 testów
YAPBuilderTests.runAll()

// Testy Zaawansowane - 6 scenariuszy
YAPAdvancedTests.runAll()

// Konkretny test
YAPBuilderTests.testChangeFieldName()
YAPBuilderTests.testToggleRequired()
YAPBuilderTests.testSubFieldEditing()

// Zaawansowany scenariusz
YAPAdvancedTests.testBasicCombinations()
YAPAdvancedTests.testConditionalLogicOperators()
```

---

## 📊 Wyniki

Po uruchomieniu zobaczysz w Console:

```
✅ PASS: Istnieje co najmniej jedno pole w builderze
✅ PASS: Modal był dodany do DOM
✅ PASS: Modal ma klasę yap-modal-show
❌ FAIL: Pole CSS Class istnieje (jeśli brak)

📈 Wskaźnik sukcesu: 87%
🎉 Większość testów przeszła!
```

---

## 🆘 Jeśli Nic Nie Działa

1. **Przładuj stronę**
   ```
   Ctrl+Shift+R (hard refresh)
   ```

2. **Przejdź do Visual Buildera**
   ```
   WordPress Admin → Visual Builder
   ```

3. **Otwórz Console (F12)**

4. **Wklej auto-loader** (patrz Rozwiązanie 2)

5. **Sprawdzaj logi** czy coś się ładuje

6. **Jeśli błąd jQuery** - sprawdź czy jQuery jest załadowany

---

## 💡 Szybka Refernecja

| Zadanie | Polecenie |
|---------|----------|
| Załaduj testy | Otwórz Visual Builder → F12 → testy się załadują |
| Uruchom testy | `YAPBuilderTests.runAll()` |
| Test zmiana nazwy | `YAPBuilderTests.testChangeFieldName()` |
| Kombinacje opcji | `YAPAdvancedTests.testBasicCombinations()` |
| Wyczyść konsolę | `console.clear()` |
| Sprawdź Status | `console.log(typeof YAPBuilderTests)` |

---

## 📂 Pliki

```
/includes/js/tests/
├── test-config.js ← Konfiguracja
├── test-loader.js ← Helper do załadowania
├── visual-builder-field-editing.test.js ← 15 testów
├── visual-builder-advanced.test.js ← 6 scenariuszy
├── test-runner.html ← GUI interfejs
└── README.md ← Pełna dokumentacja
```

---

**Gotowe!** Teraz testy powinny się załadować i pracować poprawnie 🚀
