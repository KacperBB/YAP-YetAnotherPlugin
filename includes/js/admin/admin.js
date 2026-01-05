jQuery(document).ready(function ($) { 
    console.log("✅ jQuery loaded! YAP Admin.js Version 1.0.5 - Toast Notifications");
    console.log("✅ yap_ajax config:", typeof yap_ajax !== 'undefined' ? yap_ajax : 'ERROR: yap_ajax is undefined!');

    // Toast notification helper
    function showToast(message, type = 'success') {
        // Użyj YAPBuilderExt jeśli dostępny (z Visual Builder)
        if (window.YAPBuilderExt && window.YAPBuilderExt.toast) {
            window.YAPBuilderExt.toast(message, type);
        } 
        // Użyj yapShowToast jeśli dostępny (z admin-page)
        else if (window.yapShowToast) {
            window.yapShowToast(message, type);
        }
        // Fallback: utwórz prosty toast
        else {
            const $toast = $('<div class="yap-toast yap-toast-' + type + '">' + message + '</div>');
            $toast.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '12px 20px',
                background: type === 'error' ? '#dc3545' : (type === 'info' ? '#17a2b8' : '#28a745'),
                color: 'white',
                borderRadius: '6px',
                zIndex: 100000,
                boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                opacity: 0,
                transition: 'opacity 0.3s'
            });
            $('body').append($toast);
            setTimeout(() => $toast.css('opacity', '1'), 10);
            setTimeout(() => {
                $toast.css('opacity', '0');
                setTimeout(() => $toast.remove(), 300);
            }, 3000);
        }
    }

    // FORCE HIDE WordPress update notice - JavaScript backup
    // This ensures the notice is hidden even if CSS fails
    $(document).ready(function() {
        $('.update-nag, .notice.update-nag, div.notice-warning.update-nag').each(function() {
            $(this).remove(); // Completely remove from DOM
        });
        
        // Double-check after a short delay
        setTimeout(function() {
            $('.update-nag').remove();
        }, 100);
    });

    // WordPress Media Uploader for image fields (metaboxes in posts)
    var mediaUploader;
    
    $(document).on('click', '.yap-upload-image-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var fieldId = button.data('field');
        var previewImg = button.siblings('.yap-image-preview');
        var removeBtn = button.siblings('.yap-remove-image-button');
        var hiddenInput = $('#' + fieldId);
        
        // If the uploader object has already been created, reopen the dialog
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        // Extend the wp.media object
        mediaUploader = wp.media({
            title: 'Wybierz obraz',
            button: {
                text: 'Użyj tego obrazu'
            },
            multiple: false
        });
        
        // When a file is selected, grab the URL and set it as the text field's value
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            hiddenInput.val(attachment.id);
            previewImg.attr('src', attachment.url).show();
            removeBtn.show();
        });
        
        // Open the uploader dialog
        mediaUploader.open();
    });
    
    // Remove image (metaboxes)
    $(document).on('click', '.yap-remove-image-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var fieldId = button.siblings('.yap-upload-image-button').data('field');
        var previewImg = button.siblings('.yap-image-preview');
        var hiddenInput = $('#' + fieldId);
        
        hiddenInput.val('');
        previewImg.attr('src', '').hide();
        button.hide();
    });

    // ====================================================================
    // FILE FIELD HANDLERS
    // ====================================================================
    
    // WordPress Media Uploader for file fields
    $(document).on('click', '.yap-upload-file-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var fieldId = button.data('field');
        var hiddenInput = $('#' + fieldId);
        var wrapper = button.closest('.yap-file-field-wrapper');
        
        var fileUploader = wp.media({
            title: 'Wybierz plik',
            button: {
                text: 'Użyj tego pliku'
            },
            multiple: false,
            library: {
                type: '' // Allow all file types
            }
        });
        
        fileUploader.on('select', function() {
            var attachment = fileUploader.state().get('selection').first().toJSON();
            hiddenInput.val(attachment.id);
            
            // Update preview
            var previewHtml = '<div class="yap-file-preview" style="margin-top: 10px; padding: 8px; background: #f5f5f5; border-radius: 4px;">' +
                '<a href="' + attachment.url + '" target="_blank" style="text-decoration: none;">📄 ' + attachment.filename + '</a>' +
                '<button type="button" class="button yap-remove-file-button" style="margin-left: 10px;">Usuń</button>' +
                '</div>';
            
            wrapper.find('.yap-file-preview').remove();
            wrapper.append(previewHtml);
        });
        
        fileUploader.open();
    });
    
    // Remove file
    $(document).on('click', '.yap-remove-file-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var wrapper = button.closest('.yap-file-field-wrapper');
        var hiddenInput = wrapper.find('.yap-file-id');
        
        hiddenInput.val('');
        button.closest('.yap-file-preview').remove();
    });

    // ====================================================================
    // GALLERY FIELD HANDLERS
    // ====================================================================
    
    // WordPress Media Uploader for gallery fields
    $(document).on('click', '.yap-add-gallery-images', function(e) {
        e.preventDefault();
        var button = $(this);
        var fieldId = button.data('field');
        var hiddenInput = $('#' + fieldId);
        var wrapper = button.closest('.yap-gallery-field-wrapper');
        var preview = wrapper.find('.yap-gallery-preview');
        
        var galleryUploader = wp.media({
            title: 'Wybierz obrazy',
            button: {
                text: 'Dodaj obrazy'
            },
            multiple: true,
            library: {
                type: 'image'
            }
        });
        
        galleryUploader.on('select', function() {
            var attachments = galleryUploader.state().get('selection').toJSON();
            var currentIds = hiddenInput.val() ? hiddenInput.val().split(',') : [];
            
            attachments.forEach(function(attachment) {
                if (currentIds.indexOf(attachment.id.toString()) === -1) {
                    currentIds.push(attachment.id);
                    
                    // Add preview
                    var itemHtml = '<div class="yap-gallery-item" data-id="' + attachment.id + '" style="position: relative;">' +
                        '<img src="' + (attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">' +
                        '<button type="button" class="yap-remove-gallery-item" style="position: absolute; top: 5px; right: 5px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px; line-height: 1;">×</button>' +
                        '</div>';
                    preview.append(itemHtml);
                }
            });
            
            hiddenInput.val(currentIds.join(','));
        });
        
        galleryUploader.open();
    });
    
    // Remove single image from gallery
    $(document).on('click', '.yap-remove-gallery-item', function(e) {
        e.preventDefault();
        var button = $(this);
        var item = button.closest('.yap-gallery-item');
        var imageId = item.data('id');
        var wrapper = button.closest('.yap-gallery-field-wrapper');
        var hiddenInput = wrapper.find('.yap-gallery-ids');
        
        // Remove from array
        var currentIds = hiddenInput.val() ? hiddenInput.val().split(',') : [];
        currentIds = currentIds.filter(function(id) {
            return id != imageId;
        });
        
        hiddenInput.val(currentIds.join(','));
        item.remove();
    });

    // WordPress Media Uploader for image fields (edit group form)
    $(document).on('click', '.yap-upload-image-button-edit', function(e) {
        e.preventDefault();
        var button = $(this);
        var fieldId = button.data('field-id');
        var previewImg = $('.yap-image-preview-edit[data-field-id="' + fieldId + '"]');
        var removeBtn = $('.yap-remove-image-button-edit[data-field-id="' + fieldId + '"]');
        var hiddenInput = $('.yap-image-id-edit[data-field-id="' + fieldId + '"]');
        
        console.log("🖼️ Opening media uploader for field ID:", fieldId);
        
        var editMediaUploader = wp.media({
            title: 'Wybierz obraz',
            button: {
                text: 'Użyj tego obrazu'
            },
            multiple: false
        });
        
        editMediaUploader.on('select', function() {
            var attachment = editMediaUploader.state().get('selection').first().toJSON();
            console.log("✅ Image selected:", attachment.id, attachment.url);
            hiddenInput.val(attachment.id);
            previewImg.attr('src', attachment.url).show();
            removeBtn.show();
            button.text('Zmień obraz');
        });
        
        editMediaUploader.open();
    });
    
    // Remove image (edit group form)
    $(document).on('click', '.yap-remove-image-button-edit', function(e) {
        e.preventDefault();
        var button = $(this);
        var fieldId = button.data('field-id');
        var previewImg = $('.yap-image-preview-edit[data-field-id="' + fieldId + '"]');
        var hiddenInput = $('.yap-image-id-edit[data-field-id="' + fieldId + '"]');
        var uploadBtn = $('.yap-upload-image-button-edit[data-field-id="' + fieldId + '"]');
        
        console.log("🗑️ Removing image for field ID:", fieldId);
        hiddenInput.val('');
        previewImg.attr('src', '').hide();
        button.hide();
        uploadBtn.text('Wybierz obraz');
    });

    // WordPress Media Uploader for image fields (nested groups)
    $(document).on('click', '.yap-upload-image-button-nested', function(e) {
        e.preventDefault();
        var button = $(this);
        var fieldId = button.data('field-id');
        var previewImg = $('.yap-image-preview-nested[data-field-id="' + fieldId + '"]');
        var removeBtn = $('.yap-remove-image-button-nested[data-field-id="' + fieldId + '"]');
        var hiddenInput = $('.yap-image-id-nested[data-field-id="' + fieldId + '"]');
        
        console.log("🖼️ Opening media uploader for nested field ID:", fieldId);
        
        var nestedMediaUploader = wp.media({
            title: 'Wybierz obraz',
            button: {
                text: 'Użyj tego obrazu'
            },
            multiple: false
        });
        
        nestedMediaUploader.on('select', function() {
            var attachment = nestedMediaUploader.state().get('selection').first().toJSON();
            console.log("✅ Image selected for nested field:", attachment.id, attachment.url);
            hiddenInput.val(attachment.id);
            previewImg.attr('src', attachment.url).show();
            removeBtn.show();
            button.text('Zmień obraz');
        });
        
        nestedMediaUploader.open();
    });
    
    // Remove image (nested groups)
    $(document).on('click', '.yap-remove-image-button-nested', function(e) {
        e.preventDefault();
        var button = $(this);
        var fieldId = button.data('field-id');
        var previewImg = $('.yap-image-preview-nested[data-field-id="' + fieldId + '"]');
        var hiddenInput = $('.yap-image-id-nested[data-field-id="' + fieldId + '"]');
        var uploadBtn = $('.yap-upload-image-button-nested[data-field-id="' + fieldId + '"]');
        
        console.log("🗑️ Removing image for nested field ID:", fieldId);
        hiddenInput.val('');
        previewImg.attr('src', '').hide();
        button.hide();
        uploadBtn.text('Wybierz obraz');
    });

    $('#add_field').click(function () {
        var field = $('.field').first().clone();
        field.find('input').val('');
        field.find('select').val('short_text');
        $('#fields').append(field);
    });
    
    $('form#add-group-form').submit(function (e) {
        e.preventDefault();
    
        // Walidacja przed wysłaniem AJAX
        var groupName = $('#group_name').val().trim();
        var postType = $('#post_type').val();
        var category = $('#category').val();
    
        if (!groupName) {
            showToast("Nazwa grupy jest wymagana", 'error');
            return;
        }
    
        if (!postType) {
            showToast("Typ posta jest wymagany", 'error');
            return;
        }
    
        if (!category) {
            showToast("Kategoria jest wymagana", 'error');
            return;
        }
    
        var ajaxData = {
            action: 'yap_save_group',
            nonce: yap_ajax.nonce,
            group_name: groupName,
            post_type: postType,
            category: category
        };
    
        console.log("📤 Sending AJAX request:", ajaxData);
    
        $.ajax({
            url: yap_ajax.ajax_url,
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            success: function (response) {
                console.log("✅ AJAX Response:", response);
                if (response.success) {
                    showToast(response.data.message, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast("Błąd: " + response.data, 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error("❌ AJAX Error:", status, error, xhr.responseText);
                showToast("Błąd AJAX. Sprawdź konsolę.", 'error');
            }
        });
    });
    
     // 🔹 DEBUG: Sprawdź, czy przycisk istnieje

     $('#yap_update_group').on('click', function (e) {
        e.preventDefault();
        console.log("📢 Przycisk 'Zaktualizuj Grupę' kliknięty!");

        var form = $('#yap-edit-group-form');
        var formData = form.serialize();
        var tableName = form.find('input[name="table_name"]').val(); // 🔹 Pobranie ukrytej nazwy tabeli

        if (!tableName) {
            console.error("❌ ERROR: Brak nazwy tabeli!");
            showToast("Błąd: Brak nazwy tabeli!", 'error');
            return;
        }

        console.log("📤 Wysłane dane formularza:", formData);

        $.ajax({
            url: yap_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'yap_update_group',
                nonce: yap_ajax.nonce,
                table_name: tableName, // ✅ Naprawione przekazywanie
                form_data: formData
            },
            dataType: 'json',
            success: function (response) {
                console.log("✅ AJAX Success:", response);
                if (response.success) {
                    showToast("Grupa została pomyślnie zaktualizowana!", 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast("Błąd: " + response.data, 'error');
                }
            },
            error: function (xhr, status, error) {
                showToast("Błąd AJAX: " + xhr.responseText, 'error');
            }
        });
    });

    $('.delete-field').click(function (e) {
        e.preventDefault();

        var fieldId = $(this).data('id');
        var tableName = $(this).closest('form').find('input[name="table_name"]').val();
        var isNested = $(this).closest('.nested-group').length > 0;

        if (window.YAPBuilderExt && window.YAPBuilderExt.showDeleteModal) {
            const fieldLabel = $(this).closest('tr').find('.field-label').text() || 'to pole';
            YAPBuilderExt.showDeleteModal(fieldId, fieldLabel, function() {
                deleteFieldAjax(fieldId, tableName, isNested);
            });
        } else {
            // Fallback do confirm jeśli modal niedostępny
            if (confirm("⚠️ Czy na pewno chcesz usunąć to pole?")) {
                deleteFieldAjax(fieldId, tableName, isNested);
            }
        }
    });
    
    function deleteFieldAjax(fieldId, tableName, isNested) {
        $.ajax({
            url: yap_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'yap_delete_field',
                nonce: yap_ajax.nonce,
                field_id: fieldId,
                table_name: tableName,
                is_nested: isNested
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showToast("Pole zostało usunięte", 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast("Błąd: " + response.data, 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error("❌ AJAX Delete Error:", status, error, xhr.responseText);
                showToast("Błąd podczas usuwania pola", 'error');
            }
        });
    }

    // Obsługa dodawania pól
    $('#add-field-form').submit(function (e) {
        e.preventDefault();
    
        var tableName = $('#table_name').val();
        if (!tableName) {
            showToast("Brakuje nazwy tabeli. Nie można dodać pola.", 'error');
            return;
        }
    
        var fieldType = $('#new_field_type').val(); // Pobierz typ pola
        var ajaxData = {
            action: 'yap_add_field',
            nonce: yap_ajax.nonce,
            table_name: tableName,
            new_field_name: $('#new_field_name').val().trim(),
            new_field_type: fieldType,
            new_field_value: $('#new_field_value').val().trim()
        };
    
        console.log("📢 AJAX Request Data:", ajaxData);
    
        // Obsługa pola typu "nested_group"
        if (fieldType === 'nested_group') {
            console.log("⚙️ Wybrano typ 'nested_group', tworzenie zagnieżdżonej grupy...");
            $.ajax({
                url: yap_ajax.ajax_url,
                type: 'POST',
                data: ajaxData,
                dataType: 'json',
                beforeSend: function () {
                    console.log("📢 AJAX request for nested group initiated...");
                },
                success: function (response) {
                    console.log("✅ Nested Group Creation Response:", response);
                    if (response.success) {
                        showToast("Zagnieżdżona grupa została pomyślnie utworzona!", 'success');
                        setTimeout(() => location.reload(), 800);
                    } else {
                        console.error("❌ Błąd tworzenia zagnieżdżonej grupy:", response.data);
                        showToast("Błąd: " + response.data, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error("❌ AJAX Request Failed for nested group!");
                    console.error("Status:", status);
                    console.error("Error:", error);
                    console.error("Response:", xhr.responseText);
                    showToast("Błąd AJAX (zagnieżdżona grupa): " + xhr.responseText, 'error');
                }
            });
            return; // Zatrzymaj dalsze przetwarzanie, jeśli tworzymy "nested_group"
        }
    
        // Obsługa standardowego pola
        $.ajax({
            url: yap_ajax.ajax_url,
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            beforeSend: function () {
                console.log("📢 AJAX request initiated...");
            },
            success: function (response) {
                console.log("✅ AJAX Success Response:", response);
                if (response.success) {
                    showToast("Pole zostało dodane pomyślnie!", 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    console.error("❌ AJAX Error (server response):", response);
                    showToast("Błąd: " + response.data, 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error("❌ AJAX Request Failed!");
                console.error("Status:", status);
                console.error("Error:", error);
                console.error("Full Response:", xhr.responseText);
                showToast("Błąd AJAX: " + xhr.responseText, 'error');
            }
        });
    });
     
    // Delegacja zdarzeń dla dynamicznie dodanych elementów
    $(document).on('click', '.add-nested-field', function (e) {
        e.preventDefault();

        var $button = $(this);
        var $container = $button.closest('.nested-group').length ? $button.closest('.nested-group') : $button.closest('.wrap');
        var nestedTable = $button.data('nested-table');
        var fieldName = $container.find('input[name="new_nested_field_name"]').val().trim();
        var fieldType = $container.find('select[name="new_nested_field_type"]').val();
        var fieldValue = $container.find('input[name="new_nested_field_value"]').val().trim();
        var parentFieldId = $container.find('input[name="parent_field_id"]').val();

        console.log("🚀 Debugging AJAX Data:");
        console.log("Nested Table:", nestedTable);
        console.log("Field Name:", fieldName);
        console.log("Field Type:", fieldType);
        console.log("Field Value:", fieldValue);
        console.log("Parent Field ID:", parentFieldId);

        if (!fieldName || !fieldType || !nestedTable) {
            showToast('Proszę uzupełnić nazwę pola i wybrać typ.', 'error');
            return;
        }

        if (fieldType === 'nested_group') {
            console.log('Tworzenie zagnieżdżonej grupy...');
            
            // Najpierw dodaj pole typu nested_group
            $.ajax({
                url: yap_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'yap_add_nested_field',
                    nonce: yap_ajax.nonce,
                    nested_table_name: nestedTable,
                    field_name: fieldName,
                    field_type: fieldType,
                    field_value: '',
                    parent_field_id: parentFieldId || 0
                },
                success: function (response) {
                    console.log('✅ Nested group field created:', response);
                    if (response.success) {
                        // Teraz utwórz tabelę dla zagnieżdżonej grupy
                        var newParentFieldId = response.data.field_id || response.data;
                        
                        $.ajax({
                            url: yap_ajax.ajax_url,
                            method: 'POST',
                            data: {
                                action: 'yap_add_nested_group',
                                nonce: yap_ajax.nonce,
                                parent_table: nestedTable,
                                parent_field_id: newParentFieldId
                            },
                            success: function (response2) {
                                console.log('✅ Nested group table created:', response2);
                                if (response2.success) {
                                    showToast('Zagnieżdżona grupa została utworzona.', 'success');
                                    setTimeout(() => location.reload(), 800);
                                } else {
                                    console.error('❌ Błąd tworzenia tabeli: ' + response2.data);
                                    showToast('Nie udało się utworzyć tabeli zagnieżdżonej grupy: ' + response2.data, 'error');
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('❌ AJAX Error (nested table):', error);
                                showToast('Błąd tworzenia tabeli zagnieżdżonej grupy.', 'error');
                            }
                        });
                    } else {
                        console.error('❌ Błąd: ' + response.data);
                        showToast('Nie udało się dodać pola: ' + response.data, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('❌ AJAX Error:', error);
                    console.error('Response:', xhr.responseText);
                    showToast('Błąd dodawania pola.', 'error');
                }
            });
        } else {
            console.log('Dodawanie zwykłego pola...');
            $.ajax({
                url: yap_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'yap_add_nested_field',
                    nonce: yap_ajax.nonce,
                    nested_table_name: nestedTable,
                    field_name: fieldName,
                    field_type: fieldType,
                    field_value: fieldValue,
                    parent_field_id: parentFieldId || 0
                },
                success: function (response) {
                    console.log('✅ Field creation response:', response);
                    if (response.success) {
                        showToast('Pole zostało dodane.', 'success');
                        setTimeout(() => location.reload(), 800);
                    } else {
                        console.error('❌ Błąd: ' + response.data);
                        showToast('Nie udało się dodać pola: ' + response.data, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('❌ AJAX Error:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                    showToast('Błąd AJAX.', 'error');
                }
            });
        }
    });
        
    document.querySelectorAll('.edit-field').forEach(function(button) {
        button.addEventListener('click', function() {
            var fieldId = this.getAttribute('data-id');
            // Implement edit functionality
        });
    });

    // Obsługa przycisku "Dodaj pole" - przechwytujemy kliknięcie PRZED submitem
    $(document).on('click', 'button[name="yap_add_field"]', function (e) {
        e.preventDefault();
        console.log("🚀 Przycisk 'Dodaj pole' kliknięty - wysyłam AJAX...");
        
        var $form = $('#yap-edit-group-form');
        var tableName = $form.find('input[name="table_name"]').val();
        var fieldName = $form.find('#new_field_name').val().trim();
        var fieldType = $form.find('#new_field_type').val();
        var fieldValue = $form.find('#new_field_value').val().trim();
        
        console.log("📊 Formularz dane:", {
            table_name: tableName,
            field_name: fieldName,
            field_type: fieldType,
            field_value: fieldValue
        });
        
        if (!fieldName) {
            showToast("Nazwa pola jest wymagana!", 'error');
            return false;
        }
        
        console.log("📤 Wysyłam AJAX do:", yap_ajax.ajax_url);
        
        $.ajax({
            url: yap_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'yap_add_field',
                nonce: yap_ajax.nonce,
                table_name: tableName,
                new_field_name: fieldName,
                new_field_type: fieldType,
                new_field_value: fieldValue
            },
            dataType: 'json',
            beforeSend: function() {
                console.log("⏳ AJAX request starting...");
            },
            success: function (response) {
                console.log("✅ AJAX Success Response:", response);
                if (response.success) {
                    showToast("Pole zostało dodane!", 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    console.error("❌ Server error:", response.data);
                    showToast("Błąd: " + response.data, 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error("❌ AJAX Error!");
                console.error("Status:", status);
                console.error("Error:", error);
                console.error("Response Text:", xhr.responseText);
                showToast("Błąd AJAX - sprawdź konsolę (F12)", 'error');
            }
        });
        
        return false;
    });

    // ========================================
    // REPEATER FUNCTIONALITY
    // ========================================
    
    // Add repeater row
    $(document).on('click', '.yap-add-repeater-row', function(e) {
        e.preventDefault();
        console.log("➕ Add repeater row clicked");
        
        var $btn = $(this);
        var $container = $btn.closest('.yap-repeater-container');
        var repeaterId = $container.data('repeater-id');
        var $rows = $container.find('.yap-repeater-rows');
        var maxRows = $container.data('max');
        
        console.log("Repeater ID:", repeaterId);
        console.log("Max rows:", maxRows);
        
        // Get template
        var $template = $('#' + repeaterId + '_template');
        if (!$template.length) {
            console.error("❌ Template not found:", repeaterId + '_template');
            showToast('Błąd: nie znaleziono szablonu repeatera', 'error');
            return;
        }
        
        var template = $template.html();
        console.log("Template found, length:", template.length);
        
        // Check max rows - use better selector
        var currentCount = $rows.children('.yap-repeater-row').length;
        console.log("Current rows:", currentCount);
        console.log("Rows container:", $rows);
        
        if (maxRows && maxRows > 0 && currentCount >= maxRows) {
            showToast('Osiągnięto maksymalną liczbę wierszy (' + maxRows + ')', 'error');
            return;
        }
        
        // Replace {{INDEX}} with actual index
        var newIndex = currentCount;
        var newRow = template.replace(/\{\{INDEX\}\}/g, newIndex);
        
        console.log("Appending new row HTML (first 200 chars):", newRow.substring(0, 200));
        
        // Remove template class and add active class
        newRow = newRow.replace('yap-repeater-row-template', 'yap-repeater-row');
        
        // Append new row
        $rows.append(newRow);
        
        var newCount = $rows.children('.yap-repeater-row').length;
        console.log("✅ New row added at index:", newIndex, "| Total rows now:", newCount);
        
        showToast('Wiersz dodany', 'success');
        
        // Reinitialize sortable if jQuery UI available
        if ($.fn.sortable && $rows.hasClass('ui-sortable')) {
            $rows.sortable('refresh');
        }
    });
    
    // Remove repeater row
    $(document).on('click', '.yap-remove-repeater-row', function(e) {
        e.preventDefault();
        console.log("🗑️ Remove repeater row clicked");
        
        var $btn = $(this);
        var $row = $btn.closest('.yap-repeater-row');
        var $container = $btn.closest('.yap-repeater-container');
        var minRows = $container.data('min');
        var currentCount = $container.find('.yap-repeater-rows > .yap-repeater-row').length;
        
        console.log("Current rows:", currentCount, "Min rows:", minRows);
        
        // Check min rows
        if (minRows && currentCount <= minRows) {
            showToast('Wymagana jest minimalna liczba wierszy (' + minRows + ')', 'error');
            return;
        }
        
        // Animate removal
        $row.fadeOut(200, function() {
            $(this).remove();
            console.log("✅ Row removed");
            showToast('Wiersz usunięty', 'success');
        });
    });
    
    // Initialize sortable for repeater rows
    function initRepeaterSortable() {
        if (!$.fn.sortable) {
            console.warn("⚠️ jQuery UI Sortable not available");
            return;
        }
        
        $('.yap-repeater-rows').each(function() {
            var $rows = $(this);
            if ($rows.hasClass('ui-sortable')) {
                return; // Already initialized
            }
            
            $rows.sortable({
                handle: '.yap-repeater-row-handle',
                placeholder: 'yap-repeater-row-placeholder',
                items: '> .yap-repeater-row',
                axis: 'y',
                cursor: 'move',
                opacity: 0.8,
                distance: 5,
                tolerance: 'pointer',
                start: function(e, ui) {
                    ui.placeholder.height(ui.item.height());
                },
                update: function(e, ui) {
                    console.log("✅ Rows reordered");
                }
            });
            
            console.log("✅ Sortable initialized for repeater");
        });
    }
    
    // Initialize on page load
    initRepeaterSortable();
    
    // Reinitialize after AJAX updates (if needed)
    $(document).ajaxComplete(function() {
        initRepeaterSortable();
    });
    
    // ====================================================================
    // CLICKABLE TOOLTIPS - Toggle on click, close on outside click
    // ====================================================================
    
    console.log('🎯 Tooltip handler initialized. Found tooltips:', $('.yap-field-tooltip[data-tooltip]').length);
    
    // Function to position tooltip intelligently
    function positionTooltip($tooltip) {
        const rect = $tooltip[0].getBoundingClientRect();
        const tooltipWidth = 320; // max-width from CSS
        const tooltipHeight = 80; // estimated height
        const spacing = 12;
        
        // Calculate position below the icon
        let top = rect.bottom + spacing;
        let left = rect.left + (rect.width / 2) - (tooltipWidth / 2);
        
        // Check if tooltip would go off right edge
        if (left + tooltipWidth > window.innerWidth - 20) {
            left = window.innerWidth - tooltipWidth - 20;
        }
        
        // Check if tooltip would go off left edge
        if (left < 20) {
            left = 20;
        }
        
        // Check if tooltip would go off bottom edge - show above if needed
        if (top + tooltipHeight > window.innerHeight - 20) {
            top = rect.top - tooltipHeight - spacing;
            // Change arrow direction when showing above
            $tooltip.attr('data-position', 'above');
        } else {
            $tooltip.attr('data-position', 'below');
        }
        
        // Apply positioning via CSS custom properties
        $tooltip[0].style.setProperty('--tooltip-top', top + 'px');
        $tooltip[0].style.setProperty('--tooltip-left', left + 'px');
        
        // Position arrow in center of icon
        const arrowLeft = rect.left + (rect.width / 2);
        $tooltip[0].style.setProperty('--arrow-left', arrowLeft + 'px');
        
        if ($tooltip.attr('data-position') === 'above') {
            $tooltip[0].style.setProperty('--arrow-top', (rect.top - spacing) + 'px');
        } else {
            $tooltip[0].style.setProperty('--arrow-top', (rect.bottom + spacing - 6) + 'px');
        }
    }
    
    // Use mousedown for better responsiveness
    $(document).on('mousedown', '.yap-field-tooltip[data-tooltip]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $tooltip = $(this);
        const wasActive = $tooltip.hasClass('yap-tooltip-active');
        
        console.log('🖱️ Tooltip clicked!', {
            element: this.tagName,
            wasActive: wasActive,
            tooltipText: $tooltip.data('tooltip')
        });
        
        // Close all other tooltips
        $('.yap-field-tooltip').removeClass('yap-tooltip-active');
        
        // Toggle this tooltip
        if (!wasActive) {
            positionTooltip($tooltip);
            $tooltip.addClass('yap-tooltip-active');
            console.log('✅ Tooltip shown:', $tooltip.data('tooltip'));
        } else {
            console.log('❌ Tooltip hidden');
        }
        
        return false;
    });
    
    // Prevent tooltip from moving when hovering over the icon or tooltip
    $(document).on('mouseenter mousemove', '.yap-field-tooltip[data-tooltip]', function(e) {
        e.stopPropagation();
        // Don't reposition - tooltip stays where it was initially placed
    });
    
    // Close tooltip when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.yap-field-tooltip').length) {
            $('.yap-field-tooltip').removeClass('yap-tooltip-active');
        }
    });
    
    // Close tooltip on Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            $('.yap-field-tooltip').removeClass('yap-tooltip-active');
        }
    });
});
