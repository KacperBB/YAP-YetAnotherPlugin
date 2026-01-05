# 🧪 YAP Visual Builder - Testy Edycji Pól

Kompleksowy zestaw testów automatycznych dla Visual Buildera w YetAnotherPlugin.

## 📋 Spis Treści

1. [Szybki Start](#szybki-start)
2. [Testy Podstawowe](#testy-podstawowe)
3. [Testy Zaawansowane](#testy-zaawansowane)
4. [Scenariusze Testowe](#scenariusze-testowe)
5. [Debugowanie](#debugowanie)
6. [API Testów](#api-testów)

---

## 🚀 Szybki Start

### Instalacja

Testy są już zainstalowane w:
```
/includes/js/tests/
├── visual-builder-field-editing.test.js  (testy podstawowe)
├── visual-builder-advanced.test.js       (testy zaawansowane)
└── test-runner.html                      (interfejs uruchomiania)
```

### Uruchamianie

#### Opcja 1: Graficzny Interface (Najłatwiej)

1. Otwórz plik `test-runner.html` w przeglądarce
2. Kliknij przycisk testu którą chcesz uruchomić
3. Obserwuj wyniki w konsoli

#### Opcja 2: Konsola DevTools (F12)

Otwórz tab **Console** w WordPress Admin panelu i uruchom:

```javascript
// Testy podstawowe
YAPBuilderTests.runAll()

// Testy zaawansowane
YAPAdvancedTests.runAll()

// Konkretny test
YAPBuilderTests.testChangeFieldName()
```

---

## 📝 Testy Podstawowe

### Co Testują?

Testy sprawdzają wszystkie podstawowe funkcje edycji pól w Visual Builderze:

| Test | Opis | Status |
|------|------|--------|
| `testOpenFieldSettings()` | Otwieranie modalu edycji | ✓ |
| `testChangeFieldName()` | Zmiana nazwy pola (Field Name) | ✓ |
| `testChangeFieldLabel()` | Zmiana etykiety (Field Label) | ✓ |
| `testChangeFieldPlaceholder()` | Zmiana Placeholder | ✓ |
| `testChangeDefaultValue()` | Zmiana domyślnej wartości | ✓ |
| `testChangeDescription()` | Zmiana opisu/instrukcji | ✓ |
| `testChangeCSSOClass()` | Zmiana CSS Class (zaawansowane) | ✓ |
| `testToggleRequired()` | Zaznaczanie pola wymaganego | ✓ |
| `testTabSwitching()` | Przełączanie między tabami | ✓ |
| `testConditionalLogic()` | Włączenie logiki warunkowej | ✓ |
| `testSaveAndClose()` | Zamknięcie i zapis zmian | ✓ |
| `testCombinedChanges()` | Kombinacja zmian jednocześnie | ✓ |
| `testSubFieldEditing()` | Edycja pól zagnieżdżonych | ✓ |
| `testFieldNameValidation()` | Walidacja nazwy pola | ✓ |
| `testModalClosing()` | Zamknięcie modalu (ESC, overlay) | ✓ |

### Użycie

```javascript
// Uruchom wszystkie testy podstawowe
YAPBuilderTests.runAll()

// Uruchom jeden konkretny test
YAPBuilderTests.testChangeFieldName()
YAPBuilderTests.testToggleRequired()
YAPBuilderTests.testSaveAndClose()
```

### Przykładowe Rezultaty

```
✅ PASS: Modal był dodany do DOM
✅ PASS: Modal ma klasę yap-modal-show (jest widoczny)
✅ PASS: Pole nazwy (Field Name) istnieje
✅ PASS: Wartość pola zmieniona: field_1 → test_field_1704456789
✅ PASS: Etykieta zmieniona: Old Label → Test Label 1704456789
❌ FAIL: Pole CSS Class istnieje
```

---

## 🔄 Testy Zaawansowane

### Scenariusze

#### 1. Kombinacje Podstawowych Opcji
Testuje 5 różnych kombinacji opcji pola:
- Pole tekstowe + wymagane
- Pole z CSS class + domyślną wartością
- Pole wymagane + CSS class + opis
- Pole z domyślną wartością + placeholder
- Pole minimalnie (tylko nazwa i etykieta)

```javascript
YAPAdvancedTests.testBasicCombinations()
```

#### 2. Logika Warunkowa - Operatory
Testuje 6 typów warunków warunkowych:
- `equals` - Pole widoczne gdy inne pole RÓWNE wartości
- `not_equals` - Pole widoczne gdy inne pole NIE RÓWNE
- `contains` - Pole widoczne gdy inne pole zawiera tekst
- `greater_than` - Pole widoczne gdy inne pole WIĘKSZE
- `less_than` - Pole widoczne gdy inne pole MNIEJSZE
- `is_checked` - Pole UKRYTE gdy inne pole zaznaczone

```javascript
YAPAdvancedTests.testConditionalLogicOperators()
```

#### 3. Zagnieżdżone Pola
Testuje edycję pól wewnątrz Group i Repeater fields:
- Zmianę opcji Group field
- Zmianę opcji sub-fields

```javascript
YAPAdvancedTests.testNestedFieldsEditing()
```

#### 4. Modyfikacja i Przywrócenie
Testuje funkcję Cancel - czy wartości przywrócą się do oryginału:
- Zmień wartości
- Kliknij Cancel (nie Save)
- Otwórz ponownie - sprawdzaj czy wartości są oryginalne

```javascript
YAPAdvancedTests.testModifyAndRevert()
```

#### 5. Znaki Specjalne
Testuje obsługę specjalnych wartości:
- Spacje w nazwie pola (niedozwolone)
- Cudzysłowy w etykiecie
- Znaki specjalne w placeholder
- Tagi HTML w opisie
- Wielokrotne klasy CSS

```javascript
YAPAdvancedTests.testSpecialCharacters()
```

#### 6. Szybkie Zmiany Sekwencyjne
Testuje obsługę szybkich zmian bez opóźnień:
- Zmiana nazwy → test1 → test2
- Zmiana etykiety → Label1 → Label2
- Zaznaczenie checkboxa
- Sprawdzenie, czy ostatnie wartości zostały zapisane

```javascript
YAPAdvancedTests.testRapidChanges()
```

### Użycie

```javascript
// Uruchom wszystkie zaawansowane testy
YAPAdvancedTests.runAll()

// Uruchom konkretny scenariusz
YAPAdvancedTests.testBasicCombinations()
YAPAdvancedTests.testConditionalLogicOperators()
YAPAdvancedTests.testNestedFieldsEditing()
```

---

## 🎯 Scenariusze Testowe

### Scenariusz 1: Edycja Pola Flexible Content

```javascript
// Otwórz Visual Builder
// Stwórz pole typu "Flexible Content"

// W konsoli uruchom:
YAPBuilderTests.testOpenFieldSettings()
YAPBuilderTests.testChangeFieldName()
YAPBuilderTests.testChangeFieldLabel()
YAPBuilderTests.testChangeFieldPlaceholder()
YAPBuilderTests.testToggleRequired()
```

**Oczekiwane Rezultaty:**
- ✅ Modal się otwiera
- ✅ Każde pole można zmienić
- ✅ Zmiany się aktualizują na żywo
- ✅ Przycisk Save zapisuje zmiany

### Scenariusz 2: Edycja Pola w Group

```javascript
// Stwórz pole typu "Group"
// Dodaj sub-fields (tekstowe, liczba, itp)

YAPBuilderTests.testSubFieldEditing()
```

**Oczekiwane Rezultaty:**
- ✅ Modal się otwiera dla Group field
- ✅ Modal się otwiera dla każdego sub-field
- ✅ Zmiany sub-fields się zapisują

### Scenariusz 3: Logika Warunkowa

```javascript
// Stwórz 2 pola (np. "conditional_trigger" i "conditional_target")

YAPAdvancedTests.testConditionalLogicOperators()

// Lub ręcznie:
YAPBuilderTests.testConditionalLogic()
```

**Oczekiwane Rezultaty:**
- ✅ Sekcja "Warunki" się pojawia
- ✅ Można włączyć logikę warunkową
- ✅ Pojawia się sekcja reguł warunkowych

### Scenariusz 4: CSS Styling

```javascript
// Uruchom test CSS class
YAPBuilderTests.testChangeCSSOClass()

// Dodaj klasy CSS:
// "custom-field wide-field highlight"
```

**Oczekiwane Rezultaty:**
- ✅ CSS klasy się zapisują
- ✅ Pole w preview wyglądzie zmienia styl
- ✅ Można dodać wielokrotne klasy

### Scenariusz 5: Pole Wymagane

```javascript
YAPBuilderTests.testToggleRequired()

// Zaznacz checkbox "Pole wymagane"
```

**Oczekiwane Rezultaty:**
- ✅ Checkbox zmienia stan
- ✅ Pole w preview wyglądzie pokazuje gwiazdkę (*)
- ✅ W formularzu pole musi być wypełnione

---

## 🐛 Debugowanie

### Jeśli Test Się Nie Powiedzie

#### Krok 1: Sprawdź Konsolę
```javascript
// Otwórz DevTools: F12 → Console

// Powinno być widać:
✅ Test: Otwieranie modalu edycji pola
✅ Istnieje co najmniej jedno pole w builderze
✅ Modal był dodany do DOM
✅ Modal ma klasę yap-modal-show
```

#### Krok 2: Sprawdź Elementy
```javascript
// W konsoli sprawdź czy elementy istnieją:

// Czy modal jest w DOM?
console.log($('#yap-field-settings-modal').length)  // Powinno być 1

// Czy elementy formularza istnieją?
console.log($('.yap-setting-name').length)   // Powinno być > 0
console.log($('.yap-setting-label').length)  // Powinno być > 0
console.log($('.yap-setting-placeholder').length)  // Powinno być > 0
```

#### Krok 3: Sprawdzaj Events
```javascript
// Czy event handler się wywołuje?
// Otwórz modal i zmień wartość w nazwie pola

// W konsoli sprawdzaj logi:
// 📝 Field name changed: field_1 → new_name
```

#### Krok 4: Czysty Reload
```
1. Wciśnij Ctrl+Shift+R (hard refresh)
2. Zamknij Visual Builder tab
3. Otwórz Visual Builder na nowo
4. Spróbuj testu jeszcze raz
```

### Typowe Problemy

**Problem:** Modal nie się otwiera
```
❌ Rozwiązanie:
1. Sprawdź czy Field items istnieją na stronie
2. Sprawdź czy przyciski edycji działają
3. Sprawdzaj błędy w Console pod kątem JS errors
```

**Problem:** Elementy nie są znalezione
```
❌ Rozwiązanie:
1. Sprawdź czy nazwy CSS selectów się zgadzają
2. Sprawdzaj HTML strukturę modalu
3. Czy jQuery jest załadowany?
```

**Problem:** Zmiany się nie zapisują
```
❌ Rozwiązanie:
1. Czy event handlers są bindowane?
2. Czy updateFieldUI() się wywołuje?
3. Sprawdzaj czy schema file jest zapisywalny
```

### Debug Logging

Dodaj więcej logów dla diagnostyki:

```javascript
// W konsoli:
YAPBuilderTests.log('Moja wiadomość', 'info')
YAPBuilderTests.log('Test przeszedł', 'pass')
YAPBuilderTests.log('Test nie przeszedł', 'fail')

// Przykład:
console.log('🔍 DEBUG: Szukam pola', $('.yap-field-item').length)
console.log('🔍 DEBUG: Modal HTML:', $('#yap-field-settings-modal').html())
```

---

## 🔧 API Testów

### YAPBuilderTests Object

#### Właściwości
```javascript
YAPBuilderTests.results       // Array wyników testów
YAPBuilderTests.testCount     // Liczba wszystkich testów
YAPBuilderTests.passCount     // Liczba zdanych testów
YAPBuilderTests.failCount     // Liczba niezdanych testów
```

#### Metody

**Metoda: log(message, type)**
```javascript
YAPBuilderTests.log('Moja wiadomość', 'info')
// Typy: 'info', 'pass', 'fail', 'test'
```

**Metoda: assert(condition, message)**
```javascript
YAPBuilderTests.assert($elem.length > 0, 'Element istnieje')
// Zwraca true jeśli warunek spełniony, false w przeciwnym razie
```

**Metoda: runAll()**
```javascript
// Uruchom wszystkie testy sekwencyjnie
YAPBuilderTests.runAll()
```

**Metoda: printSummary()**
```javascript
// Wydrukuj podsumowanie wyników
YAPBuilderTests.printSummary()
// Wyświetla: ✅ Przeszły: X, ❌ Nie przeszły: Y, Wskaźnik: Z%
```

### Przykład Custom Testu

```javascript
// Utwórz swój test
function myCustomTest() {
    YAPBuilderTests.log('Mój Custom Test', 'test');
    
    const $field = $('.yap-field-item').first();
    YAPBuilderTests.assert($field.length > 0, 'Pole istnieje');
    
    $field.find('.yap-field-edit').click();
    
    setTimeout(() => {
        const modal = $('#yap-field-settings-modal');
        YAPBuilderTests.assert(modal.length > 0, 'Modal się otworzył');
        
        YAPBuilderTests.printSummary();
    }, 100);
}

// Uruchom
myCustomTest()
```

---

## 📊 Raportowanie

### Eksport Wyników

```javascript
// Pobierz wyniki
const results = YAPBuilderTests.results

// Skonwertuj na JSON
const json = JSON.stringify(results, null, 2)

// Wyślij na serwer (opcjonalnie)
fetch('/wp-admin/admin-ajax.php?action=save_test_results', {
    method: 'POST',
    body: JSON.stringify(results)
})
```

### Format Rezultatów

```json
{
    "message": "PASS: Pole nazwy istnieje",
    "type": "pass"
}
```

---

## ✅ Checklist przed Deployem

Przed wdrożeniem Visual Buildera uruchom:

- [ ] `YAPBuilderTests.runAll()` - Wszystkie testy podstawowe
- [ ] `YAPAdvancedTests.runAll()` - Wszystkie testy zaawansowane
- [ ] Ręczna edycja 5+ pól w Visual Builderze
- [ ] Test Save/Cancel (czy zmiany się zapisują)
- [ ] Test Sub-fields (edycja pól w Group)
- [ ] Test Conditional Logic
- [ ] Sprawdzenie Console pod kątem błędów

---

## 📞 Wsparcie

Jeśli test się nie powiedzie:

1. 📋 Zbierz wyniki testów z konsoli
2. 🔍 Sprawdź HTML strukturę modalu
3. 🐛 Szukaj JS errorów w Console
4. 📝 Opisz problem szczegółowo
5. 🆘 Skontaktuj się z supportem

---

## 📝 Historia Zmian

### v1.0 (2024-01-05)
- Dodane 15 testów podstawowych
- Dodane 6 scenariuszy zaawansowanych
- Stworzony interfejs graficzny test-runner.html
- Dokumentacja kompletna

---

**Autor:** YetAnotherPlugin Team  
**Wersja:** 1.0  
**Ostatnia Aktualizacja:** 2024-01-05
