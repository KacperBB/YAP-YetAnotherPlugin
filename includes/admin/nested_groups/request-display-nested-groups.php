<?php

function yap_display_nested_group($nested_table_name, $parent_field_id, $depth = 1) {
    global $wpdb;

    // Pobierz pola z tabeli
    $fields = $wpdb->get_results("SELECT * FROM {$nested_table_name}");
    
    $indent_style = 'margin-left: ' . ($depth * 30) . 'px; border-left: 3px solid #' . dechex(200 - $depth * 20) . dechex(200 - $depth * 20) . 'ff; padding-left: 15px;';

    ?>
    <div class="wrap nested-group" data-nested-id="<?php echo esc_attr($nested_table_name); ?>" style="<?php echo $indent_style; ?> margin-top: 20px; background: #f9f9f9; padding: 15px; border-radius: 5px;">
        <h<?php echo min($depth + 2, 6); ?> style="color: #0073aa; margin-top: 0;">📁 Zagnieżdżona Grupa (Poziom <?php echo $depth; ?>)</h<?php echo min($depth + 2, 6); ?>>
        <p style="font-size: 12px; color: #666; margin: 5px 0;">Tabela: <code><?php echo esc_html($nested_table_name); ?></code></p>
        
        <!-- Hidden inputs for AJAX (not part of main form) -->
        <input type="hidden" name="nested_table_name" value="<?php echo esc_attr($nested_table_name); ?>">
        <input type="hidden" name="parent_field_id" value="<?php echo esc_attr($parent_field_id); ?>">
        <input type="hidden" name="table_name" value="<?php echo esc_attr($nested_table_name); ?>">

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Wygenerowana Nazwa</th>
                        <th>Nazwa Pola</th>
                        <th>Typ Pola</th>
                        <th>Wartość Pola</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fields as $field): ?>
                        <?php if ($field->generated_name === 'group_meta') continue; ?>
                        <tr>
                            <td><?php echo esc_html($field->id); ?></td>
                            <td><?php echo esc_html($field->generated_name); ?></td>
                            <td>
                                <input type="text" name="field_name[<?php echo esc_attr($field->id); ?>]" value="<?php echo esc_attr($field->user_name); ?>">
                            </td>
                            <td>
                                <select name="field_type[<?php echo esc_attr($field->id); ?>]" class="nested-field-type" data-field-id="<?php echo esc_attr($field->id); ?>">
                                    <option value="short_text" <?php selected($field->field_type, 'short_text'); ?>>Krótki tekst</option>
                                    <option value="long_text" <?php selected($field->field_type, 'long_text'); ?>>Długi tekst</option>
                                    <option value="number" <?php selected($field->field_type, 'number'); ?>>Liczba</option>
                                    <option value="image" <?php selected($field->field_type, 'image'); ?>>Obraz</option>
                                    <option value="nested_group" <?php selected($field->field_type, 'nested_group'); ?>>Zagnieżdżona grupa</option>
                                </select>
                            </td>
                            <td>
                                <?php if ($field->field_type === 'image'): ?>
                                    <?php 
                                    $image_id = $field->field_value;
                                    $image_url = '';
                                    if (is_numeric($image_id)) {
                                        $image_url = wp_get_attachment_url($image_id);
                                    }
                                    ?>
                                    <div class="yap-image-field-wrapper-nested">
                                        <input type="hidden" name="field_value[<?php echo esc_attr($field->id); ?>]" value="<?php echo esc_attr($field->field_value); ?>" class="yap-image-id-nested" data-field-id="<?php echo esc_attr($field->id); ?>">
                                        <button type="button" class="button yap-upload-image-button-nested" data-field-id="<?php echo esc_attr($field->id); ?>">
                                            <?php echo $image_url ? 'Zmień obraz' : 'Wybierz obraz'; ?>
                                        </button>
                                        <?php if ($image_url): ?>
                                            <img src="<?php echo esc_url($image_url); ?>" class="yap-image-preview-nested" data-field-id="<?php echo esc_attr($field->id); ?>" style="display: block; max-width: 100px; margin-top: 5px;">
                                            <button type="button" class="button yap-remove-image-button-nested" data-field-id="<?php echo esc_attr($field->id); ?>" style="margin-top: 5px;">Usuń obraz</button>
                                        <?php else: ?>
                                            <img src="" class="yap-image-preview-nested" data-field-id="<?php echo esc_attr($field->id); ?>" style="display: none; max-width: 100px; margin-top: 5px;">
                                            <button type="button" class="button yap-remove-image-button-nested" data-field-id="<?php echo esc_attr($field->id); ?>" style="display: none; margin-top: 5px;">Usuń obraz</button>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <input type="text" name="field_value[<?php echo esc_attr($field->id); ?>]" value="<?php echo esc_attr($field->field_value); ?>" class="nested-field-value" data-field-id="<?php echo esc_attr($field->id); ?>" <?php echo ($field->field_type === 'nested_group' || $field->field_type === 'image') ? 'disabled' : ''; ?>>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=yap-edit-group&table=' . urlencode($nested_table_name) . '&delete_field=' . $field->id); ?>" onclick="return confirm('Czy na pewno chcesz usunąć to pole?');">Usuń</a>
                            </td>
                        </tr>

                        <?php 
                        // Sprawdzamy czy są zagnieżdżone grupy
                        if ($field->field_type === 'nested_group' && !empty($field->nested_field_ids)) {
                            $nested_field_ids = json_decode($field->nested_field_ids, true);
                            if (is_array($nested_field_ids)) {
                                foreach ($nested_field_ids as $nested_sub_table) {
                                    yap_display_nested_group($nested_sub_table, $field->id, $depth + 1);
                                }
                            }
                        }
                        ?>

                    <?php endforeach; ?>
                </tbody>
            </table>
        
        <!-- Formularz dodawania pola WEWNĄTRZ tej konkretnej zagnieżdżonej grupy -->
        <div class="nested-add-field-form" style="margin-top: 20px; padding: 15px; background: #fff; border: 2px dashed #0073aa; border-radius: 5px;">
            <h4 style="margin-top: 0; color: #0073aa;">➕ Dodaj nowe pole do grupy: <code><?php echo esc_html($nested_table_name); ?></code></h4>
                <table class="form-table">
                    <tr>
                        <th><label>Nazwa pola:</label></th>
                        <td><input type="text" name="new_nested_field_name" placeholder="Nazwa pola" class="nested-field-name-input regular-text"></td>
                    </tr>
                    <tr>
                        <th><label>Typ pola:</label></th>
                        <td>
                        <select name="new_nested_field_type" class="nested-field-type-select">
                            <option value="short_text">Krótki tekst</option>
                            <option value="long_text">Długi tekst</option>
                            <option value="number">Liczba</option>
                            <option value="image">Obraz</option>
                            <option value="nested_group">Zagnieżdżona grupa</option>
                        </select>
                        </td>
                    </tr>
                    <tr class="nested-field-value-row">
                        <th><label>Wartość początkowa:</label></th>
                        <td><input type="text" name="new_nested_field_value" placeholder="Wartość pola" class="nested-field-value-input regular-text"></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <button type="button" class="add-nested-field button button-primary" data-nested-table="<?php echo esc_attr($nested_table_name); ?>">
                                <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span> Dodaj Pole
                            </button>
                        </td>
                    </tr>
                </table>
        </div>
        <!-- Koniec formularza dodawania pola -->
        
        <script>
            jQuery(document).ready(function($) {
                // Handle new field type changes in nested groups
                $('.nested-field-type-select').on('change', function() {
                    var fieldType = $(this).val();
                    var valueRow = $(this).closest('.nested-add-field-form').find('.nested-field-value-row');
                    var valueInput = $(this).closest('.nested-add-field-form').find('.nested-field-value-input');
                    
                    if (fieldType === 'nested_group' || fieldType === 'image') {
                        valueInput.prop('disabled', true).val('');
                        if (fieldType === 'nested_group') {
                            valueInput.attr('placeholder', 'Niedostępne dla zagnieżdżonej grupy');
                        } else {
                            valueInput.attr('placeholder', 'Obraz zostanie wybrany w edycji posta');
                        }
                        valueRow.hide();
                    } else {
                        valueInput.prop('disabled', false).attr('placeholder', 'Wartość pola');
                        valueRow.show();
                    }
                });
                
                // Handle existing field type changes
                $('.nested-field-type').on('change', function() {
                    var fieldId = $(this).data('field-id');
                    var valueInput = $('.nested-field-value[data-field-id="' + fieldId + '"]');
                    var fieldType = $(this).val();
                    
                    console.log('🔄 Zmiana typu pola w nested group:', fieldId, 'na', fieldType);
                    
                    if (fieldType === 'nested_group' || fieldType === 'image') {
                        valueInput.prop('disabled', true).val('');
                        if (fieldType === 'image') {
                            valueInput.hide();
                        }
                    } else {
                        valueInput.prop('disabled', false).show();
                    }
                });
            });
        </script>
    </div>
    <?php
}




?>