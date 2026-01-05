/**
 * Test: Zmiana Wartości Domyślnej (Default Value)
 * 
 * Sprawdza czy można zmienić wartość domyślną pola
 * 
 * @since 1.5.0
 */

const testChangeDefaultValue = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Changing field default value', 'test');
    
    const modal = $('#yap-field-settings-modal');
    const $defaultInput = modal.find('.yap-setting-default');
    TestAssert.assert($defaultInput.length > 0, 'Default Value input exists');
    
    if ($defaultInput.length === 0) return Promise.resolve(false);
    
    const oldValue = $defaultInput.val();
    const newValue = 'Default ' + Date.now();
    
    $defaultInput.val(newValue).trigger('input');
    
    TestAssert.assert($defaultInput.val() === newValue, `Default value changed: ${oldValue} → ${newValue}`);
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Test 5: Change Default Value', testChangeDefaultValue);

// Export
window.testChangeDefaultValue = testChangeDefaultValue;
