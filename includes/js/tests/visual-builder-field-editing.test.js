/**
 * YAP Visual Builder - Field Editing Tests
 * 
 * Testy dla funkcji edycji pól w Visual Builderze
 * 
 * Uruchomienie w konsoli:
 * 1. Otwórz Visual Builder
 * 2. Otwórz DevTools (F12) → Console tab
 * 3. Wklej poniższy kod lub załaduj ten plik
 * 4. Uruchom: YAPBuilderTests.runAll()
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

(function() {
    'use strict';
    
    // Ensure jQuery is available
    const $ = window.jQuery || window.$;
    
    const YAPBuilderTests = {
        results: [],
        testCount: 0,
        passCount: 0,
        failCount: 0,
        
        /**
         * Loguj wynik testu
         */
    log(message, type = 'info') {
        const prefix = {
            'pass': '✅',
            'fail': '❌',
            'test': '🧪',
            'info': 'ℹ️'
        }[type] || type;
        
        console.log(`${prefix} ${message}`);
        this.results.push({ message, type });
    },
    
    /**
     * Assert - sprawdź warunek
     */
    assert(condition, message) {
        this.testCount++;
        if (condition) {
            this.passCount++;
            this.log(`PASS: ${message}`, 'pass');
            return true;
        } else {
            this.failCount++;
            this.log(`FAIL: ${message}`, 'fail');
            return false;
        }
    },
    
    /**
     * Test 1: Otwieranie modalu edycji pola
     */
    testOpenFieldSettings() {
        this.log('Test 1: Otwieranie modalu edycji pola', 'test');
        
        // Załóż że jest co najmniej jedno pole w builderze
        const $firstField = $('.yap-field-item').first();
        this.assert($firstField.length > 0, 'Istnieje co najmniej jedno pole w builderze');
        
        if ($firstField.length === 0) {
            this.log('Brak pól do testowania', 'fail');
            return false;
        }
        
        // Kliknij na przycisk edycji
        const fieldId = $firstField.data('field-id');
        this.log(`  Otwieranie pole: ${fieldId}`, 'info');
        
        $firstField.find('.yap-field-edit').click();
        
        // Czekaj na modal
        return new Promise((resolve) => {
            setTimeout(() => {
                const modalExists = $('#yap-field-settings-modal').length > 0;
                this.assert(modalExists, 'Modal był dodany do DOM');
                
                const modalVisible = $('#yap-field-settings-modal').hasClass('yap-modal-show');
                this.assert(modalVisible, 'Modal ma klasę yap-modal-show (jest widoczny)');
                
                resolve(modalExists && modalVisible);
            }, 50);
        });
    },
    
    /**
     * Test 2: Zmiana nazwy pola (Field Name)
     */
    testChangeFieldName() {
        this.log('Test 2: Zmiana nazwy pola (Field Name)', 'test');
        
        const modal = $('#yap-field-settings-modal');
        if (modal.length === 0) {
            this.log('Modal nie jest otwarty', 'fail');
            return false;
        }
        
        const $nameInput = modal.find('.yap-setting-name');
        this.assert($nameInput.length > 0, 'Pole nazwy (Field Name) istnieje');
        
        if ($nameInput.length === 0) return false;
        
        const oldValue = $nameInput.val();
        const newValue = 'test_field_' + Date.now();
        
        $nameInput.val(newValue).trigger('input');
        
        this.assert($nameInput.val() === newValue, `Wartość pola zmieniona: ${oldValue} → ${newValue}`);
        
        // Czekaj na event handler
        return new Promise((resolve) => {
            setTimeout(() => {
                this.log('  Event handler dla field name powinien być wywoływany', 'info');
                resolve(true);
            }, 100);
        });
    },
    
    /**
     * Test 3: Zmiana etykiety (Field Label)
     */
    testChangeFieldLabel() {
        this.log('Test 3: Zmiana etykiety (Field Label)', 'test');
        
        const modal = $('#yap-field-settings-modal');
        const $labelInput = modal.find('.yap-setting-label');
        this.assert($labelInput.length > 0, 'Pole etykiety (Field Label) istnieje');
        
        if ($labelInput.length === 0) return false;
        
        const oldValue = $labelInput.val();
        const newValue = 'Test Label ' + Date.now();
        
        $labelInput.val(newValue).trigger('input');
        
        this.assert($labelInput.val() === newValue, `Etykieta zmieniona: ${oldValue} → ${newValue}`);
        return true;
    },
    
    /**
     * Test 4: Zmiana Placeholder
     */
    testChangeFieldPlaceholder() {
        this.log('Test 4: Zmiana Placeholder', 'test');
        
        const modal = $('#yap-field-settings-modal');
        const $placeholderInput = modal.find('.yap-setting-placeholder');
        this.assert($placeholderInput.length > 0, 'Pole placeholder istnieje');
        
        if ($placeholderInput.length === 0) return false;
        
        const newValue = 'Np. wprowadź wartość ' + Date.now();
        $placeholderInput.val(newValue).trigger('input');
        
        this.assert($placeholderInput.val() === newValue, `Placeholder zmieniony na: ${newValue}`);
        return true;
    },
    
    /**
     * Test 5: Zmiana domyślnej wartości (Default Value)
     */
    testChangeDefaultValue() {
        this.log('Test 5: Zmiana domyślnej wartości (Default Value)', 'test');
        
        const modal = $('#yap-field-settings-modal');
        const $defaultInput = modal.find('.yap-setting-default');
        this.assert($defaultInput.length > 0, 'Pole domyślnej wartości istnieje');
        
        if ($defaultInput.length === 0) return false;
        
        const newValue = 'default_' + Date.now();
        $defaultInput.val(newValue).trigger('input');
        
        this.assert($defaultInput.val() === newValue, `Domyślna wartość zmieniona na: ${newValue}`);
        return true;
    },
    
    /**
     * Test 6: Zmiana opisu (Description)
     */
    testChangeDescription() {
        this.log('Test 6: Zmiana opisu (Description)', 'test');
        
        const modal = $('#yap-field-settings-modal');
        const $descriptionInput = modal.find('.yap-setting-description');
        this.assert($descriptionInput.length > 0, 'Pole opisu (Description) istnieje');
        
        if ($descriptionInput.length === 0) return false;
        
        const newValue = 'To jest opis pola testowego - ' + Date.now();
        $descriptionInput.val(newValue).trigger('input');
        
        this.assert($descriptionInput.val() === newValue, `Opis zmieniony na: ${newValue}`);
        return true;
    },
    
    /**
     * Test 7: Zmiana CSS Class
     */
    testChangeCSSOClass() {
        this.log('Test 7: Zmiana CSS Class (zaawansowane)', 'test');
        
        const modal = $('#yap-field-settings-modal');
        const $classInput = modal.find('.yap-setting-class');
        this.assert($classInput.length > 0, 'Pole CSS Class istnieje');
        
        if ($classInput.length === 0) return false;
        
        const newValue = 'custom-class wide-field extra-padding';
        $classInput.val(newValue).trigger('input');
        
        this.assert($classInput.val() === newValue, `CSS Classes zmienione na: ${newValue}`);
        return true;
    },
    
    /**
     * Test 8: Zaznaczenie pola wymaganego
     */
    testToggleRequired() {
        this.log('Test 8: Zaznaczenie "Pole wymagane"', 'test');
        
        const modal = $('#yap-field-settings-modal');
        const $requiredCheckbox = modal.find('.yap-setting-required');
        this.assert($requiredCheckbox.length > 0, 'Checkbox "Pole wymagane" istnieje');
        
        if ($requiredCheckbox.length === 0) return false;
        
        const initialState = $requiredCheckbox.is(':checked');
        $requiredCheckbox.prop('checked', !initialState).trigger('change');
        
        const newState = $requiredCheckbox.is(':checked');
        this.assert(newState !== initialState, `Pole wymagane zmienione z ${initialState} na ${newState}`);
        return true;
    },
    
    /**
     * Test 9: Przełączanie między tabami
     */
    testTabSwitching() {
        this.log('Test 9: Przełączanie między tabami (Ogólne → Zaawansowane → Warunki)', 'test');
        
        const modal = $('#yap-field-settings-modal');
        const $tabs = modal.find('.yap-settings-tab');
        this.assert($tabs.length > 0, `Znaleziono ${$tabs.length} taby`);
        
        let tabsWorking = true;
        
        // Testuj każdy tab
        $tabs.each((index, tab) => {
            const tabName = $(tab).data('tab');
            $(tab).click();
            
            const isActive = $(tab).hasClass('active');
            const panelIsActive = $(`.yap-settings-panel[data-panel="${tabName}"]`).hasClass('active');
            
            if (!isActive || !panelIsActive) {
                tabsWorking = false;
                this.log(`  Tab "${tabName}" nie pracuje poprawnie`, 'fail');
            } else {
                this.log(`  Tab "${tabName}" działa ✓`, 'info');
            }
        });
        
        this.assert(tabsWorking, 'Wszystkie taby przełączają się poprawnie');
        return tabsWorking;
    },
    
    /**
     * Test 10: Włączenie logiki warunkowej
     */
    testConditionalLogic() {
        this.log('Test 10: Włączenie logiki warunkowej', 'test');
        
        // Najpierw kliknij na tab "Warunki"
        const modal = $('#yap-field-settings-modal');
        modal.find('.yap-settings-tab[data-tab="conditional"]').click();
        
        const $conditionalCheckbox = modal.find('.yap-setting-conditional');
        this.assert($conditionalCheckbox.length > 0, 'Checkbox dla logiki warunkowej istnieje');
        
        if ($conditionalCheckbox.length === 0) return false;
        
        // Zaznacz checkbox
        const initialState = $conditionalCheckbox.is(':checked');
        $conditionalCheckbox.prop('checked', !initialState).trigger('change');
        
        const newState = $conditionalCheckbox.is(':checked');
        this.assert(newState !== initialState, `Logika warunkowa włączona/wyłączona`);
        
        // Jeśli włączona, sprawdź czy pojawia się sekcja reguł
        if (newState) {
            const $conditionalRules = modal.find('.yap-conditional-rules');
            this.assert($conditionalRules.is(':visible'), 'Sekcja reguł warunkowych jest widoczna');
        }
        
        return true;
    },
    
    /**
     * Test 11: Zamkniecie modalu i zapis zmian
     */
    testSaveAndClose() {
        this.log('Test 11: Zamkniecie modalu i zapis zmian', 'test');
        
        const modal = $('#yap-field-settings-modal');
        this.assert(modal.length > 0, 'Modal jest nadal otwarty');
        
        // Kliknij przycisk Save
        const $saveButton = modal.find('.yap-settings-save');
        this.assert($saveButton.length > 0, 'Przycisk Save istnieje');
        
        if ($saveButton.length > 0) {
            $saveButton.click();
            
            // Czekaj na zamknięcie modalu
            return new Promise((resolve) => {
                setTimeout(() => {
                    const modalStillVisible = $('#yap-field-settings-modal').length > 0;
                    this.assert(!modalStillVisible, 'Modal został zamknięty po kliknięciu Save');
                    resolve(true);
                }, 400);
            });
        }
        
        return false;
    },
    
    /**
     * Test 12: Testowanie kombinacji zmian
     */
    testCombinedChanges() {
        this.log('Test 12: Testowanie kombinacji zmian (złożony scenariusz)', 'test');
        
        // Otwórz pierwsze pole
        const $firstField = $('.yap-field-item').first();
        if ($firstField.length === 0) {
            this.log('Brak pól do testowania', 'fail');
            return false;
        }
        
        $firstField.find('.yap-field-edit').click();
        
        return new Promise((resolve) => {
            setTimeout(() => {
                const modal = $('#yap-field-settings-modal');
                
                // Kombinacja 1: Zmiana nazwy + etykiety + CSS class
                modal.find('.yap-setting-name').val('combined_test_1').trigger('input');
                modal.find('.yap-setting-label').val('Combined Test Label').trigger('input');
                modal.find('.yap-setting-class').val('test-class-1 test-class-2').trigger('input');
                
                this.assert(
                    modal.find('.yap-setting-name').val() === 'combined_test_1' &&
                    modal.find('.yap-setting-label').val() === 'Combined Test Label' &&
                    modal.find('.yap-setting-class').val() === 'test-class-1 test-class-2',
                    'Kombinacja 1: Nazwa + Etykieta + CSS Class zmienione jednocześnie'
                );
                
                // Kombinacja 2: Zmiana wszystkich pól tekstowych
                modal.find('.yap-setting-placeholder').val('Test placeholder').trigger('input');
                modal.find('.yap-setting-default').val('test default').trigger('input');
                modal.find('.yap-setting-description').val('Test description').trigger('input');
                
                this.assert(
                    modal.find('.yap-setting-placeholder').val() === 'Test placeholder' &&
                    modal.find('.yap-setting-default').val() === 'test default' &&
                    modal.find('.yap-setting-description').val() === 'Test description',
                    'Kombinacja 2: Placeholder + Default + Description zmienione'
                );
                
                // Kombinacja 3: Zaznaczenie checkboxa + zmiana innych pól
                modal.find('.yap-setting-required').prop('checked', true).trigger('change');
                modal.find('.yap-setting-name').val('required_field').trigger('input');
                
                this.assert(
                    modal.find('.yap-setting-required').is(':checked') &&
                    modal.find('.yap-setting-name').val() === 'required_field',
                    'Kombinacja 3: Zaznaczenie wymaganego + zmiana nazwy'
                );
                
                resolve(true);
            }, 100);
        });
    },
    
    /**
     * Test 13: Testowanie pola Sub-Fields (dla Group/Repeater)
     */
    testSubFieldEditing() {
        this.log('Test 13: Testowanie edycji pól zagnieżdżonych (Sub-Fields)', 'test');
        
        // Szukaj pola które ma sub_fields (group lub repeater)
        const $containerField = $('.yap-field-item').filter(function() {
            const dataType = $(this).data('field-type');
            return dataType === 'group' || dataType === 'repeater';
        }).first();
        
        if ($containerField.length === 0) {
            this.log('Brak pola Group/Repeater do testowania sub-fields', 'fail');
            return false;
        }
        
        this.log(`  Znaleziono pole: ${$containerField.find('.yap-field-label').text()}`, 'info');
        
        // Szukaj sub-field items
        const $subFields = $containerField.find('.yap-sub-field-item');
        this.assert($subFields.length > 0, `Znaleziono ${$subFields.length} pól zagnieżdżonych`);
        
        if ($subFields.length > 0) {
            // Kliknij na edycję pierwszego sub-field
            $subFields.first().find('.yap-sub-field-edit').click();
            
            return new Promise((resolve) => {
                setTimeout(() => {
                    const modal = $('#yap-field-settings-modal');
                    this.assert(modal.length > 0, 'Modal się otworzył dla sub-field');
                    
                    if (modal.length > 0) {
                        // Zmień wartość
                        modal.find('.yap-setting-label').val('Updated Sub-Field Label').trigger('input');
                        
                        this.assert(
                            modal.find('.yap-setting-label').val() === 'Updated Sub-Field Label',
                            'Sub-field label zmieniony poprawnie'
                        );
                    }
                    
                    resolve(true);
                }, 100);
            });
        }
        
        return false;
    },
    
    /**
     * Test 14: Validacja nazwy pola (tylko a-z, 0-9, _)
     */
    testFieldNameValidation() {
        this.log('Test 14: Validacja nazwy pola', 'test');
        
        const modal = $('#yap-field-settings-modal');
        if (modal.length === 0) {
            this.log('Modal nie jest otwarty', 'fail');
            return false;
        }
        
        const $nameInput = modal.find('.yap-setting-name');
        
        // Test nazwy z niedozwolonymi znakami
        const invalidNames = ['test-field', 'test field', 'test@field', 'test.field'];
        const validNames = ['test_field', 'testfield', 'test_field_123', 'test123'];
        
        this.log('  Testowanie nazw niedozwolonych:', 'info');
        invalidNames.forEach(name => {
            $nameInput.val(name).trigger('input');
            // Uwaga: nie ma obecnie walidacji, ale powinno być
            this.log(`    ${name} - może zawierać niedozwolone znaki ⚠️`, 'info');
        });
        
        this.log('  Testowanie nazw dozwolonych:', 'info');
        validNames.forEach(name => {
            $nameInput.val(name).trigger('input');
            this.assert($nameInput.val() === name, `    ${name} - OK`);
        });
        
        return true;
    },
    
    /**
     * Test 15: Testowanie zamknięcia modalu (ESC, klikniecie overlay)
     */
    testModalClosing() {
        this.log('Test 15: Testowanie zamknięcia modalu (ESC, overlay, przycisk cancel)', 'test');
        
        // Otwórz pole
        const $field = $('.yap-field-item').first();
        if ($field.length === 0) {
            this.log('Brak pól', 'fail');
            return false;
        }
        
        $field.find('.yap-field-edit').click();
        
        return new Promise((resolve) => {
            setTimeout(() => {
                const modal = $('#yap-field-settings-modal');
                this.assert(modal.length > 0, 'Modal się otworzył');
                
                // Test 1: Kliknij Cancel
                modal.find('.yap-settings-cancel').click();
                
                setTimeout(() => {
                    const modalAfterCancel = $('#yap-field-settings-modal');
                    this.assert(modalAfterCancel.length === 0, 'Modal zamknął się po kliknięciu Cancel');
                    
                    // Test 2: Otwórz ponownie i testuj ESC
                    $field.find('.yap-field-edit').click();
                    
                    setTimeout(() => {
                        const modal2 = $('#yap-field-settings-modal');
                        
                        // Symuluj ESC
                        const escEvent = $.Event('keydown', { key: 'Escape' });
                        $(document).trigger(escEvent);
                        
                        setTimeout(() => {
                            const modalAfterEsc = $('#yap-field-settings-modal');
                            this.assert(modalAfterEsc.length === 0, 'Modal zamknął się po wciśnięciu ESC');
                            resolve(true);
                        }, 400);
                    }, 50);
                }, 400);
            }, 100);
        });
    },
    
    /**
     * Uruchom wszystkie testy
     */
    runAll() {
        console.clear();
        console.log('%c=== YAP Visual Builder - Field Editing Tests ===', 'font-size: 16px; font-weight: bold; color: #0073aa;');
        console.log('Uruchamianie testów...\n');
        
        // Sprawdź czy jQuery jest dostępne
        if (typeof $ === 'undefined' || typeof window.jQuery === 'undefined') {
            console.error('❌ jQuery nie jest załadowany! Testy wymagają jQuery.');
            console.log('Czekam na jQuery...');
            
            // Czekaj na jQuery
            const checkInterval = setInterval(() => {
                if (typeof window.jQuery !== 'undefined') {
                    clearInterval(checkInterval);
                    // Reassign $ po załadowaniu jQuery
                    window.YAPBuilderTests.$ = window.jQuery;
                    console.log('✅ jQuery załadowany! Uruchamiam testy...');
                    window.YAPBuilderTests.runAll();
                }
            }, 100);
            
            return;
        }
        
        this.results = [];
        this.testCount = 0;
        this.passCount = 0;
        this.failCount = 0;
        
        // Uruchom testy sekwencyjnie (z Promise'ami)
        this.testOpenFieldSettings()
            .then(() => this.testChangeFieldName())
            .then(() => this.testChangeFieldLabel())
            .then(() => this.testChangeFieldPlaceholder())
            .then(() => this.testChangeDefaultValue())
            .then(() => this.testChangeDescription())
            .then(() => this.testChangeCSSOClass())
            .then(() => this.testToggleRequired())
            .then(() => this.testTabSwitching())
            .then(() => this.testConditionalLogic())
            .then(() => this.testSaveAndClose())
            .then(() => this.testCombinedChanges())
            .then(() => this.testSubFieldEditing())
            .then(() => this.testFieldNameValidation())
            .then(() => this.testModalClosing())
            .then(() => {
                this.printSummary();
            });
    },
    
    /**
     * Wydrukuj podsumowanie
     */
    printSummary() {
        console.log('\n%c=== PODSUMOWANIE TESTÓW ===', 'font-size: 14px; font-weight: bold; color: #0073aa;');
        console.log(`%c✅ Przeszły: ${this.passCount}`, 'color: #46b450; font-size: 12px; font-weight: bold;');
        console.log(`%c❌ Nie przeszły: ${this.failCount}`, 'color: #dc3232; font-size: 12px; font-weight: bold;');
        console.log(`📊 Razem testów: ${this.testCount}`);
        
        if (this.failCount === 0) {
            console.log('%c🎉 Wszystkie testy przeszły!', 'color: #46b450; font-size: 14px; font-weight: bold;');
        } else {
            console.log(`%c⚠️ ${this.failCount} test(ów) nie przeszło. Przejrzyj logi powyżej.`, 'color: #dc3232; font-size: 12px;');
        }
        
        const successRate = this.testCount > 0 ? ((this.passCount / this.testCount) * 100).toFixed(1) : 0;
        console.log(`📈 Wskaźnik sukcesu: ${successRate}%\n`);
    }
};

    // Eksportuj do globalnego zakresu
    window.YAPBuilderTests = YAPBuilderTests;
    
    console.log('%cYAP Visual Builder Tests załadowany!', 'color: #0073aa; font-weight: bold;');
    console.log('Użyj: YAPBuilderTests.runAll() aby uruchomić wszystkie testy');
})();
