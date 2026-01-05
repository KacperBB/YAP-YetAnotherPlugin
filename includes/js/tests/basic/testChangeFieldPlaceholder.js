/**
 * Test: Zmiana Placeholder
 * 
 * Sprawdza czy można zmienić placeholder pola w modale edycji
 * 
 * @since 1.5.0
 */

const testChangeFieldPlaceholder = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Changing field placeholder', 'test');
    
    const modal = $('#yap-field-settings-modal');
    const $placeholderInput = modal.find('.yap-setting-placeholder');
    TestAssert.assert($placeholderInput.length > 0, 'Field Placeholder input exists');
    
    if ($placeholderInput.length === 0) return Promise.resolve(false);
    
    const oldValue = $placeholderInput.val();
    const newValue = 'Test Placeholder ' + Date.now();
    
    $placeholderInput.val(newValue).trigger('input');
    
    TestAssert.assert($placeholderInput.val() === newValue, `Placeholder changed: ${oldValue} → ${newValue}`);
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Test 4: Change Field Placeholder', testChangeFieldPlaceholder);

// Export
window.testChangeFieldPlaceholder = testChangeFieldPlaceholder;
