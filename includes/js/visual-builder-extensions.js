/**
 * YAP Visual Builder Extensions
 * 
 * Toast notifications, delete modal, multi-condition logic
 */

(function($) {
    'use strict';
    
    // Extend YAPBuilder object
    window.YAPBuilderExt = {
        
        /**
         * Toast notification system
         */
        toast(message, type = 'success', title = '') {
            // Create container if doesn't exist
            if (!$('.yap-toast-container').length) {
                $('body').append('<div class="yap-toast-container"></div>');
            }
            
            const icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };
            
            const titles = {
                success: title || 'Sukces',
                error: title || 'Błąd',
                warning: title || 'Ostrzeżenie',
                info: title || 'Informacja'
            };
            
            const toast = $(`
                <div class="yap-toast ${type}">
                    <div class="yap-toast-icon">${icons[type]}</div>
                    <div class="yap-toast-content">
                        <div class="yap-toast-title">${titles[type]}</div>
                        <div class="yap-toast-message">${message}</div>
                    </div>
                    <button class="yap-toast-close" type="button">×</button>
                </div>
            `);
            
            $('.yap-toast-container').append(toast);
            
            // Close button
            toast.find('.yap-toast-close').on('click', function() {
                toast.addClass('removing');
                setTimeout(() => toast.remove(), 300);
            });
            
            // Auto remove - longer duration for errors/warnings
            const duration = (type === 'error' || type === 'warning') ? 8000 : 5000;
            let autoHideTimer = setTimeout(() => {
                if (!toast.is(':hover')) {
                    toast.addClass('removing');
                    setTimeout(() => toast.remove(), 300);
                }
            }, duration);
            
            // Pause auto-hide on hover
            toast.on('mouseenter', function() {
                clearTimeout(autoHideTimer);
            });
            
            // Resume auto-hide on mouse leave
            toast.on('mouseleave', function() {
                autoHideTimer = setTimeout(() => {
                    toast.addClass('removing');
                    setTimeout(() => toast.remove(), 300);
                }, 2000);
            });
        },
        
        /**
         * Show delete confirmation modal
         */
        showDeleteModal(fieldId, fieldLabel, onConfirm) {
            const modal = $(`
                <div class="yap-delete-modal active">
                    <div class="yap-delete-modal-content">
                        <div class="yap-delete-modal-header">
                            <div class="yap-delete-modal-icon">🗑️</div>
                            <div class="yap-delete-modal-title">Usuń Pole</div>
                        </div>
                        <div class="yap-delete-modal-body">
                            <p>Czy na pewno chcesz usunąć to pole?</p>
                            <div class="yap-delete-modal-field-info">
                                <strong>Pole:</strong> ${fieldLabel}<br>
                                <strong>ID:</strong> ${fieldId}
                            </div>
                            <p style="margin-top: 15px; color: #dc3232; font-weight: 500;">⚠️ Tej operacji nie można cofnąć!</p>
                        </div>
                        <div class="yap-delete-modal-actions">
                            <button type="button" class="yap-delete-modal-cancel">Anuluj</button>
                            <button type="button" class="yap-delete-modal-confirm">Usuń Pole</button>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            
            // Cancel
            modal.find('.yap-delete-modal-cancel').on('click', function() {
                modal.removeClass('active');
                setTimeout(() => modal.remove(), 300);
            });
            
            // Confirm delete
            modal.find('.yap-delete-modal-confirm').on('click', function() {
                modal.removeClass('active');
                setTimeout(() => modal.remove(), 300);
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            });
            
            // Click outside to close
            modal.on('click', function(e) {
                if ($(e.target).hasClass('yap-delete-modal')) {
                    modal.removeClass('active');
                    setTimeout(() => modal.remove(), 300);
                }
            });
        },
        
        /**
         * Initialize multi-condition logic UI
         */
        renderMultiConditions(field) {
            // Initialize condition_groups if not exists
            if (!field.condition_groups) {
                field.condition_groups = [{
                    logic: 'AND',
                    conditions: [{
                        field: field.conditional_field || '',
                        operator: field.conditional_operator || '==',
                        value: field.conditional_value || ''
                    }]
                }];
            }
            
            let html = '<div class="yap-condition-groups">';
            
            field.condition_groups.forEach((group, groupIndex) => {
                html += `
                    <div class="yap-condition-group" data-group-index="${groupIndex}">
                        <div class="yap-condition-group-header">
                            <div class="yap-condition-group-logic">
                                <span>Grupa ${groupIndex + 1}:</span>
                                <select class="yap-group-logic" data-group-index="${groupIndex}">
                                    <option value="AND" ${group.logic === 'AND' ? 'selected' : ''}>AND (wszystkie)</option>
                                    <option value="OR" ${group.logic === 'OR' ? 'selected' : ''}>OR (dowolny)</option>
                                </select>
                            </div>
                            <div class="yap-condition-group-actions">
                                ${field.condition_groups.length > 1 ? `<button type="button" class="yap-condition-group-remove" data-group-index="${groupIndex}">× Usuń grupę</button>` : ''}
                            </div>
                        </div>
                        <div class="yap-conditions-list">`;
                
                group.conditions.forEach((condition, condIndex) => {
                    html += this.renderSingleCondition(field, groupIndex, condIndex, condition);
                });
                
                html += `
                        </div>
                        <button type="button" class="yap-add-condition-btn" data-group-index="${groupIndex}">
                            <span class="dashicons dashicons-plus-alt"></span>
                            Dodaj warunek
                        </button>
                    </div>
                `;
            });
            
            html += `
                </div>
                <button type="button" class="yap-add-group-btn">
                    <span class="dashicons dashicons-plus-alt"></span>
                    Dodaj grupę warunków (OR)
                </button>
            `;
            
            return html;
        },
        
        /**
         * Render single condition row
         */
        renderSingleCondition(field, groupIndex, condIndex, condition) {
            const allFields = window.YAPBuilder ? window.YAPBuilder.schema.fields : [];
            
            // Include current field in options for self-referencing conditions
            const fieldOptions = allFields.map(f => 
                `<option value="${f.name}" ${condition.field === f.name ? 'selected' : ''}>${f.label || f.name}</option>`
            ).join('');
            
            const operators = [
                { value: '==', label: 'równe' },
                { value: '!=', label: 'różne' },
                { value: '>', label: 'większe' },
                { value: '<', label: 'mniejsze' },
                { value: '>=', label: 'większe lub równe' },
                { value: '<=', label: 'mniejsze lub równe' },
                { value: 'contains', label: 'zawiera' },
                { value: 'not_contains', label: 'nie zawiera' },
                { value: 'starts_with', label: 'zaczyna się od' },
                { value: 'ends_with', label: 'kończy się na' },
                { value: 'is_empty', label: 'jest puste' },
                { value: 'is_not_empty', label: 'nie jest puste' },
                { value: 'matches_pattern', label: 'pasuje do wzorca' }
            ];
            
            const operatorOptions = operators.map(op => 
                `<option value="${op.value}" ${condition.operator === op.value ? 'selected' : ''}>${op.label}</option>`
            ).join('');
            
            const hideValue = ['is_empty', 'is_not_empty'].includes(condition.operator);
            
            return `
                <div class="yap-single-condition" data-group-index="${groupIndex}" data-cond-index="${condIndex}">
                    <div class="yap-condition-row">
                        <select class="yap-cond-field" data-group-index="${groupIndex}" data-cond-index="${condIndex}">
                            <option value="">-- Wybierz pole --</option>
                            ${fieldOptions}
                        </select>
                        <select class="yap-cond-operator" data-group-index="${groupIndex}" data-cond-index="${condIndex}">
                            ${operatorOptions}
                        </select>
                        <input type="text" 
                               class="yap-cond-value" 
                               placeholder="Wartość" 
                               value="${condition.value || ''}"
                               data-group-index="${groupIndex}" 
                               data-cond-index="${condIndex}"
                               style="display: ${hideValue ? 'none' : 'block'}">
                        <button type="button" class="yap-condition-remove" data-group-index="${groupIndex}" data-cond-index="${condIndex}">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            `;
        },
        
        /**
         * Bind multi-condition events
         */
        bindMultiConditionEvents(field) {
            const self = this;
            
            // Add condition
            $(document).off('click', '.yap-add-condition-btn').on('click', '.yap-add-condition-btn', function() {
                const groupIndex = $(this).data('group-index');
                field.condition_groups[groupIndex].conditions.push({
                    field: '',
                    operator: '==',
                    value: ''
                });
                self.refreshConditionalUI(field);
                window.YAPBuilderExt.toast('Dodano nowy warunek', 'success');
            });
            
            // Remove condition
            $(document).off('click', '.yap-condition-remove').on('click', '.yap-condition-remove', function() {
                const groupIndex = $(this).data('group-index');
                const condIndex = $(this).data('cond-index');
                field.condition_groups[groupIndex].conditions.splice(condIndex, 1);
                
                // Remove group if no conditions left
                if (field.condition_groups[groupIndex].conditions.length === 0) {
                    field.condition_groups.splice(groupIndex, 1);
                }
                
                self.refreshConditionalUI(field);
                window.YAPBuilderExt.toast('Usunięto warunek', 'info');
            });
            
            // Add group
            $(document).off('click', '.yap-add-group-btn').on('click', '.yap-add-group-btn', function() {
                field.condition_groups.push({
                    logic: 'AND',
                    conditions: [{ field: '', operator: '==', value: '' }]
                });
                self.refreshConditionalUI(field);
                window.YAPBuilderExt.toast('Dodano nową grupę warunków', 'success');
            });
            
            // Remove group
            $(document).off('click', '.yap-condition-group-remove').on('click', '.yap-condition-group-remove', function() {
                const groupIndex = $(this).data('group-index');
                field.condition_groups.splice(groupIndex, 1);
                self.refreshConditionalUI(field);
                window.YAPBuilderExt.toast('Usunięto grupę', 'info');
            });
            
            // Update group logic
            $(document).off('change', '.yap-group-logic').on('change', '.yap-group-logic', function() {
                const groupIndex = $(this).data('group-index');
                field.condition_groups[groupIndex].logic = $(this).val();
            });
            
            // Update condition field
            $(document).off('change', '.yap-cond-field').on('change', '.yap-cond-field', function() {
                const groupIndex = $(this).data('group-index');
                const condIndex = $(this).data('cond-index');
                field.condition_groups[groupIndex].conditions[condIndex].field = $(this).val();
            });
            
            // Update condition operator
            $(document).off('change', '.yap-cond-operator').on('change', '.yap-cond-operator', function() {
                const groupIndex = $(this).data('group-index');
                const condIndex = $(this).data('cond-index');
                const operator = $(this).val();
                field.condition_groups[groupIndex].conditions[condIndex].operator = operator;
                
                // Hide value input for is_empty/is_not_empty
                const $row = $(this).closest('.yap-condition-row');
                if (['is_empty', 'is_not_empty'].includes(operator)) {
                    $row.find('.yap-cond-value').hide();
                } else {
                    $row.find('.yap-cond-value').show();
                }
            });
            
            // Update condition value
            $(document).off('input', '.yap-cond-value').on('input', '.yap-cond-value', function() {
                const groupIndex = $(this).data('group-index');
                const condIndex = $(this).data('cond-index');
                field.condition_groups[groupIndex].conditions[condIndex].value = $(this).val();
            });
        },
        
        /**
         * Refresh conditional UI
         */
        refreshConditionalUI(field) {
            const html = this.renderMultiConditions(field);
            $('.yap-conditional-rules').html(`
                <div class="yap-conditional-section" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Akcja</label>
                    <select class="yap-conditional-action" style="width: 100%;">
                        <option value="show" ${(field.conditional_action || 'show') === 'show' ? 'selected' : ''}>Pokaż to pole</option>
                        <option value="hide" ${field.conditional_action === 'hide' ? 'selected' : ''}>Ukryj to pole</option>
                        <option value="message" ${field.conditional_action === 'message' ? 'selected' : ''}>Pokaż komunikat</option>
                        <option value="error" ${field.conditional_action === 'error' ? 'selected' : ''}>Pokaż błąd</option>
                        <option value="disable" ${field.conditional_action === 'disable' ? 'selected' : ''}>Wyłącz pole</option>
                        <option value="enable" ${field.conditional_action === 'enable' ? 'selected' : ''}>Włącz pole</option>
                    </select>
                </div>
                
                <div class="yap-conditional-message-box" style="display: ${['message', 'error'].includes(field.conditional_action) ? 'block' : 'none'}; margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">${field.conditional_action === 'error' ? 'Tekst błędu' : 'Tekst komunikatu'}</label>
                    <textarea class="yap-conditional-message" style="width: 100%; padding: 8px; min-height: 60px;" placeholder="Wpisz komunikat...">${field.conditional_message || ''}</textarea>
                </div>
                
                ${html}
            `);
            
            this.bindMultiConditionEvents(field);
            
            // Re-bind action/message events
            if (window.YAPBuilder && window.YAPBuilder.bindFieldSettings) {
                window.YAPBuilder.bindFieldSettings(field);
            }
        }
    };
    
})(jQuery);
