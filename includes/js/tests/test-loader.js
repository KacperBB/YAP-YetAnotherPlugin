/**
 * YAP Test Loader
 * 
 * Helper script to load YAP Visual Builder tests
 * Can be run in console to dynamically load tests
 * 
 * Usage:
 * 1. Copy this script
 * 2. Paste in Console (F12)
 * 3. It will auto-load tests
 * 
 * @since 1.5.0
 */

(function() {
    'use strict';
    
    console.log('%c📦 YAP Test Loader Starting...', 'color: #0073aa; font-size: 14px; font-weight: bold;');
    
    // Check if tests are already loaded
    if (window.YAPBuilderTests && window.YAPAdvancedTests) {
        console.log('%c✅ Tests already loaded!', 'color: #46b450; font-weight: bold;');
        console.log('%cRun: YAPBuilderTests.runAll() or YAPAdvancedTests.runAll()', 'color: #666; font-size: 12px;');
        return;
    }
    
    // Base URL for test files
    const pluginUrl = window.yapTestPaths ? window.yapTestPaths.pluginUrl : '/wp-content/plugins/YetAnotherPlugin/';
    const testsDir = window.yapTestPaths ? window.yapTestPaths.testsDir : 'js/tests/';
    
    const testFiles = [
        'test-config.js',
        'visual-builder-field-editing.test.js',
        'visual-builder-advanced.test.js'
    ];
    
    let loadedCount = 0;
    
    /**
     * Load script dynamically
     */
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            
            script.onload = () => {
                console.log(`✅ Loaded: ${src.split('/').pop()}`);
                loadedCount++;
                resolve();
            };
            
            script.onerror = () => {
                console.error(`❌ Failed to load: ${src}`);
                reject(new Error(`Failed to load: ${src}`));
            };
            
            document.head.appendChild(script);
        });
    }
    
    /**
     * Load all test files sequentially
     */
    async function loadAllTests() {
        try {
            console.log(`📥 Loading ${testFiles.length} test files...`);
            
            for (const file of testFiles) {
                const fullUrl = pluginUrl + testsDir + file;
                console.log(`📦 Loading from: ${fullUrl}`);
                await loadScript(fullUrl);
                // Small delay between loads
                await new Promise(resolve => setTimeout(resolve, 100));
            }
            
            console.log('%c✨ All tests loaded successfully!', 'color: #46b450; font-size: 13px; font-weight: bold;');
            
            // Verify tests are available
            setTimeout(() => {
                if (window.YAPBuilderTests && window.YAPAdvancedTests) {
                    console.log('%c🎉 Tests are ready to use!', 'color: #46b450; font-weight: bold;');
                    console.log('%c📋 Available commands:', 'color: #0073aa; font-weight: bold;');
                    console.log('  • YAPBuilderTests.runAll()');
                    console.log('  • YAPAdvancedTests.runAll()');
                    console.log('  • YAPBuilderTests.testChangeFieldName()');
                    console.log('  • YAPBuilderTests.testToggleRequired()');
                    console.log('  • YAPAdvancedTests.testBasicCombinations()');
                    console.log('%c\n💡 Or open: /includes/js/tests/test-runner.html', 'color: #666; font-size: 12px;');
                } else {
                    console.warn('⚠️ Tests did not load properly. Check if jQuery is available.');
                }
            }, 500);
            
        } catch (error) {
            console.error('%c❌ Error loading tests:', 'color: #dc3232; font-weight: bold;');
            console.error(error);
        }
    }
    
    // Check if jQuery is available
    if (typeof jQuery === 'undefined') {
        console.warn('⚠️ jQuery is not loaded. Waiting for jQuery...');
        
        // Wait for jQuery
        const checkJQuery = setInterval(() => {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                console.log('✅ jQuery detected. Loading tests...');
                loadAllTests();
            }
        }, 100);
        
        // Timeout after 5 seconds
        setTimeout(() => {
            clearInterval(checkJQuery);
            if (typeof jQuery === 'undefined') {
                console.error('❌ jQuery not found after 5 seconds. Cannot load tests.');
            }
        }, 5000);
    } else {
        // jQuery is available, load tests
        loadAllTests();
    }
    
})();

console.log('%c\n💡 Tip: This loader is in /includes/js/tests/test-loader.js', 'color: #666; font-size: 11px;');
