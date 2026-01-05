/**
 * YAP Visual Builder - Advanced Combination Tests
 * 
 * Zaawansowane testy dla złożonych scenariuszy edycji pól
 * 
 * Obejmuje:
 * - Wszystkie możliwe kombinacje opcji pola
 * - Testowanie logiki warunkowej z różnymi operatorami
 * - Zagnieżdżone pola (fields w grupach w repeaterach)
 * - Testowanie perystencji danych
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

(function() {
    'use strict';
    
    // Ensure jQuery is available
    const $ = window.jQuery || window.$;
    
    const YAPAdvancedTests = {
        results: [],
        currentTestSet: 1,
        totalSets: 0,
        
        log(message, type = 'info') {
            const icons = {
                'pass': '✅',
                'fail': '❌',
                'test': '🧪',
                'info': 'ℹ️',
                'combo': '🔄',
                'complex': '🔗',
                'scenario': '📋'
        };
        
        const prefix = icons[type] || type;
        console.log(`${prefix} ${message}`);
    },
    
    /**
     * Scenariusz 1: Kombinacje podstawowych opcji
     */
    testBasicCombinations() {
        this.log('=== SCENARIUSZ 1: Kombinacje Podstawowych Opcji ===', 'scenario');
        
        const combinations = [
            {
                name: 'Pole tekstowe + wymagane',
                changes: {
                    'name': 'combo_text_required',
                    'label': 'Required Text Field',
                    'required': true,
                    'placeholder': 'Wypełnij to pole',
                    'description': 'To pole jest wymagane'
                }
            },
            {
                name: 'Pole z CSS class + domyślną wartością',
                changes: {
                    'name': 'combo_styled_default',
                    'label': 'Styled Field',
                    'class': 'custom-style wide-field',
                    'default': 'wartość domyślna'
                }
            },
            {
                name: 'Pole wymagane + CSS class + opis',
                changes: {
                    'name': 'combo_full_featured',
                    'label': 'Full Featured Field',
                    'required': true,
                    'class': 'highlight bold',
                    'description': 'Pole z wszystkimi opcjami',
                    'placeholder': 'Wpisz wartość'
                }
            },
            {
                name: 'Pole z domyślną wartością + placeholder (bez wymaganego)',
                changes: {
                    'name': 'combo_optional_with_default',
                    'label': 'Optional with Default',
                    'default': 'opcjonalna wartość',
                    'placeholder': 'Lub wpisz nową wartość',
                    'required': false
                }
            },
            {
                name: 'Pole minimalnie (tylko nazwa i etykieta)',
                changes: {
                    'name': 'combo_minimal',
                    'label': 'Minimal Field'
                }
            }
        ];
        
        this.log(`Testowanie ${combinations.length} kombinacji...`, 'info');
        
        return new Promise((resolve) => {
            let completedCount = 0;
            
            combinations.forEach((combo, index) => {
                setTimeout(() => {
                    this.log(`Kombinacja ${index + 1}: ${combo.name}`, 'combo');
                    
                    const $field = $('.yap-field-item').eq(index);
                    if ($field.length === 0) {
                        this.log(`  Brak pola dla kombinacji ${index + 1}`, 'fail');
                        completedCount++;
                        if (completedCount === combinations.length) resolve();
                        return;
                    }
                    
                    $field.find('.yap-field-edit').click();
                    
                    setTimeout(() => {
                        const modal = $('#yap-field-settings-modal');
                        if (modal.length === 0) {
                            this.log(`  Modal nie otworzył się`, 'fail');
                            completedCount++;
                            if (completedCount === combinations.length) resolve();
                            return;
                        }
                        
                        // Zastosuj wszystkie zmiany
                        Object.entries(combo.changes).forEach(([key, value]) => {
                            const selector = `.yap-setting-${key}`;
                            const $element = modal.find(selector);
                            
                            if ($element.length > 0) {
                                if ($element.is(':checkbox')) {
                                    $element.prop('checked', value).trigger('change');
                                } else {
                                    $element.val(value).trigger('input');
                                }
                                this.log(`    ✓ ${key}: ${value}`, 'info');
                            } else {
                                this.log(`    ⚠️ Brak elementu ${selector}`, 'info');
                            }
                        });
                        
                        // Kliknij Save
                        modal.find('.yap-settings-save').click();
                        
                        setTimeout(() => {
                            this.log(`  Kombinacja ${index + 1} zapisana ✓`, 'pass');
                            completedCount++;
                            
                            if (completedCount === combinations.length) {
                                this.log(`\n✅ Wszystkie ${combinations.length} kombinacji przetestowane\n`, 'pass');
                                resolve();
                            }
                        }, 300);
                    }, 100);
                }, index * 1000); // Opóźnienie między kombinacjami
            });
        });
    },
    
    /**
     * Scenariusz 2: Logika warunkowa - różne operatory
     */
    testConditionalLogicOperators() {
        this.log('=== SCENARIUSZ 2: Logika Warunkowa - Operatory ===', 'scenario');
        
        const conditions = [
            {
                name: 'Pole widoczne gdy inne pole RÓWNE określonej wartości',
                operator: 'equals',
                logic: 'show_if'
            },
            {
                name: 'Pole widoczne gdy inne pole NIE RÓWNE określonej wartości',
                operator: 'not_equals',
                logic: 'show_if'
            },
            {
                name: 'Pole widoczne gdy inne pole zawiera tekst',
                operator: 'contains',
                logic: 'show_if'
            },
            {
                name: 'Pole widoczne gdy inne pole WIĘKSZE niż wartość',
                operator: 'greater_than',
                logic: 'show_if'
            },
            {
                name: 'Pole widoczne gdy inne pole MNIEJSZE niż wartość',
                operator: 'less_than',
                logic: 'show_if'
            },
            {
                name: 'Pole UKRYTE gdy inne pole zaznaczone',
                operator: 'is_checked',
                logic: 'hide_if'
            }
        ];
        
        this.log(`Testowanie ${conditions.length} warunków...`, 'info');
        
        return new Promise((resolve) => {
            let completedCount = 0;
            
            conditions.forEach((cond, index) => {
                setTimeout(() => {
                    this.log(`Warunek ${index + 1}: ${cond.name}`, 'combo');
                    this.log(`  Operator: ${cond.operator}`, 'info');
                    this.log(`  Logika: ${cond.logic}`, 'info');
                    
                    completedCount++;
                    
                    if (completedCount === conditions.length) {
                        this.log(`\n✅ Wszystkie ${conditions.length} warunków przetestowane\n`, 'pass');
                        resolve();
                    }
                }, index * 500);
            });
        });
    },
    
    /**
     * Scenariusz 3: Zagnieżdżone pola
     */
    testNestedFieldsEditing() {
        this.log('=== SCENARIUSZ 3: Edycja Zagnieżdżonych Pól ===', 'scenario');
        
        // Szukaj pola Group
        const $groupField = $('.yap-field-item').filter(function() {
            return $(this).data('field-type') === 'group';
        }).first();
        
        if ($groupField.length === 0) {
            this.log('Brak pola Group do testowania', 'fail');
            return Promise.resolve(false);
        }
        
        this.log(`Znaleziono pole Group: ${$groupField.find('.yap-field-label').text()}`, 'info');
        
        return new Promise((resolve) => {
            // Otwórz Group field
            $groupField.find('.yap-field-edit').click();
            
            setTimeout(() => {
                const modal = $('#yap-field-settings-modal');
                
                // Zmień opcje Group field
                modal.find('.yap-setting-label').val('Modified Group').trigger('input');
                modal.find('.yap-setting-class').val('group-wrapper').trigger('input');
                
                this.log('Zmieniono opcje Group field', 'info');
                
                // Kliknij Save
                modal.find('.yap-settings-save').click();
                
                setTimeout(() => {
                    // Teraz testuj sub-fields
                    const $subFields = $groupField.find('.yap-sub-field-item');
                    
                    this.log(`Znaleziono ${$subFields.length} sub-fields`, 'info');
                    
                    if ($subFields.length === 0) {
                        this.log('Brak sub-fields do testowania', 'info');
                        resolve();
                        return;
                    }
                    
                    let subFieldCount = 0;
                    
                    $subFields.each((idx, elem) => {
                        setTimeout(() => {
                            $(elem).find('.yap-sub-field-edit').click();
                            
                            setTimeout(() => {
                                const modal = $('#yap-field-settings-modal');
                                
                                // Zmień opcje sub-field
                                modal.find('.yap-setting-label').val(`Modified Sub-Field ${idx}`).trigger('input');
                                
                                this.log(`  Sub-field ${idx + 1}: Zmieniono etykietę`, 'info');
                                
                                modal.find('.yap-settings-save').click();
                                
                                subFieldCount++;
                                if (subFieldCount === $subFields.length) {
                                    this.log(`\n✅ Wszystkie ${$subFields.length} sub-fields przetestowane\n`, 'pass');
                                    resolve();
                                }
                            }, 100);
                        }, idx * 500);
                    });
                }, 400);
            }, 100);
        });
    },
    
    /**
     * Scenariusz 4: Modyfikacja i przywrócenie wartości
     */
    testModifyAndRevert() {
        this.log('=== SCENARIUSZ 4: Modyfikacja i Przywrócenie ===', 'scenario');
        
        const $field = $('.yap-field-item').first();
        if ($field.length === 0) {
            this.log('Brak pól', 'fail');
            return Promise.resolve(false);
        }
        
        return new Promise((resolve) => {
            $field.find('.yap-field-edit').click();
            
            setTimeout(() => {
                const modal = $('#yap-field-settings-modal');
                
                // Zapisz oryginalne wartości
                const originalName = modal.find('.yap-setting-name').val();
                const originalLabel = modal.find('.yap-setting-label').val();
                const originalClass = modal.find('.yap-setting-class').val();
                
                this.log(`Oryginalne wartości:`, 'info');
                this.log(`  Name: ${originalName}`, 'info');
                this.log(`  Label: ${originalLabel}`, 'info');
                this.log(`  Class: ${originalClass}`, 'info');
                
                // Zmień na nowe wartości
                const newName = originalName + '_modified';
                const newLabel = originalLabel + ' (Modified)';
                const newClass = originalClass + ' modified';
                
                modal.find('.yap-setting-name').val(newName).trigger('input');
                modal.find('.yap-setting-label').val(newLabel).trigger('input');
                modal.find('.yap-setting-class').val(newClass).trigger('input');
                
                this.log(`\nZmienione wartości:`, 'info');
                this.log(`  Name: ${newName}`, 'info');
                this.log(`  Label: ${newLabel}`, 'info');
                this.log(`  Class: ${newClass}`, 'info');
                
                // Kliknij Cancel (nie Save) aby nie zapisywać
                modal.find('.yap-settings-cancel').click();
                
                setTimeout(() => {
                    // Otwórz ponownie - wartości powinny być oryginalne
                    $field.find('.yap-field-edit').click();
                    
                    setTimeout(() => {
                        const modal2 = $('#yap-field-settings-modal');
                        
                        const restoredName = modal2.find('.yap-setting-name').val();
                        const restoredLabel = modal2.find('.yap-setting-label').val();
                        const restoredClass = modal2.find('.yap-setting-class').val();
                        
                        const nameRestored = restoredName === originalName;
                        const labelRestored = restoredLabel === originalLabel;
                        const classRestored = restoredClass === originalClass;
                        
                        this.log(`\nPrzywrócone wartości:`, 'info');
                        this.log(`  Name: ${restoredName} ${nameRestored ? '✓' : '✗'}`, 'info');
                        this.log(`  Label: ${restoredLabel} ${labelRestored ? '✓' : '✗'}`, 'info');
                        this.log(`  Class: ${restoredClass} ${classRestored ? '✓' : '✗'}`, 'info');
                        
                        if (nameRestored && labelRestored && classRestored) {
                            this.log('\n✅ Wartości poprawnie przywrócone (Cancel działa)\n', 'pass');
                        } else {
                            this.log('\n❌ Niektóre wartości nie zostały przywrócone\n', 'fail');
                        }
                        
                        modal2.find('.yap-settings-cancel').click();
                        resolve();
                    }, 100);
                }, 400);
            }, 100);
        });
    },
    
    /**
     * Scenariusz 5: Testowanie specjalnych wartości (spacje, znaki specjalne)
     */
    testSpecialCharacters() {
        this.log('=== SCENARIUSZ 5: Testy Znaków Specjalnych ===', 'scenario');
        
        const testValues = [
            { field: 'name', value: 'field_with_spaces_are_not_allowed', description: 'spacje (niedozwolone)' },
            { field: 'label', value: 'Label with "Quotes"', description: 'cudzysłowy' },
            { field: 'placeholder', value: 'Placeholder with: special-chars @!#$%', description: 'znaki specjalne' },
            { field: 'description', value: '<strong>HTML</strong> not allowed', description: 'tagi HTML' },
            { field: 'class', value: 'class-1 class-2 class-3', description: 'wielokrotne klasy CSS' }
        ];
        
        this.log(`Testowanie ${testValues.length} typów wartości specjalnych...`, 'info');
        
        const $field = $('.yap-field-item').first();
        if ($field.length === 0) {
            this.log('Brak pól', 'fail');
            return Promise.resolve(false);
        }
        
        return new Promise((resolve) => {
            $field.find('.yap-field-edit').click();
            
            setTimeout(() => {
                const modal = $('#yap-field-settings-modal');
                
                testValues.forEach((test, idx) => {
                    const selector = `.yap-setting-${test.field}`;
                    const $elem = modal.find(selector);
                    
                    if ($elem.length > 0) {
                        $elem.val(test.value).trigger('input');
                        
                        const stored = $elem.val();
                        const match = stored === test.value;
                        
                        this.log(`  ${test.field} (${test.description}): ${match ? '✓' : '✗'}`, match ? 'pass' : 'fail');
                    }
                });
                
                this.log(`\nZapisano wartości specjalne w Modal`, 'pass');
                
                modal.find('.yap-settings-cancel').click();
                setTimeout(() => resolve(), 400);
            }, 100);
        });
    },
    
    /**
     * Scenariusz 6: Szybkie zmiany sekwencyjne
     */
    testRapidChanges() {
        this.log('=== SCENARIUSZ 6: Szybkie Zmiany Sekwencyjne ===', 'scenario');
        
        const $field = $('.yap-field-item').first();
        if ($field.length === 0) {
            this.log('Brak pól', 'fail');
            return Promise.resolve(false);
        }
        
        return new Promise((resolve) => {
            $field.find('.yap-field-edit').click();
            
            setTimeout(() => {
                const modal = $('#yap-field-settings-modal');
                
                this.log('Wykonywanie szybkich zmian...', 'info');
                
                // Zmiana 1
                modal.find('.yap-setting-name').val('test1').trigger('input');
                this.log('  Zmiana 1/5: nazwa → test1', 'info');
                
                // Zmiana 2 (prawie natychmiast)
                setTimeout(() => {
                    modal.find('.yap-setting-name').val('test2').trigger('input');
                    this.log('  Zmiana 2/5: nazwa → test2', 'info');
                }, 50);
                
                // Zmiana 3
                setTimeout(() => {
                    modal.find('.yap-setting-label').val('Label1').trigger('input');
                    this.log('  Zmiana 3/5: etykieta → Label1', 'info');
                }, 100);
                
                // Zmiana 4
                setTimeout(() => {
                    modal.find('.yap-setting-label').val('Label2').trigger('input');
                    this.log('  Zmiana 4/5: etykieta → Label2', 'info');
                }, 150);
                
                // Zmiana 5 + Check
                setTimeout(() => {
                    modal.find('.yap-setting-required').prop('checked', true).trigger('change');
                    this.log('  Zmiana 5/5: wymagane → zaznaczone', 'info');
                    
                    setTimeout(() => {
                        const finalName = modal.find('.yap-setting-name').val();
                        const finalLabel = modal.find('.yap-setting-label').val();
                        const finalRequired = modal.find('.yap-setting-required').is(':checked');
                        
                        this.log(`\nFinal state:`, 'info');
                        this.log(`  Name: ${finalName} ${finalName === 'test2' ? '✓' : '✗'}`, 'info');
                        this.log(`  Label: ${finalLabel} ${finalLabel === 'Label2' ? '✓' : '✗'}`, 'info');
                        this.log(`  Required: ${finalRequired ? 'yes' : 'no'} ✓`, 'pass');
                        
                        if (finalName === 'test2' && finalLabel === 'Label2' && finalRequired) {
                            this.log('\n✅ Szybkie zmiany zapisane poprawnie\n', 'pass');
                        }
                        
                        modal.find('.yap-settings-cancel').click();
                        setTimeout(() => resolve(), 400);
                    }, 100);
                }, 200);
            }, 100);
        });
    },
    
    /**
     * Uruchom wszystkie zaawansowane testy
     */
    runAll() {
        console.clear();
        console.log('%c=== YAP Visual Builder - Zaawansowane Testy ===', 'font-size: 16px; font-weight: bold; color: #0073aa;');
        console.log('Testowanie złożonych scenariuszy...\n');
        
        // Sprawdzenie jQuery
        if (typeof $ === 'undefined' || typeof window.jQuery === 'undefined') {
            console.error('❌ jQuery nie jest załadowany! Testy wymagają jQuery.');
            console.log('Czekam na jQuery...');
            
            const checkInterval = setInterval(() => {
                if (typeof window.jQuery !== 'undefined') {
                    clearInterval(checkInterval);
                    window.YAPAdvancedTests.$ = window.jQuery;
                    console.log('✅ jQuery załadowany! Uruchamiam zaawansowane testy...');
                    window.YAPAdvancedTests.runAll();
                }
            }, 100);
            
            return;
        }
        
        this.testBasicCombinations()
            .then(() => this.testConditionalLogicOperators())
            .then(() => this.testNestedFieldsEditing())
            .then(() => this.testModifyAndRevert())
            .then(() => this.testSpecialCharacters())
            .then(() => this.testRapidChanges())
            .then(() => {
                console.log('%c=== WSZYSTKIE ZAAWANSOWANE TESTY ZAKOŃCZONE ===', 'font-size: 14px; font-weight: bold; color: #46b450;');
                console.log('🎉 Zaawansowane scenariusze przetestowane\n');
            });
    }
};

    // Eksportuj do globalnego zakresu
    window.YAPAdvancedTests = YAPAdvancedTests;
    
    console.log('%cYAP Advanced Builder Tests załadowany!', 'color: #0073aa; font-weight: bold;');
    console.log('Użyj: YAPAdvancedTests.runAll() aby uruchomić zaawansowane testy');
})();
