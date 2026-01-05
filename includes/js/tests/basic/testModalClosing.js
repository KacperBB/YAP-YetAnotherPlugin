/**
 * Test: Zamknięcie Modalu (Modal Closing)
 * 
 * Sprawdza czy modal można zamknąć poprzez ESC, overlay lub cancel button
 * 
 * @since 1.5.0
 */

const testModalClosing = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Testing modal closing', 'test');
    
    const modal = $('#yap-field-settings-modal');
    if (modal.length === 0) {
        TestLogger.log('Modal not open, opening new one', 'info');
        
        // Open a field modal
        const $field = $('.yap-field-item').first();
        if ($field.length > 0) {
            $field.find('.yap-field-edit').click();
        }
        
        return new Promise((resolve) => {
            setTimeout(() => {
                testModalClosing.call(this);
                resolve(true);
            }, 100);
        });
    }
    
    // Test cancel button
    const $cancelBtn = modal.find('.yap-settings-cancel');
    if ($cancelBtn.length > 0) {
        TestAssert.assert(true, 'Cancel button exists');
        $cancelBtn.click();
        
        return new Promise((resolve) => {
            setTimeout(() => {
                const closed = $('#yap-field-settings-modal').length === 0;
                TestAssert.assert(closed, 'Modal closed via cancel button');
                resolve(true);
            }, 350);
        });
    }
    
    // Test ESC key - need to send proper keydown event
    return new Promise((resolve) => {
        const escapeEvent = new KeyboardEvent('keydown', {
            key: 'Escape',
            code: 'Escape',
            keyCode: 27,
            which: 27,
            bubbles: true
        });
        document.dispatchEvent(escapeEvent);
        
        setTimeout(() => {
            const closed = $('#yap-field-settings-modal').length === 0 || !$('#yap-field-settings-modal').hasClass('yap-modal-show');
            TestAssert.assert(closed, 'Modal closed via ESC key');
            resolve(true);
        }, 100);
    });
    
    return new Promise((resolve) => {
        setTimeout(() => {
            const closed = $('#yap-field-settings-modal').length === 0 ||
                          !$('#yap-field-settings-modal').hasClass('yap-modal-show');
            TestAssert.assert(closed, 'Modal closed via ESC key');
            resolve(true);
        }, 50);
    });
};

// Register test
TestRunner.register('Test 15: Modal Closing', testModalClosing);

// Export
window.testModalClosing = testModalClosing;
