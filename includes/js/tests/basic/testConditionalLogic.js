/**
 * Test: Logika Warunkowa (Conditional Logic)
 * 
 * Sprawdza czy można ustawić logikę warunkową dla pola
 * 
 * @since 1.5.0
 */

const testConditionalLogic = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Setting conditional logic', 'test');
    
    const modal = $('#yap-field-settings-modal');
    const $conditionToggle = modal.find('.yap-setting-conditional');
    TestAssert.assert($conditionToggle.length > 0, 'Conditional logic toggle exists');
    
    if ($conditionToggle.length === 0) {
        TestLogger.log('Conditional logic not available', 'info');
        return Promise.resolve(true);
    }
    
    // Enable conditional logic
    if (!$conditionToggle.is(':checked')) {
        $conditionToggle.prop('checked', true).trigger('change');
    }
    
    TestAssert.assert($conditionToggle.is(':checked'), 'Conditional logic enabled');
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Test 10: Conditional Logic', testConditionalLogic);

// Export
window.testConditionalLogic = testConditionalLogic;
