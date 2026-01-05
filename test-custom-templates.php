/**
 * Test Custom Templates System
 * Otwórz w konsoli (F12) na stronie Visual Builderu
 * 
 * @since v1.0.0
 */

console.log('=== 🎨 Custom Templates Test Suite ===');

// Test 1: Sprawdzenie czy system jest załadowany
console.log('\n📋 TEST 1: Sprawdzenie załadowania');
function test1_LoadCheck() {
    console.log('✓ CustomTemplates available?', typeof CustomTemplates !== 'undefined');
    console.log('✓ YAPBuilder available?', typeof YAPBuilder !== 'undefined');
    console.log('✓ FieldHistory available?', typeof FieldHistory !== 'undefined');
    console.log('✓ FieldStabilization available?', typeof FieldStabilization !== 'undefined');
}
test1_LoadCheck();

// Test 2: Stwórz prosty template
console.log('\n📋 TEST 2: Stwórz prosty template (test_simple)');
function test2_CreateSimple() {
    const templateData = {
        name: 'simple_contact',
        label: '📧 Prosty Kontakt',
        description: 'Test template - email',
        fields: [
            {
                name: 'email_simple',
                label: 'Email',
                type: 'email',
                required: true,
                placeholder: 'test@example.com'
            }
        ]
    };
    
    const result = CustomTemplates.save('test_simple', templateData);
    console.log('✓ Template saved:', result);
    return result;
}
window.test2Result = test2_CreateSimple();

// Test 3: Stwórz zaawansowany template
console.log('\n📋 TEST 3: Stwórz zaawansowany template (test_advanced)');
function test3_CreateAdvanced() {
    const templateData = {
        name: 'full_address',
        label: '📍 Pełny Adres',
        description: 'Test template - complete address with validation',
        fields: [
            {
                name: 'street',
                label: 'Ulica',
                type: 'text',
                required: true,
                placeholder: 'ul. Przykładowa 1'
            },
            {
                name: 'city',
                label: 'Miasto',
                type: 'text',
                required: true,
                placeholder: 'Warszawa'
            },
            {
                name: 'postal_code',
                label: 'Kod pocztowy',
                type: 'text',
                required: false,
                placeholder: '00-000'
            },
            {
                name: 'country',
                label: 'Kraj',
                type: 'select',
                required: true
            }
        ]
    };
    
    const result = CustomTemplates.save('test_advanced', templateData);
    console.log('✓ Advanced template saved:', result);
    return result;
}
window.test3Result = test3_CreateAdvanced();

// Test 4: Pobierz wszystkie szablony
console.log('\n📋 TEST 4: Pobierz wszystkie szablony');
function test4_GetAll() {
    const all = CustomTemplates.getAll();
    console.log('✓ All templates:', all);
    console.log('✓ Total templates:', Object.keys(all).length);
    return all;
}
window.test4Result = test4_GetAll();

// Test 5: Pobierz konkretny template
console.log('\n📋 TEST 5: Pobierz konkretny template (test_simple)');
function test5_GetOne() {
    const template = CustomTemplates.getTemplate('test_simple');
    console.log('✓ Template found:', template);
    return template;
}
window.test5Result = test5_GetOne();

// Test 6: Dodaj template do kanwy
console.log('\n📋 TEST 6: Dodaj template do schematu (test_simple)');
function test6_AddToSchema() {
    console.log('  Przed dodaniem: fields =', window.yapBuilder.schema.fields.length);
    
    const result = CustomTemplates.addToSchema('test_simple');
    
    console.log('✓ Add result:', result);
    console.log('✓ Po dodaniu: fields =', window.yapBuilder.schema.fields.length);
    console.log('✓ Nowe pole ID:', result.field?.id);
    console.log('✓ Liczba sub_fields:', result.field?.sub_fields?.length);
    
    return result;
}
window.test6Result = test6_AddToSchema();

// Test 7: Refresh selector
console.log('\n📋 TEST 7: Odśwież selektor pól');
function test7_RefreshSelector() {
    CustomTemplates.refreshFieldSelector();
    console.log('✓ Selector refreshed');
    
    const customItems = document.querySelectorAll('.yap-custom-template');
    console.log('✓ Custom template items w DOM:', customItems.length);
}
test7_RefreshSelector();

// Test 8: Historia integracja
console.log('\n📋 TEST 8: Sprawdź integrację z historią');
function test8_HistoryIntegration() {
    const timeline = FieldHistory.getTimeline();
    console.log('✓ History timeline length:', timeline.length);
    console.log('✓ Last 3 operations:');
    timeline.slice(-3).forEach((op, idx) => {
        console.log(`  ${idx + 1}. ${op.operation} - ${op.timestamp}`);
    });
}
test8_HistoryIntegration();

// Test 9: Stwórz z emoji label
console.log('\n📋 TEST 9: Template z emoji label');
function test9_WithEmoji() {
    const templateData = {
        name: 'product_info',
        label: '📦 Informacje o Produkcie',
        description: 'Szablon z emoji',
        fields: [
            {
                name: 'product_name',
                label: '🏷️ Nazwa produktu',
                type: 'text',
                required: true
            },
            {
                name: 'product_price',
                label: '💰 Cena',
                type: 'number',
                required: true
            },
            {
                name: 'product_description',
                label: '📝 Opis',
                type: 'textarea'
            }
        ]
    };
    
    const result = CustomTemplates.save('test_emoji', templateData);
    console.log('✓ Emoji template saved:', result.label);
    return result;
}
window.test9Result = test9_WithEmoji();

// Test 10: Dodaj drugi template
console.log('\n📋 TEST 10: Dodaj zaawansowany template do schematu');
function test10_AddAdvanced() {
    const before = window.yapBuilder.schema.fields.length;
    const result = CustomTemplates.addToSchema('test_advanced');
    const after = window.yapBuilder.schema.fields.length;
    
    console.log('✓ Fields before:', before);
    console.log('✓ Fields after:', after);
    console.log('✓ New field label:', result.field?.label);
    console.log('✓ Sub-fields count:', result.field?.sub_fields?.length);
    
    return result;
}
window.test10Result = test10_AddAdvanced();

// Test 11: Usuń template
console.log('\n📋 TEST 11: Usuń template (test_simple)');
function test11_Delete() {
    const before = Object.keys(CustomTemplates.getAll()).length;
    CustomTemplates.delete('test_simple');
    const after = Object.keys(CustomTemplates.getAll()).length;
    
    console.log('✓ Templates before:', before);
    console.log('✓ Templates after:', after);
    console.log('✓ Test deleted?', !CustomTemplates.getTemplate('test_simple'));
}
test11_Delete();

// Test 12: Undo/Redo funkcjonalność
console.log('\n📋 TEST 12: Undo/Redo funkcjonalność');
function test12_UndoRedo() {
    // Add template
    const before = window.yapBuilder.schema.fields.length;
    CustomTemplates.addToSchema('test_advanced');
    const afterAdd = window.yapBuilder.schema.fields.length;
    
    console.log('✓ Fields before add:', before);
    console.log('✓ Fields after add:', afterAdd);
    
    // Try to undo
    if (typeof YAPBuilder.undo === 'function') {
        YAPBuilder.undo();
        const afterUndo = window.yapBuilder.schema.fields.length;
        console.log('✓ Fields after undo:', afterUndo);
        console.log('✓ Undo worked?', afterUndo === before);
    } else {
        console.log('⚠️  YAPBuilder.undo not available');
    }
}
test12_UndoRedo();

// Test 13: localStorage check
console.log('\n📋 TEST 13: Sprawdzenie localStorage');
function test13_StorageCheck() {
    const stored = localStorage.getItem('yap_custom_templates');
    const parsed = stored ? JSON.parse(stored) : null;
    
    console.log('✓ localStorage key exists?', !!stored);
    console.log('✓ Data in storage:', Object.keys(parsed || {}).length, 'templates');
    console.log('✓ Storage size:', (stored?.length || 0), 'bytes');
}
test13_StorageCheck();

// Test 14: Drag handler binding
console.log('\n📋 TEST 14: Drag handler binding');
function test14_DragHandlers() {
    CustomTemplates.refreshFieldSelector();
    const draggables = document.querySelectorAll('[draggable="true"].yap-custom-template');
    console.log('✓ Draggable items found:', draggables.length);
    
    draggables.forEach((el, idx) => {
        console.log(`  ${idx + 1}. ${el.textContent.trim()}`);
    });
}
test14_DragHandlers();

// Test 15: API completeness
console.log('\n📋 TEST 15: Sprawdzenie API completeness');
function test15_ApiCheck() {
    const methods = [
        'getAll',
        'getTemplate',
        'save',
        'delete',
        'addToSchema',
        'createFromSelection',
        'showCreationModal',
        'refreshFieldSelector',
        'bindCustomTemplateDragHandlers'
    ];
    
    const available = methods.filter(m => typeof CustomTemplates[m] === 'function');
    console.log('✓ Available methods:', available.length, '/', methods.length);
    
    const missing = methods.filter(m => typeof CustomTemplates[m] !== 'function');
    if (missing.length > 0) {
        console.log('✗ Missing methods:', missing);
    } else {
        console.log('✓ All methods available!');
    }
}
test15_ApiCheck();

// Summary
console.log('\n' + '='.repeat(50));
console.log('✅ Test Suite Complete!');
console.log('='.repeat(50));
console.log('\nQuick commands:');
console.log('  CustomTemplates.getAll()');
console.log('  CustomTemplates.addToSchema("test_advanced")');
console.log('  CustomTemplates.createFromSelection([...])');
console.log('  window.test2Result, window.test3Result, etc.');
