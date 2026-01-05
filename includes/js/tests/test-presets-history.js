/**
 * Tests: Field Presets Library & Undo/Redo System
 * 
 * 20 comprehensive tests covering:
 * - All 11 presets functionality
 * - Undo/Redo operations
 * - History tracking
 * - Batch operations
 */

window.PresetsAndHistoryTests = window.PresetsAndHistoryTests || {};

PresetsAndHistoryTests.runAll = function() {
    console.log('🧪 Starting Presets & History Tests...\n');
    const results = [];

    // ===== PRESET TESTS =====
    results.push(PresetsAndHistoryTests.test1_GetAllPresets());
    results.push(PresetsAndHistoryTests.test2_GetPresetByName());
    results.push(PresetsAndHistoryTests.test3_AddressPreset());
    results.push(PresetsAndHistoryTests.test4_CTAPreset());
    results.push(PresetsAndHistoryTests.test5_SEOPreset());
    results.push(PresetsAndHistoryTests.test6_ProductPreset());
    results.push(PresetsAndHistoryTests.test7_GetByCategory());
    results.push(PresetsAndHistoryTests.test8_AddPresetToSchema());
    results.push(PresetsAndHistoryTests.test9_RenderPresetSelector());

    // ===== HISTORY TESTS =====
    results.push(PresetsAndHistoryTests.test10_InitializeHistory());
    results.push(PresetsAndHistoryTests.test11_RecordAdd());
    results.push(PresetsAndHistoryTests.test12_RecordDelete());
    results.push(PresetsAndHistoryTests.test13_RecordEdit());
    results.push(PresetsAndHistoryTests.test14_UndoOperation());
    results.push(PresetsAndHistoryTests.test15_RedoOperation());
    results.push(PresetsAndHistoryTests.test16_GetPosition());
    results.push(PresetsAndHistoryTests.test17_GetTimeline());
    results.push(PresetsAndHistoryTests.test18_GetStats());
    results.push(PresetsAndHistoryTests.test19_BatchOperations());
    results.push(PresetsAndHistoryTests.test20_RenderHistoryUI());

    const passed = results.filter(r => r.passed).length;
    const total = results.length;
    const percentage = ((passed / total) * 100).toFixed(1);

    console.log('\n📊 Presets & History Tests Summary:');
    console.log(`✅ Passed: ${passed}/${total} (${percentage}%)\n`);

    return {
        tests: results,
        passed,
        total,
        percentage,
        allPassed: passed === total
    };
};

/**
 * Test 1: Get all presets
 */
PresetsAndHistoryTests.test1_GetAllPresets = function() {
    const test = { name: 'Get all presets', id: 'p-1' };
    try {
        const all = FieldPresets.getAll();
        test.passed = Object.keys(all).length >= 11;
        test.message = test.passed ? `Found ${Object.keys(all).length} presets` : 'Not all presets found';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 1: ${test.message}`);
    return test;
};

/**
 * Test 2: Get preset by name
 */
PresetsAndHistoryTests.test2_GetPresetByName = function() {
    const test = { name: 'Get preset by name', id: 'p-2' };
    try {
        const preset = FieldPresets.getPreset('address');
        test.passed = preset && preset.name === 'address' && preset.fields.length === 4;
        test.message = test.passed ? 'Address preset loaded correctly' : 'Preset mismatch';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 2: ${test.message}`);
    return test;
};

/**
 * Test 3: Address preset
 */
PresetsAndHistoryTests.test3_AddressPreset = function() {
    const test = { name: 'Address preset structure', id: 'p-3' };
    try {
        const preset = FieldPresets.getPreset('address');
        const fields = preset.fields.map(f => f.name);
        test.passed = 
            fields.includes('country') &&
            fields.includes('city') &&
            fields.includes('postal_code') &&
            fields.includes('street');
        test.message = test.passed ? 'All address fields present' : 'Missing address fields';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 3: ${test.message}`);
    return test;
};

/**
 * Test 4: CTA Button preset
 */
PresetsAndHistoryTests.test4_CTAPreset = function() {
    const test = { name: 'CTA Button preset', id: 'p-4' };
    try {
        const preset = FieldPresets.getPreset('ctaButton');
        const fields = preset.fields.map(f => f.name);
        test.passed = 
            fields.includes('button_label') &&
            fields.includes('button_url') &&
            fields.includes('button_target') &&
            fields.includes('button_style');
        test.message = test.passed ? 'CTA preset complete' : 'CTA fields incomplete';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 4: ${test.message}`);
    return test;
};

/**
 * Test 5: SEO preset
 */
PresetsAndHistoryTests.test5_SEOPreset = function() {
    const test = { name: 'SEO preset', id: 'p-5' };
    try {
        const preset = FieldPresets.getPreset('seo');
        const fields = preset.fields.map(f => f.name);
        test.passed = 
            fields.includes('meta_title') &&
            fields.includes('meta_description') &&
            fields.includes('noindex') &&
            fields.includes('canonical_url');
        test.message = test.passed ? 'SEO preset complete' : 'SEO fields incomplete';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 5: ${test.message}`);
    return test;
};

/**
 * Test 6: Product preset
 */
PresetsAndHistoryTests.test6_ProductPreset = function() {
    const test = { name: 'Product preset', id: 'p-6' };
    try {
        const preset = FieldPresets.getPreset('product');
        const fields = preset.fields.map(f => f.name);
        test.passed = 
            fields.includes('price') &&
            fields.includes('currency') &&
            fields.includes('stock_quantity') &&
            fields.includes('product_gallery');
        test.message = test.passed ? 'Product preset complete' : 'Product fields incomplete';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 6: ${test.message}`);
    return test;
};

/**
 * Test 7: Get by category
 */
PresetsAndHistoryTests.test7_GetByCategory = function() {
    const test = { name: 'Get presets by category', id: 'p-7' };
    try {
        const contact = FieldPresets.getByCategory('contact');
        const meta = FieldPresets.getByCategory('meta');
        test.passed = contact.length > 0 && meta.length > 0;
        test.message = test.passed ? `Categories working (contact: ${contact.length}, meta: ${meta.length})` : 'Category filtering failed';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 7: ${test.message}`);
    return test;
};

/**
 * Test 8: Add preset to schema
 */
PresetsAndHistoryTests.test8_AddPresetToSchema = function() {
    const test = { name: 'Add preset to schema', id: 'p-8' };
    try {
        window.yapBuilder = window.yapBuilder || { schema: { fields: [] } };
        const beforeCount = window.yapBuilder.schema.fields.length;
        
        const result = FieldPresets.addToSchema('seo');
        
        test.passed = 
            result.success === true &&
            window.yapBuilder.schema.fields.length > beforeCount &&
            result.fieldCount === 5;
        test.message = test.passed ? 'Preset added to schema' : 'Schema update failed';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 8: ${test.message}`);
    return test;
};

/**
 * Test 9: Render preset selector
 */
PresetsAndHistoryTests.test9_RenderPresetSelector = function() {
    const test = { name: 'Render preset selector', id: 'p-9' };
    try {
        const html = FieldPresets.renderSelector();
        test.passed = 
            html.includes('preset-selector') &&
            html.includes('preset-tab') &&
            html.includes('preset-button') &&
            html.includes('contact') &&
            html.includes('meta');
        test.message = test.passed ? 'Selector HTML generated' : 'HTML generation failed';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 9: ${test.message}`);
    return test;
};

/**
 * Test 10: Initialize history
 */
PresetsAndHistoryTests.test10_InitializeHistory = function() {
    const test = { name: 'Initialize history', id: 'h-10' };
    try {
        FieldHistory.clear();
        FieldHistory.init();
        test.passed = 
            FieldHistory.state.history.length === 0 &&
            FieldHistory.state.currentIndex === -1;
        test.message = test.passed ? 'History initialized' : 'Initialization failed';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 10: ${test.message}`);
    return test;
};

/**
 * Test 11: Record add
 */
PresetsAndHistoryTests.test11_RecordAdd = function() {
    const test = { name: 'Record add operation', id: 'h-11' };
    try {
        FieldHistory.clear();
        // Preserve yapBuilder.fieldTypes from wp_localize when resetting schema for tests
        window.yapBuilder = { ...window.yapBuilder, schema: { fields: [] } };
        const field = { id: 'fld_1', name: 'test', type: 'text', label: 'Test' };
        window.yapBuilder.schema.fields.push(field);
        
        FieldHistory.recordAdd(field);
        
        test.passed = 
            FieldHistory.state.history.length === 1 &&
            FieldHistory.state.history[0].type === 'add';
        test.message = test.passed ? 'Add recorded' : 'Recording failed';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 11: ${test.message}`);
    return test;
};

/**
 * Test 12: Record delete
 */
PresetsAndHistoryTests.test12_RecordDelete = function() {
    const test = { name: 'Record delete operation', id: 'h-12' };
    try {
        FieldHistory.clear();
        // Preserve yapBuilder.fieldTypes from wp_localize when resetting schema for tests
        window.yapBuilder = { ...window.yapBuilder, schema: { fields: [
            { id: 'fld_1', name: 'test', type: 'text', label: 'Test' }
        ] } };
        
        FieldHistory.recordDelete('fld_1');
        
        test.passed = 
            FieldHistory.state.history.length === 1 &&
            FieldHistory.state.history[0].type === 'delete';
        test.message = test.passed ? 'Delete recorded' : 'Recording failed';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 12: ${test.message}`);
    return test;
};

/**
 * Test 13: Record edit
 */
PresetsAndHistoryTests.test13_RecordEdit = function() {
    const test = { name: 'Record edit operation', id: 'h-13' };
    try {
        FieldHistory.clear();
        // Preserve yapBuilder.fieldTypes from wp_localize when resetting schema for tests
        window.yapBuilder = { ...window.yapBuilder, schema: { fields: [
            { id: 'fld_1', name: 'test', type: 'text', label: 'Test' }
        ] } };
        
        FieldHistory.recordEdit('fld_1', { label: 'Old' }, { label: 'New' });
        
        test.passed = 
            FieldHistory.state.history.length === 1 &&
            FieldHistory.state.history[0].type === 'edit';
        test.message = test.passed ? 'Edit recorded' : 'Recording failed';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 13: ${test.message}`);
    return test;
};

/**
 * Test 14: Undo operation
 */
PresetsAndHistoryTests.test14_UndoOperation = function() {
    const test = { name: 'Undo operation', id: 'h-14' };
    try {
        FieldHistory.clear();
        // Preserve yapBuilder.fieldTypes from wp_localize when resetting schema for tests
        window.yapBuilder = { ...window.yapBuilder, schema: { fields: [] } };
        const field = { id: 'fld_1', name: 'test', type: 'text', label: 'Test' };
        
        window.yapBuilder.schema.fields.push(field);
        FieldHistory.recordAdd(field);
        
        const result = FieldHistory.undo();
        
        test.passed = 
            result.success === true &&
            FieldHistory.state.currentIndex === -1;
        test.message = test.passed ? 'Undo successful' : 'Undo failed';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 14: ${test.message}`);
    return test;
};

/**
 * Test 15: Redo operation
 */
PresetsAndHistoryTests.test15_RedoOperation = function() {
    const test = { name: 'Redo operation', id: 'h-15' };
    try {
        FieldHistory.clear();
        // Preserve yapBuilder.fieldTypes from wp_localize when resetting schema for tests
        window.yapBuilder = { ...window.yapBuilder, schema: { fields: [] } };
        const field = { id: 'fld_1', name: 'test', type: 'text', label: 'Test' };
        
        window.yapBuilder.schema.fields.push(field);
        FieldHistory.recordAdd(field);
        FieldHistory.undo();
        
        const result = FieldHistory.redo();
        
        test.passed = result.success === true && FieldHistory.state.currentIndex === 0;
        test.message = test.passed ? 'Redo successful' : 'Redo failed';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 15: ${test.message}`);
    return test;
};

/**
 * Test 16: Get current position
 */
PresetsAndHistoryTests.test16_GetPosition = function() {
    const test = { name: 'Get current position', id: 'h-16' };
    try {
        FieldHistory.clear();
        // Preserve yapBuilder.fieldTypes from wp_localize when resetting schema for tests
        window.yapBuilder = { ...window.yapBuilder, schema: { fields: [] } };
        const field = { id: 'fld_1', name: 'test', type: 'text', label: 'Test' };
        
        window.yapBuilder.schema.fields.push(field);
        FieldHistory.recordAdd(field);
        
        const pos = FieldHistory.getCurrentPosition();
        
        test.passed = 
            pos.current === 1 &&
            pos.total === 1 &&
            pos.canUndo === true &&
            pos.canRedo === false;
        test.message = test.passed ? 'Position correct' : 'Position incorrect';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 16: ${test.message}`);
    return test;
};

/**
 * Test 17: Get timeline
 */
PresetsAndHistoryTests.test17_GetTimeline = function() {
    const test = { name: 'Get history timeline', id: 'h-17' };
    try {
        FieldHistory.clear();
        // Preserve yapBuilder.fieldTypes from wp_localize when resetting schema for tests
        window.yapBuilder = { ...window.yapBuilder, schema: { fields: [] } };
        const field = { id: 'fld_1', name: 'test', type: 'text', label: 'Test' };
        
        for (let i = 0; i < 5; i++) {
            window.yapBuilder.schema.fields.push({ ...field, id: 'fld_' + i });
            FieldHistory.recordAdd(window.yapBuilder.schema.fields[i]);
        }
        
        const timeline = FieldHistory.getTimeline();
        
        test.passed = 
            timeline.length > 0 &&
            timeline[0].description !== undefined &&
            timeline[0].timestamp !== undefined;
        test.message = test.passed ? 'Timeline retrieved' : 'Timeline retrieval failed';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 17: ${test.message}`);
    return test;
};

/**
 * Test 18: Get statistics
 */
PresetsAndHistoryTests.test18_GetStats = function() {
    const test = { name: 'Get history statistics', id: 'h-18' };
    try {
        FieldHistory.clear();
        // Preserve yapBuilder.fieldTypes from wp_localize when resetting schema for tests
        window.yapBuilder = { ...window.yapBuilder, schema: { fields: [] } };
        const field = { id: 'fld_1', name: 'test', type: 'text', label: 'Test' };
        
        window.yapBuilder.schema.fields.push(field);
        FieldHistory.recordAdd(field);
        FieldHistory.recordEdit('fld_1', {}, {});
        
        const stats = FieldHistory.getStats();
        
        test.passed = 
            stats.total === 2 &&
            stats.adds === 1 &&
            stats.edits === 1;
        test.message = test.passed ? 'Stats correct' : 'Stats incorrect';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 18: ${test.message}`);
    return test;
};

/**
 * Test 19: Batch operations
 */
PresetsAndHistoryTests.test19_BatchOperations = function() {
    const test = { name: 'Batch operations', id: 'h-19' };
    try {
        FieldHistory.clear();
        // Preserve yapBuilder.fieldTypes from wp_localize when resetting schema for tests
        window.yapBuilder = { ...window.yapBuilder, schema: { fields: [] } };
        const field = { id: 'fld_1', name: 'test', type: 'text', label: 'Test' };
        
        FieldHistory.startBatch('Test batch');
        
        for (let i = 0; i < 3; i++) {
            window.yapBuilder.schema.fields.push({ ...field, id: 'fld_' + i });
            FieldHistory.recordAdd(window.yapBuilder.schema.fields[i]);
        }
        
        const result = FieldHistory.commitBatch();
        
        test.passed = 
            result.success === true &&
            FieldHistory.state.history.length === 1 &&
            FieldHistory.state.history[0].type === 'batch';
        test.message = test.passed ? 'Batch committed' : 'Batch commit failed';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 19: ${test.message}`);
    return test;
};

/**
 * Test 20: Render history UI
 */
PresetsAndHistoryTests.test20_RenderHistoryUI = function() {
    const test = { name: 'Render history UI', id: 'h-20' };
    try {
        const controls = FieldHistory.renderControls();
        const timeline = FieldHistory.renderTimeline();
        const panel = FieldHistory.renderPanel();
        
        test.passed = 
            controls.includes('history-btn') &&
            timeline.includes('timeline-item') &&
            panel.includes('history-panel');
        test.message = test.passed ? 'UI rendered' : 'UI rendering failed';
    } catch (e) {
        test.passed = false;
        test.message = e.message;
    }
    console.log(`${test.passed ? '✅' : '❌'} Test 20: ${test.message}`);
    return test;
};

// Auto-run on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        window.presetsAndHistoryResults = PresetsAndHistoryTests.runAll();
    });
} else {
    window.presetsAndHistoryResults = PresetsAndHistoryTests.runAll();
}

console.log('✅ Presets & History Tests loaded');
