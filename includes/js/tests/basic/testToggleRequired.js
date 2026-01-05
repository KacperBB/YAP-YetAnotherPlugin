/**
 * Test: Przełączanie Pola Wymaganego (Required Field)
 * 
 * Sprawdza czy można przełączać checkbox dla pola wymaganego
 * 
 * @since 1.5.0
 */

const testToggleRequired = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Toggling required field checkbox', 'test');
    
    const modal = $('#yap-field-settings-modal');
    const $requiredCheckbox = modal.find('.yap-setting-required');
    TestAssert.assert($requiredCheckbox.length > 0, 'Required checkbox exists');
    
    if ($requiredCheckbox.length === 0) return Promise.resolve(false);
    
    const initialValue = $requiredCheckbox.is(':checked');
    $requiredCheckbox.click();
    
    const newValue = $requiredCheckbox.is(':checked');
    TestAssert.assert(initialValue !== newValue, `Required state toggled: ${initialValue} → ${newValue}`);
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Test 8: Toggle Required Field', testToggleRequired);

// Export
window.testToggleRequired = testToggleRequired;
