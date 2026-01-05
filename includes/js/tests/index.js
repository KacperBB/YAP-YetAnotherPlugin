/**
 * YAP Tests - Main Index
 * Loads all test files and initializes the test runner
 * 
 * @since 1.5.0
 */

(function() {
    'use strict';
    
    // Ensure jQuery (don't declare here - tests define their own)
    if (!window.jQuery && !window.$) {
        console.error('jQuery required for tests');
        return;
    }
    
    // Test files to load
    const testFiles = [
        // Utils
        '/includes/js/tests/utils/logger.js',
        '/includes/js/tests/utils/asserts.js',
        '/includes/js/tests/utils/TestRunner.js',
        '/includes/js/tests/utils/report-generator.js',
        
        // Basic tests
        '/includes/js/tests/basic/testOpenFieldSettings.js',
        '/includes/js/tests/basic/testChangeFieldName.js',
        '/includes/js/tests/basic/testChangeFieldLabel.js',
        '/includes/js/tests/basic/testChangeFieldPlaceholder.js',
        '/includes/js/tests/basic/testChangeDefaultValue.js',
        '/includes/js/tests/basic/testChangeDescription.js',
        '/includes/js/tests/basic/testChangeCSSOClass.js',
        '/includes/js/tests/basic/testToggleRequired.js',
        '/includes/js/tests/basic/testTabSwitching.js',
        '/includes/js/tests/basic/testConditionalLogic.js',
        '/includes/js/tests/basic/testSaveAndClose.js',
        '/includes/js/tests/basic/testCombinedChanges.js',
        '/includes/js/tests/basic/testSubFieldEditing.js',
        '/includes/js/tests/basic/testFieldNameValidation.js',
        '/includes/js/tests/basic/testModalClosing.js',
        
        // Advanced tests
        '/includes/js/tests/advanced/testBasicCombinations.js',
        '/includes/js/tests/advanced/testConditionalLogicOperators.js',
        '/includes/js/tests/advanced/testNestedFieldsEditing.js',
        '/includes/js/tests/advanced/testModifyAndRevert.js',
        '/includes/js/tests/advanced/testSpecialCharacters.js',
        '/includes/js/tests/advanced/testRapidChanges.js'
    ];
    
    // Plugin URL helper
    function getPluginUrl() {
        // Get current page URL to determine plugin path
        const pathname = window.location.pathname;
        if (pathname.includes('/fagpress/')) {
            return '/fagpress/wp-content/plugins/YetAnotherPlugin';
        }
        return '/wp-content/plugins/YetAnotherPlugin';
    }
    
    const pluginUrl = getPluginUrl();
    
    /**
     * Load all test files
     */
    function loadTestFiles() {
        console.log('%c🧪 YAP Test Loader Starting...', 'color: #0073aa; font-size: 14px; font-weight: bold;');
        
        let loadedCount = 0;
        let failedCount = 0;
        
        testFiles.forEach((file, index) => {
            const fullUrl = pluginUrl + file;
            const script = document.createElement('script');
            script.src = fullUrl + '?v=' + Math.random(); // Cache bust
            script.async = false;
            
            script.onload = () => {
                loadedCount++;
                console.log(`✅ Loaded: ${file}`);
            };
            
            script.onerror = () => {
                failedCount++;
                console.error(`❌ Failed to load: ${file}`);
            };
            
            document.head.appendChild(script);
        });
        
        // Print summary after a delay
        setTimeout(() => {
            console.log(`\n📊 Test Files Loaded: ${loadedCount}/${testFiles.length}`);
            if (failedCount > 0) {
                console.error(`❌ Failed: ${failedCount}`);
            }
            initializeTestAPIs();
        }, 500);
    }
    
    /**
     * Initialize test APIs
     */
    function initializeTestAPIs() {
        // Basic tests
        window.YAPBuilderTests = {
            runAll: function() {
                console.log('%c=== YAP Visual Builder - Basic Tests ===', 'font-size: 16px; font-weight: bold; color: #0073aa;');
                return TestRunner.runAll('YAP Visual Builder Tests (15 Basic + 6 Advanced)');
            },
            run: TestRunner.runAll
        };
        
        // Advanced tests
        window.YAPAdvancedTests = {
            runAll: function() {
                console.log('%c=== YAP Visual Builder - Advanced Tests ===', 'font-size: 16px; font-weight: bold; color: #0073aa;');
                return TestRunner.runAll('YAP Advanced Tests');
            },
            run: TestRunner.runAll
        };
        
        // Test utilities
        window.YAPTestUtils = {
            logger: TestLogger,
            asserts: TestAssert,
            runner: TestRunner,
            report: TestReportGenerator
        };
        
        console.log('%c✅ Test APIs ready!', 'color: #46b450; font-weight: bold;');
        console.log('Use: YAPBuilderTests.runAll() to start tests');
        console.log('Generate report: YAPTestUtils.report.downloadJSON()');
    }
    
    // Load tests when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadTestFiles);
    } else {
        loadTestFiles();
    }
    
})();
