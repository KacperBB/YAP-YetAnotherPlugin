<!-- Modal do zarządzania layoutami Flexible Content -->
<div id="yap-flexible-layouts-modal" style="display: none;">
    <div class="yap-modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000;">
        <div class="yap-modal-content" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; width: 90%; max-width: 900px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            
            <!-- Modal Header -->
            <div class="yap-modal-header" style="display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 2px solid #005a87; background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);">
                <h2 style="margin: 0; font-size: 20px; color: white;">🎨 Zarządzanie Layoutami Flexible Content</h2>
                <button type="button" class="yap-close-modal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: white; opacity: 0.9; transition: all 0.2s ease;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.9'">&times;</button>
            </div>
            
            <!-- Modal Body -->
            <div class="yap-modal-body" style="padding: 20px;">
                
                <!-- Info -->
                <div class="yap-info-box" style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 12px; margin-bottom: 20px;">
                    <p style="margin: 0;"><strong>ℹ️ Co to są Layouty?</strong></p>
                    <p style="margin: 5px 0 0; font-size: 13px;">
                        Layouty to różne typy sekcji, które użytkownik może dodawać do strony.<br>
                        Przykład: "Hero Section", "3 Kolumny", "Testimonials", "CTA Banner"<br>
                        Każdy layout ma własny zestaw pól.
                    </p>
                </div>
                
                <!-- Add New Layout -->
                <div class="yap-add-layout-section" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border: 2px solid #0073aa; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,115,170,0.1);">
                    <h3 style="margin-top: 0; color: #0073aa;">➕ Dodaj nowy layout</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: end;">
                        <div>
                            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #333;">Label (nazwa wyświetlana):</label>
                            <input type="text" id="yap-new-layout-label" placeholder="np. Hero Section" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; transition: all 0.3s ease;">
                            <small style="color: #666; display: block; margin-top: 4px;">👆 Wpisz nazwę layoutu</small>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #333;">Slug (automatyczny):</label>
                            <input type="text" id="yap-new-layout-name" placeholder="auto-generowany" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; background: #f9f9f9; color: #666;" readonly>
                            <small style="color: #666; display: block; margin-top: 4px;">🔒 Generowany automatycznie</small>
                        </div>
                        <div>
                            <button type="button" class="button button-primary yap-add-layout-button" style="height: 42px; background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); border: none; cursor: pointer; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,115,170,0.2);">
                                ➕ Dodaj Layout
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Existing Layouts -->
                <div class="yap-layouts-list">
                    <h3>📋 Istniejące Layouty:</h3>
                    <div id="yap-layouts-container" style="display: grid; gap: 15px;">
                        <!-- Layouts will be rendered here by JavaScript -->
                        <div class="yap-no-layouts" style="padding: 40px; text-align: center; background: #f9f9f9; border-radius: 6px;">
                            <p style="margin: 0; color: #666; font-size: 15px;">Brak layoutów. Dodaj pierwszy layout powyżej.</p>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- Modal Footer -->
            <div class="yap-modal-footer" style="padding: 15px 20px; border-top: 2px solid #e0e0e0; display: flex; justify-content: flex-end; gap: 10px; background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%);">
                <button type="button" class="button yap-close-modal" style="border-radius: 4px; transition: all 0.2s ease;">Zamknij</button>
                <button type="button" class="button button-primary yap-save-layouts" style="background: linear-gradient(135deg, #10a37f 0%, #099268 100%); border: none; border-radius: 4px; font-weight: 600; transition: all 0.2s ease; box-shadow: 0 4px 8px rgba(16, 163, 127, 0.2);">💾 Zapisz</button>
            </div>
            
        </div>
    </div>
</div>

<!-- Layout Item Template (hidden) -->
<template id="yap-layout-item-template">
    <div class="yap-layout-item" data-layout-name="" style="background: linear-gradient(135deg, #ffffff 0%, #f5f7fa 100%); border: 2px solid #0073aa; border-radius: 8px; padding: 16px; box-shadow: 0 4px 12px rgba(0,115,170,0.15); transition: all 0.3s ease;">
        <div class="yap-layout-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e0e0e0;">
            <div style="display: flex; align-items: center; gap: 12px; flex-grow: 1;">
                <span class="dashicons dashicons-menu" style="cursor: move; color: #0073aa; font-size: 20px;"></span>
                <div style="flex-grow: 1;">
                    <strong class="yap-layout-label" style="font-size: 16px; color: #0073aa; display: block; margin-bottom: 4px;"></strong>
                    <code class="yap-layout-name" style="background: #e8f2f9; padding: 4px 10px; border-radius: 4px; font-size: 12px; color: #0073aa; font-weight: 500;"></code>
                </div>
            </div>
            <div>
                <button type="button" class="button button-small yap-add-layout-field" style="background: linear-gradient(135deg, #10a37f 0%, #099268 100%); color: white; border: none; cursor: pointer; font-weight: 600; margin-right: 8px; transition: all 0.2s ease;">➕ Dodaj pole</button>
                <button type="button" class="button button-small yap-remove-layout" style="background: linear-gradient(135deg, #dc3232 0%, #a71d1d 100%); color: white; border: none; cursor: pointer; font-weight: 600; transition: all 0.2s ease;">🗑️ Usuń</button>
            </div>
        </div>
        <div class="yap-layout-fields" style="display: grid; gap: 12px;">
            <!-- Fields will be added here -->
        </div>
    </div>
</template>

<!-- Layout Field Template (hidden) -->
<template id="yap-layout-field-template">
    <div class="yap-layout-field" style="display: grid; grid-template-columns: 200px 200px 150px 1fr auto; gap: 10px; align-items: center; padding: 12px; background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f0 100%); border: 1px solid #ddd; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: all 0.3s ease;">
        <input type="text" class="yap-field-name" placeholder="Nazwa pola (slug)" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
        <input type="text" class="yap-field-label" placeholder="Label wyświetlany" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
        <select class="yap-field-type" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; background: white;">
            <option value="text">Text</option>
            <option value="textarea">Textarea</option>
            <option value="number">Number</option>
            <option value="email">Email</option>
            <option value="url">URL</option>
            <option value="date">Date</option>
            <option value="time">Time</option>
            <option value="datetime-local">DateTime</option>
            <option value="color">Color</option>
            <option value="image">Image</option>
            <option value="file">File</option>
            <option value="wysiwyg">WYSIWYG</option>
            <option value="select">Select</option>
            <option value="checkbox">Checkbox</option>
            <option value="radio">Radio</option>
        </select>
        <input type="text" class="yap-field-placeholder" placeholder="Placeholder (podpowiedź)" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
        <button type="button" class="button button-small yap-remove-field" style="color: #dc3232; padding: 6px 10px; border-radius: 4px; transition: all 0.2s ease;">✕ Usuń</button>
    </div>
</template>

<style>
.yap-modal-overlay {
    animation: yapFadeIn 0.2s ease;
}

@keyframes yapFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.yap-modal-content {
    animation: yapSlideIn 0.3s ease;
}

@keyframes yapSlideIn {
    from { 
        opacity: 0;
        transform: translate(-50%, -48%);
    }
    to { 
        opacity: 1;
        transform: translate(-50%, -50%);
    }
}

.yap-layouts-list .sortable-placeholder {
    background: #f0f6fc;
    border: 2px dashed #0073aa;
    border-radius: 6px;
    height: 100px;
}

/* Enhanced input styling */
#yap-new-layout-label:focus,
#yap-new-layout-name:focus,
.yap-field-name:focus,
.yap-field-label:focus,
.yap-field-type:focus,
.yap-field-placeholder:focus {
    outline: none;
    border-color: #0073aa !important;
    box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1) !important;
    background: #fff !important;
}

.yap-add-layout-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,115,170,0.3) !important;
}

.yap-add-layout-field:hover,
.yap-remove-layout:hover {
    transform: scale(1.05);
}

.yap-layout-field:hover {
    border-color: #0073aa;
    box-shadow: 0 4px 8px rgba(0,115,170,0.15) !important;
}

.yap-remove-field:hover {
    background: #d63232 !important;
    transform: scale(1.1);
}

/* Modal header styling */
.yap-modal-header {
    background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
    color: white;
}

.yap-modal-header h2 {
    color: white;
}

/* Smooth transitions */
* {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

<script>
jQuery(document).ready(function($) {
    let currentGroup = '';
    let currentField = '';
    let currentLayouts = [];
    
    // Auto-generate slug from label
    $(document).on('keyup change', '#yap-new-layout-label', function() {
        const label = $(this).val().trim();
        const slug = label
            .toLowerCase()
            .replace(/[àáâãäå]/g, 'a')
            .replace(/[èéêë]/g, 'e')
            .replace(/[ìíîï]/g, 'i')
            .replace(/[òóôõö]/g, 'o')
            .replace(/[ùúûü]/g, 'u')
            .replace(/[ñ]/g, 'n')
            .replace(/[ćć]/g, 'c')
            .replace(/[żź]/g, 'z')
            .replace(/[śś]/g, 's')
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '_')
            .replace(/_+/g, '_')
            .replace(/^_+|_+$/g, '');
        
        $('#yap-new-layout-name').val(slug || 'layout');
    });
    
    // Open modal (for old pattern editor - Visual Builder uses yapOpenFlexibleModal)
    $(document).on('click', '.yap-manage-flexible-layouts', function(e) {
        e.preventDefault();
        
        // Get group from button data-attribute (pattern editor) or from Visual Builder select
        const btnGroup = $(this).data('group');
        const btnField = $(this).data('field');
        
        console.log('🔵 Button clicked - data-group:', btnGroup, 'data-field:', btnField);
        
        // If no group in data attribute, try to get from Visual Builder select
        if (!btnGroup && $('#yap-builder-group-select').length) {
            const vbGroup = $('#yap-builder-group-select').val();
            console.log('🔵 No data-group, using Visual Builder select:', vbGroup);
            
            if (!vbGroup) {
                alert('⚠️ Najpierw wybierz lub utwórz grupę!');
                return;
            }
            
            currentGroup = vbGroup;
            currentField = btnField;
        } else {
            currentGroup = btnGroup;
            currentField = btnField;
        }
        
        console.log('🎨 Opening layouts manager for:', currentGroup, currentField);
        
        // Load existing layouts
        loadLayouts();
        
        $('#yap-flexible-layouts-modal').fadeIn(200);
    });
    
    // Close modal
    $(document).on('click', '.yap-close-modal, .yap-modal-overlay', function(e) {
        if (e.target === this) {
            $('#yap-flexible-layouts-modal').fadeOut(200);
        }
    });
    
    // Prevent closing when clicking inside modal content
    $(document).on('click', '.yap-modal-content', function(e) {
        e.stopPropagation();
    });
    
    // Load layouts from server
    function loadLayouts() {
        console.log('📥 Loading layouts for:', currentGroup, currentField);
        $.ajax({
            url: yapFlexible.ajax_url,
            type: 'POST',
            data: {
                action: 'yap_get_flexible_layouts',
                nonce: yapFlexible.nonce,
                group_name: currentGroup,
                field_name: currentField
            },
            success: function(response) {
                if (response.success) {
                    currentLayouts = response.data.layouts || [];
                    console.log('✅ Layouts loaded successfully:', currentLayouts);
                    renderLayouts();
                } else {
                    console.error('Failed to load layouts:', response);
                    alert('⚠️ Błąd podczas ładowania layoutów');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX error:', error);
                alert('⚠️ Błąd połączenia przy ładowaniu layoutów');
            }
        });
    }
    
    // Render layouts in UI
    function renderLayouts() {
        const $container = $('#yap-layouts-container');
        $container.empty();
        
        if (currentLayouts.length === 0) {
            $container.html(`
                <div class="yap-no-layouts" style="padding: 40px; text-align: center; background: #f9f9f9; border-radius: 6px;">
                    <p style="margin: 0; color: #666; font-size: 15px;">Brak layoutów. Dodaj pierwszy layout powyżej.</p>
                </div>
            `);
            return;
        }
        
        currentLayouts.forEach(layout => {
            const $item = createLayoutItem(layout);
            $container.append($item);
        });
        
        // Make sortable
        $container.sortable({
            handle: '.dashicons-menu',
            placeholder: 'sortable-placeholder',
            update: function() {
                updateLayoutsOrder();
            }
        });
    }
    
    // Create layout item HTML
    function createLayoutItem(layout) {
        const $template = $('#yap-layout-item-template').contents().clone();
        
        $template.attr('data-layout-name', layout.name);
        $template.find('.yap-layout-label').text(layout.label);
        $template.find('.yap-layout-name').text(layout.name);
        
        // Add fields
        const $fieldsContainer = $template.find('.yap-layout-fields');
        if (layout.sub_fields && layout.sub_fields.length > 0) {
            layout.sub_fields.forEach(field => {
                const $field = createFieldItem(field);
                $fieldsContainer.append($field);
            });
        } else {
            $fieldsContainer.html('<p style="padding: 15px; color: #999; font-style: italic; text-align: center; background: #f9f9f9; border-radius: 4px; margin: 0;">➕ Kliknij "Dodaj pole" aby rozpocząć</p>');
        }
        
        return $template;
    }
    
    // Create field item HTML
    function createFieldItem(field) {
        const $template = $('#yap-layout-field-template').contents().clone();
        
        $template.find('.yap-field-name').val(field.name || '');
        $template.find('.yap-field-label').val(field.label || '');
        $template.find('.yap-field-type').val(field.type || 'text');
        $template.find('.yap-field-placeholder').val(field.placeholder || '');
        
        return $template;
    }
    
    // Add new layout
    $(document).on('click', '.yap-add-layout-button', function() {
        const label = $('#yap-new-layout-label').val().trim();
        const name = $('#yap-new-layout-name').val().trim();
        
        if (!name || !label) {
            alert('⚠️ Wypełnij wszystkie pola');
            return;
        }
        
        // Validate name format
        if (!/^[a-z0-9_]+$/.test(name)) {
            alert('⚠️ Slug może zawierać tylko małe litery, cyfry i podkreślenia');
            return;
        }
        
        // Check if exists
        if (currentLayouts.find(l => l.name === name)) {
            alert('⚠️ Layout o tej nazwie już istnieje');
            return;
        }
        
        currentLayouts.push({
            name: name,
            label: label,
            display: 'block',
            sub_fields: []
        });
        
        renderLayouts();
        
        $('#yap-new-layout-name').val('');
        $('#yap-new-layout-label').val('');
        
        console.log('✅ Layout added:', name, label);
    });
    
    // Remove layout
    $(document).on('click', '.yap-remove-layout', function() {
        if (!confirm('🗑️ Czy na pewno chcesz usunąć ten layout?')) {
            return;
        }
        
        const $item = $(this).closest('.yap-layout-item');
        const layoutName = $item.data('layout-name');
        
        currentLayouts = currentLayouts.filter(l => l.name !== layoutName);
        renderLayouts();
        
        console.log('✅ Layout removed:', layoutName);
    });
    
    // Add field to layout
    $(document).on('click', '.yap-add-layout-field', function() {
        const $item = $(this).closest('.yap-layout-item');
        const layoutName = $item.data('layout-name');
        const $fieldsContainer = $item.find('.yap-layout-fields');
        
        // Remove "no fields" message if exists
        $fieldsContainer.find('p').remove();
        
        const $newField = createFieldItem({
            name: '',
            label: '',
            type: 'text',
            placeholder: ''
        });
        
        $fieldsContainer.append($newField);
    });
    
    // Remove field
    $(document).on('click', '.yap-remove-field', function() {
        $(this).closest('.yap-layout-field').fadeOut(200, function() {
            $(this).remove();
        });
    });
    
    // Update layouts order after drag
    function updateLayoutsOrder() {
        const newOrder = [];
        $('#yap-layouts-container .yap-layout-item').each(function() {
            const layoutName = $(this).data('layout-name');
            const layout = currentLayouts.find(l => l.name === layoutName);
            if (layout) {
                newOrder.push(layout);
            }
        });
        currentLayouts = newOrder;
    }
    
    // Save layouts
    $(document).on('click', '.yap-save-layouts', function() {
        // Collect data from UI
        const layoutsData = [];
        
        $('#yap-layouts-container .yap-layout-item').each(function() {
            const $item = $(this);
            const layoutName = $item.data('layout-name');
            const layout = currentLayouts.find(l => l.name === layoutName);
            
            if (layout) {
                const fields = [];
                
                $item.find('.yap-layout-field').each(function() {
                    const $field = $(this);
                    const fieldName = $field.find('.yap-field-name').val().trim();
                    const fieldLabel = $field.find('.yap-field-label').val().trim();
                    
                    if (fieldName && fieldLabel) {
                        fields.push({
                            name: fieldName,
                            label: fieldLabel,
                            type: $field.find('.yap-field-type').val(),
                            placeholder: $field.find('.yap-field-placeholder').val().trim()
                        });
                    }
                });
                
                layoutsData.push({
                    ...layout,
                    sub_fields: fields
                });
            }
        });
        
        if (layoutsData.length === 0) {
            alert('⚠️ Dodaj co najmniej jeden layout z polami');
            return;
        }
        
        console.log('💾 Saving layouts:', layoutsData);
        
        // Save to server
        $.ajax({
            url: yapFlexible.ajax_url,
            type: 'POST',
            data: {
                action: 'yap_save_flexible_layouts',
                nonce: yapFlexible.nonce,
                group_name: currentGroup,
                field_name: currentField,
                layouts: JSON.stringify(layoutsData)
            },
            success: function(response) {
                console.log('✅ Server response:', response);
                if (response.success) {
                    alert('✅ Layouty zostały zapisane pomyślnie!');
                    $('#yap-flexible-layouts-modal').fadeOut(200, function() {
                        location.reload();
                    });
                } else {
                    console.error('❌ Server returned error:', response);
                    alert('❌ Błąd podczas zapisywania: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX error:', {xhr: xhr, status: status, error: error});
                alert('❌ Błąd połączenia z serwerem: ' + error);
            }
        });
    });
    
    // Global function to open modal from external scripts (e.g., Visual Builder)
    window.yapOpenFlexibleModal = function(groupName, fieldName) {
        console.log('🎨 yapOpenFlexibleModal called with:', {groupName: groupName, fieldName: fieldName});
        
        currentGroup = groupName;
        currentField = fieldName;
        
        console.log('🎨 After assignment - currentGroup:', currentGroup, 'currentField:', currentField);
        
        loadLayouts();
        $('#yap-flexible-layouts-modal').fadeIn(200);
    };
});
</script>
