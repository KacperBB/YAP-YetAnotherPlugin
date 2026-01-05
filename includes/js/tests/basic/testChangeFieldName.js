/**
 * Test: Zmiana nazwy pola (Field Name)
 * 
 * Sprawdza czy można zmienić nazwę pola w modale edycji
 * 
 * @since 1.5.0
 */

const testChangeFieldName = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Changing field name', 'test');
    
    const modal = $('#yap-field-settings-modal');
    if (modal.length === 0) {
        TestLogger.log('Modal not open', 'fail');
        TestAssert.failCount++;
        return Promise.resolve(false);
    }
    
    const $nameInput = modal.find('.yap-setting-name');
    TestAssert.assert($nameInput.length > 0, 'Field Name input exists');
    
    if ($nameInput.length === 0) return Promise.resolve(false);
    
    const oldValue = $nameInput.val();
    const newValue = 'test_field_' + Date.now();
    
    $nameInput.val(newValue).trigger('input');
    
    TestAssert.assert($nameInput.val() === newValue, `Field name changed: ${oldValue} → ${newValue}`);
    
    // Wait for event handler
    return new Promise((resolve) => {
        setTimeout(() => {
            TestLogger.log('Event handler should be triggered for field name', 'info');
            resolve(true);
        }, 100);
    });
};

// Register test
TestRunner.register('Test 2: Change Field Name', testChangeFieldName);

// Export
window.testChangeFieldName = testChangeFieldName;
