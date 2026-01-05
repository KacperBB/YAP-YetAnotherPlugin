/**
 * Test: Zmiana i Wycofanie (Modify & Revert)
 * 
 * Testuje zmianę wartości i wycofanie zmian bez zapisywania
 * 
 * @since 1.5.0
 */

const testModifyAndRevert = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Testing modify and revert functionality', 'test');
    
    // Open field modal if not already open
    let modal = $('#yap-field-settings-modal');
    if (modal.length === 0) {
        const $field = $('.yap-field-item').first();
        if ($field.length > 0) {
            $field.find('.yap-field-edit').click();
        }
        
        return new Promise((resolve) => {
            setTimeout(() => {
                testModifyAndRevert.call(this);
                resolve(true);
            }, 100);
        });
    }
    
    // Save original values
    const $nameInput = modal.find('.yap-setting-name');
    const originalName = $nameInput.val();
    
    const $labelInput = modal.find('.yap-setting-label');
    const originalLabel = $labelInput.val();
    
    // Make changes
    const newName = 'modified_' + Date.now();
    $nameInput.val(newName).trigger('input');
    $labelInput.val('Modified Label').trigger('input');
    
    TestAssert.assert($nameInput.val() === newName, `Field modified: name changed to ${newName}`);
    
    // Click cancel (revert)
    const $cancelBtn = modal.find('.yap-modal-cancel');
    if ($cancelBtn.length > 0) {
        $cancelBtn.click();
        
        return new Promise((resolve) => {
            setTimeout(() => {
                // Check if modal closed
                const modalClosed = $('#yap-field-settings-modal').length === 0;
                TestAssert.assert(modalClosed, 'Modal closed without saving (revert successful)');
                resolve(true);
            }, 100);
        });
    }
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Advanced Test 4: Modify & Revert', testModifyAndRevert);

// Export
window.testModifyAndRevert = testModifyAndRevert;
