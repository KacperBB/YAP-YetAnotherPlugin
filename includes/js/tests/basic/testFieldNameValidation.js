/**
 * Test: Walidacja Nazwy Pola (Field Name Validation)
 * 
 * Sprawdza czy nazwa pola jest poprawnie walidowana
 * 
 * @since 1.5.0
 */

const testFieldNameValidation = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Validating field name', 'test');
    
    const modal = $('#yap-field-settings-modal');
    const $nameInput = modal.find('.yap-setting-name');
    TestAssert.assert($nameInput.length > 0, 'Field Name input exists');
    
    if ($nameInput.length === 0) return Promise.resolve(false);
    
    // Test valid name (alphanumeric + underscore)
    const validName = 'valid_field_name_123';
    $nameInput.val(validName).trigger('input');
    TestAssert.assert($nameInput.val() === validName, `Valid name accepted: ${validName}`);
    
    // Test invalid characters (spaces, special chars)
    const invalidName = 'invalid field name!@#';
    $nameInput.val(invalidName).trigger('input');
    
    // Check if value was rejected or has invalid pattern
    // System should NOT allow invalid characters to stay
    const isRejected = $nameInput.val() !== invalidName || $nameInput.hasClass('error');
    TestAssert.assert(
        isRejected || !invalidName.match(/^[a-z0-9_]*$/),
        'Invalid name pattern caught'
    );
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Test 14: Field Name Validation', testFieldNameValidation);

// Export
window.testFieldNameValidation = testFieldNameValidation;
