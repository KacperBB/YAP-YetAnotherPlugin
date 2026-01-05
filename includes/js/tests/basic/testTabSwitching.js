/**
 * Test: Przełączanie Zakładek (Tab Switching)
 * 
 * Sprawdza czy można przełączać między zakładkami w modale
 * 
 * @since 1.5.0
 */

const testTabSwitching = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Switching modal tabs', 'test');
    
    const modal = $('#yap-field-settings-modal');
    const $tabs = modal.find('.yap-settings-tab');
    TestAssert.assert($tabs.length > 0, 'Modal tabs exist');
    
    if ($tabs.length === 0) return Promise.resolve(false);
    
    // Click second tab
    const $secondTab = $tabs.eq(1);
    if ($secondTab.length === 0) {
        TestLogger.log('Only one tab available', 'info');
        return Promise.resolve(true);
    }
    
    $secondTab.click();
    
    return new Promise((resolve) => {
        setTimeout(() => {
            const isActive = $secondTab.hasClass('active');
            TestAssert.assert(isActive, 'Second tab is active after click');
            resolve(true);
        }, 50);
    });
};

// Register test
TestRunner.register('Test 9: Tab Switching', testTabSwitching);

// Export
window.testTabSwitching = testTabSwitching;
