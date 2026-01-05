/**
 * YAP Conditional Logic Handler
 * Obsługa logiki warunkowej w interfejsie admina
 */
(function($) {
    'use strict';
    
    const YAP_Conditional = {
        
        /**
         * Inicjalizacja
         */
        init: function() {
            this.bindEvents();
            this.checkAllConditions();
        },
        
        /**
         * Podpinanie eventów
         */
        bindEvents: function() {
            $(document).on('change', '[data-conditional-field]', function() {
                YAP_Conditional.checkAllConditions();
            });
        },
        
        /**
         * Sprawdź wszystkie warunki
         */
        checkAllConditions: function() {
            $('[data-conditional-logic]').each(function() {
                const $field = $(this);
                const conditions = $field.data('conditional-logic');
                
                if (YAP_Conditional.evaluateConditions(conditions)) {
                    $field.slideDown(200);
                } else {
                    $field.slideUp(200);
                }
            });
        },
        
        /**
         * Oceń warunki
         */
        evaluateConditions: function(conditions) {
            if (!conditions || !conditions.rules || conditions.rules.length === 0) {
                return true;
            }
            
            const logic = conditions.logic || 'and';
            const results = [];
            
            conditions.rules.forEach(function(rule) {
                const fieldValue = YAP_Conditional.getFieldValue(rule.field);
                results.push(YAP_Conditional.evaluateRule(fieldValue, rule.operator, rule.value));
            });
            
            if (logic === 'or') {
                return results.includes(true);
            } else {
                return !results.includes(false);
            }
        },
        
        /**
         * Pobierz wartość pola
         */
        getFieldValue: function(fieldName) {
            const $field = $('[name="' + fieldName + '"]');
            
            if ($field.length === 0) {
                return null;
            }
            
            if ($field.is(':checkbox')) {
                return $field.is(':checked') ? '1' : '0';
            } else if ($field.is(':radio')) {
                return $field.filter(':checked').val();
            } else {
                return $field.val();
            }
        },
        
        /**
         * Oceń pojedynczą regułę
         */
        evaluateRule: function(fieldValue, operator, ruleValue) {
            switch (operator) {
                case '==':
                    return fieldValue == ruleValue;
                case '!=':
                    return fieldValue != ruleValue;
                case '>':
                    return parseFloat(fieldValue) > parseFloat(ruleValue);
                case '<':
                    return parseFloat(fieldValue) < parseFloat(ruleValue);
                case '>=':
                    return parseFloat(fieldValue) >= parseFloat(ruleValue);
                case '<=':
                    return parseFloat(fieldValue) <= parseFloat(ruleValue);
                case 'contains':
                    return fieldValue && fieldValue.indexOf(ruleValue) !== -1;
                case 'not_contains':
                    return !fieldValue || fieldValue.indexOf(ruleValue) === -1;
                case 'starts_with':
                    return fieldValue && fieldValue.indexOf(ruleValue) === 0;
                case 'ends_with':
                    return fieldValue && fieldValue.lastIndexOf(ruleValue) === (fieldValue.length - ruleValue.length);
                case 'empty':
                    return !fieldValue || fieldValue === '';
                case 'not_empty':
                    return fieldValue && fieldValue !== '';
                case 'in':
                    const inArray = Array.isArray(ruleValue) ? ruleValue : ruleValue.split(',');
                    return inArray.includes(fieldValue);
                case 'not_in':
                    const notInArray = Array.isArray(ruleValue) ? ruleValue : ruleValue.split(',');
                    return !notInArray.includes(fieldValue);
                default:
                    return false;
            }
        }
    };
    
    /**
     * YAP Repeater Handler
     * Obsługa repeatera w interfejsie admina
     */
    const YAP_Repeater = {
        
        /**
         * Inicjalizacja
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Podpinanie eventów
         */
        bindEvents: function() {
            // Dodaj rząd
            $(document).on('click', '.yap-repeater-add-row', function(e) {
                e.preventDefault();
                const $repeater = $(this).closest('.yap-repeater-field');
                YAP_Repeater.addRow($repeater);
            });
            
            // Usuń rząd
            $(document).on('click', '.yap-repeater-remove-row', function(e) {
                e.preventDefault();
                const $row = $(this).closest('.yap-repeater-row');
                YAP_Repeater.removeRow($row);
            });
            
            // Sortowanie rzędów
            $('.yap-repeater-rows').sortable({
                handle: '.yap-repeater-sort-handle',
                axis: 'y',
                update: function(event, ui) {
                    YAP_Repeater.updateRowIndexes($(this));
                }
            });
        },
        
        /**
         * Dodaj rząd
         */
        addRow: function($repeater) {
            const $template = $repeater.find('.yap-repeater-row-template');
            const $rows = $repeater.find('.yap-repeater-rows');
            const rowIndex = $rows.find('.yap-repeater-row').length;
            
            let $newRow = $template.clone();
            $newRow.removeClass('yap-repeater-row-template').addClass('yap-repeater-row').show();
            
            // Zaktualizuj indeksy w nazwach pól
            $newRow.find('[name*="[INDEX]"]').each(function() {
                const name = $(this).attr('name').replace('[INDEX]', '[' + rowIndex + ']');
                $(this).attr('name', name);
            });
            
            $rows.append($newRow);
            this.checkMinMax($repeater);
        },
        
        /**
         * Usuń rząd
         */
        removeRow: function($row) {
            const $repeater = $row.closest('.yap-repeater-field');
            
            $row.fadeOut(200, function() {
                $(this).remove();
                YAP_Repeater.updateRowIndexes($repeater.find('.yap-repeater-rows'));
                YAP_Repeater.checkMinMax($repeater);
            });
        },
        
        /**
         * Zaktualizuj indeksy rzędów
         */
        updateRowIndexes: function($container) {
            $container.find('.yap-repeater-row').each(function(index) {
                $(this).find('[name]').each(function() {
                    const name = $(this).attr('name');
                    const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', newName);
                });
                
                $(this).find('.yap-repeater-row-number').text(index + 1);
            });
        },
        
        /**
         * Sprawdź limity min/max
         */
        checkMinMax: function($repeater) {
            const $rows = $repeater.find('.yap-repeater-row');
            const count = $rows.length;
            const min = parseInt($repeater.data('min')) || 0;
            const max = parseInt($repeater.data('max')) || 0;
            
            const $addButton = $repeater.find('.yap-repeater-add-row');
            const $removeButtons = $rows.find('.yap-repeater-remove-row');
            
            // Sprawdź max
            if (max > 0 && count >= max) {
                $addButton.prop('disabled', true).addClass('disabled');
            } else {
                $addButton.prop('disabled', false).removeClass('disabled');
            }
            
            // Sprawdź min
            if (min > 0 && count <= min) {
                $removeButtons.prop('disabled', true).addClass('disabled');
            } else {
                $removeButtons.prop('disabled', false).removeClass('disabled');
            }
        }
    };
    
    /**
     * YAP Flexible Content Handler
     * Obsługa flexible content w interfejsie admina
     */
    const YAP_Flexible = {
        
        /**
         * Inicjalizacja
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Podpinanie eventów
         */
        bindEvents: function() {
            // Dodaj layout
            $(document).on('click', '.yap-flexible-add-layout', function(e) {
                e.preventDefault();
                const layoutType = $(this).data('layout-type');
                const $flexible = $(this).closest('.yap-flexible-field');
                YAP_Flexible.addLayout($flexible, layoutType);
            });
            
            // Usuń layout
            $(document).on('click', '.yap-flexible-remove-layout', function(e) {
                e.preventDefault();
                const $layout = $(this).closest('.yap-flexible-layout');
                YAP_Flexible.removeLayout($layout);
            });
            
            // Toggle layout
            $(document).on('click', '.yap-flexible-layout-toggle', function(e) {
                e.preventDefault();
                const $layout = $(this).closest('.yap-flexible-layout');
                $layout.find('.yap-flexible-layout-content').slideToggle(200);
                $(this).toggleClass('collapsed');
            });
            
            // Sortowanie layoutów
            $('.yap-flexible-layouts').sortable({
                handle: '.yap-flexible-layout-handle',
                axis: 'y',
                update: function(event, ui) {
                    YAP_Flexible.updateLayoutIndexes($(this));
                }
            });
        },
        
        /**
         * Dodaj layout
         */
        addLayout: function($flexible, layoutType) {
            const $template = $flexible.find('.yap-flexible-layout-template[data-layout-type="' + layoutType + '"]');
            const $layouts = $flexible.find('.yap-flexible-layouts');
            const layoutIndex = $layouts.find('.yap-flexible-layout').length;
            
            let $newLayout = $template.clone();
            $newLayout.removeClass('yap-flexible-layout-template').addClass('yap-flexible-layout').show();
            
            // Zaktualizuj indeksy
            $newLayout.find('[name*="[INDEX]"]').each(function() {
                const name = $(this).attr('name').replace('[INDEX]', '[' + layoutIndex + ']');
                $(this).attr('name', name);
            });
            
            $layouts.append($newLayout);
        },
        
        /**
         * Usuń layout
         */
        removeLayout: function($layout) {
            $layout.fadeOut(200, function() {
                const $container = $(this).closest('.yap-flexible-layouts');
                $(this).remove();
                YAP_Flexible.updateLayoutIndexes($container);
            });
        },
        
        /**
         * Zaktualizuj indeksy layoutów
         */
        updateLayoutIndexes: function($container) {
            $container.find('.yap-flexible-layout').each(function(index) {
                $(this).find('[name]').each(function() {
                    const name = $(this).attr('name');
                    const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', newName);
                });
                
                $(this).find('.yap-flexible-layout-number').text(index + 1);
            });
        }
    };
    
    /**
     * YAP Validation Handler
     * Walidacja po stronie klienta
     */
    const YAP_Validation = {
        
        /**
         * Inicjalizacja
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Podpinanie eventów
         */
        bindEvents: function() {
            // Walidacja przed submitem
            $('form[data-yap-validate]').on('submit', function(e) {
                if (!YAP_Validation.validateForm($(this))) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Walidacja na blur
            $(document).on('blur', '[data-validation-rules]', function() {
                YAP_Validation.validateField($(this));
            });
        },
        
        /**
         * Waliduj formularz
         */
        validateForm: function($form) {
            let isValid = true;
            
            $form.find('[data-validation-rules]').each(function() {
                if (!YAP_Validation.validateField($(this))) {
                    isValid = false;
                }
            });
            
            return isValid;
        },
        
        /**
         * Waliduj pojedyncze pole
         */
        validateField: function($field) {
            const rules = $field.data('validation-rules');
            const value = $field.val();
            const fieldName = $field.attr('placeholder') || 'To pole';
            
            const errors = [];
            
            // Required
            if (rules.required && (!value || value === '')) {
                errors.push(fieldName + ' jest wymagane.');
            }
            
            // Min length
            if (rules.min_length && value && value.length < rules.min_length) {
                errors.push(fieldName + ' musi mieć minimum ' + rules.min_length + ' znaków.');
            }
            
            // Max length
            if (rules.max_length && value && value.length > rules.max_length) {
                errors.push(fieldName + ' może mieć maksymalnie ' + rules.max_length + ' znaków.');
            }
            
            // Pattern
            if (rules.pattern && value) {
                const regex = new RegExp(rules.pattern);
                if (!regex.test(value)) {
                    errors.push(rules.pattern_message || fieldName + ' ma nieprawidłowy format.');
                }
            }
            
            // Wyświetl błędy
            YAP_Validation.showErrors($field, errors);
            
            return errors.length === 0;
        },
        
        /**
         * Pokaż błędy
         */
        showErrors: function($field, errors) {
            const $wrapper = $field.closest('.field-wrapper');
            $wrapper.find('.yap-validation-error').remove();
            
            if (errors.length > 0) {
                $field.addClass('error');
                errors.forEach(function(error) {
                    $wrapper.append('<span class="yap-validation-error">' + error + '</span>');
                });
            } else {
                $field.removeClass('error');
            }
        }
    };
    
    // Inicjalizacja po załadowaniu DOM
    $(document).ready(function() {
        YAP_Conditional.init();
        YAP_Repeater.init();
        YAP_Flexible.init();
        YAP_Validation.init();
    });
    
})(jQuery);
