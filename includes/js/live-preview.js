/**
 * YAP Live Preview - Frontend JavaScript
 * 
 * Handles real-time preview updates and device switching
 */

(function($) {
    'use strict';
    
    const YAPLivePreview = {
        modal: null,
        iframe: null,
        currentTemplate: null,
        currentDevice: 'desktop',
        updateTimeout: null,
        
        init() {
            this.modal = $('#yap-preview-modal');
            this.iframe = $('#yap-preview-frame');
            
            this.bindEvents();
            this.addPreviewButton();
        },
        
        /**
         * Add "Live Preview" button to YAP field groups
         */
        addPreviewButton() {
            // Add button to each YAP meta box
            $('.yap-field-group-meta-box').each(function() {
                const $metaBox = $(this);
                const $header = $metaBox.find('.postbox-header, h2').first();
                
                if ($header.length && !$header.find('.yap-preview-trigger').length) {
                    $header.append(
                        '<button type="button" class="button button-small yap-preview-trigger" style="float: right; margin: 5px 10px;">' +
                        '<span class="dashicons dashicons-visibility" style="vertical-align: middle;"></span> Live Preview' +
                        '</button>'
                    );
                }
            });
        },
        
        /**
         * Bind event handlers
         */
        bindEvents() {
            // Open preview modal
            $(document).on('click', '.yap-preview-trigger', (e) => {
                e.preventDefault();
                this.openModal();
            });
            
            // Close modal
            $('#yap-preview-close, .yap-modal-overlay').on('click', () => {
                this.closeModal();
            });
            
            // Template selection
            $('#yap-preview-template').on('change', (e) => {
                this.currentTemplate = $(e.target).val();
                this.updatePreview();
            });
            
            // Device switching
            $('.yap-device-btn').on('click', (e) => {
                const $btn = $(e.currentTarget);
                this.currentDevice = $btn.data('device');
                
                $('.yap-device-btn').removeClass('active');
                $btn.addClass('active');
                
                $('#yap-preview-container')
                    .removeClass('yap-preview-desktop yap-preview-tablet yap-preview-mobile')
                    .addClass('yap-preview-' + this.currentDevice);
            });
            
            // Refresh preview
            $('#yap-preview-refresh').on('click', () => {
                this.updatePreview();
            });
            
            // Watch field changes for live updates
            $(document).on('input change', '.yap-field input, .yap-field textarea, .yap-field select', () => {
                clearTimeout(this.updateTimeout);
                this.updateTimeout = setTimeout(() => {
                    this.updatePreview();
                }, 500); // Debounce 500ms
            });
            
            // ESC key to close
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.modal.is(':visible')) {
                    this.closeModal();
                }
            });
        },
        
        /**
         * Open preview modal
         */
        openModal() {
            this.modal.fadeIn(200);
            this.updatePreview();
        },
        
        /**
         * Close preview modal
         */
        closeModal() {
            this.modal.fadeOut(200);
        },
        
        /**
         * Update preview with current field values
         */
        updatePreview() {
            if (!this.currentTemplate) {
                this.setStatus('Please select a template', 'warning');
                this.iframe[0].srcdoc = '<div style="padding: 40px; text-align: center; color: #666;">Please select a template from the dropdown above</div>';
                return;
            }
            
            this.setStatus('Loading preview...', 'loading');
            
            const fieldValues = this.collectFieldValues();
            const postId = $('#post_ID').val() || 0;
            
            $.ajax({
                url: yapLivePreview.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'yap_render_preview',
                    nonce: yapLivePreview.nonce,
                    template_id: this.currentTemplate,
                    field_values: fieldValues,
                    post_id: postId
                },
                success: (response) => {
                    if (response.success) {
                        this.iframe[0].srcdoc = response.data.html;
                        this.setStatus('Preview updated', 'success');
                        
                        // Auto-clear success message
                        setTimeout(() => {
                            this.setStatus('Ready', 'ready');
                        }, 2000);
                    } else {
                        this.setStatus('Error: ' + response.data.message, 'error');
                    }
                },
                error: (xhr) => {
                    this.setStatus('Preview failed to load', 'error');
                    console.error('Preview error:', xhr);
                }
            });
        },
        
        /**
         * Collect all field values from the page
         */
        collectFieldValues() {
            const values = {};
            
            // Collect from YAP fields
            $('.yap-field').each(function() {
                const $field = $(this);
                const fieldName = $field.data('field-name') || $field.find('[name]').attr('name');
                
                if (!fieldName) return;
                
                const $input = $field.find('input, textarea, select').first();
                
                if ($input.attr('type') === 'checkbox') {
                    values[fieldName] = $input.is(':checked');
                } else if ($input.attr('type') === 'radio') {
                    values[fieldName] = $field.find('input:checked').val();
                } else if ($input.is('select[multiple]')) {
                    values[fieldName] = $input.val() || [];
                } else {
                    values[fieldName] = $input.val();
                }
            });
            
            // Also collect from standard WordPress fields if needed
            values.post_title = $('#title').val();
            values.post_content = $('#content').val();
            
            return values;
        },
        
        /**
         * Set status message
         */
        setStatus(message, type = 'ready') {
            const $status = $('#yap-preview-status');
            
            const icons = {
                loading: '⏳',
                success: '✅',
                error: '❌',
                warning: '⚠️',
                ready: '✓'
            };
            
            const colors = {
                loading: '#0073aa',
                success: '#46b450',
                error: '#dc3232',
                warning: '#f0b849',
                ready: '#666'
            };
            
            $status
                .html(icons[type] + ' ' + message)
                .css('color', colors[type]);
        }
    };
    
    // Initialize on document ready
    $(document).ready(() => {
        YAPLivePreview.init();
    });
    
    // Make available globally
    window.YAPLivePreview = YAPLivePreview;
    
})(jQuery);
