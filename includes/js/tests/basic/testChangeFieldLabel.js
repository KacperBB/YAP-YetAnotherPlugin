/**
 * Test: Zmiana etykiety (Field Label)
 * 
 * Sprawdza czy można zmienić etykietę pola w modale edycji
 * 
 * @since 1.5.0
 */

const testChangeFieldLabel = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Changing field label', 'test');
    
    const modal = $('#yap-field-settings-modal');
    const $labelInput = modal.find('.yap-setting-label');
    TestAssert.assert($labelInput.length > 0, 'Field Label input exists');
    
    if ($labelInput.length === 0) return Promise.resolve(false);
    
    const oldValue = $labelInput.val();
    const newValue = 'Test Label ' + Date.now();
    
    $labelInput.val(newValue).trigger('input');
    
    TestAssert.assert($labelInput.val() === newValue, `Field label changed: ${oldValue} → ${newValue}`);
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Test 3: Change Field Label', testChangeFieldLabel);

// Export
window.testChangeFieldLabel = testChangeFieldLabel;
