/**
 * TEST CUSTOM FIELD TYPE - Slug Field
 * 
 * Simple example field type for testing custom implementation
 * - Generates URL-friendly slug from input
 * - Auto-converts spaces to hyphens
 * - Validates URL-safe characters
 * 
 * @example
 * const field = FieldTypeRegistry.createField('slug', { label: 'URL Slug' });
 * const html = SlugFieldType.renderAdmin(field, 'my-page');
 * const valid = SlugFieldType.validate('my-page', field);
 */

class SlugFieldType extends BaseFieldType {
    /**
     * Type identifier
     */
    static get type() {
        return 'slug';
    }

    /**
     * Default configuration
     */
    static defaults() {
        return {
            ...super.defaults(),
            type: 'slug',
            min_length: 3,
            max_length: 100,
            auto_generate: false,      // Auto-generate from another field
            source_field: null,          // Which field to generate from
            separator: '-',              // Character to use (- or _)
            lowercase: true              // Force lowercase
        };
    }

    /**
     * Settings schema for admin UI
     */
    static settingsSchema() {
        const base = super.settingsSchema();
        
        base[1].fields.push(
            {
                name: 'auto_generate',
                label: 'Auto-generate slug',
                type: 'checkbox',
                hint: 'Automatically generate from another field'
            },
            {
                name: 'source_field',
                label: 'Generate from field',
                type: 'text',
                hint: 'Field name to generate slug from (e.g., title)'
            },
            {
                name: 'separator',
                label: 'Separator character',
                type: 'select',
                options: [
                    { value: '-', label: 'Hyphen (-)' },
                    { value: '_', label: 'Underscore (_)' }
                ]
            },
            {
                name: 'lowercase',
                label: 'Force lowercase',
                type: 'checkbox',
                hint: 'Convert to lowercase automatically'
            }
        );
        
        return base;
    }

    /**
     * Render field in admin panel
     */
    static renderAdmin(field, value = '') {
        return `
            <div class="yap-field-input slug-field">
                <label for="${field.name}">
                    ${field.label}
                    ${field.required ? '<span class="required">*</span>' : ''}
                </label>
                <input 
                    type="text" 
                    name="${field.name}" 
                    id="${field.name}"
                    value="${value}"
                    placeholder="my-page-name"
                    pattern="[a-z0-9${field.separator}]*"
                    ${field.required ? 'required' : ''}
                    class="slug-input"
                    data-separator="${field.separator}"
                    data-lowercase="${field.lowercase}"
                >
                ${field.description ? `<p class="help">${field.description}</p>` : ''}
                <small style="color: #666;">
                    Allowed: letters, numbers, and ${field.separator}
                </small>
            </div>
        `;
    }

    /**
     * Render preview in Visual Builder
     */
    static renderPreview(field) {
        return `
            <div class="yap-field-preview slug-preview">
                <label>${field.label}</label>
                <div class="preview-content">
                    <code>my-page-slug</code>
                    ${field.auto_generate ? '<em>(auto-generated)</em>' : ''}
                </div>
                ${field.required ? '<span class="required">*</span>' : ''}
            </div>
        `;
    }

    /**
     * Validate slug value
     */
    static validate(value, field) {
        // Base validation
        const base = super.validate(value, field);
        if (!base.valid) return base;

        // Empty check
        if (!value || !value.trim()) {
            if (field.required) {
                return { valid: false, error: 'Slug is required' };
            }
            return { valid: true };
        }

        // Length validation
        if (value.length < field.min_length) {
            return { valid: false, error: `Minimum ${field.min_length} characters` };
        }
        if (value.length > field.max_length) {
            return { valid: false, error: `Maximum ${field.max_length} characters` };
        }

        // Pattern validation - only alphanumeric and separator
        const sep = field.separator === '_' ? '_' : '-';
        const regex = new RegExp(`^[a-z0-9${sep}]+$`, 'i');
        
        if (!regex.test(value)) {
            return { 
                valid: false, 
                error: `Only letters, numbers, and "${sep}" allowed` 
            };
        }

        // Check for consecutive separators
        if (value.includes(sep + sep)) {
            return { 
                valid: false, 
                error: `No consecutive separators allowed` 
            };
        }

        // Check start/end with separator
        if (value.startsWith(sep) || value.endsWith(sep)) {
            return { 
                valid: false, 
                error: `Cannot start or end with separator` 
            };
        }

        return { valid: true };
    }

    /**
     * Sanitize slug value
     */
    static sanitize(value) {
        if (!value) return '';

        value = String(value);
        const sep = '-';

        // Remove HTML tags and special chars
        value = value.replace(/<[^>]*>/g, '');
        
        // Convert to lowercase
        value = value.toLowerCase();

        // Replace spaces and underscores with separator
        value = value.replace(/[\s_]+/g, sep);

        // Remove anything that's not alphanumeric or separator
        value = value.replace(/[^a-z0-9-]/g, '');

        // Remove consecutive separators
        value = value.replace(new RegExp(sep + '+', 'g'), sep);

        // Remove leading/trailing separators
        value = value.replace(new RegExp(`^${sep}+|${sep}+$`, 'g'), '');

        return value;
    }

    /**
     * Format slug for display
     */
    static format(value, field) {
        if (!value) return '';
        return this.sanitize(value);
    }

    /**
     * Generate slug from text
     * 
     * @param {string} text - Text to generate slug from
     * @param {string} separator - Separator to use (- or _)
     * @returns {string} Generated slug
     */
    static generateFromText(text, separator = '-') {
        if (!text) return '';

        let slug = String(text)
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '') // Remove special chars
            .replace(/[\s_-]+/g, separator) // Replace spaces/underscores with separator
            .replace(new RegExp(`^${separator}+|${separator}+$`, 'g'), ''); // Trim separators

        return slug;
    }
}

// Auto-register on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        FieldTypeRegistry.register('slug', SlugFieldType);
        console.log('✅ Slug Field Type registered');
    });
} else {
    FieldTypeRegistry.register('slug', SlugFieldType);
    console.log('✅ Slug Field Type registered');
}

// Export
window.SlugFieldType = SlugFieldType;

console.log('✅ Test Custom Field Type (Slug) loaded');
