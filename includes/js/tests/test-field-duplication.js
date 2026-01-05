/**
 * TEST: Field Duplication System
 * 
 * Testuje:
 * - Duplikację pola z nowymi id/key
 * - Rozwiązywanie kolizji name
 * - Kopiowanie ustawień (options, validation, conditional rules)
 * - Duplikację sub-fields (repeater, flexible content)
 * - Paste as new funkcjonalność
 */

window.FieldDuplicationTests = window.FieldDuplicationTests || {};

FieldDuplicationTests.runAll = function() {
    const testResults = [];

    console.log('🧪 Starting Field Duplication Tests...\n');

    // Test 1: Basic duplication
    testResults.push(FieldDuplicationTests.test1_BasicDuplication());

    // Test 2: New IDs generated
    testResults.push(FieldDuplicationTests.test2_NewIDsGenerated());

    // Test 3: Name collision resolution
    testResults.push(FieldDuplicationTests.test3_NameCollisionResolution());

    // Test 4: Settings preserved
    testResults.push(FieldDuplicationTests.test4_SettingsPreserved());

    // Test 5: Options copied
    testResults.push(FieldDuplicationTests.test5_OptionsCopied());

    // Test 6: Validation rules copied
    testResults.push(FieldDuplicationTests.test6_ValidationRulesCopied());

    // Test 7: Conditional logic copied
    testResults.push(FieldDuplicationTests.test7_ConditionalLogicCopied());

    // Test 8: Sub-fields duplicated
    testResults.push(FieldDuplicationTests.test8_SubFieldsDuplicated());

    // Test 9: Paste as new
    testResults.push(FieldDuplicationTests.test9_PasteAsNew());

    // Test 10: Field comparison
    testResults.push(FieldDuplicationTests.test10_FieldComparison());

    // Test 11: Multiple duplicates
    testResults.push(FieldDuplicationTests.test11_MultipleDuplicates());

    // Test 12: Duplicate button rendering
    testResults.push(FieldDuplicationTests.test12_DuplicateButtonRendering());

    // Test 13: Collision with multiple copies
    testResults.push(FieldDuplicationTests.test13_CollisionWithMultipleCopies());

    // Test 14: Deep clone validation
    testResults.push(FieldDuplicationTests.test14_DeepCloneValidation());

    // Test 15: Key never locked on duplicate
    testResults.push(FieldDuplicationTests.test15_KeyNotLockedOnDuplicate());

    // Summary
    const passed = testResults.filter(r => r.passed).length;
    const total = testResults.length;
    const percentage = ((passed / total) * 100).toFixed(1);

    console.log('\n📊 Field Duplication Tests Summary:');
    console.log(`✅ Passed: ${passed}/${total} (${percentage}%)\n`);

    return {
        tests: testResults,
        passed,
        total,
        percentage,
        allPassed: passed === total
    };
};

/**
 * Test 1: Basic duplication
 */
FieldDuplicationTests.test1_BasicDuplication = function() {
    const test = {
        name: 'Basic duplication',
        id: 'dup-1'
    };

    try {
        const original = FieldStabilization.createStableField('text', {
            label: 'First Name',
            name: 'first_name'
        });

        const result = FieldStabilization.duplicateField(original);

        test.passed = result.success === true && result.field !== null;
        test.message = test.passed ? 'Duplication successful' : 'Duplication failed';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 1: ${test.message}`);
    return test;
};

/**
 * Test 2: New IDs generated
 */
FieldDuplicationTests.test2_NewIDsGenerated = function() {
    const test = {
        name: 'New IDs generated',
        id: 'dup-2'
    };

    try {
        const original = FieldStabilization.createStableField('text', { label: 'Test' });
        const result = FieldStabilization.duplicateField(original);

        test.passed = 
            result.field.id !== original.id &&
            result.field.key !== original.key &&
            result.field.id.startsWith('fld_') &&
            result.field.key.startsWith('fld_');
        
        test.message = test.passed ? 'New id and key generated' : 'IDs not properly generated';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 2: ${test.message}`);
    return test;
};

/**
 * Test 3: Name collision resolution
 */
FieldDuplicationTests.test3_NameCollisionResolution = function() {
    const test = {
        name: 'Name collision resolution',
        id: 'dup-3'
    };

    try {
        // Setup schema
        window.yapBuilder = window.yapBuilder || {};
        window.yapBuilder.schema = window.yapBuilder.schema || {};
        window.yapBuilder.schema.fields = [];

        const original = FieldStabilization.createStableField('text', {
            label: 'Title',
            name: 'title'
        });
        window.yapBuilder.schema.fields.push(original);

        const result = FieldStabilization.duplicateField(original);

        test.passed = result.field.name === 'title_2';
        test.message = test.passed ? `Name resolved to: ${result.field.name}` : 'Name not resolved correctly';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 3: ${test.message}`);
    return test;
};

/**
 * Test 4: Settings preserved
 */
FieldDuplicationTests.test4_SettingsPreserved = function() {
    const test = {
        name: 'Settings preserved',
        id: 'dup-4'
    };

    try {
        const original = FieldStabilization.createStableField('text', {
            label: 'Test',
            name: 'test',
            placeholder: 'Enter text',
            required: true,
            help_text: 'This is help'
        });

        const result = FieldStabilization.duplicateField(original);

        test.passed = 
            result.field.type === original.type &&
            result.field.label === original.label &&
            result.field.placeholder === original.placeholder &&
            result.field.required === original.required &&
            result.field.help_text === original.help_text;
        
        test.message = test.passed ? 'All settings copied' : 'Settings not copied correctly';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 4: ${test.message}`);
    return test;
};

/**
 * Test 5: Options copied
 */
FieldDuplicationTests.test5_OptionsCopied = function() {
    const test = {
        name: 'Options copied',
        id: 'dup-5'
    };

    try {
        const original = FieldStabilization.createStableField('select', {
            label: 'Color',
            name: 'color',
            options: [
                { label: 'Red', value: 'red' },
                { label: 'Blue', value: 'blue' }
            ]
        });

        const result = FieldStabilization.duplicateField(original);

        test.passed = 
            JSON.stringify(result.field.options) === JSON.stringify(original.options) &&
            result.field.options.length === 2;
        
        test.message = test.passed ? 'Options copied correctly' : 'Options not copied';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 5: ${test.message}`);
    return test;
};

/**
 * Test 6: Validation rules copied
 */
FieldDuplicationTests.test6_ValidationRulesCopied = function() {
    const test = {
        name: 'Validation rules copied',
        id: 'dup-6'
    };

    try {
        const original = FieldStabilization.createStableField('text', {
            label: 'Email',
            name: 'email',
            validation: {
                type: 'email',
                required: true,
                min_length: 5
            }
        });

        const result = FieldStabilization.duplicateField(original);

        test.passed = JSON.stringify(result.field.validation) === JSON.stringify(original.validation);
        test.message = test.passed ? 'Validation rules copied' : 'Validation rules not copied';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 6: ${test.message}`);
    return test;
};

/**
 * Test 7: Conditional logic copied
 */
FieldDuplicationTests.test7_ConditionalLogicCopied = function() {
    const test = {
        name: 'Conditional logic copied',
        id: 'dup-7'
    };

    try {
        const original = FieldStabilization.createStableField('text', {
            label: 'Field',
            name: 'field',
            conditional_logic: [
                {
                    field: 'status',
                    operator: 'equals',
                    value: 'active'
                }
            ]
        });

        const result = FieldStabilization.duplicateField(original);

        test.passed = JSON.stringify(result.field.conditional_logic) === JSON.stringify(original.conditional_logic);
        test.message = test.passed ? 'Conditional logic copied' : 'Conditional logic not copied';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 7: ${test.message}`);
    return test;
};

/**
 * Test 8: Sub-fields duplicated
 */
FieldDuplicationTests.test8_SubFieldsDuplicated = function() {
    const test = {
        name: 'Sub-fields duplicated',
        id: 'dup-8'
    };

    try {
        const original = FieldStabilization.createStableField('repeater', {
            label: 'Items',
            name: 'items',
            fields: [
                { id: 'fld_1', name: 'item_name', type: 'text' },
                { id: 'fld_2', name: 'item_price', type: 'number' }
            ]
        });

        const result = FieldStabilization.duplicateField(original, true);

        test.passed = 
            result.field.fields.length === 2 &&
            result.field.fields[0].id !== original.fields[0].id &&
            result.field.fields[1].id !== original.fields[1].id;
        
        test.message = test.passed ? 'Sub-fields duplicated with new IDs' : 'Sub-fields not duplicated correctly';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 8: ${test.message}`);
    return test;
};

/**
 * Test 9: Paste as new
 */
FieldDuplicationTests.test9_PasteAsNew = function() {
    const test = {
        name: 'Paste as new',
        id: 'dup-9'
    };

    try {
        window.yapBuilder = window.yapBuilder || {};
        window.yapBuilder.schema = window.yapBuilder.schema || {};
        window.yapBuilder.schema.fields = [];

        const original = FieldStabilization.createStableField('text', {
            label: 'Test',
            name: 'test'
        });

        const result = FieldStabilization.pasteAsNew(original, 'end');

        test.passed = 
            result.success === true &&
            window.yapBuilder.schema.fields.length === 1 &&
            window.yapBuilder.schema.fields[0].id === result.field.id;
        
        test.message = test.passed ? 'Field added to schema' : 'Field not added to schema';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 9: ${test.message}`);
    return test;
};

/**
 * Test 10: Field comparison
 */
FieldDuplicationTests.test10_FieldComparison = function() {
    const test = {
        name: 'Field comparison',
        id: 'dup-10'
    };

    try {
        const original = FieldStabilization.createStableField('text', {
            label: 'Test',
            name: 'test',
            options: { a: 1 }
        });

        const result = FieldStabilization.duplicateField(original);
        const comparison = FieldStabilization.compareFields(original, result.field);

        test.passed = 
            comparison.is_duplicate === true &&
            comparison.settings_preserved === true;
        
        test.message = test.passed ? 'Comparison successful' : 'Comparison failed';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 10: ${test.message}`);
    return test;
};

/**
 * Test 11: Multiple duplicates
 */
FieldDuplicationTests.test11_MultipleDuplicates = function() {
    const test = {
        name: 'Multiple duplicates',
        id: 'dup-11'
    };

    try {
        window.yapBuilder = window.yapBuilder || {};
        window.yapBuilder.schema = window.yapBuilder.schema || {};
        window.yapBuilder.schema.fields = [];

        const original = FieldStabilization.createStableField('text', { name: 'title' });
        window.yapBuilder.schema.fields.push(original);

        // Duplikuj wielokrotnie
        const dup1 = FieldStabilization.duplicateField(original);
        window.yapBuilder.schema.fields.push(dup1.field);

        const dup2 = FieldStabilization.duplicateField(original);
        window.yapBuilder.schema.fields.push(dup2.field);

        test.passed = 
            dup1.field.name === 'title_2' &&
            dup2.field.name === 'title_3';
        
        test.message = test.passed ? 'Multiple duplicates named correctly' : 'Names conflict';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 11: ${test.message}`);
    return test;
};

/**
 * Test 12: Duplicate button rendering
 */
FieldDuplicationTests.test12_DuplicateButtonRendering = function() {
    const test = {
        name: 'Duplicate button rendering',
        id: 'dup-12'
    };

    try {
        const field = FieldStabilization.createStableField('text', { label: 'Test' });
        const html = FieldStabilization.renderDuplicateButton(field);

        test.passed = 
            html.includes('field-duplicate-btn') &&
            html.includes(field.id) &&
            html.includes('Duplicate');
        
        test.message = test.passed ? 'Button HTML rendered correctly' : 'Button rendering failed';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 12: ${test.message}`);
    return test;
};

/**
 * Test 13: Collision with multiple copies
 */
FieldDuplicationTests.test13_CollisionWithMultipleCopies = function() {
    const test = {
        name: 'Collision resolution with many copies',
        id: 'dup-13'
    };

    try {
        window.yapBuilder = window.yapBuilder || {};
        window.yapBuilder.schema = window.yapBuilder.schema || {};
        window.yapBuilder.schema.fields = [];

        const original = FieldStabilization.createStableField('text', { name: 'field' });
        window.yapBuilder.schema.fields.push(original);

        // Stwórz 5 kopii
        const names = [];
        for (let i = 0; i < 5; i++) {
            const dup = FieldStabilization.duplicateField(original);
            names.push(dup.field.name);
            window.yapBuilder.schema.fields.push(dup.field);
        }

        // Sprawdź czy wszystkie są unikatowe
        const unique = new Set(names);
        test.passed = unique.size === 5 && names.includes('field_2') && names.includes('field_6');
        test.message = test.passed ? 'All duplicates have unique names' : 'Name collision detected';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 13: ${test.message}`);
    return test;
};

/**
 * Test 14: Deep clone validation
 */
FieldDuplicationTests.test14_DeepCloneValidation = function() {
    const test = {
        name: 'Deep clone validation',
        id: 'dup-14'
    };

    try {
        const original = FieldStabilization.createStableField('text', {
            label: 'Test',
            name: 'test',
            nested: { deep: { value: 123 } }
        });

        const result = FieldStabilization.duplicateField(original);

        // Zmień original, sprawdź czy duplicate się nie zmienił
        original.nested.deep.value = 999;

        test.passed = result.field.nested.deep.value === 123;
        test.message = test.passed ? 'Deep clone working (no reference issues)' : 'Clone has references';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 14: ${test.message}`);
    return test;
};

/**
 * Test 15: Key not locked on duplicate
 */
FieldDuplicationTests.test15_KeyNotLockedOnDuplicate = function() {
    const test = {
        name: 'Key not locked on duplicate',
        id: 'dup-15'
    };

    try {
        const original = FieldStabilization.createStableField('text', { label: 'Test' });
        const locked = FieldStabilization.lockFieldKey(original);

        const result = FieldStabilization.duplicateField(locked);

        test.passed = result.field._locked_key === false;
        test.message = test.passed ? 'Key is unlocked on duplicate' : 'Key should be unlocked';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 15: ${test.message}`);
    return test;
};

// Auto-run on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        window.fieldDuplicationResults = FieldDuplicationTests.runAll();
    });
} else {
    window.fieldDuplicationResults = FieldDuplicationTests.runAll();
}

console.log('✅ Field Duplication Tests loaded');
