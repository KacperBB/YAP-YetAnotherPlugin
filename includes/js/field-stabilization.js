/**
 * Field Stabilization System
 * 
 * Automatyczne generowanie name/key
 * - name generowany z label (Imię i nazwisko → imie_i_nazwisko)
 * - key jako stały UUID/shortid (nie zmienia się nigdy)
 * - Po pierwszym zapisie: blokuj zmianę key
 * - Ostrzeżenia przy zmianie name
 * 
 * @since 1.5.0
 */

window.FieldStabilization = window.FieldStabilization || {};

/**
 * Generuj krótki unique ID dla klucza pola
 * Format: fld_XXXXX (5 znaków)
 */
FieldStabilization.generateShortId = function() {
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    let id = 'fld_';
    for (let i = 0; i < 8; i++) {
        id += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return id;
};

/**
 * Konwertuj label na name
 * "Imię i nazwisko" → "imie_i_nazwisko"
 * "E-mail (kontakt)" → "e_mail_kontakt"
 */
FieldStabilization.labelToName = function(label) {
    if (!label) return '';
    
    return label
        .toLowerCase()
        // Zamień polskie znaki
        .replace(/ą/g, 'a')
        .replace(/ć/g, 'c')
        .replace(/ę/g, 'e')
        .replace(/ł/g, 'l')
        .replace(/ń/g, 'n')
        .replace(/ó/g, 'o')
        .replace(/ś/g, 's')
        .replace(/ź/g, 'z')
        .replace(/ż/g, 'z')
        // Usuń znaki specjalne, zachowaj spacje
        .replace(/[^\w\s]/g, '')
        // Zamień spacje na underscore
        .replace(/\s+/g, '_')
        // Usuń wielokrotne underscores
        .replace(/_+/g, '_')
        // Usuń leading/trailing underscores
        .replace(/^_+|_+$/g, '');
};

/**
 * Waliduj name - czy jest unikalny?
 */
FieldStabilization.isNameUnique = function(name, excludeFieldId = null) {
    if (!window.yapBuilder || !window.yapBuilder.schema) {
        return true; // Nie możemy sprawdzić
    }
    
    return !window.yapBuilder.schema.fields.some(field => 
        field.name === name && field.id !== excludeFieldId
    );
};

/**
 * Stwórz pole ze stabilnymi wartościami
 */
FieldStabilization.createStableField = function(fieldType, config = {}) {
    const field = {
        id: FieldStabilization.generateShortId(),
        key: FieldStabilization.generateShortId(), // Stały klucz
        type: fieldType,
        name: config.name || '',
        label: config.label || '',
        ...config,
        _created_at: new Date().toISOString(),
        _locked_key: false, // Key nie jest jeszcze zablokowany
    };
    
    // Jeśli label ale brak name, generuj z label
    if (field.label && !field.name) {
        field.name = FieldStabilization.labelToName(field.label);
    }
    
    return field;
};

/**
 * Update pole z walidacją i ostrzeżeniami
 */
FieldStabilization.updateField = function(field, updates) {
    const warnings = [];
    const errors = [];
    
    // Ostrzeżenie: zmiana name
    if (updates.name && updates.name !== field.name) {
        warnings.push({
            type: 'name_change',
            message: `Zmiana nazwy pola z "${field.name}" na "${updates.name}" może wpłynąć na:
            - API REST (zmieni się endpoint)
            - Zapisane dane (mogą być niedostępne)
            - Migracje bazy danych
            - Integracje zewnętrzne`,
            action: 'continue_or_cancel'
        });
    }
    
    // Blokada: key nie może się zmienić po pierwszym zapisie
    if (updates.key && updates.key !== field.key) {
        if (field._locked_key) {
            errors.push({
                type: 'key_locked',
                message: `Klucz pola nie może być zmieniony po zapisie. Bieżący klucz: ${field.key}`
            });
        } else {
            warnings.push({
                type: 'key_change_first_time',
                message: 'Po pierwszym zapisie, klucz pola będzie zablokowany i nie będzie możliwe jego zmienienie.'
            });
        }
    }
    
    // Walidacja: name musi być unikalny
    if (updates.name && !FieldStabilization.isNameUnique(updates.name, field.id)) {
        errors.push({
            type: 'name_not_unique',
            message: `Nazwa "${updates.name}" jest już używana przez inne pole`
        });
    }
    
    // Jeśli są błędy, zwróć je
    if (errors.length > 0) {
        return {
            success: false,
            errors,
            warnings,
            field: field // Niezmienione
        };
    }
    
    // Aktualizuj pole
    const updatedField = {
        ...field,
        ...updates,
        _updated_at: new Date().toISOString()
    };
    
    return {
        success: true,
        errors: [],
        warnings,
        field: updatedField
    };
};

/**
 * Zablokuj klucz pola po zapisie
 */
FieldStabilization.lockFieldKey = function(field) {
    return {
        ...field,
        _locked_key: true,
        _locked_at: new Date().toISOString()
    };
};

/**
 * Pokaż ostrzeżenia użytkownikowi
 */
FieldStabilization.showWarnings = function(warnings) {
    if (warnings.length === 0) return true;
    
    let message = 'Ostrzeżenia:\n\n';
    warnings.forEach((warn, i) => {
        message += `${i + 1}. ${warn.message}\n`;
    });
    message += '\nCzy chcesz kontynuować?';
    
    return confirm(message);
};

/**
 * Pokaż błędy użytkownikowi
 */
FieldStabilization.showErrors = function(errors) {
    if (errors.length === 0) return;
    
    let message = 'Błędy walidacji:\n\n';
    errors.forEach((err, i) => {
        message += `${i + 1}. ${err.message}\n`;
    });
    
    alert(message);
};

/**
 * Helper do wyświetlenia statusu klucza
 */
FieldStabilization.getKeyStatus = function(field) {
    if (!field._locked_key) {
        return {
            status: 'unlocked',
            icon: '🔓',
            label: 'Niezablokowany (zmienialny)',
            color: '#ff9800'
        };
    }
    
    return {
        status: 'locked',
        icon: '🔒',
        label: 'Zablokowany (stały)',
        color: '#4caf50'
    };
};

/**
 * Eksportuj do HTML (dla Visual Buildera)
 */
FieldStabilization.renderFieldHeader = function(field) {
    const keyStatus = FieldStabilization.getKeyStatus(field);
    
    return `
        <div class="field-header" style="padding: 10px; background: #f5f5f5; border-radius: 4px; margin-bottom: 10px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong>${field.label || 'Bez nazwy'}</strong>
                    <br>
                    <code style="font-size: 12px; color: #666;">name: ${field.name || 'auto'}</code>
                </div>
                <div style="text-align: right;">
                    <span style="color: ${keyStatus.color};">
                        ${keyStatus.icon} ${keyStatus.label}
                    </span>
                    <br>
                    <code style="font-size: 11px; color: #999;">${field.key}</code>
                </div>
            </div>
        </div>
    `;
};

/**
 * Test - Obsługa formularza edycji pola
 */
FieldStabilization.handleFieldSave = function(fieldElement) {
    const formData = {
        label: fieldElement.querySelector('[name="label"]')?.value || '',
        name: fieldElement.querySelector('[name="name"]')?.value || '',
        type: fieldElement.querySelector('[name="type"]')?.value || '',
    };
    
    // Pobierz istniejące pole
    const fieldId = fieldElement.dataset.fieldId;
    const field = window.yapBuilder?.schema?.fields?.find(f => f.id === fieldId);
    
    if (!field) {
        console.error('Field not found:', fieldId);
        return false;
    }
    
    // Waliduj update
    const result = FieldStabilization.updateField(field, formData);
    
    // Pokaż błędy
    if (!result.success) {
        FieldStabilization.showErrors(result.errors);
        return false;
    }
    
    // Pokaż ostrzeżenia
    if (result.warnings.length > 0) {
        if (!FieldStabilization.showWarnings(result.warnings)) {
            return false; // Użytkownik anulował
        }
    }
    
    // Zapisz pole
    const finalField = FieldStabilization.lockFieldKey(result.field);
    
    // Aktualizuj w schema
    const fieldIndex = window.yapBuilder.schema.fields.findIndex(f => f.id === fieldId);
    if (fieldIndex !== -1) {
        window.yapBuilder.schema.fields[fieldIndex] = finalField;
    }
    
    console.log('Field saved:', finalField);
    return true;
};

/**
 * Znajdź unikalną nazwę dla zduplikowanego pola
 * title → title_2, title_3, etc.
 */
FieldStabilization.findUniqueName = function(baseName, excludeFieldId = null) {
    if (!window.yapBuilder || !window.yapBuilder.schema) {
        return baseName;
    }
    
    let counter = 2;
    let newName = baseName + '_' + counter;
    
    while (!FieldStabilization.isNameUnique(newName, excludeFieldId)) {
        counter++;
        newName = baseName + '_' + counter;
    }
    
    return newName;
};

/**
 * Duplikuj pole - Enterprise Edition
 * 
 * Generuje:
 * ✅ Nowy id
 * ✅ Nowy key
 * ✅ Unikalny name (title → title_2)
 * ✅ Kopię wszystkich settings (options, validation, rules)
 * ✅ Nowy created_at timestamp
 */
FieldStabilization.duplicateField = function(fieldToDuplicate, includeSubFields = false) {
    if (!fieldToDuplicate) {
        return {
            success: false,
            error: 'Field not found',
            field: null
        };
    }
    
    // Deep clone pola
    const duplicated = JSON.parse(JSON.stringify(fieldToDuplicate));
    
    // Generuj nowe identyfikatory
    duplicated.id = FieldStabilization.generateShortId();
    duplicated.key = FieldStabilization.generateShortId();
    
    // Rozwiąż kolizję name
    duplicated.name = FieldStabilization.findUniqueName(
        duplicated.name || FieldStabilization.labelToName(duplicated.label)
    );
    
    // Waliduj nowe name
    if (!FieldStabilization.isNameUnique(duplicated.name)) {
        return {
            success: false,
            error: `Cannot create unique name for duplicate. Base name: ${fieldToDuplicate.name}`,
            field: null
        };
    }
    
    // Update timestamps
    duplicated._created_at = new Date().toISOString();
    duplicated._updated_at = new Date().toISOString();
    duplicated._locked_key = false; // Klucz nowy, nie zablokowany
    
    // Jeśli pole ma sub-fields (repeater, flexible content)
    if (includeSubFields && duplicated.fields && Array.isArray(duplicated.fields)) {
        duplicated.fields = duplicated.fields.map(subField => {
            const dupSub = JSON.parse(JSON.stringify(subField));
            dupSub.id = FieldStabilization.generateShortId();
            dupSub.key = FieldStabilization.generateShortId();
            dupSub._created_at = new Date().toISOString();
            return dupSub;
        });
    }
    
    return {
        success: true,
        error: null,
        field: duplicated,
        metadata: {
            original_id: fieldToDuplicate.id,
            original_name: fieldToDuplicate.name,
            new_name: duplicated.name,
            new_key: duplicated.key,
            settings_copied: {
                type: duplicated.type,
                label: duplicated.label,
                options: duplicated.options ? true : false,
                validation: duplicated.validation ? true : false,
                conditional_logic: duplicated.conditional_logic ? true : false,
                sub_fields: includeSubFields && duplicated.fields ? duplicated.fields.length : 0
            }
        }
    };
};

/**
 * Paste as new - dodaj zduplikowane pole do schema
 */
FieldStabilization.pasteAsNew = function(fieldToDuplicate, position = 'end', includeSubFields = false) {
    const result = FieldStabilization.duplicateField(fieldToDuplicate, includeSubFields);
    
    if (!result.success) {
        return result;
    }
    
    if (!window.yapBuilder || !window.yapBuilder.schema) {
        return {
            success: false,
            error: 'Schema not available',
            field: null
        };
    }
    
    // Dodaj do schema
    if (position === 'end' || position === undefined) {
        window.yapBuilder.schema.fields.push(result.field);
    } else if (typeof position === 'number') {
        window.yapBuilder.schema.fields.splice(position, 0, result.field);
    }
    
    return {
        success: true,
        error: null,
        field: result.field,
        metadata: result.metadata,
        position_added: position === 'end' ? window.yapBuilder.schema.fields.length - 1 : position
    };
};

/**
 * Porównaj ustawienia oryginalnego i zduplikowanego pola
 */
FieldStabilization.compareFields = function(original, duplicated) {
    const differences = {
        id: original.id !== duplicated.id,
        key: original.key !== duplicated.key,
        name: original.name !== duplicated.name,
        created_at: original._created_at !== duplicated._created_at,
        settings: {
            type_same: original.type === duplicated.type,
            label_same: original.label === duplicated.label,
            options_same: JSON.stringify(original.options) === JSON.stringify(duplicated.options),
            validation_same: JSON.stringify(original.validation) === JSON.stringify(duplicated.validation),
            conditional_same: JSON.stringify(original.conditional_logic) === JSON.stringify(duplicated.conditional_logic)
        }
    };
    
    return {
        is_duplicate: differences.id && differences.key && differences.name,
        settings_preserved: Object.values(differences.settings).every(v => v === true),
        differences
    };
};

/**
 * Render duplicate button dla pola
 */
FieldStabilization.renderDuplicateButton = function(field) {
    return `
        <button 
            class="field-duplicate-btn" 
            data-field-id="${field.id}" 
            title="Duplicate this field"
            style="
                padding: 8px 12px;
                background: #0073aa;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
                font-weight: 500;
                margin: 5px;
            "
        >
            📋 Duplicate
        </button>
    `;
};

/**
 * Obsługi duplicate pola - Vue/React style
 */
FieldStabilization.handleDuplicateField = function(fieldId) {
    if (!window.yapBuilder || !window.yapBuilder.schema) {
        console.error('Schema not available');
        return false;
    }
    
    // Znajdź pole
    const originalField = window.yapBuilder.schema.fields.find(f => f.id === fieldId);
    if (!originalField) {
        console.error('Field not found:', fieldId);
        return false;
    }
    
    // Duplikuj
    const result = FieldStabilization.pasteAsNew(originalField, 'end', true);
    
    if (!result.success) {
        alert('❌ Error: ' + result.error);
        return false;
    }
    
    // Success feedback
    console.log('%c✅ Field duplicated!', 'color: #4caf50; font-weight: bold;');
    console.log('Original:', originalField.name);
    console.log('Duplicate:', result.field.name);
    console.log('Metadata:', result.metadata);
    
    return result;
};

console.log('%c✅ Field Stabilization System loaded', 'color: #4caf50; font-weight: bold;');
console.log('Use: FieldStabilization.createStableField(), labelToName(), updateField(), duplicateField(), etc.');
