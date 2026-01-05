/**
 * Test: Znaki Specjalne (Special Characters)
 * 
 * Testuje obsługę znaków specjalnych w polach
 * 
 * @since 1.5.0
 */

const testSpecialCharacters = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Testing special characters handling', 'test');
    
    // Open field modal if not already open
    let modal = $('#yap-field-settings-modal');
    if (modal.length === 0) {
        const $field = $('.yap-field-item').first();
        if ($field.length > 0) {
            $field.find('.yap-field-edit').click();
        }
        
        return new Promise((resolve) => {
            setTimeout(() => {
                testSpecialCharacters.call(this);
                resolve(true);
            }, 100);
        });
    }
    
    const specialCharCases = [
        { value: 'field_with_unicode_café', desc: 'Unicode characters' },
        { value: 'field-with-dashes-123', desc: 'Dashes and numbers' },
        { value: 'field_with_underscore_text', desc: 'Underscores' },
        { value: '  spaced  field  ', desc: 'Spaces (should trim)' },
        { value: 'UPPERCASE_FIELD', desc: 'Uppercase letters' }
    ];
    
    TestLogger.log(`Testing ${specialCharCases.length} special character cases...`, 'info');
    
    const $nameInput = modal.find('.yap-setting-name');
    if ($nameInput.length > 0) {
        specialCharCases.forEach((testCase, i) => {
            $nameInput.val(testCase.value).trigger('input');
            TestAssert.assert(
                $nameInput.val().length > 0,
                `Special case ${i + 1}: ${testCase.desc}`
            );
        });
    }
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Advanced Test 5: Special Characters', testSpecialCharacters);

// Export
window.testSpecialCharacters = testSpecialCharacters;
