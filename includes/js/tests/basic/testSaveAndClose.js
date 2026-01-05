/**
 * Test: Zapis i Zamknięcie Modalu (Save & Close)
 * 
 * Sprawdza czy można zapisać i zamknąć modal
 * 
 * @since 1.5.0
 */

const testSaveAndClose = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Saving and closing modal', 'test');
    
    const modal = $('#yap-field-settings-modal');
    const $saveBtn = modal.find('.yap-settings-save');
    TestAssert.assert($saveBtn.length > 0, 'Save button exists');
    
    if ($saveBtn.length === 0) return Promise.resolve(false);
    
    $saveBtn.click();
    
    return new Promise((resolve) => {
        setTimeout(() => {
            const modalClosed = $('#yap-field-settings-modal').length === 0 || 
                              !$('#yap-field-settings-modal').hasClass('yap-modal-show');
            TestAssert.assert(modalClosed, 'Modal closed after save');
            resolve(true);
        }, 100);
    });
};

// Register test
TestRunner.register('Test 11: Save and Close Modal', testSaveAndClose);

// Export
window.testSaveAndClose = testSaveAndClose;
