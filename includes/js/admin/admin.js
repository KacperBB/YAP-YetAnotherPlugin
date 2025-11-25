jQuery(document).ready(function ($) { 
    console.log("✅ jQuery loaded! YAP Admin.js Version 1.0.4 - FIXED");
    console.log("✅ yap_ajax config:", typeof yap_ajax !== 'undefined' ? yap_ajax : 'ERROR: yap_ajax is undefined!');

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
            alert("Group name is required.");
            return;
        }
    
        if (!postType) {
            alert("Post type is required.");
            return;
        }
    
        if (!category) {
            alert("Category is required.");
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
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert("Error: " + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error("❌ AJAX Error:", status, error, xhr.responseText);
                alert("AJAX request failed. See console for details.");
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
            alert("Błąd: Brak nazwy tabeli!");
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
                    alert("Grupa została pomyślnie zaktualizowana!");
                    location.reload();
                } else {
                    alert("❌ Błąd: " + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error("❌ AJAX Error:", status, error, xhr.responseText);
                alert("Błąd AJAX: " + xhr.responseText);
            }
        });
    });

    $('.delete-field').click(function (e) {
        e.preventDefault();

        var fieldId = $(this).data('id');
        var tableName = $(this).closest('form').find('input[name="table_name"]').val();
        var isNested = $(this).closest('.nested-group').length > 0; // Sprawdzamy, czy to pole zagnieżdżone

        if (!confirm("Czy na pewno chcesz usunąć to pole?")) return;

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
                    alert("✅ Pole zostało usunięte.");
                    location.reload();
                } else {
                    alert("❌ Błąd: " + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error("❌ AJAX Delete Error:", status, error, xhr.responseText);
            }
        });
    });

    // Obsługa dodawania pól
    $('#add-field-form').submit(function (e) {
        e.preventDefault();
    
        var tableName = $('#table_name').val();
        if (!tableName) {
            alert("Brakuje nazwy tabeli. Nie można dodać pola.");
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
                        alert("Zagnieżdżona grupa została pomyślnie utworzona!");
                        location.reload();
                    } else {
                        console.error("❌ Błąd tworzenia zagnieżdżonej grupy:", response.data);
                        alert("Błąd: " + response.data);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("❌ AJAX Request Failed for nested group!");
                    console.error("Status:", status);
                    console.error("Error:", error);
                    console.error("Response:", xhr.responseText);
                    alert("Błąd AJAX (zagnieżdżona grupa): " + xhr.responseText);
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
                    alert("Pole zostało dodane pomyślnie!");
                    location.reload();
                } else {
                    console.error("❌ AJAX Error (server response):", response);
                    alert("Błąd: " + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error("❌ AJAX Request Failed!");
                console.error("Status:", status);
                console.error("Error:", error);
                console.error("Full Response:", xhr.responseText);
                alert("Błąd AJAX: " + xhr.responseText);
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
            alert('Proszę uzupełnić nazwę pola i wybrać typ.');
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
                                    alert('Zagnieżdżona grupa została utworzona.');
                                    location.reload();
                                } else {
                                    console.error('❌ Błąd tworzenia tabeli: ' + response2.data);
                                    alert('Nie udało się utworzyć tabeli zagnieżdżonej grupy: ' + response2.data);
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('❌ AJAX Error (nested table):', error);
                                alert('Błąd tworzenia tabeli zagnieżdżonej grupy.');
                            }
                        });
                    } else {
                        console.error('❌ Błąd: ' + response.data);
                        alert('Nie udało się dodać pola: ' + response.data);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('❌ AJAX Error:', error);
                    console.error('Response:', xhr.responseText);
                    alert('Błąd dodawania pola.');
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
                        alert('Pole zostało dodane.');
                        location.reload();
                    } else {
                        console.error('❌ Błąd: ' + response.data);
                        alert('Nie udało się dodać pola: ' + response.data);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('❌ AJAX Error:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                    alert('Błąd AJAX.');
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
            alert("Nazwa pola jest wymagana!");
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
                    alert("Pole zostało dodane!");
                    location.reload();
                } else {
                    console.error("❌ Server error:", response.data);
                    alert("Błąd: " + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error("❌ AJAX Error!");
                console.error("Status:", status);
                console.error("Error:", error);
                console.error("Response Text:", xhr.responseText);
                alert("Błąd AJAX - sprawdź konsolę (F12)");
            }
        });
        
        return false;
    });
});
