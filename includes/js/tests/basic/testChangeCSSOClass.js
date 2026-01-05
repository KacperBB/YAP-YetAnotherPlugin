/**
 * Test: Zmiana CSS Class
 * 
 * Sprawdza czy można zmienić klasy CSS pola
 * 
 * @since 1.5.0
 */

const testChangeCSSOClass = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Changing field CSS class', 'test');
    
    const modal = $('#yap-field-settings-modal');
    const $cssInput = modal.find('.yap-setting-class');
    TestAssert.assert($cssInput.length > 0, 'CSS Class input exists');
    
    if ($cssInput.length === 0) return Promise.resolve(false);
    
    const oldValue = $cssInput.val();
    const newValue = 'test-class-' + Date.now();
    
    $cssInput.val(newValue).trigger('input');
    
    TestAssert.assert($cssInput.val() === newValue, `CSS class changed: ${oldValue} → ${newValue}`);
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Test 7: Change CSS Class', testChangeCSSOClass);

// Export
window.testChangeCSSOClass = testChangeCSSOClass;
