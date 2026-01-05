/**
 * Text Field Type
 */
class TextFieldType extends BaseFieldType {
    static get type() { return 'text'; }
    
    static defaults() {
        return {
            ...super.defaults(),
            type: 'text',
            min_length: 0,
            max_length: null,
            pattern: null
        };
    }
    
    static settingsSchema() {
        const base = super.settingsSchema();
        
        // Add text-specific settings to Advanced tab
        base[1].fields.push(
            {
                name: 'min_length',
                label: 'Min. Long.',
                type: 'number',
                min: 0,
                hint: 'Minimum znaków'
            },
            {
                name: 'max_length',
                label: 'Max. Long.',
                type: 'number',
                min: 1,
                hint: 'Maximum znaków'
            },
            {
                name: 'pattern',
                label: 'Regex Pattern',
                type: 'text',
                hint: 'np. ^[A-Za-z0-9]+$'
            }
        );
        
        return base;
    }
    
    static renderPreview(field) {
        return `
            <div class="yap-field-preview text-field">
                <label>${field.label}</label>
                <input type="text" placeholder="${field.placeholder || 'Tekst...'}" disabled>
                ${field.required ? '<span class="required">*</span>' : ''}
            </div>
        `;
    }
    
    static renderAdmin(field, value = '') {
        return `
            <div class="yap-field-input">
                <label for="${field.name}">${field.label}${field.required ? '<span class="required">*</span>' : ''}</label>
                <input 
                    type="text" 
                    name="${field.name}" 
                    id="${field.name}"
                    placeholder="${field.placeholder}"
                    value="${value}"
                    ${field.min_length ? `minlength="${field.min_length}"` : ''}
                    ${field.max_length ? `maxlength="${field.max_length}"` : ''}
                    ${field.pattern ? `pattern="${field.pattern}"` : ''}
                    ${field.required ? 'required' : ''}
                >
                ${field.description ? `<p class="help">${field.description}</p>` : ''}
            </div>
        `;
    }
    
    static validate(value, field) {
        const base = super.validate(value, field);
        if (!base.valid) return base;
        
        if (field.min_length && value.length < field.min_length) {
            return { valid: false, error: `Min. ${field.min_length} znaków` };
        }
        
        if (field.max_length && value.length > field.max_length) {
            return { valid: false, error: `Max. ${field.max_length} znaków` };
        }
        
        if (field.pattern && !new RegExp(field.pattern).test(value)) {
            return { valid: false, error: 'Format nie pasuje' };
        }
        
        return { valid: true };
    }
}

/**
 * Textarea Field Type
 */
class TextareaFieldType extends BaseFieldType {
    static get type() { return 'textarea'; }
    
    static defaults() {
        return {
            ...super.defaults(),
            type: 'textarea',
            rows: 4,
            max_length: null
        };
    }
    
    static settingsSchema() {
        const base = super.settingsSchema();
        base[1].fields.push({
            name: 'rows',
            label: 'Liczba Wierszy',
            type: 'number',
            min: 1,
            default: 4
        });
        return base;
    }
    
    static renderPreview(field) {
        return `
            <div class="yap-field-preview textarea-field">
                <label>${field.label}</label>
                <textarea placeholder="${field.placeholder}" rows="${field.rows || 4}" disabled></textarea>
                ${field.required ? '<span class="required">*</span>' : ''}
            </div>
        `;
    }
    
    static renderAdmin(field, value = '') {
        return `
            <div class="yap-field-input">
                <label for="${field.name}">${field.label}${field.required ? '<span class="required">*</span>' : ''}</label>
                <textarea 
                    name="${field.name}" 
                    id="${field.name}"
                    placeholder="${field.placeholder}"
                    rows="${field.rows || 4}"
                    ${field.max_length ? `maxlength="${field.max_length}"` : ''}
                    ${field.required ? 'required' : ''}
                >${value}</textarea>
                ${field.description ? `<p class="help">${field.description}</p>` : ''}
            </div>
        `;
    }
}

/**
 * Select Field Type
 */
class SelectFieldType extends BaseFieldType {
    static get type() { return 'select'; }
    
    static defaults() {
        return {
            ...super.defaults(),
            type: 'select',
            options: [
                { label: 'Opcja 1', value: 'opt1' },
                { label: 'Opcja 2', value: 'opt2' }
            ],
            multiple: false,
            allow_custom: false
        };
    }
    
    static settingsSchema() {
        const base = super.settingsSchema();
        base[1].fields.push(
            {
                name: 'multiple',
                label: 'Wybór Wielokrotny',
                type: 'checkbox'
            },
            {
                name: 'allow_custom',
                label: 'Zezwól na Custom Wartości',
                type: 'checkbox'
            }
        );
        return base;
    }
    
    static renderPreview(field) {
        const options = field.options.map(opt => 
            `<option value="${opt.value}">${opt.label}</option>`
        ).join('');
        
        return `
            <div class="yap-field-preview select-field">
                <label>${field.label}</label>
                <select ${field.multiple ? 'multiple' : ''} disabled>
                    <option>-- Wybierz --</option>
                    ${options}
                </select>
                ${field.required ? '<span class="required">*</span>' : ''}
            </div>
        `;
    }
    
    static renderAdmin(field, value = '') {
        const values = field.multiple && typeof value === 'string' ? value.split(',') : [value];
        const options = field.options.map(opt => 
            `<option value="${opt.value}" ${values.includes(opt.value) ? 'selected' : ''}>${opt.label}</option>`
        ).join('');
        
        return `
            <div class="yap-field-input">
                <label for="${field.name}">${field.label}${field.required ? '<span class="required">*</span>' : ''}</label>
                <select 
                    name="${field.name}" 
                    id="${field.name}"
                    ${field.multiple ? 'multiple' : ''}
                    ${field.required ? 'required' : ''}
                >
                    <option value="">-- Wybierz --</option>
                    ${options}
                </select>
                ${field.description ? `<p class="help">${field.description}</p>` : ''}
            </div>
        `;
    }
    
    static validate(value, field) {
        const base = super.validate(value, field);
        if (!base.valid) return base;
        
        const validValues = field.options.map(opt => opt.value);
        const values = field.multiple && typeof value === 'string' ? value.split(',') : [value];
        
        if (!field.allow_custom) {
            for (const val of values) {
                if (!validValues.includes(val)) {
                    return { valid: false, error: 'Nieznana opcja' };
                }
            }
        }
        
        return { valid: true };
    }
}

/**
 * Checkbox Field Type
 */
class CheckboxFieldType extends BaseFieldType {
    static get type() { return 'checkbox'; }
    
    static defaults() {
        return {
            ...super.defaults(),
            type: 'checkbox',
            checkbox_label: 'Zaznacz'
        };
    }
    
    static settingsSchema() {
        const base = super.settingsSchema();
        base[0].fields.push({
            name: 'checkbox_label',
            label: 'Etykieta Checkboxa',
            type: 'text'
        });
        return base;
    }
    
    static renderPreview(field) {
        return `
            <div class="yap-field-preview checkbox-field">
                <label>${field.label}</label>
                <input type="checkbox" disabled>
                <span>${field.checkbox_label}</span>
                ${field.required ? '<span class="required">*</span>' : ''}
            </div>
        `;
    }
    
    static renderAdmin(field, value = '') {
        return `
            <div class="yap-field-input">
                <label>
                    <input 
                        type="checkbox" 
                        name="${field.name}" 
                        id="${field.name}"
                        value="1"
                        ${value === '1' || value === 1 ? 'checked' : ''}
                        ${field.required ? 'required' : ''}
                    >
                    ${field.checkbox_label || field.label}
                </label>
                ${field.description ? `<p class="help">${field.description}</p>` : ''}
            </div>
        `;
    }
    
    static sanitize(value) {
        return value ? '1' : '0';
    }
}

/**
 * Radio Field Type
 */
class RadioFieldType extends BaseFieldType {
    static get type() { return 'radio'; }
    
    static defaults() {
        return {
            ...super.defaults(),
            type: 'radio',
            options: [
                { label: 'Opcja 1', value: 'opt1' },
                { label: 'Opcja 2', value: 'opt2' }
            ]
        };
    }
    
    static renderPreview(field) {
        const options = field.options.map(opt => 
            `<label><input type="radio" disabled> ${opt.label}</label>`
        ).join('');
        
        return `
            <div class="yap-field-preview radio-field">
                <label>${field.label}</label>
                ${options}
                ${field.required ? '<span class="required">*</span>' : ''}
            </div>
        `;
    }
    
    static renderAdmin(field, value = '') {
        const options = field.options.map(opt => 
            `<label><input type="radio" name="${field.name}" value="${opt.value}" ${value === opt.value ? 'checked' : ''} ${field.required ? 'required' : ''}> ${opt.label}</label>`
        ).join('');
        
        return `
            <div class="yap-field-input">
                <label>${field.label}${field.required ? '<span class="required">*</span>' : ''}</label>
                ${options}
                ${field.description ? `<p class="help">${field.description}</p>` : ''}
            </div>
        `;
    }
}

/**
 * Email Field Type
 */
class EmailFieldType extends TextFieldType {
    static get type() { return 'email'; }
    
    static defaults() {
        return {
            ...super.defaults(),
            type: 'email'
        };
    }
    
    static renderAdmin(field, value = '') {
        return `
            <div class="yap-field-input">
                <label for="${field.name}">${field.label}${field.required ? '<span class="required">*</span>' : ''}</label>
                <input 
                    type="email" 
                    name="${field.name}" 
                    id="${field.name}"
                    placeholder="${field.placeholder}"
                    value="${value}"
                    ${field.required ? 'required' : ''}
                >
                ${field.description ? `<p class="help">${field.description}</p>` : ''}
            </div>
        `;
    }
    
    static validate(value, field) {
        const base = super.validate(value, field);
        if (!base.valid) return base;
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (value && !emailRegex.test(value)) {
            return { valid: false, error: 'Nieprawidłowy adres email' };
        }
        
        return { valid: true };
    }
}

/**
 * Number Field Type
 */
class NumberFieldType extends TextFieldType {
    static get type() { return 'number'; }
    
    static defaults() {
        return {
            ...super.defaults(),
            type: 'number',
            min: null,
            max: null,
            step: 1
        };
    }
    
    static settingsSchema() {
        const base = super.settingsSchema();
        const advFields = base[1].fields;
        
        // Remove text-specific fields
        advFields.splice(0, 3);
        
        // Add number-specific fields
        advFields.unshift(
            {
                name: 'min',
                label: 'Min. Wartość',
                type: 'number',
                hint: 'Minimum'
            },
            {
                name: 'max',
                label: 'Max. Wartość',
                type: 'number',
                hint: 'Maximum'
            },
            {
                name: 'step',
                label: 'Krok',
                type: 'number',
                default: 1
            }
        );
        
        return base;
    }
    
    static renderAdmin(field, value = '') {
        return `
            <div class="yap-field-input">
                <label for="${field.name}">${field.label}${field.required ? '<span class="required">*</span>' : ''}</label>
                <input 
                    type="number" 
                    name="${field.name}" 
                    id="${field.name}"
                    placeholder="${field.placeholder}"
                    value="${value}"
                    ${field.min !== null ? `min="${field.min}"` : ''}
                    ${field.max !== null ? `max="${field.max}"` : ''}
                    step="${field.step || 1}"
                    ${field.required ? 'required' : ''}
                >
                ${field.description ? `<p class="help">${field.description}</p>` : ''}
            </div>
        `;
    }
    
    static validate(value, field) {
        const base = super.validate(value, field);
        if (!base.valid) return base;
        
        const num = parseFloat(value);
        if (isNaN(num)) {
            return { valid: false, error: 'Musi być liczbą' };
        }
        
        if (field.min !== null && num < field.min) {
            return { valid: false, error: `Min. ${field.min}` };
        }
        
        if (field.max !== null && num > field.max) {
            return { valid: false, error: `Max. ${field.max}` };
        }
        
        return { valid: true };
    }
    
    static format(value) {
        return isNaN(value) ? '' : parseFloat(value);
    }
}

/**
 * Register all field types
 */
function registerFieldTypes() {
    FieldTypeRegistry.register('text', TextFieldType);
    FieldTypeRegistry.register('textarea', TextareaFieldType);
    FieldTypeRegistry.register('select', SelectFieldType);
    FieldTypeRegistry.register('checkbox', CheckboxFieldType);
    FieldTypeRegistry.register('radio', RadioFieldType);
    FieldTypeRegistry.register('email', EmailFieldType);
    FieldTypeRegistry.register('number', NumberFieldType);
    
    console.log('✅ Registered 7 field types');
}

// Auto-register on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', registerFieldTypes);
} else {
    registerFieldTypes();
}

// Export
window.TextFieldType = TextFieldType;
window.TextareaFieldType = TextareaFieldType;
window.SelectFieldType = SelectFieldType;
window.CheckboxFieldType = CheckboxFieldType;
window.RadioFieldType = RadioFieldType;
window.EmailFieldType = EmailFieldType;
window.NumberFieldType = NumberFieldType;
