/**
 * Test Assertions Utility
 * Shared assertion logic for all tests
 * 
 * @since 1.5.0
 */

const TestAssert = {
    testCount: 0,
    passCount: 0,
    failCount: 0,
    
    /**
     * Assert condition
     */
    assert(condition, message) {
        this.testCount++;
        
        if (condition) {
            this.passCount++;
            TestLogger.log(`PASS: ${message}`, 'pass');
            return true;
        } else {
            this.failCount++;
            TestLogger.log(`FAIL: ${message}`, 'fail');
            return false;
        }
    },
    
    /**
     * Assert equal
     */
    assertEqual(actual, expected, message) {
        const condition = actual === expected;
        return this.assert(condition, `${message} (actual: ${actual}, expected: ${expected})`);
    },
    
    /**
     * Assert exists
     */
    assertExists(element, selector, message) {
        const condition = element.length > 0 || document.querySelector(selector) !== null;
        return this.assert(condition, `Element exists: ${message}`);
    },
    
    /**
     * Assert has class
     */
    assertHasClass(element, className, message) {
        const condition = element.hasClass ? element.hasClass(className) : element.classList.contains(className);
        return this.assert(condition, `Has class '${className}': ${message}`);
    },
    
    /**
     * Assert value
     */
    assertValue(element, expectedValue, message) {
        const actualValue = element.val ? element.val() : element.value;
        return this.assertEqual(actualValue, expectedValue, message);
    },
    
    /**
     * Reset counters
     */
    reset() {
        this.testCount = 0;
        this.passCount = 0;
        this.failCount = 0;
    },
    
    /**
     * Get summary
     */
    getSummary() {
        return {
            total: this.testCount,
            passed: this.passCount,
            failed: this.failCount,
            successRate: this.testCount > 0 ? ((this.passCount / this.testCount) * 100).toFixed(1) : 0
        };
    }
};

// Export
window.TestAssert = TestAssert;
