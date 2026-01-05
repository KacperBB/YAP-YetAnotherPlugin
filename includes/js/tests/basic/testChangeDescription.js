/**
 * Test: Zmiana Opisu (Description)
 * 
 * Sprawdza czy można zmienić opis pola
 * 
 * @since 1.5.0
 */

const testChangeDescription = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Changing field description', 'test');
    
    const modal = $('#yap-field-settings-modal');
    const $descriptionInput = modal.find('.yap-setting-description');
    TestAssert.assert($descriptionInput.length > 0, 'Description input exists');
    
    if ($descriptionInput.length === 0) return Promise.resolve(false);
    
    const oldValue = $descriptionInput.val();
    const newValue = 'Test Description ' + Date.now();
    
    $descriptionInput.val(newValue).trigger('input');
    
    TestAssert.assert($descriptionInput.val() === newValue, `Description changed: ${oldValue} → ${newValue}`);
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Test 6: Change Description', testChangeDescription);

// Export
window.testChangeDescription = testChangeDescription;
