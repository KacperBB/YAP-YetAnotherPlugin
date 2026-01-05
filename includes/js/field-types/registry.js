/**
 * YAP Field Type Registry
 * Sistema rejestrowania i zarządzania typami pól
 * 
 * @since 1.5.0
 */

const FieldTypeRegistry = {
    types: {},
    
    /**
     * Register field type
     */
    register(typeId, fieldTypeClass) {
        if (this.types[typeId]) {
            console.warn(`⚠️ Field type "${typeId}" already registered, overwriting...`);
        }
        
        // Validate field type has required methods
        const requiredMethods = ['defaults', 'settingsSchema', 'renderPreview', 'renderAdmin', 'validate', 'sanitize'];
        for (const method of requiredMethods) {
            if (typeof fieldTypeClass[method] !== 'function') {
                console.error(`❌ Field type "${typeId}" missing required method: ${method}`);
                return false;
            }
        }
        
        this.types[typeId] = fieldTypeClass;
        console.log(`✅ Registered field type: ${typeId}`);
        return true;
    },
    
    /**
     * Get field type
     */
    get(typeId) {
        return this.types[typeId] || null;
    },
    
    /**
     * Get all registered types
     */
    getAll() {
        return this.types;
    },
    
    /**
     * Check if type is registered
     */
    has(typeId) {
        return !!this.types[typeId];
    },
    
    /**
     * Get defaults for type
     */
    getDefaults(typeId) {
        const fieldType = this.get(typeId);
        return fieldType ? fieldType.defaults() : null;
    },
    
    /**
     * Create field instance with defaults
     */
    createField(typeId, overrides = {}) {
        const fieldType = this.get(typeId);
        if (!fieldType) return null;
        
        return {
            ...fieldType.defaults(),
            ...overrides,
            type: typeId
        };
    }
};

/**
 * Base Field Type Template
 */
class BaseFieldType {
    /**
     * Get default configuration
     */
    static defaults() {
        return {
            id: `field_${Date.now()}`,
            name: '',
            label: '',
            type: this.type,
            placeholder: '',
            default_value: null,
            description: '',
            required: false,
            css_class: '',
            conditional: null,
            help_text: ''
        };
    }
    
    /**
     * Settings schema for UI
     * Defines form inputs, validation rules, UI rendering
     */
    static settingsSchema() {
        return [
            {
                id: 'general',
                label: '📝 Podstawowe informacje',
                fields: [
                    {
                        name: 'name',
                        label: 'Nazwa pola',
                        type: 'text',
                        required: true,
                        validate: (val) => /^[a-z0-9_]+$/.test(val),
                        hint: 'Tylko a-z, 0-9, _ (bez spacji)'
                    },
                    {
                        name: 'label',
                        label: 'Etykieta',
                        type: 'text',
                        required: true,
                        hint: 'Wyświetlana użytkownikowi'
                    },
                    {
                        name: 'placeholder',
                        label: 'Placeholder',
                        type: 'text',
                        hint: 'Tekst wskazówki'
                    },
                    {
                        name: 'default_value',
                        label: 'Wartość domyślna',
                        type: 'text',
                        hint: 'Opcjonalnie'
                    },
                    {
                        name: 'description',
                        label: 'Opis',
                        type: 'textarea',
                        hint: 'Tekst pomocniczy'
                    },
                    {
                        name: 'required',
                        label: 'Pole wymagane',
                        type: 'checkbox'
                    }
                ]
            },
            {
                id: 'advanced',
                label: '🔧 Zaawansowane',
                fields: [
                    {
                        name: 'css_class',
                        label: 'CSS Class',
                        type: 'text',
                        hint: 'Klasy CSS dla stylizacji'
                    },
                    {
                        name: 'help_text',
                        label: 'Tekst Pomocy',
                        type: 'textarea',
                        hint: 'Dodatkowy tekst pomocy'
                    }
                ]
            }
        ];
    }
    
    /**
     * Render field preview w Visual Builderze
     */
    static renderPreview(field) {
        return `
            <div class="yap-field-preview">
                <label>${field.label}</label>
                <input type="text" placeholder="${field.placeholder || 'Placeholder'}" disabled>
                ${field.required ? '<span class="required-badge">*</span>' : ''}
            </div>
        `;
    }
    
    /**
     * Render field w metaboxie/edytorze
     */
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
                    ${field.required ? 'required' : ''}
                >
                ${field.description ? `<p class="help">${field.description}</p>` : ''}
            </div>
        `;
    }
    
    /**
     * Validate field value
     */
    static validate(value, field) {
        if (field.required && !value) {
            return { valid: false, error: `${field.label} jest wymagane` };
        }
        return { valid: true };
    }
    
    /**
     * Sanitize field value
     */
    static sanitize(value) {
        return typeof value === 'string' ? value.trim() : value;
    }
    
    /**
     * Format field value for display
     */
    static format(value) {
        return value || '';
    }
}

// Export
window.FieldTypeRegistry = FieldTypeRegistry;
window.BaseFieldType = BaseFieldType;
