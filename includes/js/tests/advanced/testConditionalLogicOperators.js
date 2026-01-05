/**
 * Test: Operatory Logiki Warunkowej (Conditional Logic Operators)
 * 
 * Testuje wszystkie operatory logiki warunkowej
 * 
 * @since 1.5.0
 */

const testConditionalLogicOperators = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Testing conditional logic operators', 'test');
    
    const operators = [
        { name: 'equals', operator: '==' },
        { name: 'not equals', operator: '!=' },
        { name: 'contains', operator: 'contains' },
        { name: 'greater than', operator: '>' },
        { name: 'less than', operator: '<' },
        { name: 'is empty', operator: 'empty' }
    ];
    
    TestLogger.log(`Testing ${operators.length} conditional operators...`, 'info');
    
    operators.forEach((op, i) => {
        const $conditionSelect = $('#yap-field-settings-modal').find('.yap-condition-operator');
        if ($conditionSelect.length > 0) {
            $conditionSelect.val(op.operator).trigger('change');
            TestAssert.assert(
                $conditionSelect.val() === op.operator,
                `Operator set to: ${op.name}`
            );
        }
    });
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Advanced Test 2: Conditional Logic Operators', testConditionalLogicOperators);

// Export
window.testConditionalLogicOperators = testConditionalLogicOperators;
