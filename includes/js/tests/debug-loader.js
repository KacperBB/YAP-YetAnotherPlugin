/**
 * YAP Test Loader - Debug & Load
 * 
 * Wklej ten kod w Console (F12) aby:
 * 1. Sprawdzić czy pliki istnieją
 * 2. Załadować testy z debugowaniem
 * 3. Uruchomić testy
 */

(function() {
    'use strict';
    
    console.clear();
    console.log('%c=== YAP Test Debug & Loader ===', 'color: #0073aa; font-size: 16px; font-weight: bold;');
    
    // ===== STEP 1: CHECK ENVIRONMENT =====
    console.log('\n📋 Step 1: Checking environment...\n');
    
    console.log('✓ jQuery:', typeof jQuery !== 'undefined' ? 'YES ✅' : 'NO ❌');
    console.log('✓ Window object:', typeof window !== 'undefined' ? 'YES ✅' : 'NO ❌');
    console.log('✓ Document object:', typeof document !== 'undefined' ? 'YES ✅' : 'NO ❌');
    console.log('✓ Current URL:', window.location.href);
    
    // ===== STEP 2: DETERMINE BASE PATH =====
    console.log('\n📍 Step 2: Determining base path...\n');
    
    const pathname = window.location.pathname;
    console.log('Current pathname:', pathname);
    
    let baseUrl = '';
    if (pathname.includes('/fagpress/')) {
        baseUrl = '/fagpress';
        console.log('✓ Detected: /fagpress/ installation');
    } else if (pathname.includes('/wp-admin/')) {
        baseUrl = '';
        console.log('✓ Detected: Root installation');
    } else {
        baseUrl = '/fagpress';
        console.log('⚠ Using fallback: /fagpress');
    }
    
    // ===== STEP 3: CHECK FILES EXIST =====
    console.log('\n📁 Step 3: Checking if test files exist...\n');
    
    const testFiles = [
        'test-config.js',
        'visual-builder-field-editing.test.js',
        'visual-builder-advanced.test.js'
    ];
    
    const baseTestUrl = baseUrl + '/wp-content/plugins/YetAnotherPlugin/includes/js/tests/';
    
    console.log('Base URL:', baseTestUrl + '\n');
    
    // ===== STEP 4: LOAD FILES WITH DEBUG =====
    console.log('🔄 Step 4: Loading test files...\n');
    
    let loadedCount = 0;
    let failedCount = 0;
    const loadedFiles = [];
    const failedFiles = [];
    
    function loadTestFile(filename, index) {
        return new Promise((resolve) => {
            setTimeout(() => {
                const fullUrl = baseTestUrl + filename;
                
                console.log(`[${index + 1}/${testFiles.length}] Attempting to load: ${filename}`);
                console.log(`    Full URL: ${fullUrl}`);
                
                const script = document.createElement('script');
                script.src = fullUrl;
                script.async = false;
                
                script.onload = () => {
                    loadedCount++;
                    loadedFiles.push(filename);
                    console.log(`    ✅ SUCCESS\n`);
                    resolve(true);
                };
                
                script.onerror = () => {
                    failedCount++;
                    failedFiles.push(filename);
                    console.log(`    ❌ FAILED (404 Not Found)\n`);
                    resolve(false);
                };
                
                document.head.appendChild(script);
            }, index * 300);
        });
    }
    
    // Load all files
    Promise.all(testFiles.map((file, i) => loadTestFile(file, i)))
        .then(() => {
            // ===== STEP 5: VERIFY TESTS LOADED =====
            console.log('📊 Step 5: Verification...\n');
            
            console.log(`Loaded: ${loadedCount}/${testFiles.length}`);
            console.log(`Failed: ${failedCount}/${testFiles.length}`);
            
            if (loadedFiles.length > 0) {
                console.log('\n✅ Loaded files:');
                loadedFiles.forEach(f => console.log(`   • ${f}`));
            }
            
            if (failedFiles.length > 0) {
                console.log('\n❌ Failed files:');
                failedFiles.forEach(f => console.log(`   • ${f}`));
            }
            
            // ===== STEP 6: CHECK GLOBAL OBJECTS =====
            console.log('\n🔍 Step 6: Checking global objects...\n');
            
            setTimeout(() => {
                const hasTestConfig = typeof window.YAPTestConfig !== 'undefined';
                const hasBuilderTests = typeof window.YAPBuilderTests !== 'undefined';
                const hasAdvancedTests = typeof window.YAPAdvancedTests !== 'undefined';
                
                console.log('window.YAPTestConfig:', hasTestConfig ? '✅ YES' : '❌ NO');
                console.log('window.YAPBuilderTests:', hasBuilderTests ? '✅ YES' : '❌ NO');
                console.log('window.YAPAdvancedTests:', hasAdvancedTests ? '✅ YES' : '❌ NO');
                
                // ===== FINAL RESULT =====
                console.log('\n' + '='.repeat(50));
                
                if (hasBuilderTests && hasAdvancedTests) {
                    console.log('%c✨ SUCCESS! Tests are ready!', 'color: #46b450; font-size: 14px; font-weight: bold;');
                    console.log('%cRun: YAPBuilderTests.runAll()', 'color: #0073aa; font-size: 13px; font-weight: bold;');
                    console.log('or:  YAPAdvancedTests.runAll()', 'color: #0073aa; font-size: 13px;');
                    console.log('%c\nOther commands:', 'color: #0073aa; font-weight: bold;');
                    console.log('  YAPBuilderTests.testChangeFieldName()');
                    console.log('  YAPAdvancedTests.testBasicCombinations()');
                } else {
                    console.log('%c❌ TESTS NOT AVAILABLE', 'color: #dc3232; font-size: 14px; font-weight: bold;');
                    console.log('\nTroubleshooting:');
                    console.log('1. Check Network tab (F12 → Network) for 404 errors');
                    console.log('2. Verify file paths are correct');
                    console.log('3. Check if jQuery is loaded: typeof jQuery');
                    console.log('4. Check for other JS errors in console');
                    console.log('\nDebug info:');
                    console.log('  Base URL: ' + baseTestUrl);
                    console.log('  Loaded: ' + loadedCount + '/' + testFiles.length);
                    console.log('  Failed: ' + failedCount + '/' + testFiles.length);
                }
                
                console.log('='.repeat(50));
            }, 1000);
        });
})();
