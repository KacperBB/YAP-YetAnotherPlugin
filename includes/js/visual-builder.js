/**
 * YAP Visual Builder - JavaScript
 * Drag & drop functionality for field builder
 */

(function($) {
    'use strict';
    
    const YAPBuilder = {
        schema: {
            name: '',
            fields: []
        },
        selectedField: null,
        
        init() {
            this.initDragDrop();
            this.initEvents();
            this.initSortable();
            
            // Initialize FieldHistory for tracking changes
            if (typeof FieldHistory !== 'undefined') {
                FieldHistory.init();
                console.log('✅ Field History initialized in Visual Builder');
            } else {
                console.warn('⚠️ Field History not available');
            }
            
            // Auto-load group if specified in URL
            if (yapBuilder.autoLoadGroup) {
                $('#yap-builder-group-select').val(yapBuilder.autoLoadGroup).trigger('change');
            }
        },
        
        /**
         * Initialize drag & drop
         */
        initDragDrop() {
            const self = this;
            
            // Make field types draggable
            $('.yap-field-type-item').on('dragstart', function(e) {
                const fieldType = $(this).data('field-type');
                e.originalEvent.dataTransfer.setData('fieldType', fieldType);
                $(this).addClass('dragging');
            });
            
            $('.yap-field-type-item').on('dragend', function() {
                $(this).removeClass('dragging');
            });
            
            // Drop zone
            const $dropZone = $('#yap-drop-zone');
            
            $dropZone.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });
            
            $dropZone.on('dragleave', function() {
                $(this).removeClass('dragover');
            });
            
            $dropZone.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                
                const fieldType = e.originalEvent.dataTransfer.getData('fieldType');
                console.log('Dropped field type:', fieldType);
                
                if (fieldType) {
                    self.addField(fieldType);
                } else {
                    console.error('No field type in drop event');
                }
            });
        },
        
        /**
         * Initialize sortable for fields
         */
        initSortable() {
            const self = this; // FIX: Store context BEFORE using in callbacks
            
            $('#yap-drop-zone').sortable({
                items: '.yap-field-item',
                handle: '.yap-field-drag-handle',
                placeholder: 'yap-field-placeholder',
                opacity: 0.8,
                update: function() {
                    console.log('📍 Sortable update - reordering fields...');
                    self.updateFieldOrder(); // Use self instead of this
                },
                stop: function() {
                    console.log('✅ Sortable stop - rebinding events...');
                    // Rebind events after sorting
                    self.bindFieldEvents();
                    // Reinitialize drop zones for container fields
                    self.reinitializeContainerFields();
                }
            });
        },
        
        /**
         * Initialize events
         */
        initEvents() {
            const self = this;
            
            // Save button
            $('#yap-builder-save').on('click', () => {
                this.saveSchema();
            });
            
            // Export button
            $('#yap-builder-export').on('click', () => {
                this.exportSchema();
            });
            
            // Preview button
            $('#yap-builder-preview').on('click', () => {
                this.showPreview();
            });
            
            // Group Settings button
            $('#yap-builder-group-settings').on('click', () => {
                this.showGroupSettingsModal();
            });
            
            // Group select
            $('#yap-builder-group-select').on('change', function() {
                const groupName = $(this).val();
                if (groupName) {
                    self.loadSchema(groupName);
                    // Show location rules section when group is selected
                    $('#yap-builder-location-section').slideDown(300);
                    // Show group settings button
                    $('#yap-builder-group-settings').fadeIn(300);
                } else {
                    self.clearCanvas();
                    // Hide location rules for new group
                    $('#yap-builder-location-section').slideUp(300);
                    // Hide group settings button
                    $('#yap-builder-group-settings').fadeOut(300);
                }
            });
            
            // Group name input
            $('#yap-group-name').on('input', function() {
                self.schema.name = $(this).val();
            });
            
            // Canvas mode toggle
            $('input[name="canvas_mode"]').on('change', function() {
                const mode = $(this).val();
                if (mode === 'preview') {
                    self.enterPreviewMode();
                } else {
                    self.enterEditMode();
                }
            });
            
            // Edit button
            $(document).on('click', '.yap-field-edit', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const $field = $(this).closest('.yap-field-item');
                const fieldId = $field.data('field-id');
                self.editField(fieldId);
            });
            
            // Duplicate button
            $(document).on('click', '.yap-field-duplicate', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const $field = $(this).closest('.yap-field-item');
                const fieldId = $field.data('field-id');
                self.duplicateField(fieldId);
            });
            
            // Delete button
            $(document).on('click', '.yap-field-delete', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const $field = $(this).closest('.yap-field-item');
                const fieldId = $field.data('field-id');
                const fieldLabel = $field.find('.yap-field-label').text();
                
                // Show modal instead of browser confirm
                if (window.YAPBuilderExt) {
                    window.YAPBuilderExt.showDeleteModal(fieldId, fieldLabel, function() {
                        self.deleteField(fieldId);
                    });
                } else {
                    // Fallback to confirm if extensions not loaded
                    if (confirm('Delete this field?')) {
                        self.deleteField(fieldId);
                    }
                }
            });
            
            // Template use button
            $('.yap-template-use').on('click', function() {
                const templateKey = $(this).closest('.yap-template-item').data('template-key');
                self.useTemplate(templateKey);
            });
            
            // Modal close
            $('.yap-modal-close, .yap-modal-overlay').on('click', () => {
                $('.yap-modal').hide();
            });
            
            // Inspector close
            $('.yap-inspector-close').on('click', () => {
                this.closeInspector();
            });
        },
        
        /**
         * Bind field events (edit, duplicate, delete buttons)
         */
        bindFieldEvents() {
            const self = this;
            
            // Unbind first to avoid duplicates
            $(document).off('click', '.yap-field-edit');
            $(document).off('click', '.yap-field-duplicate');
            $(document).off('click', '.yap-field-delete');
            
            // Edit button
            $(document).on('click', '.yap-field-edit', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const $field = $(this).closest('.yap-field-item');
                const fieldId = $field.attr('data-field-id'); // Use attr() not data()
                console.log('✏️ Edit button clicked - fieldId from DOM:', fieldId);
                console.log('   Available fields in schema:', self.schema.fields.map(f => f.id).join(', '));
                
                // Validate field exists before opening modal
                const field = self.schema.fields.find(f => f.id === fieldId);
                if (!field) {
                    console.error('❌ Field not found in schema!', fieldId);
                    console.log('   Syncing DOM with schema...');
                    // Rebuild from schema to fix mismatch
                    self.clearCanvas();
                    self.schema.fields.forEach(f => self.renderField(f));
                    self.bindFieldEvents();
                    return;
                }
                
                self.editField(fieldId);
            });
            
            // Duplicate button
            $(document).on('click', '.yap-field-duplicate', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const $field = $(this).closest('.yap-field-item');
                const fieldId = $field.attr('data-field-id'); // Use attr() not data()
                self.duplicateField(fieldId);
            });
            
            // Delete button
            $(document).on('click', '.yap-field-delete', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const $field = $(this).closest('.yap-field-item');
                const fieldId = $field.attr('data-field-id'); // Use attr() not data()
                const fieldLabel = $field.find('.yap-field-label').text();
                
                if (window.YAPBuilderExt) {
                    window.YAPBuilderExt.showDeleteModal(fieldId, fieldLabel, function() {
                        self.deleteField(fieldId);
                    });
                } else {
                    if (confirm('Delete this field?')) {
                        self.deleteField(fieldId);
                    }
                }
            });
            
            // Manage Flexible Layouts button
            $(document).on('click', '.yap-manage-flexible-layouts', function(e) {
                e.stopPropagation();
                e.preventDefault();
                
                // Get group name from the select dropdown
                const groupName = $('#yap-builder-group-select').val();
                const fieldName = $(this).data('field');
                
                console.log('🔵 Opening flexible modal - Group:', groupName, 'Field:', fieldName);
                
                if (!groupName) {
                    alert('⚠️ Najpierw wybierz lub utwórz grupę!');
                    return;
                }
                
                // Trigger the flexible content modal from flexible-content.php
                if (typeof window.yapOpenFlexibleModal === 'function') {
                    window.yapOpenFlexibleModal(groupName, fieldName);
                } else {
                    console.error('Flexible Content modal function not found');
                }
            });
        },
        
        /**
         * Reinitialize all container fields drop zones after sorting
         */
        reinitializeContainerFields() {
            const self = this;
            
            // Find all container fields and reinitialize their drop zones
            $('.yap-field-container').each(function() {
                const fieldId = $(this).data('field-id');
                const fieldType = $(this).data('field-type');
                
                if (fieldType === 'group' || fieldType === 'repeater') {
                    // Destroy existing sortable if any
                    const $dropZone = $(`.yap-sub-fields-drop-zone[data-parent-id="${fieldId}"]`);
                    if ($dropZone.hasClass('ui-sortable')) {
                        $dropZone.sortable('destroy');
                    }
                    
                    // Unbind existing events
                    $dropZone.off('dragover dragleave drop');
                    $dropZone.off('click', '.yap-sub-field-edit');
                    $dropZone.off('click', '.yap-sub-field-remove');
                    
                    // Reinitialize
                    self.initSubFieldsDropZone(fieldId);
                }
            });
        },
        
        /**
         * Add field to canvas
         */
        addField(fieldType) {
            console.log('Adding field:', fieldType);
            
            // First try to get field metadata from yapBuilder.fieldTypes (primary source)
            let fieldData = null;
            if (yapBuilder && yapBuilder.fieldTypes && yapBuilder.fieldTypes[fieldType]) {
                console.log('Using yapBuilder.fieldTypes');
                fieldData = yapBuilder.fieldTypes[fieldType];
            }
            
            // If not found, try FieldTypeRegistry (fallback)
            if (!fieldData && typeof FieldTypeRegistry !== 'undefined' && FieldTypeRegistry.get) {
                console.log('Using FieldTypeRegistry (fallback)');
                fieldData = FieldTypeRegistry.get(fieldType);
            }
            
            // Final check - if still no data
            if (!fieldData) {
                console.error('❌ CRITICAL: Field type not found!');
                console.error('fieldType:', fieldType);
                console.error('yapBuilder.fieldTypes available?', yapBuilder && yapBuilder.fieldTypes);
                console.error('FieldTypeRegistry available?', typeof FieldTypeRegistry !== 'undefined');
                return;
            }
            
            const fieldId = 'field_' + Date.now();
            const fieldName = fieldType + '_' + Math.random().toString(36).substr(2, 5);
            
            const field = {
                id: fieldId,
                name: fieldName,
                type: fieldType,
                label: fieldData.label,
                icon: fieldData.icon,
                settings: {}
            };
            
            // Initialize sub_fields for container types
            if (fieldType === 'group' || fieldType === 'repeater') {
                field.sub_fields = [];
            }
            
            // Initialize settings for all fields
            if (!field.required) field.required = false;
            if (!field.placeholder) field.placeholder = '';
            if (!field.default_value) field.default_value = '';
            if (!field.description) field.description = '';
            if (!field.css_class) field.css_class = '';
            
            this.schema.fields.push(field);
            this.renderField(field);
            
            // Record addition in history
            if (typeof FieldHistory !== 'undefined' && FieldHistory.recordAdd) {
                FieldHistory.recordAdd(field);
            }
            
            console.log('Field added successfully:', field);
            
            // Remove placeholder
            $('.yap-drop-zone-placeholder').hide();
        },
        
        /**
         * Render field on canvas
         */
        renderField(field) {
            const self = this;
            
            // Check if this is a container field (group, repeater)
            const isContainer = ['group', 'repeater'].includes(field.type);
            
            // Initialize sub_fields if container
            if (isContainer && !field.sub_fields) {
                field.sub_fields = [];
            }
            
            // Render sub-fields HTML for containers (recursive for nested containers)
            let subFieldsHTML = '';
            if (isContainer) {
                if (field.sub_fields && field.sub_fields.length > 0) {
                    subFieldsHTML = field.sub_fields.map(subField => {
                        const isSubContainer = ['group', 'repeater'].includes(subField.type);
                        const subFieldCount = isSubContainer && subField.sub_fields ? subField.sub_fields.length : 0;
                        
                        // Render nested sub-fields for nested containers
                        let nestedSubFieldsHTML = '';
                        if (isSubContainer) {
                            if (subField.sub_fields && subField.sub_fields.length > 0) {
                                nestedSubFieldsHTML = subField.sub_fields.map(nestedField => {
                                    return `
                                        <div class="yap-sub-field-item yap-nested-level-2" data-field-id="${nestedField.id}" data-field-type="${nestedField.type}">
                                            <span class="yap-sub-field-icon">${nestedField.icon}</span>
                                            <span class="yap-sub-field-label">${nestedField.label}</span>
                                            <span class="yap-sub-field-type">(${nestedField.type})</span>
                                            <div class="yap-sub-field-actions">
                                                <button class="yap-sub-field-edit" title="Edytuj">
                                                    <span class="dashicons dashicons-edit"></span>
                                                </button>
                                                <button class="yap-sub-field-remove" title="Usuń">
                                                    <span class="dashicons dashicons-no-alt"></span>
                                                </button>
                                            </div>
                                        </div>
                                    `;
                                }).join('');
                            } else {
                                nestedSubFieldsHTML = `
                                    <div class="yap-sub-field-placeholder yap-nested-placeholder">
                                        <span class="dashicons dashicons-plus-alt"></span>
                                        <p>Przeciągnij pola tutaj</p>
                                    </div>
                                `;
                            }
                        }
                        
                        return `
                            <div class="yap-sub-field-item ${isSubContainer ? 'yap-sub-field-container' : ''}" data-field-id="${subField.id}" data-field-type="${subField.type}" data-expanded="true">
                                <div class="yap-sub-field-main">
                                    ${isSubContainer ? `<button class="yap-sub-field-toggle" title="Rozwiń/Zwiń" style="background: none; border: none; padding: 0; margin-right: 8px; cursor: pointer; color: #0073aa; font-size: 16px; min-width: 24px;"><span class="dashicons dashicons-arrow-down-alt2"></span></button>` : ''}
                                    <span class="yap-sub-field-icon">${subField.icon}</span>
                                    <span class="yap-sub-field-label">${subField.label}</span>
                                    <span class="yap-sub-field-type">(${subField.type})</span>
                                    ${isSubContainer ? `<span class="yap-sub-field-count" title="Zagnieżdżone pola">${subFieldCount} 📦</span>` : ''}
                                    <div class="yap-sub-field-actions">
                                        <button class="yap-sub-field-edit" title="Edytuj">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>
                                        <button class="yap-sub-field-remove" title="Usuń">
                                            <span class="dashicons dashicons-no-alt"></span>
                                        </button>
                                    </div>
                                </div>
                                ${isSubContainer ? `
                                    <div class="yap-nested-drop-zone" data-parent-id="${subField.id}" style="display: block;">
                                        ${nestedSubFieldsHTML}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    }).join('');
                } else {
                    subFieldsHTML = `
                        <div class="yap-sub-field-placeholder">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <p>Przeciągnij pola tutaj aby ${field.type === 'group' ? 'zagnieździć' : 'dodać do repeatera'}</p>
                        </div>
                    `;
                }
            }
            
            const template = `
                <div class="yap-field-item ${isContainer ? 'yap-field-container' : ''}" data-field-id="${field.id}" data-field-type="${field.type}">
                    <div class="yap-field-item-header">
                        <span class="yap-field-icon">${field.icon}</span>
                        <span class="yap-field-label">${field.label}</span>
                        <div class="yap-field-actions">
                            <button class="yap-field-edit" title="Edit">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button class="yap-field-duplicate" title="Duplicate">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                            <button class="yap-field-delete" title="Delete">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                            <span class="yap-field-drag-handle dashicons dashicons-menu"></span>
                        </div>
                    </div>
                    <div class="yap-field-item-body">
                        <div class="yap-field-meta">
                            <span class="yap-field-name">${field.name}</span>
                            <span class="yap-field-type-badge">${field.type}</span>
                        </div>
                        ${field.type === 'flexible_content' ? `
                        <div class="yap-flexible-layouts-manager" style="padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; margin-top: 10px;">
                            <button type="button" class="button button-primary yap-manage-flexible-layouts" 
                                data-group="" 
                                data-field="${field.name}"
                                style="background: white; color: #667eea; border: none; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                                <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                                Zarządzaj Layoutami
                            </button>
                            <p style="color: white; margin: 10px 0 0; font-size: 12px; opacity: 0.95;">
                                Kliknij aby skonfigurować dostępne sekcje dla tego pola
                            </p>
                        </div>
                        ` : ''}
                        ${isContainer ? `
                        <div class="yap-field-sub-fields-container" data-parent-id="${field.id}">
                            <div class="yap-sub-fields-header">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                <strong>${field.type === 'group' ? 'Zagnieżdżone pola' : 'Pola repeatera'}</strong>
                                <span class="yap-sub-fields-count">(${field.sub_fields ? field.sub_fields.length : 0})</span>
                            </div>
                            <div class="yap-sub-fields-drop-zone" data-parent-id="${field.id}">
                                ${subFieldsHTML}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            const $field = $(template);
            $('#yap-drop-zone').append($field);
            
            // Initialize sub-fields drop zone if container
            if (isContainer) {
                this.initSubFieldsDropZone(field.id);
            }
        },
        
        /**
         * Initialize drop zone for sub-fields (group/repeater)
         */
        initSubFieldsDropZone(parentId) {
            const self = this;
            const $dropZone = $(`.yap-sub-fields-drop-zone[data-parent-id="${parentId}"]`);
            
            // Make droppable
            $dropZone.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });
            
            $dropZone.on('dragleave', function(e) {
                $(this).removeClass('dragover');
            });
            
            $dropZone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
                
                const fieldType = e.originalEvent.dataTransfer.getData('fieldType');
                console.log('Dropped into sub-field zone:', fieldType, 'Parent:', parentId);
                
                if (fieldType) {
                    self.addSubField(parentId, fieldType);
                }
            });
            
            // Make sub-fields sortable
            $dropZone.sortable({
                items: '.yap-sub-field-item',
                placeholder: 'yap-sub-field-placeholder-sort',
                opacity: 0.8,
                connectWith: '.yap-sub-fields-drop-zone',
                update: function(event, ui) {
                    self.updateSubFieldsOrder(parentId);
                }
            });
            
            // Bind toggle button (expand/collapse)
            $dropZone.on('click', '.yap-sub-field-toggle', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const $item = $(this).closest('.yap-sub-field-item');
                const $nestedZone = $item.find('.yap-nested-drop-zone');
                const isExpanded = $item.data('expanded') !== false;
                
                if (isExpanded) {
                    // Collapse
                    $nestedZone.slideUp(200);
                    $(this).find('.dashicons').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
                    $item.data('expanded', false);
                } else {
                    // Expand
                    $nestedZone.slideDown(200);
                    $(this).find('.dashicons').removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
                    $item.data('expanded', true);
                }
            });
            
            // Bind edit button
            $dropZone.on('click', '.yap-sub-field-edit', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const subFieldId = $(this).closest('.yap-sub-field-item').data('field-id');
                console.log('🔧 Sub-field edit clicked - parent:', parentId, 'subField:', subFieldId);
                
                const parentField = self.schema.fields.find(f => f.id === parentId);
                console.log('🔧 Parent field found:', parentField ? 'YES' : 'NO', parentField);
                
                if (parentField && parentField.sub_fields) {
                    const subField = parentField.sub_fields.find(f => f.id === subFieldId);
                    console.log('🔧 Sub-field found:', subField ? 'YES' : 'NO', subField);
                    if (subField) {
                        console.log('🔧 Opening modal for sub-field:', subField.label);
                        self.showFieldSettingsModal(subField, parentId);
                    } else {
                        console.error('❌ Sub-field not found:', subFieldId, 'in', parentField.sub_fields);
                    }
                } else {
                    console.error('❌ Parent field not found or has no sub_fields:', parentId);
                }
            });
            
            // Bind remove button
            $dropZone.on('click', '.yap-sub-field-remove', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const subFieldId = $(this).closest('.yap-sub-field-item').data('field-id');
                self.removeSubField(parentId, subFieldId);
            });
            
            // Double-click to edit sub-field
            $dropZone.on('dblclick', '.yap-sub-field-item', function(e) {
                e.stopPropagation();
                const subFieldId = $(this).data('field-id');
                const parentField = self.schema.fields.find(f => f.id === parentId);
                
                if (parentField && parentField.sub_fields) {
                    const subField = parentField.sub_fields.find(f => f.id === subFieldId);
                    if (subField) {
                        self.showFieldSettingsModal(subField, parentId);
                    }
                }
            });
            
            // Initialize nested drop zones for nested containers
            $dropZone.find('.yap-nested-drop-zone').each(function() {
                const nestedParentId = $(this).data('parent-id');
                self.initNestedDropZone(nestedParentId, parentId);
            });
        },
        
        /**
         * Initialize nested drop zone for nested containers (group in group, repeater in repeater, etc.)
         */
        initNestedDropZone(nestedParentId, topParentId) {
            const self = this;
            const $nestedDropZone = $(`.yap-nested-drop-zone[data-parent-id="${nestedParentId}"]`);
            
            // Make droppable
            $nestedDropZone.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });
            
            $nestedDropZone.on('dragleave', function(e) {
                e.stopPropagation();
                $(this).removeClass('dragover');
            });
            
            $nestedDropZone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
                
                const fieldType = e.originalEvent.dataTransfer.getData('fieldType');
                console.log('Dropped into nested zone:', fieldType, 'Nested Parent:', nestedParentId, 'Top Parent:', topParentId);
                
                if (fieldType) {
                    self.addNestedSubField(topParentId, nestedParentId, fieldType);
                }
            });
            
            // Make nested sub-fields sortable
            $nestedDropZone.sortable({
                items: '.yap-sub-field-item',
                placeholder: 'yap-sub-field-placeholder-sort',
                opacity: 0.8,
                connectWith: '.yap-nested-drop-zone, .yap-sub-fields-drop-zone',
                update: function(event, ui) {
                    self.updateNestedSubFieldsOrder(topParentId, nestedParentId);
                }
            });
            
            // Bind edit button for nested fields
            $nestedDropZone.on('click', '.yap-sub-field-edit', function(e) {
                e.stopPropagation();
                const subFieldId = $(this).closest('.yap-sub-field-item').data('field-id');
                
                // Find the nested field
                const topParentField = self.schema.fields.find(f => f.id === topParentId);
                if (topParentField && topParentField.sub_fields) {
                    const nestedParent = topParentField.sub_fields.find(f => f.id === nestedParentId);
                    if (nestedParent && nestedParent.sub_fields) {
                        const nestedField = nestedParent.sub_fields.find(f => f.id === subFieldId);
                        if (nestedField) {
                            self.showFieldSettingsModal(nestedField, nestedParentId);
                        }
                    }
                }
            });
            
            // Bind remove button for nested fields
            $nestedDropZone.on('click', '.yap-sub-field-remove', function(e) {
                e.stopPropagation();
                const subFieldId = $(this).closest('.yap-sub-field-item').data('field-id');
                self.removeNestedSubField(topParentId, nestedParentId, subFieldId);
            });
        },
        
        /**
         * Add sub-field to container (group/repeater)
         */
        addSubField(parentId, fieldType) {
            const parentField = this.schema.fields.find(f => f.id === parentId);
            
            if (!parentField) {
                console.error('Parent field not found:', parentId);
                return;
            }
            
            const fieldData = yapBuilder.fieldTypes[fieldType];
            
            if (!fieldData) {
                console.error('Field type not found:', fieldType);
                return;
            }
            
            const subField = {
                id: 'field_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                name: fieldType + '_' + (parentField.sub_fields.length + 1),
                label: fieldData.label + ' ' + (parentField.sub_fields.length + 1),
                type: fieldType,
                icon: fieldData.icon,
                required: false,
                placeholder: '',
                default_value: '',
                css_class: '',
                description: ''
            };
            
            // Initialize sub_fields array if this is a container field (group/repeater)
            if (fieldType === 'group' || fieldType === 'repeater') {
                subField.sub_fields = [];
            }
            
            if (!parentField.sub_fields) {
                parentField.sub_fields = [];
            }
            parentField.sub_fields.push(subField);
            
            // Record addition in history
            if (typeof FieldHistory !== 'undefined' && FieldHistory.recordAdd) {
                FieldHistory.recordAdd(subField);
            }
            
            console.log('Sub-field added to parent:', subField, parentField);
            
            this.reRenderField(parentId);
            
            if (window.YAPBuilderExt) {
                window.YAPBuilderExt.toast(`Dodano ${fieldData.label} do ${parentField.label}`, 'success');
            }
        },
        
        /**
         * Remove sub-field from container
         */
        removeSubField(parentId, subFieldId) {
            const parentField = this.schema.fields.find(f => f.id === parentId);
            
            if (!parentField || !parentField.sub_fields) return;
            
            const index = parentField.sub_fields.findIndex(f => f.id === subFieldId);
            
            if (index === -1) return;
            
            const subField = parentField.sub_fields[index];
            const subFieldLabel = subField.label;
            
            if (window.YAPBuilderExt) {
                window.YAPBuilderExt.showDeleteModal(subFieldId, subFieldLabel, () => {
                    parentField.sub_fields.splice(index, 1);
                    this.reRenderField(parentId);
                    
                    // Record deletion in history
                    if (typeof FieldHistory !== 'undefined' && FieldHistory.recordDelete) {
                        FieldHistory.recordDelete(subField);
                    }
                    
                    window.YAPBuilderExt.toast('Pole usunięte', 'info');
                });
            } else {
                if (confirm(`Usunąć pole: ${subFieldLabel}?`)) {
                    parentField.sub_fields.splice(index, 1);
                    this.reRenderField(parentId);
                    
                    // Record deletion in history
                    if (typeof FieldHistory !== 'undefined' && FieldHistory.recordDelete) {
                        FieldHistory.recordDelete(subField);
                    }
                }
            }
        },
        
        /**
         * Add nested sub-field (field inside nested container)
         */
        addNestedSubField(topParentId, nestedParentId, fieldType) {
            const topParentField = this.schema.fields.find(f => f.id === topParentId);
            
            if (!topParentField || !topParentField.sub_fields) {
                console.error('Top parent field not found:', topParentId);
                return;
            }
            
            const nestedParent = topParentField.sub_fields.find(f => f.id === nestedParentId);
            
            if (!nestedParent) {
                console.error('Nested parent not found:', nestedParentId);
                return;
            }
            
            const fieldData = yapBuilder.fieldTypes[fieldType];
            
            if (!fieldData) {
                console.error('Field type not found:', fieldType);
                return;
            }
            
            const nestedField = {
                id: 'field_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                name: fieldType + '_' + ((nestedParent.sub_fields?.length || 0) + 1),
                label: fieldData.label + ' ' + ((nestedParent.sub_fields?.length || 0) + 1),
                type: fieldType,
                icon: fieldData.icon,
                required: false,
                placeholder: '',
                default_value: '',
                css_class: '',
                description: ''
            };
            
            // Initialize sub_fields if this is also a container
            if (fieldType === 'group' || fieldType === 'repeater') {
                nestedField.sub_fields = [];
            }
            
            if (!nestedParent.sub_fields) {
                nestedParent.sub_fields = [];
            }
            nestedParent.sub_fields.push(nestedField);
            
            console.log('Nested field added:', nestedField, 'to', nestedParent);
            
            this.reRenderField(topParentId);
            
            if (window.YAPBuilderExt) {
                window.YAPBuilderExt.toast(`Dodano ${fieldData.label} do ${nestedParent.label}`, 'success');
            }
        },
        
        /**
         * Remove nested sub-field
         */
        removeNestedSubField(topParentId, nestedParentId, subFieldId) {
            const topParentField = this.schema.fields.find(f => f.id === topParentId);
            
            if (!topParentField || !topParentField.sub_fields) return;
            
            const nestedParent = topParentField.sub_fields.find(f => f.id === nestedParentId);
            
            if (!nestedParent || !nestedParent.sub_fields) return;
            
            const index = nestedParent.sub_fields.findIndex(f => f.id === subFieldId);
            
            if (index === -1) return;
            
            const fieldLabel = nestedParent.sub_fields[index].label;
            
            if (window.YAPBuilderExt) {
                window.YAPBuilderExt.showDeleteModal(subFieldId, fieldLabel, () => {
                    nestedParent.sub_fields.splice(index, 1);
                    this.reRenderField(topParentId);
                    
                    window.YAPBuilderExt.toast('Pole usunięte', 'info');
                });
            } else {
                if (confirm(`Usunąć pole: ${fieldLabel}?`)) {
                    nestedParent.sub_fields.splice(index, 1);
                    this.reRenderField(topParentId);
                }
            }
        },
        
        /**
         * Update nested sub-fields order after sorting
         */
        updateNestedSubFieldsOrder(topParentId, nestedParentId) {
            const topParentField = this.schema.fields.find(f => f.id === topParentId);
            
            if (!topParentField || !topParentField.sub_fields) return;
            
            const nestedParent = topParentField.sub_fields.find(f => f.id === nestedParentId);
            
            if (!nestedParent || !nestedParent.sub_fields) return;
            
            const $nestedDropZone = $(`.yap-nested-drop-zone[data-parent-id="${nestedParentId}"]`);
            const newOrder = [];
            
            $nestedDropZone.find('.yap-sub-field-item').each(function() {
                const subFieldId = $(this).data('field-id');
                const nestedField = nestedParent.sub_fields.find(f => f.id === subFieldId);
                if (nestedField) {
                    newOrder.push(nestedField);
                }
            });
            
            nestedParent.sub_fields = newOrder;
            
            console.log('Nested sub-fields reordered:', newOrder);
        },
        
        /**
         * Update sub-fields order after sorting
         */
        updateSubFieldsOrder(parentId) {
            const parentField = this.schema.fields.find(f => f.id === parentId);
            
            if (!parentField || !parentField.sub_fields) return;
            
            const $dropZone = $(`.yap-sub-fields-drop-zone[data-parent-id="${parentId}"]`);
            const newOrder = [];
            
            $dropZone.find('.yap-sub-field-item').each(function() {
                const subFieldId = $(this).data('field-id');
                const subField = parentField.sub_fields.find(f => f.id === subFieldId);
                if (subField) {
                    newOrder.push(subField);
                }
            });
            
            parentField.sub_fields = newOrder;
            
            console.log('Sub-fields reordered:', newOrder);
        },
        
        /**
         * Re-render single field (useful after sub-field changes)
         */
        reRenderField(fieldId) {
            const field = this.schema.fields.find(f => f.id === fieldId);
            
            if (!field) return;
            
            $(`.yap-field-item[data-field-id="${fieldId}"]`).remove();
            
            this.renderField(field);
            
            this.bindFieldEvents();
        },
        
        /**
         * Edit field (opens modal)
         */
        editField(fieldId) {
            const field = this.schema.fields.find(f => f.id === fieldId);
            
            console.log('🔧 editField called for fieldId:', fieldId);
            console.log('🔧 Field found:', field ? 'YES' : 'NO', field);
            
            if (!field) {
                console.error('❌ Field not found:', fieldId);
                return;
            }
            
            this.selectedField = fieldId;
            
            // Create modal - bindFieldSettings is called inside showFieldSettingsModal
            console.log('🔧 Showing field settings modal for:', field.label);
            this.showFieldSettingsModal(field);
        },
        
        /**
         * Show field settings modal
         */
        showFieldSettingsModal(field, parentId = null) {
            const self = this;
            
            console.log('📋 showFieldSettingsModal called', 'field:', field.label, 'parentId:', parentId);
            
            // Create modal HTML
            const modalHTML = `
                <div id="yap-field-settings-modal" class="yap-settings-modal">
                    <div class="yap-settings-modal-overlay"></div>
                    <div class="yap-settings-modal-content">
                        <div class="yap-settings-modal-header">
                            <div class="yap-settings-modal-title">
                                <span class="yap-field-type-icon">${yapBuilder.fieldTypes[field.type].icon}</span>
                                <div>
                                    <h2>Ustawienia pola: ${field.label}</h2>
                                    <p class="yap-field-meta-info">Typ: <strong>${yapBuilder.fieldTypes[field.type].label}</strong> | ID: <code>${field.id}</code></p>
                                </div>
                            </div>
                            <button class="yap-settings-modal-close" title="Zamknij (Esc)">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                        
                        <div class="yap-settings-modal-body">
                            <div class="yap-settings-tabs">
                                <button class="yap-settings-tab active" data-tab="general">
                                    <span class="dashicons dashicons-admin-settings"></span> Ogólne
                                </button>
                                <button class="yap-settings-tab" data-tab="advanced">
                                    <span class="dashicons dashicons-admin-generic"></span> Zaawansowane
                                </button>
                                <button class="yap-settings-tab" data-tab="conditional">
                                    <span class="dashicons dashicons-randomize"></span> Warunki
                                </button>
                            </div>
                            
                            <div class="yap-settings-content">
                                <div class="yap-settings-panel active" data-panel="general">
                                    ${this.getGeneralSettingsHTML(field)}
                                </div>
                                
                                <div class="yap-settings-panel" data-panel="advanced">
                                    ${this.getAdvancedSettingsHTML(field)}
                                </div>
                                
                                <div class="yap-settings-panel" data-panel="conditional">
                                    ${this.getConditionalSettingsHTML(field)}
                                </div>
                            </div>
                        </div>
                        
                        <div class="yap-settings-modal-footer">
                            <button class="button button-large yap-create-template" title="Stwórz template z tego pola i jego ustawień">
                                <span class="dashicons dashicons-admin-tools"></span> 🎨 Stwórz Template
                            </button>
                            <div class="yap-settings-footer-spacer"></div>
                            <button class="button button-large yap-settings-cancel">Anuluj</button>
                            <button class="button button-primary button-large yap-settings-save">
                                <span class="dashicons dashicons-saved"></span> Zapisz ustawienia
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            $('#yap-field-settings-modal').remove();
            
            // Add modal to body
            $('body').append(modalHTML);
            console.log('📋 Modal added to DOM');
            
            // Show modal with animation and bind events AFTER modal is in DOM
            setTimeout(() => {
                $('#yap-field-settings-modal').addClass('yap-modal-show');
                console.log('📋 Modal class added - should be visible now');
                
                // Unbind first to avoid duplicates
                $('.yap-settings-tab').off('click');
                $('.yap-settings-modal-close, .yap-settings-modal-overlay, .yap-settings-cancel').off('click');
                $('.yap-settings-save').off('click');
                $(document).off('keydown.yapModal');
                
                // Bind modal events
                self.bindModalEvents(field);
                
                // Tab switching
                $('.yap-settings-tab').on('click', function() {
                    const tab = $(this).data('tab');
                    $('.yap-settings-tab').removeClass('active');
                    $(this).addClass('active');
                    $('.yap-settings-panel').removeClass('active');
                    $(`.yap-settings-panel[data-panel="${tab}"]`).addClass('active');
                });
                
                // Close modal
                $('.yap-settings-modal-close, .yap-settings-modal-overlay, .yap-settings-cancel').on('click', function() {
                    self.closeFieldSettingsModal();
                });
                
                // Save button
                $('.yap-settings-save').on('click', function() {
                    console.log('💾 Save button clicked');
                    self.updateFieldUI(field);
                    
                    // If this is a sub-field, re-render parent container
                    if (parentId) {
                        console.log('💾 Re-rendering parent field:', parentId);
                        self.reRenderField(parentId);
                    }
                    
                    self.closeFieldSettingsModal();
                    if (window.YAPBuilderExt) {
                        window.YAPBuilderExt.toast('Ustawienia zapisane!', 'success', '✓ Zapisano');
                    }
                });
                
                // Create Template button
                $('.yap-create-template').on('click', function() {
                    console.log('🎨 Create template from field:', field.label);
                    
                    if (typeof CustomTemplates === 'undefined') {
                        alert('Custom Templates system nie jest załadowany!');
                        return;
                    }
                    
                    // Prepare field data for template
                    const fieldForTemplate = {
                        ...field
                    };
                    
                    // If it's a group, use its sub_fields as template fields
                    let fieldsForTemplate = [];
                    
                    if (field.type === 'group' && field.sub_fields) {
                        fieldsForTemplate = field.sub_fields.map(f => ({
                            name: f.name,
                            label: f.label,
                            type: f.type,
                            icon: yapBuilder.fieldTypes[f.type] ? yapBuilder.fieldTypes[f.type].icon : '📝',
                            required: f.required || false,
                            placeholder: f.placeholder || '',
                            default_value: f.default_value || '',
                            ...f
                        }));
                    } else {
                        // Single field becomes template with one field
                        fieldsForTemplate = [{
                            name: field.name,
                            label: field.label,
                            type: field.type,
                            icon: yapBuilder.fieldTypes[field.type] ? yapBuilder.fieldTypes[field.type].icon : '📝',
                            required: field.required || false,
                            placeholder: field.placeholder || '',
                            default_value: field.default_value || '',
                            ...field
                        }];
                    }
                    
                    // Show creation modal
                    CustomTemplates.createFromSelection(fieldsForTemplate);
                    
                    // Close settings modal
                    self.closeFieldSettingsModal();
                });
                
                // ESC key to close
                $(document).on('keydown.yapModal', function(e) {
                    if (e.key === 'Escape') {
                        self.closeFieldSettingsModal();
                    }
                });
                
                // NOW bind field settings (input events) after modal is in DOM
                console.log('📋 Binding field settings events');
                self.bindFieldSettings(field);
            }, 10);
        },
        
        /**
         * Close field settings modal
         */
        closeFieldSettingsModal() {
            $('#yap-field-settings-modal').removeClass('yap-modal-show');
            setTimeout(() => {
                $('#yap-field-settings-modal').remove();
            }, 300);
            $(document).off('keydown.yapModal');
        },
        
        /**
         * Bind modal events
         */
        bindModalEvents(field) {
            // Events will be bound in bindFieldSettings
        },
        
        /**
         * Get general settings HTML
         */
        getGeneralSettingsHTML(field) {
            return `
                <div class="yap-settings-section">
                    <h3>📝 Podstawowe informacje</h3>
                    
                    <div class="yap-setting-row">
                        <div class="yap-setting-col">
                            <label>Nazwa pola (Field Name)</label>
                            <input type="text" class="yap-setting-name" value="${field.name}">
                            <p class="description">Unikalna nazwa używana w kodzie (bez spacji, tylko a-z, 0-9, _)</p>
                        </div>
                        
                        <div class="yap-setting-col">
                            <label>Etykieta (Field Label)</label>
                            <input type="text" class="yap-setting-label" value="${field.label}">
                            <p class="description">Nazwa wyświetlana użytkownikowi</p>
                        </div>
                    </div>
                    
                    <div class="yap-setting-row">
                        <div class="yap-setting-col">
                            <label>Typ pola</label>
                            <input type="text" value="${yapBuilder.fieldTypes[field.type].label}" disabled>
                        </div>
                        
                        <div class="yap-setting-col">
                            <label>
                                <input type="checkbox" class="yap-setting-required" ${field.required ? 'checked' : ''}>
                                <strong>Pole wymagane</strong>
                            </label>
                            <p class="description">Użytkownik musi wypełnić to pole</p>
                        </div>
                    </div>
                    
                    <div class="yap-setting-row">
                        <div class="yap-setting-col">
                            <label>Placeholder</label>
                            <input type="text" class="yap-setting-placeholder" value="${field.placeholder || ''}" placeholder="np. Wprowadź tekst...">
                            <p class="description">Tekst podpowiedzi w pustym polu</p>
                        </div>
                        
                        <div class="yap-setting-col">
                            <label>Domyślna wartość (Default Value)</label>
                            <input type="text" class="yap-setting-default" value="${field.default_value || ''}" placeholder="Opcjonalnie">
                            <p class="description">Wartość wstępna przy tworzeniu</p>
                        </div>
                    </div>
                    
                    <div class="yap-setting-row">
                        <div class="yap-setting-col-full">
                            <label>Opis/Instrukcja (Description)</label>
                            <textarea class="yap-setting-description" rows="2" placeholder="Opcjonalny tekst pomocniczy wyświetlany pod polem">${field.description || ''}</textarea>
                        </div>
                    </div>
                </div>
            `;
        },
        
        /**
         * Get advanced settings HTML
         */
        getAdvancedSettingsHTML(field) {
            return `
                <div class="yap-settings-section">
                    <h3>🔧 Ustawienia zaawansowane</h3>
                    
                    <div class="yap-setting-row">
                        <div class="yap-setting-col-full">
                            <label>CSS Class</label>
                            <input type="text" class="yap-setting-class" value="${field.css_class || ''}" placeholder="np. custom-field wide-field">
                            <p class="description">Dodatkowe klasy CSS dla stylizacji (oddziel spacją)</p>
                        </div>
                    </div>
                    
                    <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">
                    
                    ${window.YAPFieldSettings ? window.YAPFieldSettings.getTypeSpecificSettings(field) : '<p>Ładowanie ustawień typu pola...</p>'}
                </div>
            `;
        },
        
        /**
         * Get conditional settings HTML
         */
        getConditionalSettingsHTML(field) {
            return `
                <div class="yap-settings-section">
                    <h3>🔀 Logika warunkowa (Conditional Logic)</h3>
                    <p class="description" style="margin-bottom: 20px;">Kontroluj widoczność i zachowanie tego pola na podstawie wartości innych pól</p>
                    
                    <div class="yap-setting-row">
                        <div class="yap-setting-col-full">
                            <label class="yap-toggle-switch">
                                <input type="checkbox" class="yap-setting-conditional" ${field.conditional ? 'checked' : ''}>
                                <span class="yap-toggle-slider"></span>
                                <span class="yap-toggle-label">Włącz logikę warunkową</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="yap-conditional-rules" style="display: ${field.conditional ? 'block' : 'none'}; margin-top: 20px;">
                        ${window.YAPBuilderExt ? window.YAPBuilderExt.renderMultiConditions(field) : '<p>Ładowanie systemu warunków...</p>'}
                    </div>
                </div>
            `;
        },
        
        /**
         * Get field settings HTML (legacy - kept for compatibility)
         */
        getFieldSettingsHTML(field) {
            return `
                <div class="yap-field-settings">
                    <div class="yap-setting-group">
                        <label>Field Name</label>
                        <input type="text" class="yap-setting-name" value="${field.name}">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Field Label</label>
                        <input type="text" class="yap-setting-label" value="${field.label}">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Field Type</label>
                        <input type="text" value="${field.type}" disabled>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-required" ${field.required ? 'checked' : ''}>
                            Required
                        </label>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Placeholder</label>
                        <input type="text" class="yap-setting-placeholder" value="${field.placeholder || ''}">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Default Value</label>
                        <input type="text" class="yap-setting-default" value="${field.default_value || ''}">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>CSS Class</label>
                        <input type="text" class="yap-setting-class" value="${field.css_class || ''}">
                    </div>
                    
                    <hr>
                    
                    <!-- Type-Specific Settings -->
                    ${window.YAPFieldSettings ? window.YAPFieldSettings.getTypeSpecificSettings(field) : ''}
                    
                    <hr>
                    
                    <div class="yap-setting-group">
                        <h4>Conditional Logic (Multi-Condition)</h4>
                        <label>
                            <input type="checkbox" class="yap-setting-conditional" ${field.conditional ? 'checked' : ''}>
                            Włącz logikę warunkową
                        </label>
                        <div class="yap-conditional-rules" style="display: ${field.conditional ? 'block' : 'none'};">
                            ${window.YAPBuilderExt ? window.YAPBuilderExt.renderMultiConditions(field) : 'Loading...'}
                        </div>
                    </div>
                </div>
            `;
        },
        
        /**
         * Bind field settings events
         */
        bindFieldSettings(field) {
            const self = this;
            let editTimeout = null;
            
            console.log('🔗 bindFieldSettings called for field:', field.label);
            console.log('   Looking for .yap-setting-name elements...');
            console.log('   Found:', $('.yap-setting-name').length, 'elements');
            
            // Helper to record edit with debounce
            const recordEdit = () => {
                if (editTimeout) clearTimeout(editTimeout);
                editTimeout = setTimeout(() => {
                    if (typeof FieldHistory !== 'undefined' && FieldHistory.recordEdit) {
                        const oldField = { ...field }; // This is simplified - ideally track actual changes
                        FieldHistory.recordEdit(field.id, oldField, field);
                    }
                }, 500); // Debounce 500ms to avoid spamming history
            };
            
            $('.yap-setting-name').on('input', function() {
                const newName = $(this).val();
                console.log('📝 Field name changed:', field.name, '→', newName);
                field.name = newName;
                self.updateFieldUI(field);
                recordEdit();
            });
            
            $('.yap-setting-label').on('input', function() {
                const newLabel = $(this).val();
                console.log('📝 Field label changed:', field.label, '→', newLabel);
                field.label = newLabel;
                self.updateFieldUI(field);
                recordEdit();
            });
            
            $('.yap-setting-required').on('change', function() {
                field.required = $(this).is(':checked');
                console.log('📝 Field required changed:', field.required);
                recordEdit();
            });
            
            $('.yap-setting-placeholder').on('input', function() {
                field.placeholder = $(this).val();
                console.log('📝 Field placeholder changed:', field.placeholder);
                recordEdit();
            });
            
            $('.yap-setting-default').on('input', function() {
                field.default_value = $(this).val();
                console.log('📝 Field default_value changed:', field.default_value);
                recordEdit();
            });
            
            $('.yap-setting-class').on('input', function() {
                field.css_class = $(this).val();
                console.log('📝 Field css_class changed:', field.css_class);
                recordEdit();
            });
            
            $('.yap-setting-conditional').on('change', function() {
                field.conditional = $(this).is(':checked');
                console.log('📝 Field conditional changed:', field.conditional);
                $('.yap-conditional-rules').toggle(field.conditional);
                
                // Initialize multi-condition UI if enabling
                if (field.conditional && window.YAPBuilderExt) {
                    window.YAPBuilderExt.refreshConditionalUI(field);
                }
                
                self.updateFieldUI(field);
                recordEdit();
            });
            
            // Conditional action
            $('.yap-conditional-action').on('change', function() {
                field.conditional_action = $(this).val();
                console.log('📝 Field conditional_action changed:', field.conditional_action);
                const showMessage = ['message', 'error'].includes($(this).val());
                $('.yap-conditional-message-box').toggle(showMessage);
                $('.yap-conditional-message-box label').text(
                    $(this).val() === 'error' ? 'Tekst błędu' : 'Tekst komunikatu'
                );
            });
            
            // Conditional message
            $('.yap-conditional-message').on('input', function() {
                field.conditional_message = $(this).val();
                console.log('📝 Field conditional_message changed:', field.conditional_message);
            });
            
            // Field description
            $('.yap-setting-description').on('input', function() {
                field.description = $(this).val();
                console.log('📝 Field description changed:', field.description);
            });
        },
        
        /**
         * Get conditional logic example
         */
        getConditionalExample(field) {
            if (!field.conditional || !field.conditional_field) {
                return 'Configure the condition above';
            }
            
            const action = field.conditional_action || 'show';
            const targetField = this.schema.fields.find(f => f.name === field.conditional_field);
            const fieldLabel = targetField ? targetField.label : field.conditional_field;
            const operator = field.conditional_operator || '==';
            const value = field.conditional_value || '[value]';
            
            let operatorText = {
                '==': 'equals',
                '!=': 'does not equal',
                '>': 'is greater than',
                '<': 'is less than',
                '>=': 'is greater or equal to',
                '<=': 'is less or equal to',
                'contains': 'contains',
                'not_contains': 'does not contain',
                'starts_with': 'starts with',
                'ends_with': 'ends with',
                'is_empty': 'is empty',
                'is_not_empty': 'is not empty',
                'matches_pattern': 'matches pattern'
            }[operator] || operator;
            
            let actionText = {
                'show': 'Show',
                'hide': 'Hide',
                'message': 'Show message for',
                'error': 'Show error for',
                'disable': 'Disable',
                'enable': 'Enable'
            }[action] || action;
            
            const valueText = ['is_empty', 'is_not_empty'].includes(operator) ? '' : ` "${value}"`;
            
            return `${actionText} this field when "${fieldLabel}" ${operatorText}${valueText}`;
        },
        
        /**
         * Update conditional example in real-time
         */
        updateConditionalExample(field) {
            const example = this.getConditionalExample(field);
            $('.yap-conditional-rules .description').html('<strong>Example:</strong> ' + example);
        },
        
        /**
         * Update field UI
         */
        updateFieldUI(field) {
            const $field = $(`.yap-field-item[data-field-id="${field.id}"]`);
            
            $field.find('.yap-field-label').text(field.label);
            $field.find('.yap-field-name').text(field.name);
            
            // Conditional logic indicator with action type
            $field.find('.yap-field-conditional').remove();
            
            if (field.conditional) {
                const action = field.conditional_action || 'show';
                const actionIcons = {
                    'show': 'visibility',
                    'hide': 'hidden',
                    'message': 'info',
                    'error': 'warning',
                    'disable': 'lock',
                    'enable': 'unlock'
                };
                const actionColors = {
                    'show': '#46b450',
                    'hide': '#dc3232',
                    'message': '#00a0d2',
                    'error': '#f56e28',
                    'disable': '#999',
                    'enable': '#46b450'
                };
                
                $field.find('.yap-field-item-header').append(`
                    <div class="yap-field-conditional" style="display: flex; align-items: center; gap: 4px; font-size: 11px; color: ${actionColors[action]}; margin-right: 10px;">
                        <span class="dashicons dashicons-${actionIcons[action]}" style="font-size: 16px;"></span>
                        <span>${action.toUpperCase()}</span>
                    </div>
                `);
            }
        },
        
        /**
         * Duplicate field
         */
        duplicateField(fieldId) {
            const field = this.schema.fields.find(f => f.id === fieldId);
            
            if (!field) return;
            
            const newField = {
                ...field,
                id: 'field_' + Date.now(),
                name: field.name + '_copy'
            };
            
            this.schema.fields.push(newField);
            this.renderField(newField);
            
            // Show toast
            if (window.YAPBuilderExt) {
                window.YAPBuilderExt.toast(`Zduplikowano pole "${field.label}"`, 'success');
            }
        },
        
        /**
         * Delete field
         */
        deleteField(fieldId) {
            const field = this.schema.fields.find(f => f.id === fieldId);
            const fieldLabel = field ? field.label : 'pole';
            
            this.schema.fields = this.schema.fields.filter(f => f.id !== fieldId);
            $(`.yap-field-item[data-field-id="${fieldId}"]`).remove();
            
            // Record deletion in history
            if (typeof FieldHistory !== 'undefined' && FieldHistory.recordDelete && field) {
                FieldHistory.recordDelete(field);
            }
            
            if (this.schema.fields.length === 0) {
                $('.yap-drop-zone-placeholder').show();
            }
            
            if (this.selectedField === fieldId) {
                this.closeInspector();
            }
            
            // Show toast
            if (window.YAPBuilderExt) {
                window.YAPBuilderExt.toast(`Pole "${fieldLabel}" zostało usunięte`, 'info');
            }
        },
        
        /**
         * Update field order after sorting
         */
        updateFieldOrder() {
            console.log('🔄 updateFieldOrder called');
            const newOrder = [];
            const self = this; // FIX: Store this context
            
            console.log('📍 DOM has', $('#yap-drop-zone .yap-field-item').length, 'field items');
            console.log('📍 Schema has', this.schema.fields.length, 'fields');
            
            $('#yap-drop-zone .yap-field-item').each(function() {
                // Use attr() instead of data() to avoid jQuery cache issues
                const fieldId = $(this).attr('data-field-id');
                const label = $(this).find('.yap-field-label').text();
                console.log(`  Checking DOM element: "${label}" (${fieldId})`);
                
                const field = self.schema.fields.find(f => f.id === fieldId);
                if (field) {
                    newOrder.push(field);
                    console.log(`    ✅ Found in schema: ${field.label}`);
                } else {
                    console.warn(`    ⚠️ NOT found in schema - fieldId: ${fieldId}`);
                    console.warn(`       Available IDs: ${self.schema.fields.map(f => f.id).join(', ')}`);
                }
            });
            
            console.log('📍 Before update:');
            console.log(`   Schema fields (${this.schema.fields.length}):`, this.schema.fields.map(f => f.label).join(', '));
            console.log('📍 After reorder:');
            console.log(`   New order (${newOrder.length}):`, newOrder.map(f => f.label).join(', '));
            
            if (newOrder.length !== this.schema.fields.length) {
                console.error(`❌ MISMATCH: Lost ${this.schema.fields.length - newOrder.length} fields!`);
            }
            
            this.schema.fields = newOrder;
        },
        
        /**
         * Use template
         */
        useTemplate(templateKey) {
            const template = yapBuilder.templates[templateKey];
            
            if (!template || !template.fields) return;
            
            template.fields.forEach(fieldData => {
                const fieldId = 'field_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                
                const field = {
                    id: fieldId,
                    name: fieldData.name,
                    type: fieldData.type,
                    label: fieldData.label || fieldData.name,
                    icon: yapBuilder.fieldTypes[fieldData.type].icon,
                    ...fieldData
                };
                
                this.schema.fields.push(field);
                this.renderField(field);
            });
            
            $('.yap-drop-zone-placeholder').hide();
        },
        
        /**
         * Save schema
         */
        saveSchema() {
            const groupName = $('#yap-group-name').val().trim();
            
            if (!groupName) {
                if (window.YAPBuilderExt) {
                    window.YAPBuilderExt.toast('Wprowadź nazwę grupy!', 'warning');
                } else {
                    alert('⚠️ Please enter a group name!');
                }
                return;
            }
            
            if (this.schema.fields.length === 0) {
                if (window.YAPBuilderExt) {
                    window.YAPBuilderExt.toast('Dodaj przynajmniej jedno pole!', 'warning');
                } else {
                    alert('⚠️ Please add at least one field!');
                }
                return;
            }
            
            this.schema.name = groupName;
            
            // Collect location rules
            const locationRules = this.collectLocationRules();
            
            console.log('Saving schema:', this.schema);
            console.log('Location rules:', locationRules);
            
            // Show loading toast
            if (window.YAPBuilderExt) {
                window.YAPBuilderExt.toast('Zapisywanie schema...', 'info');
            }
            
            $.ajax({
                url: yapBuilder.ajaxurl,
                type: 'POST',
                data: {
                    action: 'yap_builder_save_schema',
                    nonce: yapBuilder.nonce,
                    group_name: groupName,
                    schema: JSON.stringify(this.schema),
                    location_rules: JSON.stringify(locationRules)
                },
                success: (response) => {
                    console.log('Save response:', response);
                    if (response.success) {
                        if (window.YAPBuilderExt) {
                            window.YAPBuilderExt.toast(
                                `Grupa "${groupName}" z ${this.schema.fields.length} polami została zapisana`,
                                'success',
                                'Schema zapisana pomyślnie!'
                            );
                        } else {
                            alert('✅ Schema saved successfully!');
                        }
                        
                        // NO RELOAD - update group select via AJAX without page refresh
                        // This preserves the current state and allows continued editing
                        console.log('✅ Schema saved without page reload');
                    } else {
                        if (window.YAPBuilderExt) {
                            window.YAPBuilderExt.toast(
                                response.data ? response.data.message : 'Nieznany błąd',
                                'error',
                                'Błąd zapisu'
                            );
                        } else {
                            alert('❌ Error: ' + (response.data ? response.data.message : 'Unknown error'));
                        }
                        // NO RELOAD ON ERROR
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Save error:', xhr, status, error);
                    if (window.YAPBuilderExt) {
                        window.YAPBuilderExt.toast('Błąd zapisu schema: ' + error, 'error');
                    } else {
                        alert('❌ Error saving schema: ' + error);
                    }
                }
            });
        },
        
        /**
         * Collect location rules from form
         */
        collectLocationRules() {
            const rules = [];
            
            $('.yap-location-rule-group').each(function(groupIndex) {
                const groupRules = [];
                
                $(this).find('.yap-location-rule').each(function() {
                    const type = $(this).find('.yap-location-type').val();
                    const operator = $(this).find('.yap-location-operator').val();
                    const value = $(this).find('.yap-location-value').val();
                    
                    if (type && value) {
                        groupRules.push({
                            type: type,
                            operator: operator,
                            value: value
                        });
                    }
                });
                
                if (groupRules.length > 0) {
                    rules.push(groupRules);
                }
            });
            
            return rules;
        },
        
        /**
         * Populate location rules from loaded data
         */
        populateLocationRules(locationRules) {
            // Clear existing rules
            $('.yap-location-rules-groups').empty();
            
            if (locationRules.length === 0) {
                // Add empty rule group
                this.addLocationRuleGroup(0);
                return;
            }
            
            // Add each rule group
            locationRules.forEach((ruleGroup, groupIndex) => {
                this.addLocationRuleGroup(groupIndex);
                
                ruleGroup.forEach((rule, ruleIndex) => {
                    if (ruleIndex > 0) {
                        // Add additional rule to group
                        const $group = $(`.yap-location-rule-group[data-group-index="${groupIndex}"]`);
                        const $lastRule = $group.find('.yap-location-rule').last();
                        $lastRule.find('.yap-add-location-rule').click();
                    }
                    
                    // Set rule values
                    const $rule = $(`.yap-location-rule-group[data-group-index="${groupIndex}"] .yap-location-rule[data-rule-index="${ruleIndex}"]`);
                    $rule.find('.yap-location-type').val(rule.type).trigger('change');
                    $rule.find('.yap-location-operator').val(rule.operator);
                    
                    // Wait for type change to populate value options
                    setTimeout(() => {
                        $rule.find('.yap-location-value').val(rule.value);
                    }, 100);
                });
            });
        },
        
        /**
         * Add location rule group
         */
        addLocationRuleGroup(groupIndex) {
            const html = `
                <div class="yap-location-rule-group" data-group-index="${groupIndex}">
                    <div class="yap-location-rules">
                        <div class="yap-location-rule" data-rule-index="0">
                            <select name="location_rules[${groupIndex}][0][type]" class="yap-location-type">
                                <option value="">Wybierz lokalizację...</option>
                                <optgroup label="Post">
                                    <option value="post_type">Typ posta</option>
                                    <option value="post">Konkretny post</option>
                                    <option value="page">Konkretna strona</option>
                                    <option value="page_template">Szablon strony</option>
                                </optgroup>
                                <optgroup label="Taksonomie">
                                    <option value="taxonomy">Taksonomia</option>
                                    <option value="taxonomy_term">Term taksonomii</option>
                                </optgroup>
                                <optgroup label="Użytkownicy">
                                    <option value="user_role">Rola użytkownika</option>
                                    <option value="user">Konkretny użytkownik</option>
                                </optgroup>
                                <optgroup label="Inne">
                                    <option value="attachment">Załączniki</option>
                                    <option value="comment">Komentarze</option>
                                    <option value="widget">Widgety</option>
                                    <option value="nav_menu">Menu</option>
                                    <option value="options_page">Strona opcji</option>
                                </optgroup>
                            </select>
                            <select name="location_rules[${groupIndex}][0][operator]" class="yap-location-operator">
                                <option value="==">jest równe</option>
                                <option value="!=">nie jest równe</option>
                            </select>
                            <select name="location_rules[${groupIndex}][0][value]" class="yap-location-value">
                                <option value="">Najpierw wybierz typ lokalizacji</option>
                            </select>
                            <button type="button" class="button yap-add-location-rule" title="Dodaj regułę (AND)">
                                <span class="dashicons dashicons-plus"></span> AND
                            </button>
                            <button type="button" class="button yap-remove-location-rule" title="Usuń regułę">
                                <span class="dashicons dashicons-minus"></span>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="button yap-remove-rule-group" title="Usuń grupę">
                        <span class="dashicons dashicons-trash"></span> Usuń grupę
                    </button>
                </div>
            `;
            
            $('.yap-location-rules-groups').append(html);
        },
        
        /**
         * Load schema
         */
        /**
         * Refresh canvas - redraw all fields without reloading from server
         * Used when schema is modified programmatically (e.g., FieldPresets.addToSchema)
         */
        refreshCanvas() {
            console.log('Refreshing canvas...');
            this.clearCanvas();
            
            if (this.schema && this.schema.fields) {
                this.schema.fields.forEach(field => {
                    this.renderField(field);
                });
                
                if (this.schema.fields.length > 0) {
                    $('.yap-drop-zone-placeholder').hide();
                }
            }
            
            console.log('✅ Canvas refreshed');
            return true;
        },
        
        loadSchema(groupName) {
            $.ajax({
                url: yapBuilder.ajaxurl,
                type: 'POST',
                data: {
                    action: 'yap_builder_load_schema',
                    nonce: yapBuilder.nonce,
                    group_name: groupName
                },
                success: (response) => {
                    if (response.success) {
                        this.clearCanvas();
                        this.schema = response.data.schema;
                        
                        $('#yap-group-name').val(this.schema.name);
                        
                        this.schema.fields.forEach(field => {
                            this.renderField(field);
                        });
                        
                        if (this.schema.fields.length > 0) {
                            $('.yap-drop-zone-placeholder').hide();
                        }
                        
                        // Load location rules if available
                        if (response.data.location_rules) {
                            this.populateLocationRules(response.data.location_rules);
                        }
                    }
                },
                error: () => {
                    alert('Error loading schema');
                }
            });
        },
        
        /**
         * Export schema
         */
        exportSchema() {
            const dataStr = JSON.stringify(this.schema, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
            
            const exportName = (this.schema.name || 'schema') + '.json';
            
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportName);
            linkElement.click();
        },
        
        /**
         * Show group settings modal
         */
        showGroupSettingsModal() {
            const groupName = $('#yap-builder-group-select').val() || this.schema.name;
            
            if (!groupName) {
                if (window.YAPBuilderExt) {
                    window.YAPBuilderExt.toast('Najpierw wybierz lub utwórz grupę', 'warning');
                } else {
                    alert('Najpierw wybierz lub utwórz grupę');
                }
                return;
            }
            
            const modalHTML = `
                <div id="yap-group-settings-modal" class="yap-settings-modal yap-modal-show">
                    <div class="yap-settings-modal-overlay"></div>
                    <div class="yap-settings-modal-content">
                        <div class="yap-settings-modal-header">
                            <div class="yap-settings-modal-title">
                                <span class="yap-field-type-icon" style="font-size: 42px;">⚙️</span>
                                <div>
                                    <h2>Ustawienia Grupy: ${groupName}</h2>
                                    <p class="yap-field-meta-info">Globalne ustawienia dla całej grupy pól</p>
                                </div>
                            </div>
                            <button class="yap-modal-close" type="button">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                        
                        <div class="yap-settings-content">
                            <div class="yap-setting-section">
                                <h3>📋 Podstawowe</h3>
                                
                                <div class="yap-setting-row">
                                    <div class="yap-setting-col">
                                        <label>Tytuł Grupy</label>
                                        <input type="text" class="yap-group-title" value="${groupName}" placeholder="Nazwa wyświetlana">
                                        <span class="yap-help-text">Nazwa wyświetlana w interfejsie WordPress</span>
                                    </div>
                                    <div class="yap-setting-col">
                                        <label>Pozycja</label>
                                        <select class="yap-group-position">
                                            <option value="normal">Normal (pod edytorem)</option>
                                            <option value="side">Sidebar (boczny panel)</option>
                                            <option value="advanced">Advanced (na dole)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="yap-setting-row">
                                    <div class="yap-setting-col">
                                        <label>Priorytet</label>
                                        <select class="yap-group-priority">
                                            <option value="high">Wysoki</option>
                                            <option value="default" selected>Domyślny</option>
                                            <option value="low">Niski</option>
                                        </select>
                                        <span class="yap-help-text">Kolejność wyświetlania metabox</span>
                                    </div>
                                    <div class="yap-setting-col">
                                        <label>Styl</label>
                                        <select class="yap-group-style">
                                            <option value="default">Domyślny (metabox)</option>
                                            <option value="seamless">Seamless (bez ramki)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="yap-setting-section">
                                <h3>🎨 Wygląd</h3>
                                
                                <div class="yap-setting-row">
                                    <div class="yap-setting-col-full">
                                        <label class="yap-toggle-switch">
                                            <input type="checkbox" class="yap-group-hide-title">
                                            <span class="yap-toggle-slider"></span>
                                            <span class="yap-toggle-label">Ukryj tytuł grupy</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="yap-setting-row">
                                    <div class="yap-setting-col-full">
                                        <label class="yap-toggle-switch">
                                            <input type="checkbox" class="yap-group-label-placement">
                                            <span class="yap-toggle-slider"></span>
                                            <span class="yap-toggle-label">Etykiety w linii (label obok pola)</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="yap-setting-row">
                                    <div class="yap-setting-col">
                                        <label>Klasa CSS</label>
                                        <input type="text" class="yap-group-css-class" placeholder="np. custom-group-class">
                                        <span class="yap-help-text">Dodatkowe klasy CSS dla grupy</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="yap-setting-section">
                                <h3>📝 Opis</h3>
                                
                                <div class="yap-setting-row">
                                    <div class="yap-setting-col-full">
                                        <label>Instrukcja</label>
                                        <textarea class="yap-group-instruction" rows="3" placeholder="Opcjonalny opis lub instrukcja dla użytkowników..."></textarea>
                                        <span class="yap-help-text">Wyświetlany nad polami grupy</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="yap-settings-modal-footer">
                            <button type="button" class="button yap-settings-cancel">
                                <span class="dashicons dashicons-no"></span> Anuluj
                            </button>
                            <button type="button" class="button button-primary yap-group-settings-save">
                                <span class="dashicons dashicons-saved"></span> Zapisz Ustawienia
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHTML);
            
            // Load existing group settings if available
            if (this.schema.group_settings) {
                const settings = this.schema.group_settings;
                $('.yap-group-title').val(settings.title || '');
                $('.yap-group-position').val(settings.position || 'normal');
                $('.yap-group-priority').val(settings.priority || 'default');
                $('.yap-group-style').val(settings.style || 'default');
                $('.yap-group-hide-title').prop('checked', settings.hide_title || false);
                $('.yap-group-label-placement').prop('checked', settings.label_placement === 'inline');
                $('.yap-group-css-class').val(settings.css_class || '');
                $('.yap-group-instruction').val(settings.instruction || '');
            }
            
            // Save button
            $('.yap-group-settings-save').on('click', () => {
                this.schema.group_settings = {
                    title: $('.yap-group-title').val(),
                    position: $('.yap-group-position').val(),
                    priority: $('.yap-group-priority').val(),
                    style: $('.yap-group-style').val(),
                    hide_title: $('.yap-group-hide-title').is(':checked'),
                    label_placement: $('.yap-group-label-placement').is(':checked') ? 'inline' : 'block',
                    css_class: $('.yap-group-css-class').val(),
                    instruction: $('.yap-group-instruction').val()
                };
                
                this.closeGroupSettingsModal();
                
                if (window.YAPBuilderExt) {
                    window.YAPBuilderExt.toast('Ustawienia grupy zapisane! Pamiętaj kliknąć "Save Schema"', 'success');
                }
            });
            
            // Cancel/Close buttons
            $('.yap-settings-cancel, .yap-modal-close, .yap-settings-modal-overlay').on('click', () => {
                this.closeGroupSettingsModal();
            });
            
            // ESC key
            $(document).on('keydown.yapGroupModal', (e) => {
                if (e.key === 'Escape') {
                    this.closeGroupSettingsModal();
                }
            });
        },
        
        /**
         * Close group settings modal
         */
        closeGroupSettingsModal() {
            $('#yap-group-settings-modal').removeClass('yap-modal-show');
            setTimeout(() => {
                $('#yap-group-settings-modal').remove();
            }, 300);
            $(document).off('keydown.yapGroupModal');
        },
        
        /**
         * Show preview
         */
        showPreview() {
            const self = this;
            if (this.schema.fields.length === 0) {
                alert('⚠️ No fields to preview!\n\nAdd some fields first by dragging them from the left sidebar.');
                return;
            }
            
            // Build preview HTML using renderPreviewField
            let previewHTML = '<div class="yap-preview-wrapper" style="max-width: 800px; margin: 0 auto; padding: 20px;">';
            previewHTML += '<h2>Preview: ' + ($('#yap-group-name').val() || 'Untitled Group') + '</h2>';
            previewHTML += '<div class="yap-preview-form">';
            
            this.schema.fields.forEach(field => {
                const fieldId = 'preview_' + field.id;
                previewHTML += '<div class="yap-preview-field" style="margin-bottom: 20px;">';
                previewHTML += '<label style="display: block; font-weight: 600; margin-bottom: 8px;">';
                previewHTML += field.label;
                if (field.required) previewHTML += ' <span style="color: red;">*</span>';
                previewHTML += '</label>';
                
                // Use renderPreviewField to support all field types including group/repeater/fc
                previewHTML += self.renderPreviewField(field, fieldId);
                
                if (field.description) {
                    previewHTML += `<p style="font-size: 12px; color: #666; margin-top: 4px;">${field.description}</p>`;
                }
                
                if (field.conditional) {
                    previewHTML += '<p style="font-size: 11px; color: #0073aa; margin-top: 4px;"><span class="dashicons dashicons-randomize"></span> Conditional logic enabled</p>';
                }
                
                previewHTML += '</div>';
            });
            
            previewHTML += '</div></div>';
            
            // Show in modal or new window
            const previewWindow = window.open('', 'YAP Preview', 'width=900,height=700');
            previewWindow.document.write(`
                <html>
                <head>
                    <title>YAP Field Preview</title>
                    <link rel="stylesheet" href="${yapBuilder.ajaxurl.replace('admin-ajax.php', '')}wp-admin/load-styles.php?c=1&dir=ltr&load=dashicons,buttons,forms,common,wp-admin">
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f0f0f1; }
                        .yap-preview-wrapper { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                    </style>
                </head>
                <body>
                    ${previewHTML}
                    <div style="text-align: center; margin-top: 30px;">
                        <button onclick="window.close()" class="button button-primary" style="padding: 10px 20px;">Close Preview</button>
                    </div>
                </body>
                </html>
            `);
        },
        
        /**
         * Clear canvas
         */
        clearCanvas() {
            this.schema = { name: '', fields: [] };
            $('#yap-drop-zone .yap-field-item').remove();
            $('.yap-drop-zone-placeholder').show();
            $('#yap-group-name').val('');
            this.closeInspector();
        },
        
        /**
         * Close inspector
         */
        closeInspector() {
            this.selectedField = null;
            $('#yap-inspector-content').html('<p class="yap-inspector-placeholder">Select a field to edit its settings</p>');
        },
        
        /**
         * Enter preview mode - Show actual form fields
         */
        enterPreviewMode() {
            const self = this;
            console.log('Entering preview mode');
            
            // Hide builder UI elements
            $('.yap-builder-sidebar, .yap-builder-inspector').hide();
            $('#yap-group-name').prop('readonly', true);
            
            // Show preview message
            if (!$('.yap-preview-notice').length) {
                $('.yap-canvas-header').after(`
                    <div class="yap-preview-notice" style="background: #e3f2fd; padding: 10px 15px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #0073aa;">
                        👁️ <strong>Preview Mode</strong> - This is how your form will look. Change field values to test conditional logic. Switch to Edit Mode to make changes.
                    </div>
                `);
            }
            
            // Replace field items with actual form fields
            const $dropZone = $('#yap-drop-zone');
            $dropZone.sortable('disable');
            
            // Store original HTML
            if (!this.originalFieldsHTML) {
                this.originalFieldsHTML = $dropZone.html();
            }
            
            // Build preview form
            let formHTML = '<div class="yap-preview-form" style="max-width: 800px; margin: 0 auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
            
            this.schema.fields.forEach((field, index) => {
                const fieldId = 'preview_' + field.id;
                
                formHTML += `
                    <div class="yap-preview-field-wrapper" 
                         data-field-id="${field.id}"
                         data-conditional="${field.conditional || false}"
                         data-conditional-action="${field.conditional_action || 'show'}"
                         data-conditional-field="${field.conditional_field || ''}"
                         data-conditional-operator="${field.conditional_operator || '=='}"
                         data-conditional-value="${field.conditional_value || ''}"
                         data-conditional-message="${field.conditional_message || ''}"
                         style="margin-bottom: 25px; ${field.conditional && field.conditional_action === 'hide' ? 'display:none;' : ''}">
                        
                        <label for="${fieldId}" style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                            ${field.label}
                            ${field.required ? '<span style="color: red;">*</span>' : ''}
                            ${field.conditional ? `<span class="yap-conditional-badge" style="font-size: 10px; background: #0073aa; color: white; padding: 2px 6px; border-radius: 3px; margin-left: 8px;">CONDITIONAL</span>` : ''}
                        </label>
                        
                        ${self.renderPreviewField(field, fieldId)}
                        
                        ${field.description ? `<p style="font-size: 12px; color: #666; margin-top: 6px;">${field.description}</p>` : ''}
                        
                        <div class="yap-conditional-message-box" style="display: none; margin-top: 10px; padding: 12px; border-radius: 4px;"></div>
                    </div>
                `;
            });
            
            formHTML += '</div>';
            
            $dropZone.html(formHTML);
            
            // Initialize conditional logic watcher
            this.initConditionalLogic();
        },
        
        /**
         * Render preview field based on type
         */
        renderPreviewField(field, fieldId) {
            const self = this;
            const baseStyle = 'width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; font-family: inherit;';
            const placeholder = field.placeholder ? `placeholder="${field.placeholder}"` : '';
            const defaultValue = field.default_value || '';
            const disabled = field.conditional && field.conditional_action === 'disable' ? 'disabled' : '';
            
            switch(field.type) {
                case 'textarea':
                    return `<textarea id="${fieldId}" name="${field.name}" ${placeholder} ${disabled} style="${baseStyle} min-height: 120px; resize: vertical;">${defaultValue}</textarea>`;
                
                case 'select':
                    return `<select id="${fieldId}" name="${field.name}" ${disabled} style="${baseStyle}">
                        <option value="">-- Select --</option>
                        <option value="option1">Option 1</option>
                        <option value="option2">Option 2</option>
                        <option value="option3">Option 3</option>
                    </select>`;
                
                case 'checkbox':
                    return `<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="${fieldId}" name="${field.name}" ${disabled} style="width: auto;">
                        <span>${field.label}</span>
                    </label>`;
                
                case 'radio':
                    return `<div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="${field.name}" value="option1" ${disabled} style="width: auto;">
                            <span>Option 1</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="${field.name}" value="option2" ${disabled} style="width: auto;">
                            <span>Option 2</span>
                        </label>
                    </div>`;
                
                case 'wysiwyg':
                    // Render WYSIWYG editor with rich text capabilities
                    const wysiwygId = fieldId + '_editor';
                    return `
                        <div class="yap-wysiwyg-editor-wrapper" style="border: 1px solid #ddd; border-radius: 4px; overflow: hidden; background: white;">
                            <!-- Toolbar -->
                            <div class="yap-wysiwyg-toolbar" style="background: #f5f5f5; border-bottom: 1px solid #ddd; padding: 8px; display: flex; gap: 4px; flex-wrap: wrap;">
                                <button type="button" class="yap-wysiwyg-btn" title="Bold" style="padding: 6px 10px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 3px; font-weight: bold;"><strong>B</strong></button>
                                <button type="button" class="yap-wysiwyg-btn" title="Italic" style="padding: 6px 10px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 3px; font-style: italic;"><em>I</em></button>
                                <button type="button" class="yap-wysiwyg-btn" title="Underline" style="padding: 6px 10px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 3px; text-decoration: underline;"><u>U</u></button>
                                <span style="width: 1px; background: #ddd; margin: 0 4px;"></span>
                                <button type="button" class="yap-wysiwyg-btn" title="Bullet List" style="padding: 6px 10px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 3px;">• List</button>
                                <button type="button" class="yap-wysiwyg-btn" title="Heading" style="padding: 6px 10px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 3px;"><strong>H2</strong></button>
                                <button type="button" class="yap-wysiwyg-btn" title="Link" style="padding: 6px 10px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 3px;">🔗 Link</button>
                                <button type="button" class="yap-wysiwyg-btn" title="Quote" style="padding: 6px 10px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 3px;">❝ Quote</button>
                                <button type="button" class="yap-wysiwyg-btn" title="Code" style="padding: 6px 10px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 3px;">Code</button>
                            </div>
                            <!-- Editor Content -->
                            <div id="${wysiwygId}" class="yap-wysiwyg-content" contenteditable="true" style="min-height: 200px; padding: 15px; outline: none; font-size: 14px; line-height: 1.6;">${defaultValue}</div>
                            <!-- Hidden input for form submission -->
                            <textarea id="${fieldId}" name="${field.name}" style="display: none;">${defaultValue}</textarea>
                        </div>
                        <p style="font-size: 11px; color: #999; margin-top: 4px;">📝 Rich text editor - Format tekstu, dodaj listy, linki i wiele więcej</p>
                    `;
                
                case 'gallery':
                    // Render gallery uploader with media selection
                    const galleryId = fieldId + '_gallery';
                    return `
                        <div class="yap-gallery-wrapper" style="border: 2px dashed #0073aa; border-radius: 4px; padding: 20px; text-align: center; background: #f9fafb;">
                            <div style="margin-bottom: 15px;">
                                <div style="font-size: 32px; margin-bottom: 10px;">🖼️</div>
                                <p style="margin: 0 0 15px; color: #333; font-weight: 500;">Galeria Obrazów</p>
                                <p style="margin: 0 0 15px; color: #666; font-size: 13px;">Kliknij aby wybrać wiele obrazów z galerii mediów</p>
                                <button type="button" class="button button-primary yap-gallery-upload" data-field-id="${fieldId}" style="margin-bottom: 10px;">
                                    <span class="dashicons dashicons-upload"></span>
                                    Wybierz Obrazy
                                </button>
                                <button type="button" class="button yap-gallery-clear" data-field-id="${fieldId}" style="display: none;">
                                    <span class="dashicons dashicons-trash"></span>
                                    Wyczyść
                                </button>
                            </div>
                            <!-- Selected images preview -->
                            <div id="${galleryId}" class="yap-gallery-selected" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                                <p style="margin-bottom: 12px; color: #333; font-weight: 500;">Wybrane obrazy:</p>
                                <div class="yap-gallery-thumbs" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px;"></div>
                            </div>
                            <!-- Hidden input to store selected image IDs -->
                            <input type="hidden" id="${fieldId}" name="${field.name}" value="">
                        </div>
                        <p style="font-size: 11px; color: #999; margin-top: 4px;">💾 Wybrane obrazy będą zapisane jako ID mediów</p>
                    `;

                
                case 'group':
                    // Render group as a fieldset with nested fields
                    const groupSubFields = field.sub_fields && field.sub_fields.length > 0 ? 
                        field.sub_fields.map((subField, idx) => {
                            const subFieldId = fieldId + '_sub_' + idx;
                            return `
                                <div style="margin-bottom: 15px; padding-left: 15px; border-left: 3px solid #0073aa;">
                                    <label style="display: block; font-weight: 500; margin-bottom: 6px; font-size: 13px;">
                                        ${subField.label}
                                        ${subField.required ? '<span style="color: red;">*</span>' : ''}
                                    </label>
                                    ${self.renderPreviewField(subField, subFieldId)}
                                </div>
                            `;
                        }).join('') : 
                        '<p style="color: #999; font-style: italic; padding: 10px;">Brak pól w tej grupie</p>';
                    
                    return `<fieldset style="border: 1px solid #ddd; border-radius: 4px; padding: 15px; background: #f9f9f9;">
                        ${groupSubFields}
                    </fieldset>`;
                
                case 'repeater':
                    // Render repeater as a container with "add row" option
                    const repeaterSubFields = field.sub_fields && field.sub_fields.length > 0 ?
                        `<div style="background: #f0f0f0; padding: 15px; border-radius: 4px; margin-top: 10px;">
                            <p style="font-weight: 600; margin-bottom: 15px; color: #333;">📋 Row 1 (Example)</p>
                            ${field.sub_fields.map((subField, idx) => {
                                const subFieldId = fieldId + '_row_sub_' + idx;
                                return `
                                    <div style="margin-bottom: 12px;">
                                        <label style="display: block; font-weight: 500; margin-bottom: 6px; font-size: 13px;">
                                            ${subField.label}
                                            ${subField.required ? '<span style="color: red;">*</span>' : ''}
                                        </label>
                                        ${self.renderPreviewField(subField, subFieldId)}
                                    </div>
                                `;
                            }).join('')}
                        </div>` :
                        '<p style="color: #999; font-style: italic; padding: 10px;">Brak pól w repeaterzea</p>';
                    
                    return `<div style="border: 2px dashed #0073aa; border-radius: 4px; padding: 15px; background: #f9f9f9;">
                        ${repeaterSubFields}
                        <button type="button" style="margin-top: 15px; padding: 8px 15px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
                            + Dodaj rząd
                        </button>
                    </div>`;
                
                case 'flexible_content':
                    // Render flexible content as container with layout options
                    const fcLayouts = field.layouts && field.layouts.length > 0 ?
                        `<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px; border-radius: 4px; color: white;">
                            <p style="font-weight: 600; margin-bottom: 10px;">📐 Dostępne sekcje:</p>
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                ${field.layouts.map(layout => `
                                    <li style="padding: 6px 0;">
                                        <span style="display: inline-block; width: 8px; height: 8px; background: white; border-radius: 50%; margin-right: 8px;"></span>
                                        ${layout.label || layout.name}
                                    </li>
                                `).join('')}
                            </ul>
                            <button type="button" style="margin-top: 12px; padding: 8px 15px; background: white; color: #667eea; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
                                + Dodaj sekcję
                            </button>
                        </div>` :
                        `<div style="border: 2px dashed #999; border-radius: 4px; padding: 15px; text-align: center; background: #f9f9f9;">
                            <p style="color: #666; margin: 0;">⚙️ Kliknij w edytorze aby skonfigurować sekcje</p>
                        </div>`;
                    
                    return fcLayouts;
                
                case 'file':
                    // Render file uploader with drag & drop
                    const fileId = fieldId + '_uploader';
                    return `
                        <div class="yap-file-uploader-wrapper" style="border: 2px dashed #0073aa; border-radius: 8px; padding: 30px 20px; text-align: center; background: #f9fafb; transition: all 0.3s ease; cursor: pointer;" data-field-id="${fieldId}">
                            <!-- Drag & Drop Zone -->
                            <div class="yap-file-drop-zone" style="pointer-events: none;">
                                <div style="font-size: 40px; margin-bottom: 12px;">📁</div>
                                <p style="margin: 0 0 8px; color: #0073aa; font-weight: 600; font-size: 15px;">Przeciągnij plik tutaj</p>
                                <p style="margin: 0 0 15px; color: #666; font-size: 13px;">lub</p>
                                <button type="button" class="button button-primary yap-file-select" style="background: #0073aa; color: white; border: none;">
                                    <span class="dashicons dashicons-upload"></span>
                                    Wybierz Plik
                                </button>
                                ${field.description ? `<p style="margin: 12px 0 0; font-size: 12px; color: #999;">${field.description}</p>` : ''}
                            </div>
                            
                            <!-- Selected file info -->
                            <div class="yap-file-info" style="display: none; margin-top: 15px; padding: 12px; background: #e8f5e9; border-radius: 4px; border-left: 4px solid #4caf50;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="dashicons dashicons-yes-alt" style="color: #4caf50; font-size: 24px;"></span>
                                    <div style="text-align: left; flex: 1;">
                                        <p class="yap-file-name" style="margin: 0; font-weight: 500; color: #333;"></p>
                                        <p class="yap-file-size" style="margin: 4px 0 0; font-size: 12px; color: #666;"></p>
                                    </div>
                                    <button type="button" class="button yap-file-remove" style="padding: 5px 10px; font-size: 12px;">
                                        <span class="dashicons dashicons-trash"></span>
                                        Usuń
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Hidden file input -->
                            <input type="file" id="${fileId}" name="${field.name}" style="display: none;">
                            <!-- Hidden file data input for form submission -->
                            <input type="hidden" class="yap-file-data" name="${field.name}_data" value="">
                        </div>
                        <p style="font-size: 11px; color: #999; margin-top: 4px;">💾 Przeciągnij plik tutaj lub kliknij aby wybrać</p>
                    `;
                
                default:
                    return `<input type="${field.type}" id="${fieldId}" name="${field.name}" ${placeholder} value="${defaultValue}" ${disabled} style="${baseStyle}">`;
            }
        },
        
        /**
         * Initialize conditional logic for preview mode
         */
        initConditionalLogic() {
            const self = this;
            
            console.log('Initializing conditional logic...');
            
            // Watch all form inputs for changes
            $('.yap-preview-form').on('change input', 'input, select, textarea', function() {
                const fieldName = $(this).attr('name');
                const fieldValue = $(this).val();
                
                console.log('Field changed:', fieldName, '=', fieldValue);
                
                // Check all fields with conditional logic
                $('.yap-preview-field-wrapper[data-conditional="true"]').each(function() {
                    const $wrapper = $(this);
                    const conditionalField = $wrapper.data('conditional-field');
                    const operator = $wrapper.data('conditional-operator');
                    const compareValue = $wrapper.data('conditional-value');
                    const action = $wrapper.data('conditional-action');
                    const message = $wrapper.data('conditional-message');
                    
                    // Find the field this depends on
                    const dependentField = self.schema.fields.find(f => f.name === conditionalField);
                    if (!dependentField) return;
                    
                    // Get current value of dependent field
                    const $dependentInput = $(`[name="${conditionalField}"]`);
                    const currentValue = $dependentInput.val() || '';
                    
                    // Evaluate condition
                    const conditionMet = self.evaluateCondition(currentValue, operator, compareValue);
                    
                    console.log('Checking condition:', {
                        field: conditionalField,
                        currentValue: currentValue,
                        operator: operator || '(empty, using ==)',
                        compareValue: compareValue,
                        result: conditionMet,
                        action: action
                    });
                    
                    // Apply action based on condition
                    self.applyConditionalAction($wrapper, action, conditionMet, message);
                });
            });
            
            // Trigger initial check
            $('.yap-preview-form input, .yap-preview-form select, .yap-preview-form textarea').first().trigger('change');
            
            // Handle WYSIWYG editor - sync contenteditable to hidden input
            $('.yap-wysiwyg-content').on('input blur', function() {
                const $editor = $(this);
                const content = $editor.html();
                // Find the hidden textarea and update it
                $editor.closest('.yap-wysiwyg-editor-wrapper').find('textarea').val(content).trigger('change');
            });
            
            // Handle WYSIWYG toolbar buttons
            $('.yap-wysiwyg-btn').on('click', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const title = $btn.attr('title');
                
                // Apply formatting based on button
                switch(title) {
                    case 'Bold':
                        document.execCommand('bold', false, null);
                        break;
                    case 'Italic':
                        document.execCommand('italic', false, null);
                        break;
                    case 'Underline':
                        document.execCommand('underline', false, null);
                        break;
                    case 'Bullet List':
                        document.execCommand('insertUnorderedList', false, null);
                        break;
                    case 'Heading':
                        document.execCommand('formatBlock', false, '<h2>');
                        break;
                    case 'Link':
                        const url = prompt('Enter URL:');
                        if (url) document.execCommand('createLink', false, url);
                        break;
                    case 'Quote':
                        document.execCommand('formatBlock', false, '<blockquote>');
                        break;
                    case 'Code':
                        document.execCommand('formatBlock', false, '<pre>');
                        break;
                }
                
                // Re-focus editor
                $btn.closest('.yap-wysiwyg-editor-wrapper').find('.yap-wysiwyg-content').focus();
            });
            
            // Handle Gallery uploader
            $('.yap-gallery-upload').on('click', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const fieldId = $btn.data('field-id');
                
                // Check if WordPress media library exists
                if (typeof wp === 'undefined' || !wp.media) {
                    alert('Media Library not available. This feature requires WordPress admin context.');
                    return;
                }
                
                // Open media library
                const mediaFrame = wp.media({
                    title: 'Wybierz Obrazy do Galerii',
                    button: {
                        text: 'Wybierz'
                    },
                    multiple: true,  // Allow multiple selection
                    library: {
                        type: 'image'  // Only images
                    }
                });
                
                mediaFrame.on('select', function() {
                    const images = mediaFrame.state().get('selection').toJSON();
                    const imageIds = images.map(img => img.id).join(',');
                    
                    // Update hidden input
                    $(`#${fieldId}`).val(imageIds).trigger('change');
                    
                    // Display selected images
                    const $wrapper = $(`#${fieldId}`).closest('.yap-gallery-wrapper');
                    const $selected = $wrapper.find('.yap-gallery-selected');
                    const $thumbs = $wrapper.find('.yap-gallery-thumbs');
                    
                    $thumbs.empty();
                    
                    images.forEach(img => {
                        const thumb = img.sizes && img.sizes.thumbnail ? img.sizes.thumbnail.url : img.url;
                        $thumbs.append(`
                            <div style="position: relative; border-radius: 4px; overflow: hidden; background: #f0f0f0;">
                                <img src="${thumb}" alt="${img.alt}" style="width: 100%; height: auto; display: block;">
                                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center;" class="yap-gallery-overlay">
                                    <span style="color: white; font-size: 20px;">${img.id}</span>
                                </div>
                            </div>
                        `);
                    });
                    
                    // Show selected images container
                    if (images.length > 0) {
                        $selected.show();
                        $wrapper.find('.yap-gallery-clear').show();
                    }
                });
                
                mediaFrame.open();
            });
            
            // Handle Gallery clear button
            $('.yap-gallery-clear').on('click', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const fieldId = $btn.data('field-id');
                const $wrapper = $btn.closest('.yap-gallery-wrapper');
                
                // Clear hidden input
                $(`#${fieldId}`).val('').trigger('change');
                
                // Hide selected images
                $wrapper.find('.yap-gallery-selected').hide();
                $wrapper.find('.yap-gallery-thumbs').empty();
                $btn.hide();
            });
            
            // Handle File Uploader
            $('.yap-file-uploader-wrapper').on('click', function(e) {
                if (!$(e.target).closest('button').length) {
                    // Click anywhere in wrapper opens file picker
                    $(this).find('.yap-file-select').click();
                }
            });
            
            // File select button
            $('.yap-file-select').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).closest('.yap-file-uploader-wrapper').find('input[type="file"]').click();
            });
            
            // File input change (user selects file)
            $('.yap-preview-form').on('change', 'input[type="file"]', function() {
                const $input = $(this);
                const file = $input[0].files[0];
                const $wrapper = $input.closest('.yap-file-uploader-wrapper');
                
                if (file) {
                    // Display file info
                    const fileSize = (file.size / 1024 / 1024).toFixed(2); // Convert to MB
                    $wrapper.find('.yap-file-name').text(file.name);
                    $wrapper.find('.yap-file-size').text(`${fileSize} MB`);
                    $wrapper.find('.yap-file-info').show();
                    $wrapper.find('.yap-file-drop-zone').hide();
                    
                    // Store file data (as JSON string for demo)
                    const fileData = {
                        name: file.name,
                        size: file.size,
                        type: file.type
                    };
                    $wrapper.find('.yap-file-data').val(JSON.stringify(fileData));
                    
                    // Trigger change for conditional logic
                    $input.trigger('change');
                }
            });
            
            // File remove button
            $('.yap-file-remove').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $btn = $(this);
                const $wrapper = $btn.closest('.yap-file-uploader-wrapper');
                const $fileInput = $wrapper.find('input[type="file"]');
                
                // Clear file input
                $fileInput.val('');
                
                // Hide info and show drop zone
                $wrapper.find('.yap-file-info').hide();
                $wrapper.find('.yap-file-drop-zone').show();
                
                // Clear hidden data input
                $wrapper.find('.yap-file-data').val('').trigger('change');
            });
            
            // Drag & drop for files
            $('.yap-file-uploader-wrapper').on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).css({
                    'background-color': '#e3f2fd',
                    'border-color': '#667eea',
                    'box-shadow': '0 0 0 3px rgba(102, 126, 234, 0.1)'
                });
            });
            
            $('.yap-file-uploader-wrapper').on('dragleave', function(e) {
                e.preventDefault();
                $(this).css({
                    'background-color': '#f9fafb',
                    'border-color': '#0073aa',
                    'box-shadow': 'none'
                });
            });
            
            $('.yap-file-uploader-wrapper').on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                $(this).css({
                    'background-color': '#f9fafb',
                    'border-color': '#0073aa',
                    'box-shadow': 'none'
                });
                
                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    // Set first file to input
                    const $fileInput = $(this).find('input[type="file"]')[0];
                    $fileInput.files = files;
                    
                    // Trigger change event
                    $(this).find('input[type="file"]').trigger('change');
                }
            });
        },
        
        /**
         * Evaluate conditional logic condition
         */
        evaluateCondition(value, operator, compareValue) {
            // Default to == if operator is empty or undefined
            if (!operator || operator === '') {
                operator = '==';
            }
            
            switch(operator) {
                case '==':
                    return value == compareValue;
                case '!=':
                    return value != compareValue;
                case '>':
                    return parseFloat(value) > parseFloat(compareValue);
                case '<':
                    return parseFloat(value) < parseFloat(compareValue);
                case '>=':
                    return parseFloat(value) >= parseFloat(compareValue);
                case '<=':
                    return parseFloat(value) <= parseFloat(compareValue);
                case 'contains':
                    return value.toLowerCase().includes(compareValue.toLowerCase());
                case 'not_contains':
                    return !value.toLowerCase().includes(compareValue.toLowerCase());
                case 'starts_with':
                    return value.toLowerCase().startsWith(compareValue.toLowerCase());
                case 'ends_with':
                    return value.toLowerCase().endsWith(compareValue.toLowerCase());
                case 'is_empty':
                    return value === '' || value === null || value === undefined;
                case 'is_not_empty':
                    return value !== '' && value !== null && value !== undefined;
                case 'matches_pattern':
                    try {
                        const regex = new RegExp(compareValue);
                        return regex.test(value);
                    } catch(e) {
                        return false;
                    }
                default:
                    return false;
            }
        },
        
        /**
         * Apply conditional action
         */
        applyConditionalAction($wrapper, action, conditionMet, message) {
            const $input = $wrapper.find('input, select, textarea').first();
            const $messageBox = $wrapper.find('.yap-conditional-message-box');
            
            // Clear previous messages
            $messageBox.hide().html('');
            
            switch(action) {
                case 'show':
                    $wrapper.toggle(conditionMet);
                    break;
                    
                case 'hide':
                    $wrapper.toggle(!conditionMet);
                    break;
                    
                case 'message':
                    if (conditionMet && message) {
                        $messageBox
                            .html(`<span class="dashicons dashicons-info" style="color: #0073aa;"></span> ${message}`)
                            .css({
                                'display': 'flex',
                                'align-items': 'center',
                                'gap': '8px',
                                'background': '#e3f2fd',
                                'border': '1px solid #0073aa',
                                'color': '#0073aa'
                            })
                            .show();
                    }
                    break;
                    
                case 'error':
                    if (conditionMet && message) {
                        $messageBox
                            .html(`<span class="dashicons dashicons-warning" style="color: #dc3232;"></span> ${message}`)
                            .css({
                                'display': 'flex',
                                'align-items': 'center',
                                'gap': '8px',
                                'background': '#fef7f7',
                                'border': '1px solid #dc3232',
                                'color': '#dc3232'
                            })
                            .show();
                    }
                    break;
                    
                case 'disable':
                    $input.prop('disabled', conditionMet);
                    if (conditionMet) {
                        $input.css('opacity', '0.5');
                    } else {
                        $input.css('opacity', '1');
                    }
                    break;
                    
                case 'enable':
                    $input.prop('disabled', !conditionMet);
                    if (!conditionMet) {
                        $input.css('opacity', '0.5');
                    } else {
                        $input.css('opacity', '1');
                    }
                    break;
            }
        },
        
        /**
         * Enter edit mode - Restore builder from preview
         */
        enterEditMode() {
            console.log('Entering edit mode');
            
            // Remove preview message
            $('.yap-preview-notice').remove();
            
            // Restore original field items if we have them
            if (this.originalFieldsHTML) {
                $('#yap-drop-zone').html(this.originalFieldsHTML);
                this.originalFieldsHTML = null;
            }
            
            // Enable sortable
            $('#yap-drop-zone').sortable('enable');
            
            // Show sidebar and inspector
            $('.yap-builder-sidebar, .yap-builder-inspector').show();
            $('#yap-group-name').prop('readonly', false);
            
            // Rebind events to restored elements
            this.bindFieldEvents();
        }
    };
    
    /**
     * History Inspector Handler
     */
    const HistoryInspector = {
        init() {
            // Check if inspector tabs exist in DOM
            if ($('.yap-inspector-tab').length === 0) {
                console.warn('⚠️ History Inspector: No tabs found in DOM, skipping initialization');
                return;
            }
            
            this.bindTabSwitching();
            this.bindHistoryControls();
            this.updateHistoryUI();
            
            // Update history UI whenever something changes
            document.addEventListener('yapFieldAdded', () => this.updateHistoryUI());
            document.addEventListener('yapFieldDeleted', () => this.updateHistoryUI());
            document.addEventListener('yapFieldMoved', () => this.updateHistoryUI());
            document.addEventListener('yapFieldEdited', () => this.updateHistoryUI());
            
            console.log('✅ History Inspector fully initialized');
        },
        
        /**
         * Bind tab switching functionality
         */
        bindTabSwitching() {
            $('.yap-inspector-tab').on('click', (e) => {
                const $btn = $(e.currentTarget);
                const tabName = $btn.data('tab');
                
                // Update active tab button
                $('.yap-inspector-tab').removeClass('active');
                $btn.addClass('active');
                
                // Update active tab content
                $('.yap-inspector-tab-content').removeClass('active');
                $(`.yap-inspector-tab-content[data-tab="${tabName}"]`).addClass('active');
                
                // Update header based on tab
                if (tabName === 'history') {
                    $('.yap-inspector-header').hide();
                } else {
                    $('.yap-inspector-header').show();
                }
            });
        },
        
        /**
         * Bind history control buttons
         */
        bindHistoryControls() {
            // Check if buttons exist
            const $undoBtn = $('#yap-history-undo');
            const $redoBtn = $('#yap-history-redo');
            const $clearBtn = $('#yap-history-clear');
            
            if ($undoBtn.length === 0 && $redoBtn.length === 0) {
                console.warn('⚠️ History Inspector: No control buttons found');
                return;
            }
            
            const self = this;
            
            $undoBtn.on('click', () => {
                if (typeof FieldHistory !== 'undefined') {
                    FieldHistory.undo();
                    self.updateHistoryUI();
                }
            });
            
            $redoBtn.on('click', () => {
                if (typeof FieldHistory !== 'undefined') {
                    FieldHistory.redo();
                    self.updateHistoryUI();
                }
            });
            
            $clearBtn.on('click', () => {
                if (typeof FieldHistory !== 'undefined') {
                    if (confirm('Are you sure you want to clear the entire change history?')) {
                        FieldHistory.clear();
                        self.updateHistoryUI();
                    }
                }
            });
        },
        
        /**
         * Update history UI with latest data
         */
        updateHistoryUI() {
            if (typeof FieldHistory === 'undefined') {
                return;
            }
            
            // Check if history panel exists
            if ($('#yap-inspector-history').length === 0) {
                return;
            }
            
            // Update position
            const pos = FieldHistory.getCurrentPosition();
            const $posText = $('#yap-history-position-text');
            if ($posText.length > 0) {
                $posText.text(`${pos.current}/${pos.total}`);
            }
            
            // Update undo/redo button states
            const $undoBtn = $('#yap-history-undo');
            const $redoBtn = $('#yap-history-redo');
            if ($undoBtn.length > 0) {
                $undoBtn.prop('disabled', !pos.canUndo);
            }
            if ($redoBtn.length > 0) {
                $redoBtn.prop('disabled', !pos.canRedo);
            }
            
            // Update badge
            const $badge = $('.yap-history-badge');
            if ($badge.length > 0) {
                if (pos.total > 0) {
                    $badge.text(pos.total).show();
                } else {
                    $badge.hide();
                }
            }
            
            // Update timeline
            this.renderTimeline();
            
            // Update statistics
            this.renderStatistics();
        },
        
        /**
         * Render history timeline
         */
        renderTimeline() {
            if (typeof FieldHistory === 'undefined') {
                return;
            }
            
            const $timeline = $('#yap-history-timeline');
            if ($timeline.length === 0) {
                return;
            }
            
            const timeline = FieldHistory.getTimeline(20);
            
            if (timeline.length === 0) {
                $timeline.html('<p class="yap-history-placeholder">No changes yet</p>');
                return;
            }
            
            let html = '';
            const pos = FieldHistory.getCurrentPosition();
            
            timeline.forEach((item, index) => {
                const isCurrent = (index === pos.current - 1);
                const icon = this.getChangeIcon(item.type);
                
                html += `
                    <div class="yap-history-item${isCurrent ? ' current' : ''}" data-index="${index}">
                        <div class="yap-history-item-icon">${icon}</div>
                        <div class="yap-history-item-info">
                            <div class="yap-history-item-type">${item.type}</div>
                            <div class="yap-history-item-desc">${this.escapeHtml(item.description)}</div>
                            <div class="yap-history-item-time">${item.timeAgo}</div>
                        </div>
                    </div>
                `;
            });
            
            $timeline.html(html);
        },
        
        /**
         * Render statistics
         */
        renderStatistics() {
            if (typeof FieldHistory === 'undefined') {
                return;
            }
            
            const $statsContent = $('#yap-history-stats-content');
            if ($statsContent.length === 0) {
                return;
            }
            
            const stats = FieldHistory.getStats();
            
            let html = `
                <div class="yap-history-stats-grid">
                    <div class="yap-history-stat-item">
                        <div class="yap-history-stat-value">${stats.total}</div>
                        <div class="yap-history-stat-label">Total Changes</div>
                    </div>
                    <div class="yap-history-stat-item">
                        <div class="yap-history-stat-value">${stats.adds}</div>
                        <div class="yap-history-stat-label">Adds</div>
                    </div>
                    <div class="yap-history-stat-item">
                        <div class="yap-history-stat-value">${stats.deletes}</div>
                        <div class="yap-history-stat-label">Deletes</div>
                    </div>
                    <div class="yap-history-stat-item">
                        <div class="yap-history-stat-value">${stats.moves}</div>
                        <div class="yap-history-stat-label">Moves</div>
                    </div>
                    <div class="yap-history-stat-item">
                        <div class="yap-history-stat-value">${stats.edits}</div>
                        <div class="yap-history-stat-label">Edits</div>
                    </div>
                    <div class="yap-history-stat-item">
                        <div class="yap-history-stat-value">${stats.batches}</div>
                        <div class="yap-history-stat-label">Batches</div>
                    </div>
                </div>
            `;
            
            $statsContent.html(html);
        },
        
        /**
         * Get icon for change type
         */
        getChangeIcon(type) {
            const icons = {
                'add': '➕',
                'delete': '➖',
                'move': '⟷',
                'edit': '✎',
                'batch': '📦'
            };
            return icons[type] || '•';
        },
        
        /**
         * Escape HTML entities
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    // Make globally accessible
    window.HistoryInspector = HistoryInspector;
    
    // Make YAPBuilder globally accessible for extensions
    window.YAPBuilder = YAPBuilder;
    
    // Initialize on document ready
    $(document).ready(() => {
        console.log('YAP Visual Builder initializing...');
        console.log('yapBuilder global object:', window.yapBuilder);
        console.log('yapBuilder.fieldTypes:', yapBuilder && yapBuilder.fieldTypes);
        console.log('typeof yapBuilder:', typeof yapBuilder);
        YAPBuilder.init();
        
        // Initialize history inspector
        HistoryInspector.init();
        console.log('🎯 History Inspector initialized');
        
        console.log('YAP Visual Builder initialized');
        
        // Log extensions status
        if (window.YAPBuilderExt) {
            console.log('✅ YAP Builder Extensions loaded (Toast, Modal, Multi-Conditions)');
        } else {
            console.warn('⚠️ YAP Builder Extensions not loaded - some features may be limited');
        }
    });
    
})(jQuery);
