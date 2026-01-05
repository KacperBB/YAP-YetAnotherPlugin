/**
 * YAP Developer Overlay
 * Floating widget w prawym dolnym rogu do testowania
 * 
 * Features:
 * - Kółko w prawym dolnym rogu
 * - Kliknij aby otworzyć menu
 * - Przyciski do testów
 * - Link na standalone test runner
 * 
 * @since 1.5.0
 */

(function() {
    'use strict';
    
    // Tylko dla admina z WP_DEBUG
    if (!window.yapDebugMode) {
        return;
    }
    
    // Wait for jQuery if not available
    function ensureJQuery(callback) {
        if (typeof window.jQuery !== 'undefined') {
            callback();
        } else {
            console.log('Waiting for jQuery to load...');
            const checkInterval = setInterval(() => {
                if (typeof window.jQuery !== 'undefined') {
                    clearInterval(checkInterval);
                    console.log('jQuery loaded!');
                    callback();
                }
            }, 100);
        }
    }
    
    // CSS Styles
    const styles = `
        .yap-dev-overlay-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
            border: 3px solid white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(0, 115, 170, 0.4);
            z-index: 9998;
            transition: all 0.3s ease;
            user-select: none;
        }
        
        .yap-dev-overlay-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0, 115, 170, 0.6);
        }
        
        .yap-dev-overlay-toggle.active {
            background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%);
        }
        
        .yap-dev-overlay-menu {
            position: fixed;
            bottom: 100px;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            min-width: 280px;
            max-width: 320px;
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
            transition: all 0.3s ease;
            padding: 0;
            overflow: hidden;
        }
        
        .yap-dev-overlay-menu.visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        
        .yap-dev-menu-header {
            background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
            color: white;
            padding: 15px;
            font-weight: bold;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .yap-dev-menu-body {
            padding: 15px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .yap-dev-btn {
            display: block;
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 8px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: #333;
            text-align: left;
            transition: all 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .yap-dev-btn:hover {
            background: #e8e8e8;
            border-color: #0073aa;
            color: #0073aa;
        }
        
        .yap-dev-btn:last-child {
            margin-bottom: 0;
        }
        
        .yap-dev-btn.primary {
            background: #0073aa;
            color: white;
            border-color: #0073aa;
        }
        
        .yap-dev-btn.primary:hover {
            background: #005a87;
            border-color: #005a87;
        }
        
        .yap-dev-btn.success {
            background: #46b450;
            color: white;
            border-color: #46b450;
        }
        
        .yap-dev-btn.success:hover {
            background: #398a40;
            border-color: #398a40;
        }
        
        .yap-dev-btn-icon {
            font-size: 16px;
        }
        
        .yap-dev-divider {
            height: 1px;
            background: #e0e0e0;
            margin: 8px 0;
        }
        
        .yap-dev-info {
            font-size: 12px;
            color: #666;
            padding: 8px 0;
            line-height: 1.5;
        }
        
        .yap-dev-info strong {
            color: #333;
        }
        
        /* Scrollbar styling */
        .yap-dev-menu-body::-webkit-scrollbar {
            width: 6px;
        }
        
        .yap-dev-menu-body::-webkit-scrollbar-track {
            background: #f0f0f0;
        }
        
        .yap-dev-menu-body::-webkit-scrollbar-thumb {
            background: #0073aa;
            border-radius: 3px;
        }
        
        .yap-dev-menu-body::-webkit-scrollbar-thumb:hover {
            background: #005a87;
        }
    `;
    
    // HTML Template
    const html = `
        <div class="yap-dev-overlay-toggle" id="yapDevToggle" title="YAP Developer Tools">
            🧪
        </div>
        
        <div class="yap-dev-overlay-menu" id="yapDevMenu">
            <div class="yap-dev-menu-header">
                <span class="yap-dev-btn-icon">🧪</span>
                YAP Developer Tools
            </div>
            <div class="yap-dev-menu-body">
                <div style="margin-bottom: 10px;">
                    <button class="yap-dev-btn primary" id="yapRunAllTests">
                        <span class="yap-dev-btn-icon">▶️</span>
                        Run All Tests
                    </button>
                    <button class="yap-dev-btn" id="yapRunBasicTests">
                        <span class="yap-dev-btn-icon">📝</span>
                        Basic Tests (15)
                    </button>
                    <button class="yap-dev-btn" id="yapRunAdvancedTests">
                        <span class="yap-dev-btn-icon">⚡</span>
                        Advanced Tests (6)
                    </button>
                    <button class="yap-dev-btn" id="yapRunCustomFieldTests">
                        <span class="yap-dev-btn-icon">🎯</span>
                        Custom Field Tests (15)
                    </button>
                </div>
                
                <div class="yap-dev-divider"></div>
                
                <div style="margin-bottom: 10px;">
                    <button class="yap-dev-btn success" id="yapOpenRunner">
                        <span class="yap-dev-btn-icon">🎯</span>
                        Test Runner Dashboard
                    </button>
                </div>
                
                <div class="yap-dev-divider"></div>
                
                <div class="yap-dev-info">
                    <strong>Visual Builder:</strong><br>
                    Location: <code style="background: #f0f0f0; padding: 2px 4px; border-radius: 2px;">Visual Builder</code>
                </div>
                
                <div class="yap-dev-info" style="margin-top: 10px;">
                    <strong>Debug Mode:</strong> <span style="color: #46b450;">✓ Enabled</span><br>
                    <strong>Tests:</strong> <span id="yapTestStatus">Loading...</span>
                </div>
            </div>
        </div>
    `;
    
    // Initialize on document ready
    document.addEventListener('DOMContentLoaded', function() {
        ensureJQuery(function() {
            init();
        });
    });
    
    function init() {
        // Add styles
        const styleSheet = document.createElement('style');
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
        
        // Add HTML
        const container = document.createElement('div');
        container.innerHTML = html;
        document.body.appendChild(container);
        
        // Setup event listeners
        setupEventListeners();
        
        // Check test availability
        checkTestAvailability();
    }
    
    function setupEventListeners() {
        const toggle = document.getElementById('yapDevToggle');
        const menu = document.getElementById('yapDevMenu');
        
        // Toggle menu
        toggle.addEventListener('click', function() {
            menu.classList.toggle('visible');
            toggle.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#yapDevToggle') && !e.target.closest('#yapDevMenu')) {
                menu.classList.remove('visible');
                toggle.classList.remove('active');
            }
        });
        
        // Test buttons
        document.getElementById('yapRunAllTests').addEventListener('click', function() {
            runAllTests();
        });
        
        document.getElementById('yapRunBasicTests').addEventListener('click', function() {
            runBasicTests();
        });
        
        document.getElementById('yapRunAdvancedTests').addEventListener('click', function() {
            runAdvancedTests();
        });
        
        document.getElementById('yapRunCustomFieldTests').addEventListener('click', function() {
            runCustomFieldTests();
        });
        
        document.getElementById('yapOpenRunner').addEventListener('click', function() {
            openTestRunner();
        });
    }
    
    function checkTestAvailability() {
        const status = document.getElementById('yapTestStatus');
        
        setTimeout(function() {
            let statusHtml = '';
            let allLoaded = false;
            
            // Check Builder Tests
            const builderTests = typeof window.YAPBuilderTests !== 'undefined';
            const advancedTests = typeof window.YAPAdvancedTests !== 'undefined';
            const customFieldTests = typeof window.YAPCustomFieldTypeTests !== 'undefined';
            
            if (builderTests && advancedTests && customFieldTests) {
                statusHtml = '<span style="color: #46b450;">✓ All Tests Available</span>';
                allLoaded = true;
            } else {
                statusHtml = '<span style="color: #ff9800;">⚠ Partial Load</span><br>';
                if (builderTests) statusHtml += '  📝 Builder: ✓<br>';
                if (advancedTests) statusHtml += '  ⚡ Advanced: ✓<br>';
                if (customFieldTests) statusHtml += '  🎯 Custom Fields: ✓<br>';
            }
            
            if (status) {
                status.innerHTML = statusHtml;
            }
            
            console.log(`Test Status: Builder=${builderTests}, Advanced=${advancedTests}, CustomField=${customFieldTests}`);
        }, 1500);
    }
    
    function runAllTests() {
        if (typeof window.YAPBuilderTests === 'undefined') {
            alert('❌ Tests not loaded yet. Try refreshing the page.');
            return;
        }
        
        console.log('%c🧪 Running All Tests...', 'color: #0073aa; font-size: 14px; font-weight: bold;');
        window.YAPBuilderTests.runAll()
            .then(results => {
                console.log('%c✅ All tests completed!', 'color: #46b450; font-size: 14px; font-weight: bold;');
            })
            .catch(err => {
                console.error('%c❌ Tests error:', 'color: #dc3545; font-size: 14px; font-weight: bold;', err);
            });
    }
    
    function runBasicTests() {
        if (typeof window.YAPBuilderTests === 'undefined') {
            alert('❌ Tests not loaded yet. Try refreshing the page.');
            return;
        }
        
        console.log('%c🧪 Running Basic Tests (15)...', 'color: #0073aa; font-size: 14px; font-weight: bold;');
        window.YAPBuilderTests.runAll()
            .then(results => {
                console.log('%c✅ Basic tests completed!', 'color: #46b450; font-size: 14px; font-weight: bold;');
            })
            .catch(err => {
                console.error('%c❌ Tests error:', 'color: #dc3545; font-size: 14px; font-weight: bold;', err);
            });
    }
    
    function runAdvancedTests() {
        if (typeof window.YAPAdvancedTests === 'undefined') {
            alert('❌ Advanced tests not loaded yet. Try refreshing the page.');
            return;
        }
        
        console.log('%c⚡ Running Advanced Tests (6)...', 'color: #0073aa; font-size: 14px; font-weight: bold;');
        window.YAPAdvancedTests.runAll()
            .then(results => {
                console.log('%c✅ Advanced tests completed!', 'color: #46b450; font-size: 14px; font-weight: bold;');
            })
            .catch(err => {
                console.error('%c❌ Tests error:', 'color: #dc3545; font-size: 14px; font-weight: bold;', err);
            });
    }
    
    function runCustomFieldTests() {
        if (typeof window.YAPCustomFieldTypeTests === 'undefined') {
            alert('❌ Custom field type tests not loaded yet. Try refreshing the page.');
            return;
        }
        
        console.log('%c🎯 Running Custom Field Type Tests (15)...', 'color: #0073aa; font-size: 14px; font-weight: bold;');
        try {
            const results = window.YAPCustomFieldTypeTests.runAll();
            console.log('%c✅ Custom field type tests completed!', 'color: #46b450; font-size: 14px; font-weight: bold;');
            console.log('Test Results:', results);
        } catch (err) {
            console.error('%c❌ Tests error:', 'color: #dc3545; font-size: 14px; font-weight: bold;', err);
        }
    }
    
    function openTestRunner() {
        const pluginUrl = window.location.pathname.includes('fagpress') 
            ? '/fagpress/wp-content/plugins/YetAnotherPlugin/includes/js/tests/standalone-runner.html'
            : '/wp-content/plugins/YetAnotherPlugin/includes/js/tests/standalone-runner.html';
        
        window.open(pluginUrl, 'yap-test-runner', 'width=1200,height=800');
    }
    
    // Make API available globally
    window.YAPDevTools = {
        runAllTests: runAllTests,
        runBasicTests: runBasicTests,
        runAdvancedTests: runAdvancedTests,
        runCustomFieldTests: runCustomFieldTests,
        openTestRunner: openTestRunner,
        toggle: function() {
            document.getElementById('yapDevToggle').click();
        }
    };
    
    console.log('%c🧪 YAP Developer Tools Loaded', 'color: #0073aa; font-size: 13px; font-weight: bold;');
    console.log('Use: YAPDevTools.runAllTests() or YAPDevTools.toggle()');
    
})();
