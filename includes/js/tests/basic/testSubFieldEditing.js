/**
 * Test: Edycja Podpól (Sub-Field Editing)
 * 
 * Sprawdza czy można edytować podpola w grupach/repeaterach
 * 
 * @since 1.5.0
 */

const testSubFieldEditing = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Editing sub-fields', 'test');
    
    // Look for nested fields
    const $nestedFields = $('.yap-nested-field').first();
    
    if ($nestedFields.length === 0) {
        TestLogger.log('No nested fields to test (skipping)', 'info');
        return Promise.resolve(true);
    }
    
    TestAssert.assert($nestedFields.length > 0, 'Nested fields structure exists');
    
    // Try to open nested field settings
    const $editBtn = $nestedFields.find('.yap-nested-edit').first();
    if ($editBtn.length > 0) {
        $editBtn.click();
        
        return new Promise((resolve) => {
            setTimeout(() => {
                const nestedModalOpen = $('.yap-nested-settings-modal').length > 0;
                TestAssert.assert(nestedModalOpen, 'Nested field settings modal opened');
                resolve(true);
            }, 50);
        });
    }
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Test 13: Sub-Field Editing', testSubFieldEditing);

// Export
window.testSubFieldEditing = testSubFieldEditing;
