/**
 * Custom Templates/Blocks System
 * 
 * Umożliwia użytkownikom tworzenie własnych szablonów pól (grup pól)
 * i ponowne użycie ich jako szybkich bloków
 * 
 * @since 1.0.0
 * @version 1.1.0 - Added icon picker and improved UI
 */

window.CustomTemplates = window.CustomTemplates || {};

/**
 * Storage key for localStorage
 */
CustomTemplates.STORAGE_KEY = 'yap_custom_templates';

/**
 * Get all custom templates from localStorage
 */
CustomTemplates.getAll = function() {
    const stored = localStorage.getItem(this.STORAGE_KEY);
    return stored ? JSON.parse(stored) : {};
};

/**
 * Get single custom template
 */
CustomTemplates.getTemplate = function(templateId) {
    const all = this.getAll();
    return all[templateId] || null;
};

/**
 * Save custom template
 */
CustomTemplates.save = function(templateId, templateData) {
    const all = this.getAll();
    all[templateId] = {
        id: templateId,
        name: templateData.name,
        label: templateData.label || templateData.name,
        icon: templateData.icon || '🎨',
        description: templateData.description || '',
        fields: templateData.fields || [],
        created_at: templateData.created_at || Date.now(),
        updated_at: Date.now()
    };
    localStorage.setItem(this.STORAGE_KEY, JSON.stringify(all));
    console.log('✅ Custom template saved:', templateId);
    return all[templateId];
};

/**
 * Delete custom template
 */
CustomTemplates.delete = function(templateId) {
    const all = this.getAll();
    delete all[templateId];
    localStorage.setItem(this.STORAGE_KEY, JSON.stringify(all));
    console.log('✅ Custom template deleted:', templateId);
};

/**
 * Create template from current field/selection
 * Shows modal where user can configure template
 */
CustomTemplates.createFromSelection = function(fields) {
    console.log('📋 Creating template from fields:', fields);
    
    // Generate unique ID
    const templateId = 'custom_' + Date.now();
    
    // Show creation modal
    this.showCreationModal(templateId, fields);
};

/**
 * Show creation/edit modal
 */
CustomTemplates.showCreationModal = function(templateId, fieldsToUse) {
    const isEdit = templateId && this.getTemplate(templateId);
    const template = isEdit ? this.getTemplate(templateId) : null;
    const defaultIcon = '🎨';
    const savedIcon = template ? template.icon : defaultIcon;
    
    const iconPicker = [
        '🎨', '📝', '📋', '📊', '📈', '📉', '💼', '👤', '🏢', '🏭',
        '📞', '📧', '🌐', '🔐', '🔑', '⚙️', '🛠️', '📅', '⏰', '💰',
        '💳', '📦', '🚚', '📌', '🗺️', '⭐', '✅', '❌', '⚠️', '🔔',
        '📱', '💻', '⌨️', '🖱️', '🖥️', '📁', '🎯', '🎪', '🎭', '🎬'
    ];
    
    const modalHTML = `
        <div class="yap-custom-template-modal" id="customTemplateModal">
            <div class="yap-modal-overlay"></div>
            <div class="yap-modal-content">
                <div class="yap-modal-header">
                    <h2>${isEdit ? '✏️ Edytuj' : '➕ Stwórz'} Custom Template</h2>
                    <button type="button" class="yap-modal-close" data-dismiss="modal">&times;</button>
                </div>
                <div class="yap-modal-body">
                    <form class="yap-template-form">
                        <div class="yap-form-row yap-form-row-2col">
                            <div class="yap-form-group">
                                <label for="templateName">Nazwa szablonu <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="templateName" 
                                    class="yap-input" 
                                    placeholder="np. Dane osobowe"
                                    value="${template ? template.name : ''}"
                                    required
                                >
                                <small>Będzie widoczna w menu jako nazwa bloku</small>
                            </div>
                            
                            <div class="yap-form-group">
                                <label for="templateIcon">Ikona <span class="required">*</span></label>
                                <div class="yap-icon-picker-wrapper">
                                    <input 
                                        type="text" 
                                        id="templateIcon" 
                                        class="yap-input yap-icon-input" 
                                        placeholder="🎨"
                                        value="${savedIcon}"
                                        maxlength="2"
                                        required
                                    >
                                    <div class="yap-icon-preview" id="iconPreview">${savedIcon}</div>
                                </div>
                                <small>Emoji lub symbol (będzie wyświetlany obok nazwy)</small>
                            </div>
                        </div>
                        
                        <div class="yap-form-group">
                            <label>Szybki wybór ikony:</label>
                            <div class="yap-icon-picker-grid">
                                ${iconPicker.map(icon => `
                                    <button type="button" class="yap-icon-picker-btn" data-icon="${icon}" title="${icon}">
                                        ${icon}
                                    </button>
                                `).join('')}
                            </div>
                        </div>
                        
                        <div class="yap-form-group">
                            <label for="templateLabel">Etykieta (label) <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="templateLabel" 
                                class="yap-input" 
                                placeholder="np. Dane osobowe"
                                value="${template ? template.label : ''}"
                                required
                            >
                            <small>Nazwa wyświetlana w selektorze pól (bez emoji)</small>
                        </div>
                        
                        <div class="yap-form-group">
                            <label for="templateDescription">Opis</label>
                            <textarea 
                                id="templateDescription" 
                                class="yap-input" 
                                placeholder="Opcjonalny opis szablonu..."
                                rows="3"
                            >${template ? template.description : ''}</textarea>
                        </div>
                        
                        <div class="yap-form-group">
                            <label>Pola w szablonie</label>
                            <div class="yap-template-fields-list">
                                ${fieldsToUse && fieldsToUse.length > 0 ? `
                                    ${fieldsToUse.map((f, idx) => `
                                        <div class="yap-template-field-item">
                                            <span class="yap-template-field-icon">${f.icon || '📝'}</span>
                                            <span class="yap-template-field-label">${f.label}</span>
                                            <span class="yap-template-field-type">(${f.type})</span>
                                        </div>
                                    `).join('')}
                                ` : `
                                    <p class="yap-template-fields-empty">Brak pól do zapamiętania</p>
                                `}
                            </div>
                            <small>Wszystkie wybrane pola zostaną skopiowane jako podpola gdy dodasz ten template</small>
                        </div>
                    </form>
                </div>
                <div class="yap-modal-footer">
                    <button type="button" class="button button-secondary" data-dismiss="modal">Anuluj</button>
                    <button type="button" class="button button-primary yap-template-save">
                        ${isEdit ? '💾 Zaktualizuj' : '➕ Stwórz Template'}
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Append to body
    const $modal = jQuery(modalHTML);
    jQuery('body').append($modal);
    
    // Bind events
    $modal.find('.yap-modal-close, [data-dismiss="modal"]').on('click', function() {
        $modal.remove();
    });
    
    // Icon picker button handlers
    $modal.find('.yap-icon-picker-btn').on('click', function(e) {
        e.preventDefault();
        const icon = jQuery(this).data('icon');
        $modal.find('#templateIcon').val(icon);
        $modal.find('#iconPreview').text(icon);
        jQuery(this).addClass('active').siblings().removeClass('active');
    });
    
    // Icon input live preview
    $modal.find('#templateIcon').on('input', function() {
        const icon = jQuery(this).val();
        $modal.find('#iconPreview').text(icon || '🎨');
    });
    
    // Highlight selected icon on load
    $modal.find('.yap-icon-picker-btn').each(function() {
        if (jQuery(this).data('icon') === savedIcon) {
            jQuery(this).addClass('active');
        }
    });
    
    $modal.find('.yap-template-save').on('click', function() {
        const name = $modal.find('#templateName').val();
        const label = $modal.find('#templateLabel').val();
        const icon = $modal.find('#templateIcon').val() || '🎨';
        const description = $modal.find('#templateDescription').val();
        
        if (!name || !label || !icon) {
            alert('Nazwa, etykieta i ikona są wymagane!');
            return;
        }
        
        const newTemplateId = isEdit ? templateId : 'custom_' + Date.now();
        
        CustomTemplates.save(newTemplateId, {
            name: name,
            label: label,
            icon: icon,
            description: description,
            fields: fieldsToUse || []
        });
        
        console.log('✅ Template saved:', newTemplateId);
        
        if (window.YAPBuilderExt && window.YAPBuilderExt.toast) {
            window.YAPBuilderExt.toast(
                `Template "${label}" ${isEdit ? 'zaktualizowany' : 'stworzony'}!`,
                'success'
            );
        }
        
        // Refresh field types selector
        CustomTemplates.refreshFieldSelector();
        
        $modal.remove();
    });
    
    // Show modal
    $modal.css({
        position: 'fixed',
        top: 0,
        left: 0,
        zIndex: 99999
    });
};

/**
 * Add custom template to schema
 * Similar to FieldPresets.addToSchema()
 */
CustomTemplates.addToSchema = function(templateId) {
    console.log('📋 Adding custom template to schema:', templateId);
    
    const template = this.getTemplate(templateId);
    if (!template) {
        console.error('❌ Custom template not found:', templateId);
        return { success: false, error: 'Template not found' };
    }
    
    if (!window.yapBuilder || !window.yapBuilder.schema) {
        console.error('❌ Schema not initialized');
        return { success: false, error: 'Schema not initialized' };
    }
    
    // Create group field from template
    const groupField = {
        id: FieldStabilization.generateShortId('fld_'),
        key: FieldStabilization.generateShortId('fld_'),
        name: template.name,
        label: template.label,
        type: 'group',
        _created_at: Date.now(),
        _updated_at: Date.now(),
        _locked_key: false,
        sub_fields: template.fields.map(f => ({
            ...f,
            id: FieldStabilization.generateShortId('fld_'),
            key: FieldStabilization.generateShortId('fld_')
        }))
    };
    
    // Add to schema
    window.yapBuilder.schema.fields.push(groupField);
    console.log('✅ Custom template added to schema');
    
    // Record in history
    if (typeof FieldHistory !== 'undefined' && FieldHistory.recordAdd) {
        FieldHistory.recordAdd(groupField);
    }
    
    // Refresh canvas
    if (typeof YAPBuilder !== 'undefined' && YAPBuilder.refreshCanvas) {
        YAPBuilder.refreshCanvas();
    }
    
    return {
        success: true,
        field: groupField,
        template: templateId,
        fieldCount: template.fields.length
    };
};

/**
 * Refresh field type selector to include custom templates
 */
CustomTemplates.refreshFieldSelector = function() {
    const templates = this.getAll();
    const customTypesContainer = jQuery('#yap-field-types');
    
    if (customTypesContainer.length === 0) return;
    
    // Build custom templates HTML with custom icons
    const customTemplatesHTML = Object.keys(templates).map(id => `
        <div class="yap-field-type-item yap-custom-template" 
             data-template-id="${id}" 
             data-category="custom"
             draggable="true"
             title="${templates[id].description || templates[id].label}">
            <span class="yap-field-type-icon" title="${templates[id].name}">${templates[id].icon || '🎨'}</span>
            <span class="yap-field-type-label">${templates[id].label}</span>
        </div>
    `).join('');
    
    // Find or create Custom category
    let customCategory = customTypesContainer.find('.yap-field-category:has(.yap-field-type-item[data-category="custom"])').parent();
    
    if (customCategory.length === 0 && customTemplatesHTML) {
        // Create new Custom category
        const customHTML = `
            <div class="yap-field-category">
                <h4>Custom Templates 🎨</h4>
                <div class="yap-field-type-list" data-category="custom">
                    ${customTemplatesHTML}
                </div>
            </div>
        `;
        
        customTypesContainer.append(customHTML);
        
        // Bind drag handlers
        this.bindCustomTemplateDragHandlers();
    } else if (customCategory.length > 0) {
        // Update existing Custom category
        const listContainer = customCategory.find('[data-category="custom"]');
        listContainer.html(customTemplatesHTML);
        
        this.bindCustomTemplateDragHandlers();
    }
};

/**
 * Bind drag handlers for custom template items
 */
CustomTemplates.bindCustomTemplateDragHandlers = function() {
    const self = this;
    
    jQuery('.yap-custom-template').on('dragstart', function(e) {
        const templateId = jQuery(this).data('template-id');
        e.originalEvent.dataTransfer.setData('templateId', templateId);
        jQuery(this).addClass('dragging');
    });
    
    jQuery('.yap-custom-template').on('dragend', function() {
        jQuery(this).removeClass('dragging');
    });
    
    // Handle drop in canvas
    jQuery('#yap-drop-zone').on('dragover.custom', function(e) {
        if (e.originalEvent.dataTransfer.types.includes('templateId')) {
            e.preventDefault();
            jQuery(this).addClass('drag-over');
        }
    }).on('dragleave.custom', function() {
        jQuery(this).removeClass('drag-over');
    }).on('drop.custom', function(e) {
        e.stopPropagation();
        const templateId = e.originalEvent.dataTransfer.getData('templateId');
        if (templateId) {
            console.log('🎨 Dropped custom template:', templateId);
            self.addToSchema(templateId);
        }
        jQuery(this).removeClass('drag-over');
    });
};

/**
 * Initialize on page load
 */
jQuery(document).ready(function() {
    console.log('✅ Custom Templates System loaded');
    CustomTemplates.refreshFieldSelector();
});

// Export for console access
console.log('Use: CustomTemplates.createFromSelection(fields) or CustomTemplates.addToSchema(templateId)');
