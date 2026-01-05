/**
 * Test: Otwieranie modalu edycji pola
 * 
 * Sprawdza czy modal ustawień pola się otworzy po kliknięciu przycisku edycji
 * 
 * @since 1.5.0
 */

const testOpenFieldSettings = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Opening field settings modal', 'test');
    
    // Check if there's at least one field
    const $firstField = $('.yap-field-item').first();
    TestAssert.assert($firstField.length > 0, 'Field exists in builder');
    
    if ($firstField.length === 0) {
        TestLogger.log('No fields to test', 'fail');
        return Promise.resolve(false);
    }
    
    // Click edit button
    const fieldId = $firstField.data('field-id');
    TestLogger.log(`Opening field: ${fieldId}`, 'info');
    
    $firstField.find('.yap-field-edit').click();
    
    // Wait for modal to appear
    return new Promise((resolve) => {
        setTimeout(() => {
            const modalExists = $('#yap-field-settings-modal').length > 0;
            TestAssert.assert(modalExists, 'Modal added to DOM');
            
            const modalVisible = $('#yap-field-settings-modal').hasClass('yap-modal-show');
            TestAssert.assert(modalVisible, 'Modal has yap-modal-show class (visible)');
            
            resolve(modalExists && modalVisible);
        }, 50);
    });
};

// Register test
TestRunner.register('Test 1: Open Field Settings Modal', testOpenFieldSettings);

// Export
window.testOpenFieldSettings = testOpenFieldSettings;
