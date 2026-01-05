/**
 * Debug Field Presets - Test Addition Flow
 * 
 * Run in console (F12):
 * FieldPresets.debugAddToSchema('address')
 * 
 * @since v1.4.6
 */

console.log('=== 🎯 Field Presets Debug System ===\n');

/**
 * Debug wrapper dla addToSchema
 */
FieldPresets.debugAddToSchema = function(presetName) {
    console.log(`\n${'='.repeat(60)}`);
    console.log(`🎯 DEBUG: Adding preset "${presetName}" to schema`);
    console.log(`${'='.repeat(60)}\n`);
    
    // Step 1: Check preset exists
    console.log('STEP 1: Checking if preset exists...');
    const preset = this.getPreset(presetName);
    if (!preset) {
        console.error('❌ FAILED: Preset not found!');
        console.log('Available presets:', Object.keys(this.getAll()).join(', '));
        return;
    }
    console.log('✅ Preset found:');
    console.log('   Name:', preset.name);
    console.log('   Label:', preset.label);
    console.log('   Fields:', preset.fields.length);
    
    // Step 2: Check schema
    console.log('\nSTEP 2: Checking yapBuilder.schema...');
    if (!window.yapBuilder || !window.yapBuilder.schema) {
        console.error('❌ FAILED: Schema not initialized!');
        console.log('   window.yapBuilder:', window.yapBuilder);
        return;
    }
    console.log('✅ Schema available');
    console.log('   Current fields count:', window.yapBuilder.schema.fields.length);
    
    // Step 3: Create group field
    console.log('\nSTEP 3: Creating group field...');
    const groupField = {
        id: FieldStabilization.generateShortId('fld_'),
        key: FieldStabilization.generateShortId('fld_'),
        name: preset.name,
        label: preset.label,
        type: 'group',
        _created_at: Date.now(),
        _updated_at: Date.now(),
        _locked_key: false,
        sub_fields: preset.fields.map(f => ({
            ...f,
            id: FieldStabilization.generateShortId('fld_'),
            key: FieldStabilization.generateShortId('fld_')
        }))
    };
    console.log('✅ Group field created:');
    console.log('   ID:', groupField.id);
    console.log('   Label:', groupField.label);
    console.log('   Type:', groupField.type);
    console.log('   Sub-fields:', groupField.sub_fields.length);
    
    // Step 4: Add to schema
    console.log('\nSTEP 4: Adding field to schema...');
    window.yapBuilder.schema.fields.push(groupField);
    console.log('✅ Field added to schema');
    console.log('   New fields count:', window.yapBuilder.schema.fields.length);
    
    // Step 5: Check if field is in schema
    console.log('\nSTEP 5: Verifying field was added...');
    const addedField = window.yapBuilder.schema.fields.find(f => f.id === groupField.id);
    if (!addedField) {
        console.error('❌ FAILED: Field not found in schema after adding!');
        return;
    }
    console.log('✅ Field verified in schema');
    console.log('   Found by ID:', addedField.label);
    
    // Step 6: Check history
    console.log('\nSTEP 6: Recording in history...');
    if (typeof FieldHistory !== 'undefined' && FieldHistory.recordAdd) {
        FieldHistory.recordAdd(groupField);
        console.log('✅ Recorded in FieldHistory');
    } else {
        console.warn('⚠️  FieldHistory not available');
    }
    
    // Step 7: Check YAPBuilder
    console.log('\nSTEP 7: Checking YAPBuilder...');
    if (typeof YAPBuilder !== 'undefined') {
        console.log('✅ YAPBuilder available');
        
        if (typeof YAPBuilder.refreshCanvas === 'function') {
            console.log('✅ refreshCanvas method available');
            
            // Step 8: Call refresh
            console.log('\nSTEP 8: Calling YAPBuilder.refreshCanvas()...');
            YAPBuilder.refreshCanvas();
            console.log('✅ refreshCanvas executed');
            
            // Step 9: Verify canvas
            console.log('\nSTEP 9: Verifying canvas...');
            const canvasItems = document.querySelectorAll('.yap-field-item');
            console.log('✅ Canvas items found:', canvasItems.length);
            
            // Find our new field
            const newFieldElement = Array.from(canvasItems).find(el => 
                el.getAttribute('data-field-id') === groupField.id
            );
            if (newFieldElement) {
                console.log('✅ New field element found in DOM!');
                console.log('   Label:', newFieldElement.querySelector('.yap-field-label')?.textContent);
                console.log('   Type:', newFieldElement.querySelector('.yap-field-type')?.textContent);
            } else {
                console.warn('⚠️  New field element NOT found in DOM');
                console.log('   Field ID:', groupField.id);
                console.log('   Looking for element with data-field-id="' + groupField.id + '"');
            }
        } else {
            console.error('❌ FAILED: YAPBuilder.refreshCanvas not a function!');
            console.log('   Type:', typeof YAPBuilder.refreshCanvas);
        }
    } else {
        console.error('❌ FAILED: YAPBuilder not available!');
    }
    
    // Final summary
    console.log(`\n${'='.repeat(60)}`);
    console.log('✅ DEBUG COMPLETE');
    console.log(`${'='.repeat(60)}`);
    console.log('\nNext steps:');
    console.log('1. Check if preset fields are visible on canvas');
    console.log('2. Check for JavaScript errors in console');
    console.log('3. Try refreshing page with Ctrl+Shift+R');
    console.log('\nResult stored in: window.debugAddResult');
    
    return {
        success: true,
        preset: presetName,
        fieldId: groupField.id,
        fieldLabel: groupField.label,
        subFields: groupField.sub_fields.length,
        totalFields: window.yapBuilder.schema.fields.length
    };
};

// Store result globally
window.debugAddResult = null;

/**
 * Quick test all presets
 */
FieldPresets.testAllPresets = function() {
    console.log('\n🎯 Testing ALL presets...\n');
    
    const presets = this.getAll();
    const results = {};
    
    Object.keys(presets).forEach(key => {
        const preset = presets[key];
        console.log(`Testing: ${preset.label}`);
        
        try {
            const fieldCount = window.yapBuilder.schema.fields.length;
            
            const groupField = {
                id: FieldStabilization.generateShortId('fld_'),
                key: FieldStabilization.generateShortId('fld_'),
                name: preset.name,
                label: preset.label,
                type: 'group',
                sub_fields: preset.fields.map(f => ({
                    ...f,
                    id: FieldStabilization.generateShortId('fld_'),
                    key: FieldStabilization.generateShortId('fld_')
                }))
            };
            
            window.yapBuilder.schema.fields.push(groupField);
            
            if (typeof FieldHistory !== 'undefined' && FieldHistory.recordAdd) {
                FieldHistory.recordAdd(groupField);
            }
            
            results[key] = {
                success: true,
                fields: preset.fields.length
            };
            
            console.log(`  ✅ Added ${preset.fields.length} fields`);
        } catch (e) {
            results[key] = {
                success: false,
                error: e.message
            };
            console.error(`  ❌ Error: ${e.message}`);
        }
    });
    
    console.log('\n' + '='.repeat(50));
    console.log('Results:');
    console.table(results);
    console.log('='.repeat(50));
    
    if (typeof YAPBuilder !== 'undefined' && YAPBuilder.refreshCanvas) {
        console.log('\nRefreshing canvas...');
        YAPBuilder.refreshCanvas();
        console.log('✅ Canvas refreshed');
    }
    
    return results;
};

/**
 * Visual check
 */
FieldPresets.visualCheck = function() {
    console.log('\n🔍 Visual Check of Canvas...\n');
    
    const canvas = document.getElementById('yap-drop-zone');
    if (!canvas) {
        console.error('❌ Canvas not found!');
        return;
    }
    
    const fieldItems = canvas.querySelectorAll('.yap-field-item');
    console.log('Field items on canvas:', fieldItems.length);
    
    fieldItems.forEach((el, idx) => {
        const label = el.querySelector('.yap-field-label')?.textContent;
        const type = el.querySelector('.yap-field-type')?.textContent;
        const id = el.getAttribute('data-field-id');
        console.log(`  ${idx + 1}. ${label} (${type}) [${id}]`);
    });
    
    return fieldItems.length;
};

/**
 * Export current schema to JSON
 */
FieldPresets.exportSchema = function() {
    console.log('\n💾 Exporting current schema...\n');
    
    const schema = JSON.stringify(window.yapBuilder.schema, null, 2);
    console.log(schema);
    
    console.log('\nCopied to clipboard ready for export!');
    return window.yapBuilder.schema;
};

console.log('Available debug functions:');
console.log('  FieldPresets.debugAddToSchema("address")');
console.log('  FieldPresets.testAllPresets()');
console.log('  FieldPresets.visualCheck()');
console.log('  FieldPresets.exportSchema()');
console.log('\nTry: FieldPresets.debugAddToSchema("address")\n');
