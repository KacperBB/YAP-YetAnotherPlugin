/**
 * EXAMPLE: Custom Field Type - Color Picker
 * 
 * Ten plik pokazuje jak tworzyć custom field types
 * Skopiuj i dostosuj do swoich potrzeb!
 */

class ColorFieldType extends BaseFieldType {
    static get type() { return 'color'; }
    
    static defaults() {
        return {
            ...super.defaults(),
            type: 'color',
            default_value: '#000000',
            enable_alpha: false
        };
    }
    
    static settingsSchema() {
        const base = super.settingsSchema();
        base[1].fields.push({
            name: 'enable_alpha',
            label: 'Włącz Alpha Channel',
            type: 'checkbox',
            hint: 'Obsługuje przezroczystość (RGBA)'
        });
        return base;
    }
    
    static renderPreview(field) {
        return `
            <div class="yap-field-preview color-field">
                <label>${field.label}</label>
                <div class="color-preview" style="background-color: ${field.default_value}; width: 100px; height: 50px; border: 1px solid #ddd; border-radius: 4px;"></div>
                ${field.required ? '<span class="required">*</span>' : ''}
            </div>
        `;
    }
    
    static renderAdmin(field, value = '') {
        const colorValue = value || field.default_value || '#000000';
        return `
            <div class="yap-field-input">
                <label for="${field.name}">${field.label}${field.required ? '<span class="required">*</span>' : ''}</label>
                <input 
                    type="color" 
                    name="${field.name}" 
                    id="${field.name}"
                    value="${colorValue}"
                    ${field.required ? 'required' : ''}
                    class="color-picker"
                >
                ${field.description ? `<p class="help">${field.description}</p>` : ''}
            </div>
        `;
    }
    
    static validate(value, field) {
        const base = super.validate(value, field);
        if (!base.valid) return base;
        
        // Validate color format
        const colorRegex = field.enable_alpha 
            ? /^#(?:[0-9a-f]{3}){1,2}(?:[0-9a-f]{2})?$/i  // RGB or RGBA
            : /^#(?:[0-9a-f]{3}){1,2}$/i;                  // RGB only
        
        if (!colorRegex.test(value)) {
            return { valid: false, error: 'Nieznany format koloru' };
        }
        
        return { valid: true };
    }
    
    static sanitize(value) {
        // Ensure it's a valid hex color
        return /^#[0-9A-F]{6}$/i.test(value) ? value : '#000000';
    }
}

// Rejestracja
// FieldTypeRegistry.register('color', ColorFieldType);

/**
 * EXAMPLE 2: Date Field Type
 */
class DateFieldType extends BaseFieldType {
    static get type() { return 'date'; }
    
    static defaults() {
        return {
            ...super.defaults(),
            type: 'date',
            min_date: null,
            max_date: null,
            date_format: 'YYYY-MM-DD'
        };
    }
    
    static renderAdmin(field, value = '') {
        return `
            <div class="yap-field-input">
                <label for="${field.name}">${field.label}${field.required ? '<span class="required">*</span>' : ''}</label>
                <input 
                    type="date" 
                    name="${field.name}" 
                    id="${field.name}"
                    value="${value}"
                    ${field.min_date ? `min="${field.min_date}"` : ''}
                    ${field.max_date ? `max="${field.max_date}"` : ''}
                    ${field.required ? 'required' : ''}
                >
                ${field.description ? `<p class="help">${field.description}</p>` : ''}
            </div>
        `;
    }
    
    static validate(value, field) {
        const base = super.validate(value, field);
        if (!base.valid) return base;
        
        const date = new Date(value);
        
        if (field.min_date && date < new Date(field.min_date)) {
            return { valid: false, error: `Nie wcześniej niż ${field.min_date}` };
        }
        
        if (field.max_date && date > new Date(field.max_date)) {
            return { valid: false, error: `Nie później niż ${field.max_date}` };
        }
        
        return { valid: true };
    }
}

// FieldTypeRegistry.register('date', DateFieldType);

console.log('✅ Custom field type examples loaded (color, date)');

// Export
window.ColorFieldType = ColorFieldType;
window.DateFieldType = DateFieldType;
