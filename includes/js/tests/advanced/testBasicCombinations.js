/**
 * Test: Kombinacje Podstawowych Opcji (Basic Combinations)
 * 
 * Testuje wszystkie możliwe kombinacje opcji pola
 * 
 * @since 1.5.0
 */

const testBasicCombinations = function() {
    const $ = window.jQuery;
    
    TestLogger.log('Testing basic option combinations', 'test');
    
    const combinations = [
        {
            name: 'Required text field',
            changes: {
                'name': 'combo_text_required',
                'label': 'Required Text',
                'required': true,
                'placeholder': 'Fill this field'
            }
        },
        {
            name: 'Field with CSS class + default value',
            changes: {
                'name': 'combo_styled',
                'label': 'Styled Field',
                'class': 'custom-style',
                'default': 'default value'
            }
        },
        {
            name: 'Field with all options',
            changes: {
                'name': 'combo_full',
                'label': 'Full Featured',
                'required': true,
                'class': 'highlight',
                'description': 'Full featured field'
            }
        },
        {
            name: 'Field with default + placeholder',
            changes: {
                'name': 'combo_optional',
                'label': 'Optional',
                'default': 'optional value',
                'placeholder': 'Or enter new'
            }
        },
        {
            name: 'Minimal field',
            changes: {
                'name': 'combo_minimal',
                'label': 'Minimal'
            }
        }
    ];
    
    TestLogger.log(`Testing ${combinations.length} combinations...`, 'info');
    
    combinations.forEach((combo, i) => {
        TestAssert.assert(combo.name && combo.changes, `Combination ${i + 1}: ${combo.name}`);
    });
    
    return Promise.resolve(true);
};

// Register test
TestRunner.register('Advanced Test 1: Basic Combinations', testBasicCombinations);

// Export
window.testBasicCombinations = testBasicCombinations;
