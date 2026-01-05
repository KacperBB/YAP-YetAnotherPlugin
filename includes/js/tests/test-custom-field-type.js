/**
 * TEST: Custom Field Type - Slug
 * 
 * Tests the SlugFieldType custom implementation
 * Verifies:
 * - Registration in registry
 * - Default values
 * - Validation (length, pattern, separators)
 * - Sanitization (cleanup, conversion)
 * - Rendering (admin HTML)
 * - Slug generation from text
 */

window.YAPCustomFieldTypeTests = window.YAPCustomFieldTypeTests || {};

YAPCustomFieldTypeTests.runAll = function() {
    const testResults = [];

    console.log('🧪 Starting Custom Field Type Tests (Slug)...\n');

    // Test 1: Type is registered
    testResults.push(YAPCustomFieldTypeTests.test1_TypeRegistered());

    // Test 2: Get type from registry
    testResults.push(YAPCustomFieldTypeTests.test2_GetTypeFromRegistry());

    // Test 3: Create field with defaults
    testResults.push(YAPCustomFieldTypeTests.test3_CreateFieldWithDefaults());

    // Test 4: Validate valid slug
    testResults.push(YAPCustomFieldTypeTests.test4_ValidateValidSlug());

    // Test 5: Validate invalid slug (spaces)
    testResults.push(YAPCustomFieldTypeTests.test5_ValidateInvalidSpaces());

    // Test 6: Validate slug too short
    testResults.push(YAPCustomFieldTypeTests.test6_ValidateTooShort());

    // Test 7: Validate slug too long
    testResults.push(YAPCustomFieldTypeTests.test7_ValidateTooLong());

    // Test 8: Validate consecutive separators
    testResults.push(YAPCustomFieldTypeTests.test8_ValidateConsecutiveSeparators());

    // Test 9: Sanitize slug
    testResults.push(YAPCustomFieldTypeTests.test9_SanitizeSlug());

    // Test 10: Sanitize with uppercase
    testResults.push(YAPCustomFieldTypeTests.test10_SanitizeUppercase());

    // Test 11: Sanitize with special chars
    testResults.push(YAPCustomFieldTypeTests.test11_SanitizeSpecialChars());

    // Test 12: Generate slug from text
    testResults.push(YAPCustomFieldTypeTests.test12_GenerateFromText());

    // Test 13: Render admin HTML
    testResults.push(YAPCustomFieldTypeTests.test13_RenderAdmin());

    // Test 14: Render preview
    testResults.push(YAPCustomFieldTypeTests.test14_RenderPreview());

    // Test 15: Settings schema
    testResults.push(YAPCustomFieldTypeTests.test15_SettingsSchema());

    // Summary
    const passed = testResults.filter(r => r.passed).length;
    const total = testResults.length;
    const percentage = ((passed / total) * 100).toFixed(1);

    console.log('\n📊 Custom Field Type Tests Summary:');
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
 * Test 1: Type is registered
 */
YAPCustomFieldTypeTests.test1_TypeRegistered = function() {
    const test = {
        name: 'Type is registered',
        id: 'slug-custom-1'
    };

    try {
        const has = FieldTypeRegistry.has('slug');
        test.passed = has === true;
        test.message = has ? 'SlugFieldType registered' : 'Not registered';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 1: ${test.message}`);
    return test;
};

/**
 * Test 2: Get type from registry
 */
YAPCustomFieldTypeTests.test2_GetTypeFromRegistry = function() {
    const test = {
        name: 'Get type from registry',
        id: 'slug-custom-2'
    };

    try {
        const Type = FieldTypeRegistry.get('slug');
        test.passed = Type && Type.type === 'slug';
        test.message = test.passed ? 'Type retrieved correctly' : 'Type not found or invalid';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 2: ${test.message}`);
    return test;
};

/**
 * Test 3: Create field with defaults
 */
YAPCustomFieldTypeTests.test3_CreateFieldWithDefaults = function() {
    const test = {
        name: 'Create field with defaults',
        id: 'slug-custom-3'
    };

    try {
        const field = FieldTypeRegistry.createField('slug', {
            name: 'page_slug',
            label: 'Page Slug'
        });
        
        test.passed = field.type === 'slug' 
            && field.min_length === 3 
            && field.max_length === 100
            && field.separator === '-';
        test.message = test.passed ? 'Defaults set correctly' : 'Defaults mismatch';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 3: ${test.message}`);
    return test;
};

/**
 * Test 4: Validate valid slug
 */
YAPCustomFieldTypeTests.test4_ValidateValidSlug = function() {
    const test = {
        name: 'Validate valid slug',
        id: 'slug-custom-4'
    };

    try {
        const field = FieldTypeRegistry.createField('slug');
        const result = SlugFieldType.validate('my-awesome-page', field);
        
        test.passed = result.valid === true;
        test.message = test.passed ? 'Valid slug accepted' : `Validation failed: ${result.error}`;
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 4: ${test.message}`);
    return test;
};

/**
 * Test 5: Validate invalid slug (spaces)
 */
YAPCustomFieldTypeTests.test5_ValidateInvalidSpaces = function() {
    const test = {
        name: 'Validate invalid (spaces)',
        id: 'slug-custom-5'
    };

    try {
        const field = FieldTypeRegistry.createField('slug');
        const result = SlugFieldType.validate('my page slug', field);
        
        test.passed = result.valid === false;
        test.message = test.passed ? 'Space rejected correctly' : 'Should reject spaces';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 5: ${test.message}`);
    return test;
};

/**
 * Test 6: Validate slug too short
 */
YAPCustomFieldTypeTests.test6_ValidateTooShort = function() {
    const test = {
        name: 'Validate too short',
        id: 'slug-custom-6'
    };

    try {
        const field = FieldTypeRegistry.createField('slug');
        const result = SlugFieldType.validate('ab', field);
        
        test.passed = result.valid === false && result.error.includes('Minimum');
        test.message = test.passed ? 'Short slug rejected' : 'Should reject short slugs';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 6: ${test.message}`);
    return test;
};

/**
 * Test 7: Validate slug too long
 */
YAPCustomFieldTypeTests.test7_ValidateTooLong = function() {
    const test = {
        name: 'Validate too long',
        id: 'slug-custom-7'
    };

    try {
        const field = FieldTypeRegistry.createField('slug', { max_length: 10 });
        const result = SlugFieldType.validate('this-is-a-very-long-slug', field);
        
        test.passed = result.valid === false && result.error.includes('Maximum');
        test.message = test.passed ? 'Long slug rejected' : 'Should reject long slugs';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 7: ${test.message}`);
    return test;
};

/**
 * Test 8: Validate consecutive separators
 */
YAPCustomFieldTypeTests.test8_ValidateConsecutiveSeparators = function() {
    const test = {
        name: 'Validate consecutive separators',
        id: 'slug-custom-8'
    };

    try {
        const field = FieldTypeRegistry.createField('slug');
        const result = SlugFieldType.validate('my--page', field);
        
        test.passed = result.valid === false && result.error.includes('consecutive');
        test.message = test.passed ? 'Consecutive separators rejected' : 'Should reject consecutive separators';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 8: ${test.message}`);
    return test;
};

/**
 * Test 9: Sanitize slug
 */
YAPCustomFieldTypeTests.test9_SanitizeSlug = function() {
    const test = {
        name: 'Sanitize slug',
        id: 'slug-custom-9'
    };

    try {
        const result = SlugFieldType.sanitize('My Awesome Page!');
        
        test.passed = result === 'my-awesome-page';
        test.message = test.passed ? 'Sanitized correctly' : `Got: "${result}"`;
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 9: ${test.message}`);
    return test;
};

/**
 * Test 10: Sanitize with uppercase
 */
YAPCustomFieldTypeTests.test10_SanitizeUppercase = function() {
    const test = {
        name: 'Sanitize uppercase',
        id: 'slug-custom-10'
    };

    try {
        const result = SlugFieldType.sanitize('HELLO_WORLD');
        
        test.passed = result === 'hello-world';
        test.message = test.passed ? 'Uppercase converted' : `Got: "${result}"`;
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 10: ${test.message}`);
    return test;
};

/**
 * Test 11: Sanitize with special chars
 */
YAPCustomFieldTypeTests.test11_SanitizeSpecialChars = function() {
    const test = {
        name: 'Sanitize special chars',
        id: 'slug-custom-11'
    };

    try {
        const result = SlugFieldType.sanitize('Hello@World#123$%');
        
        test.passed = result === 'helloworld123';
        test.message = test.passed ? 'Special chars removed' : `Got: "${result}"`;
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 11: ${test.message}`);
    return test;
};

/**
 * Test 12: Generate slug from text
 */
YAPCustomFieldTypeTests.test12_GenerateFromText = function() {
    const test = {
        name: 'Generate slug from text',
        id: 'slug-custom-12'
    };

    try {
        const result = SlugFieldType.generateFromText('My Awesome Blog Post');
        
        test.passed = result === 'my-awesome-blog-post';
        test.message = test.passed ? 'Generated correctly' : `Got: "${result}"`;
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 12: ${test.message}`);
    return test;
};

/**
 * Test 13: Render admin HTML
 */
YAPCustomFieldTypeTests.test13_RenderAdmin = function() {
    const test = {
        name: 'Render admin HTML',
        id: 'slug-custom-13'
    };

    try {
        const field = FieldTypeRegistry.createField('slug', {
            name: 'test_slug',
            label: 'Test Slug'
        });
        const html = SlugFieldType.renderAdmin(field, 'test-value');
        
        test.passed = html.includes('type="text"') 
            && html.includes('name="test_slug"')
            && html.includes('Test Slug')
            && html.includes('slug-input');
        test.message = test.passed ? 'HTML rendered correctly' : 'HTML mismatch';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 13: ${test.message}`);
    return test;
};

/**
 * Test 14: Render preview
 */
YAPCustomFieldTypeTests.test14_RenderPreview = function() {
    const test = {
        name: 'Render preview',
        id: 'slug-custom-14'
    };

    try {
        const field = FieldTypeRegistry.createField('slug', {
            label: 'Page Slug',
            auto_generate: true
        });
        const html = SlugFieldType.renderPreview(field);
        
        test.passed = html.includes('slug-preview') 
            && html.includes('Page Slug')
            && html.includes('auto-generated');
        test.message = test.passed ? 'Preview rendered correctly' : 'Preview mismatch';
    } catch (error) {
        test.passed = false;
        test.message = error.message;
    }

    console.log(`${test.passed ? '✅' : '❌'} Test 14: ${test.message}`);
    return test;
};

/**
 * Test 15: Settings schema
 */
YAPCustomFieldTypeTests.test15_SettingsSchema = function() {
    const test = {
        name: 'Settings schema',
        id: 'slug-custom-15'
    };

    try {
        const schema = SlugFieldType.settingsSchema();
        
        // Schema should have multiple panels
        test.passed = Array.isArray(schema) 
            && schema.length > 1
            && schema[1].fields.some(f => f.name === 'auto_generate')
            && schema[1].fields.some(f => f.name === 'separator');
        test.message = test.passed ? 'Schema is valid' : 'Schema incomplete';
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
        window.customFieldTypeResults = YAPCustomFieldTypeTests.runAll();
    });
} else {
    window.customFieldTypeResults = YAPCustomFieldTypeTests.runAll();
}

console.log('✅ Custom Field Type Tests loaded');
