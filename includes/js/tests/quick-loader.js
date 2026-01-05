/**
 * YAP Test Quick Loader
 * 
 * Wklej ten kod bezpośrednio w Console (F12)
 * To załaduje wszystkie testy automatycznie
 * 
 * UŻYCIE:
 * 1. Otwórz Visual Builder
 * 2. Naciśnij F12 → Console
 * 3. Wklej poniższy kod
 * 4. Naciśnij Enter
 * 5. Po załadowaniu: YAPBuilderTests.runAll()
 */

(function() {
    console.log('%c🧪 YAP Quick Test Loader', 'color: #0073aa; font-size: 14px; font-weight: bold;');
    
    // Dynamicznie ustal URL pluginu
    const scriptSrc = document.currentScript?.src || 'unknown';
    let pluginUrl = '/wp-content/plugins/YetAnotherPlugin/';
    
    // Spróbuj znaleźć pluginUrl z URL dokumentu
    const pathname = window.location.pathname;
    if (pathname.includes('/fagpress/')) {
        pluginUrl = '/fagpress/wp-content/plugins/YetAnotherPlugin/';
    }
    
    console.log(`📍 Plugin URL: ${pluginUrl}`);
    
    const testFiles = [
        'test-config.js',
        'visual-builder-field-editing.test.js',
        'visual-builder-advanced.test.js'
    ];
    
    let loaded = 0;
    let failed = 0;
    
    function loadFile(file, index) {
        setTimeout(() => {
            const url = pluginUrl + 'includes/js/tests/' + file;
            const script = document.createElement('script');
            script.src = url;
            script.async = false;
            
            script.onload = () => {
                loaded++;
                console.log(`✅ ${loaded}/${testFiles.length} Loaded: ${file}`);
                
                if (loaded + failed === testFiles.length) {
                    finishLoading();
                }
            };
            
            script.onerror = () => {
                failed++;
                console.error(`❌ Failed to load: ${url}`);
                console.error(`   Status: 404 Not Found`);
                console.error(`   Try different URL...`);
                
                if (loaded + failed === testFiles.length) {
                    finishLoading();
                }
            };
            
            document.head.appendChild(script);
        }, index * 200);
    }
    
    function finishLoading() {
        if (failed > 0) {
            console.error(`\n❌ ${failed} file(s) failed to load`);
            console.error(`\nTroubleshoot:`);
            console.error(`1. Check if files exist: /includes/js/tests/`);
            console.error(`2. Check Network tab (F12 → Network)`);
            console.error(`3. Verify plugin URL is correct`);
            console.error(`\nDebug info:`);
            console.error(`  Plugin URL: ${pluginUrl}`);
            console.error(`  Full URL: ${pluginUrl}includes/js/tests/test-config.js`);
        } else {
            console.log('%c✨ All test files loaded successfully!', 'color: #46b450; font-weight: bold; font-size: 13px;');
            
            // Check availability
            setTimeout(() => {
                if (window.YAPBuilderTests && window.YAPAdvancedTests) {
                    console.log('%c🎉 Tests ready!', 'color: #46b450; font-weight: bold;');
                    console.log('%cRun: YAPBuilderTests.runAll()', 'color: #0073aa; font-size: 12px;');
                    console.log('or:  YAPAdvancedTests.runAll()', 'color: #0073aa; font-size: 12px;');
                } else {
                    console.warn('%c⚠️ Tests loaded but not available', 'color: #ffb81c; font-weight: bold;');
                    if (!window.YAPBuilderTests) console.warn('  - YAPBuilderTests: NOT FOUND');
                    if (!window.YAPAdvancedTests) console.warn('  - YAPAdvancedTests: NOT FOUND');
                }
            }, 500);
        }
    }
    
    console.log(`📥 Loading ${testFiles.length} test files...\n`);
    testFiles.forEach((file, i) => loadFile(file, i));
})();
