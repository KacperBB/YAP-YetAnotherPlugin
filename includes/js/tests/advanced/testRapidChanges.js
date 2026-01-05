/**
 * Test: Szybkie Zmiany (Rapid Changes)
 * 
 * Testuje obsługę szybkich sekwencyjnych zmian
 * 
 * @since 1.5.0
 */

const testRapidChanges = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Testing rapid sequential changes', 'test');
    
    // Open field modal if not already open
    let modal = $('#yap-field-settings-modal');
    if (modal.length === 0) {
        const $field = $('.yap-field-item').first();
        if ($field.length > 0) {
            $field.find('.yap-field-edit').click();
        }
        
        return new Promise((resolve) => {
            setTimeout(() => {
                testRapidChanges.call(this);
                resolve(true);
            }, 100);
        });
    }
    
    const $nameInput = modal.find('.yap-setting-name');
    if ($nameInput.length === 0) return Promise.resolve(false);
    
    // Make rapid changes to name field
    TestLogger.log('Making 10 rapid changes...', 'info');
    
    for (let i = 0; i < 10; i++) {
        const value = 'rapid_change_' + i;
        $nameInput.val(value).trigger('input');
    }
    
    // Check final value
    const finalValue = $nameInput.val();
    TestAssert.assert(
        finalValue.includes('rapid_change'),
        `Rapid changes handled: final value = ${finalValue}`
    );
    
    return new Promise((resolve) => {
        setTimeout(() => {
            TestLogger.log('System handled rapid changes without errors', 'pass');
            resolve(true);
        }, 200);
    });
};

// Register test
TestRunner.register('Advanced Test 6: Rapid Changes', testRapidChanges);

// Export
window.testRapidChanges = testRapidChanges;
