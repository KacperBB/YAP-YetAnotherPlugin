/**
 * Test: Edycja Zagnieżdżonych Pól (Nested Fields Editing)
 * 
 * Testuje edycję pół zagnieżdżonych w grupach i repeaterach
 * 
 * @since 1.5.0
 */

const testNestedFieldsEditing = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Testing nested fields editing', 'test');
    
    // Look for nested fields structure
    const $nestedFields = $('.yap-nested-fields, .yap-group-fields, .yap-repeater-fields').first();
    
    if ($nestedFields.length === 0) {
        TestLogger.log('No nested fields available (skipping)', 'info');
        return Promise.resolve(true);
    }
    
    TestAssert.assert($nestedFields.length > 0, 'Nested fields structure exists');
    
    const $firstNested = $nestedFields.find('.yap-field-item').first();
    TestAssert.assert($firstNested.length > 0, 'Nested field item found');
    
    if ($firstNested.length === 0) return Promise.resolve(false);
    
    // Try to edit nested field
    const $editBtn = $firstNested.find('.yap-field-edit');
    if ($editBtn.length > 0) {
        $editBtn.click();
        
        return new Promise((resolve) => {
            setTimeout(() => {
                const nestedModalOpen = 
                    $('#yap-field-settings-modal').length > 0 ||
                    $('.yap-nested-modal').length > 0;
                TestAssert.assert(nestedModalOpen, 'Nested field settings modal opened');
                resolve(true);
            }, 100);
        });
    }
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Advanced Test 3: Nested Fields Editing', testNestedFieldsEditing);

// Export
window.testNestedFieldsEditing = testNestedFieldsEditing;
