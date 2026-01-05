/**
 * YAP Flexible Content - JavaScript
 * 
 * Handles adding, removing, reordering, and duplicating flexible content sections
 */

(function($) {
    'use strict';
    
    console.log('🎨 YAP Flexible Content initialized');
    
    // Add new section
    $(document).on('click', '.yap-add-flexible-section', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const flexibleId = $button.data('flexible-id');
        const layoutType = $button.data('layout');
        const $container = $('.yap-flexible-container[data-flexible-id="' + flexibleId + '"]');
        const $sections = $container.find('.yap-flexible-sections');
        const layouts = window.yapFlexibleLayouts[flexibleId];
        
        if (!layouts) {
            console.error('❌ No layouts found for', flexibleId);
            return;
        }
        
        // Find layout config
        const layout = layouts.find(l => l.name === layoutType);
        if (!layout) {
            console.error('❌ Layout not found:', layoutType);
            return;
        }
        
        const sectionIndex = $sections.find('.yap-flexible-section').length;
        const inputName = $container.find('.yap-flexible-value').attr('name');
        
        console.log('➕ Adding section:', layoutType, 'at index', sectionIndex);
        
        // Build section HTML
        let sectionHtml = `
            <div class="yap-flexible-section" data-section-index="${sectionIndex}" data-layout="${layoutType}" style="margin-bottom: 16px; padding: 16px; background: linear-gradient(135deg, #ffffff 0%, #f5f7fa 100%); border: 2px solid #0073aa; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,115,170,0.1); transition: all 0.3s ease;">
                <div class="yap-flexible-section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e0e0e0;">
                    <div style="display: flex; align-items: center; gap: 12px; flex-grow: 1;">
                        <span class="dashicons dashicons-menu" style="cursor: move; color: #0073aa; font-size: 20px;"></span>
                        <div>
                            <strong style="font-size: 15px; color: #0073aa; display: block; margin-bottom: 4px;">${layout.label}</strong>
                            <span style="font-size: 11px; color: #666; background: #e8f2f9; padding: 2px 8px; border-radius: 3px; display: inline-block;">${layoutType}</span>
                        </div>
                    </div>
                    <div class="yap-flexible-section-actions" style="display: flex; gap: 8px;">
                        <button type="button" class="button button-small yap-collapse-section" title="Zwiń/Rozwiń" style="padding: 6px 10px; border-radius: 4px; transition: all 0.2s ease;">−</button>
                        <button type="button" class="button button-small yap-duplicate-section" title="Duplikuj" style="padding: 6px 10px; border-radius: 4px; transition: all 0.2s ease;">📋</button>
                        <button type="button" class="button button-small yap-remove-section" title="Usuń" style="color: #fff; background: #dc3232; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; transition: all 0.2s ease;">✕</button>
                    </div>
                </div>
                <div class="yap-flexible-section-fields" style="display: grid; gap: 16px;">
        `;
        
        // Add fields
        layout.sub_fields.forEach(field => {
            const fieldName = `${inputName}[${sectionIndex}][fields][${field.name}]`;
            const fieldId = `${flexibleId}_${sectionIndex}_${field.name}`;
            
            console.log('🔵 Rendering field:', field.name, 'Type:', field.type, 'Full field:', field);
            
            sectionHtml += `
                <div class="yap-field-wrapper" style="padding: 12px; background: white; border: 1px solid #e0e0e0; border-radius: 6px; transition: all 0.2s ease;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #0073aa; font-size: 14px;">${field.label}</label>
                    ${renderFieldInput(field, '', fieldName, fieldId)}
                </div>
            `;
        });
        
        sectionHtml += `
                </div>
            </div>
        `;
        
        $sections.append(sectionHtml);
        updateFlexibleValue($container);
    });
    
    // Remove section
    $(document).on('click', '.yap-remove-section', function(e) {
        e.preventDefault();
        
        if (!confirm('Czy na pewno chcesz usunąć tę sekcję?')) {
            return;
        }
        
        const $section = $(this).closest('.yap-flexible-section');
        const $container = $section.closest('.yap-flexible-container');
        
        $section.remove();
        reindexSections($container);
        updateFlexibleValue($container);
    });
    
    // Duplicate section
    $(document).on('click', '.yap-duplicate-section', function(e) {
        e.preventDefault();
        
        const $section = $(this).closest('.yap-flexible-section');
        const $container = $section.closest('.yap-flexible-container');
        const $clone = $section.clone();
        
        // Clear field values in clone (optional - you can keep values)
        // $clone.find('input, textarea, select').val('');
        
        $section.after($clone);
        reindexSections($container);
        updateFlexibleValue($container);
    });
    
    // Collapse/Expand section
    $(document).on('click', '.yap-collapse-section', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $fields = $button.closest('.yap-flexible-section').find('.yap-flexible-section-fields');
        
        $fields.slideToggle(200, function() {
            $button.text($fields.is(':visible') ? '−' : '+');
        });
    });
    
    // Make sections sortable
    $('.yap-flexible-sections').sortable({
        handle: '.dashicons-menu',
        placeholder: 'yap-flexible-placeholder',
        start: function(e, ui) {
            ui.item.css('opacity', '0.8');
            ui.placeholder.height(ui.item.height());
            ui.placeholder.css('background', '#f0f6fc');
            ui.placeholder.css('border', '2px dashed #0073aa');
            ui.placeholder.css('border-radius', '8px');
            ui.placeholder.css('margin-bottom', '16px');
        },
        stop: function(e, ui) {
            ui.item.css('opacity', '1');
            ui.item.css('transform', 'translateY(0)');
            const $container = ui.item.closest('.yap-flexible-container');
            reindexSections($container);
            updateFlexibleValue($container);
        }
    });
    
    // Update value when fields change
    $(document).on('change', '.yap-flexible-section input, .yap-flexible-section textarea, .yap-flexible-section select', function() {
        const $container = $(this).closest('.yap-flexible-container');
        updateFlexibleValue($container);
    });
    
    /**
     * Reindex sections after add/remove/reorder
     */
    function reindexSections($container) {
        $container.find('.yap-flexible-section').each(function(index) {
            $(this).attr('data-section-index', index);
            
            // Update field names
            $(this).find('input, textarea, select').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                
                if (name) {
                    // Replace [0][fields] with [newIndex][fields]
                    const newName = name.replace(/\[\d+\]\[fields\]/, `[${index}][fields]`);
                    $field.attr('name', newName);
                }
            });
        });
    }
    
    /**
     * Update hidden input with current flexible content value
     */
    function updateFlexibleValue($container) {
        const $hiddenInput = $container.find('.yap-flexible-value');
        const sections = [];
        
        $container.find('.yap-flexible-section').each(function() {
            const $section = $(this);
            const layout = $section.data('layout');
            const fields = {};
            
            $section.find('.yap-flexible-section-fields input, .yap-flexible-section-fields textarea, .yap-flexible-section-fields select').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                
                if (name) {
                    // Extract field name from: name[0][fields][field_name]
                    const match = name.match(/\[fields\]\[([^\]]+)\]/);
                    if (match) {
                        const fieldName = match[1];
                        
                        if ($field.attr('type') === 'checkbox') {
                            fields[fieldName] = $field.is(':checked') ? '1' : '0';
                        } else {
                            fields[fieldName] = $field.val();
                        }
                    }
                }
            });
            
            sections.push({
                layout: layout,
                fields: fields
            });
        });
        
        $hiddenInput.val(JSON.stringify(sections));
        console.log('💾 Flexible value updated:', sections);
    }
    
    /**
     * Render field input HTML based on field type
     */
    function renderFieldInput(field, value, name, id) {
        console.log('🎨 renderFieldInput called - Type:', field.type, 'Name:', name);
        
        switch (field.type) {
            case 'textarea':
                return `<textarea name="${name}" id="${id}" rows="4" class="widefat">${value}</textarea>`;
            
            case 'number':
                return `<input type="number" name="${name}" id="${id}" value="${value}" class="widefat">`;
            
            case 'email':
                return `<input type="email" name="${name}" id="${id}" value="${value}" class="widefat">`;
            
            case 'url':
                return `<input type="url" name="${name}" id="${id}" value="${value}" class="widefat">`;
            
            case 'date':
                return `<input type="date" name="${name}" id="${id}" value="${value}" class="widefat">`;
            
            case 'time':
                return `<input type="time" name="${name}" id="${id}" value="${value}" class="widefat">`;
            
            case 'datetime':
            case 'datetime-local':
                return `<input type="datetime-local" name="${name}" id="${id}" value="${value}" class="widefat">`;
            
            case 'color':
                return `<input type="color" name="${name}" id="${id}" value="${value}" style="width: 100px; height: 40px;">`;
            
            case 'select':
                let selectHtml = `<select name="${name}" id="${id}" class="widefat"><option value="">-- Wybierz --</option>`;
                if (field.choices) {
                    Object.keys(field.choices).forEach(key => {
                        const selected = value === key ? 'selected' : '';
                        selectHtml += `<option value="${key}" ${selected}>${field.choices[key]}</option>`;
                    });
                }
                selectHtml += '</select>';
                return selectHtml;
            
            case 'checkbox':
                const checked = value ? 'checked' : '';
                return `<label><input type="checkbox" name="${name}" id="${id}" value="1" ${checked}> Tak</label>`;
            
            case 'wysiwyg':
                return `<textarea name="${name}" id="${id}" rows="8" class="widefat">${value}</textarea>`;
            
            case 'image':
                return `
                    <div class="yap-image-field-wrapper">
                        <input type="hidden" id="${id}" name="${name}" value="${value}" class="yap-image-id">
                        <button type="button" class="button yap-upload-image-button" data-field="${id}">Wybierz obraz</button>
                        <img src="" class="yap-image-preview" style="margin-top: 10px; max-width: 150px; display: none;">
                        <button type="button" class="button yap-remove-image-button" style="margin-top: 5px; display: none;">Usuń</button>
                    </div>
                `;
            
            default:
                return `<input type="text" name="${name}" id="${id}" value="${value}" class="widefat" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">`;
        }
    }
    
    /**
     * Add CSS for sortable placeholder
     */
    const style = document.createElement('style');
    style.textContent = `
        .yap-flexible-placeholder {
            background: #f0f6fc !important;
            border: 2px dashed #0073aa !important;
            border-radius: 8px !important;
            margin-bottom: 16px !important;
            animation: yapPulse 1.5s ease infinite;
        }
        
        @keyframes yapPulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
        
        .yap-flexible-section-actions button:hover {
            transform: translateY(-2px);
        }
    `;
    document.head.appendChild(style);

})(jQuery);
