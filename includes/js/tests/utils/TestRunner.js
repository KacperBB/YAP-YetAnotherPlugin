/**
 * Test Runner
 * Manages test execution flow
 * 
 * @since 1.5.0
 */

const TestRunner = {
    tests: [],
    isRunning: false,
    
    /**
     * Register test
     */
    register(name, testFn) {
        this.tests.push({ name, fn: testFn });
    },
    
    /**
     * Reset field to original state
     */
    resetTestField() {
        const $ = window.jQuery;
        if (!$) return;
        
        // Find first field in builder
        const $field = $('.yap-field-item').first();
        if ($field.length === 0) return;
        
        const fieldId = $field.data('field-id');
        if (!fieldId || !window.yapBuilder) return;
        
        // Find field in schema
        const field = this.findFieldById(fieldId, window.yapBuilder.schema.fields);
        if (!field) return;
        
        TestLogger.log('🔄 Resetting test field to original state', 'info');
        
        // Reset to original values
        field.name = field._original_name || field.name;
        field.label = field._original_label || field.label;
        field.placeholder = field._original_placeholder || '';
        field.default_value = field._original_default_value || '';
        field.description = field._original_description || '';
        field.css_class = field._original_css_class || '';
        field.required = field._original_required || false;
        
        // Update UI
        if (window.yapBuilder && window.yapBuilder.updateFieldUI) {
            window.yapBuilder.updateFieldUI(field);
        }
        
        // Close modal if open
        this.closeAllModals();
    },
    
    /**
     * Find field by ID recursively
     */
    findFieldById(id, fields) {
        for (const field of fields) {
            if (field.id === id) return field;
            if (field.fields && field.fields.length > 0) {
                const found = this.findFieldById(id, field.fields);
                if (found) return found;
            }
        }
        return null;
    },
    
    /**
     * Preserve original field values
     */
    preserveOriginalValues() {
        const $ = window.jQuery;
        if (!$ || !window.yapBuilder) return;
        
        const $field = $('.yap-field-item').first();
        if ($field.length === 0) return;
        
        const fieldId = $field.data('field-id');
        const field = this.findFieldById(fieldId, window.yapBuilder.schema.fields);
        
        if (field && !field._original_name) {
            // Save original values first time only
            field._original_name = field.name;
            field._original_label = field.label;
            field._original_placeholder = field.placeholder || '';
            field._original_default_value = field.default_value || '';
            field._original_description = field.description || '';
            field._original_css_class = field.css_class || '';
            field._original_required = field.required || false;
            
            TestLogger.log('💾 Original field values preserved', 'info');
        }
    },
    
    /**
     * Run all tests sequentially
     */
    async runAll(title = 'Tests') {
        if (this.isRunning) {
            TestLogger.log('Tests already running', 'warn');
            return;
        }
        
        this.isRunning = true;
        TestLogger.clear();
        TestAssert.reset();
        
        // Close any open modals BEFORE starting
        this.closeAllModals();
        
        // Preserve original field values before tests modify them
        this.preserveOriginalValues();
        
        console.log('%c=== ' + title + ' ===', 'font-size: 16px; font-weight: bold; color: #0073aa;');
        TestLogger.log(`Running ${this.tests.length} tests...`, 'info');
        
        // Ensure jQuery
        await this.ensureJQuery();
        
        // Run tests sequentially
        for (const test of this.tests) {
            TestLogger.group(test.name);
            try {
                const result = test.fn();
                if (result instanceof Promise) {
                    await result;
                }
            } catch (err) {
                TestLogger.log(`ERROR in ${test.name}: ${err.message}`, 'fail');
                TestAssert.failCount++;
            }
            TestLogger.groupEnd();
        }
        
        // Reset field to original state AFTER ALL tests
        setTimeout(() => {
            this.resetTestField();
            this.closeAllModals();
        }, 100);
        
        // Print summary
        this.printSummary();
        this.isRunning = false;
        
        return TestAssert.getSummary();
    },
    
    /**
     * Wait for jQuery
     */
    ensureJQuery() {
        return new Promise((resolve) => {
            if (typeof window.jQuery !== 'undefined') {
                resolve();
            } else {
                TestLogger.log('Waiting for jQuery...', 'warn');
                const interval = setInterval(() => {
                    if (typeof window.jQuery !== 'undefined') {
                        clearInterval(interval);
                        TestLogger.log('jQuery loaded!', 'pass');
                        resolve();
                    }
                }, 100);
            }
        });
    },
    
    /**
     * Print summary
     */
    printSummary() {
        const summary = TestAssert.getSummary();
        
        console.log('\n%c=== TEST SUMMARY ===', 'font-size: 14px; font-weight: bold; color: #0073aa;');
        console.log(`%c✅ Passed: ${summary.passed}`, 'color: #46b450; font-weight: bold;');
        console.log(`%c❌ Failed: ${summary.failed}`, 'color: #dc3545; font-weight: bold;');
        console.log(`📊 Total: ${summary.total}`);
        console.log(`📈 Success Rate: ${summary.successRate}%`);
        
        if (summary.failed === 0) {
            console.log('%c🎉 All tests passed!', 'color: #46b450; font-size: 14px; font-weight: bold;');
        } else {
            console.log(`%c⚠️ ${summary.failed} test(s) failed`, 'color: #dc3545; font-weight: bold;');
        }
        console.log('');
    }
};

// Export
window.TestRunner = TestRunner;
