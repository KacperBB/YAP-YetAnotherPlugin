/**
 * Test: Zmiana Wielu Opcji Jednocześnie (Combined Changes)
 * 
 * Sprawdza czy można zmienić wiele opcji pola w jednym czasie
 * 
 * @since 1.5.0
 */

const testCombinedChanges = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Making combined field changes', 'test');
    
    const modal = $('#yap-field-settings-modal');
    
    // Change name
    const $nameInput = modal.find('.yap-setting-name');
    const newName = 'combined_' + Date.now();
    if ($nameInput.length > 0) {
        $nameInput.val(newName).trigger('input');
        TestAssert.assert($nameInput.val() === newName, `Name changed to ${newName}`);
    }
    
    // Change label
    const $labelInput = modal.find('.yap-setting-label');
    const newLabel = 'Combined Label';
    if ($labelInput.length > 0) {
        $labelInput.val(newLabel).trigger('input');
        TestAssert.assert($labelInput.val() === newLabel, `Label changed to ${newLabel}`);
    }
    
    // Change placeholder
    const $placeholderInput = modal.find('.yap-setting-placeholder');
    const newPlaceholder = 'Combined Placeholder';
    if ($placeholderInput.length > 0) {
        $placeholderInput.val(newPlaceholder).trigger('input');
        TestAssert.assert($placeholderInput.val() === newPlaceholder, `Placeholder changed to ${newPlaceholder}`);
    }
    
    // Toggle required
    const $requiredCheckbox = modal.find('.yap-setting-required');
    if ($requiredCheckbox.length > 0) {
        const beforeToggle = $requiredCheckbox.is(':checked');
        $requiredCheckbox.click();
        const afterToggle = $requiredCheckbox.is(':checked');
        TestAssert.assert(beforeToggle !== afterToggle, 'Required field toggled');
    }
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Test 12: Combined Changes', testCombinedChanges);

// Export
window.testCombinedChanges = testCombinedChanges;
