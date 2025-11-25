<div class="wrap">
    <h1>Edytuj Grupę: <?php echo esc_html($table_name); ?></h1>
    
    <?php 
    // Extract clean group name
    $clean_name = preg_replace('/^' . preg_quote($wpdb->prefix, '/') . 'group_(.*?)_pattern$/', '$1', $table_name);
    ?>
    
    <div class="notice notice-info" style="padding: 15px; margin: 20px 0; background: #f0f6fc; border-left: 4px solid #0073aa;">
        <h3 style="margin-top: 0;">ℹ️ Jak użyć tej grupy w kodzie:</h3>
        <p><strong>Pobieranie wartości pola:</strong></p>
        <pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;">$value = yap_get_field('nazwa_pola', get_the_ID(), '<?php echo esc_js($clean_name); ?>');
echo $value;</pre>
        
        <p><strong>Wyświetlanie wszystkich pól z grupy:</strong></p>
        <pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;">$fields = yap_get_all_fields(get_the_ID(), '<?php echo esc_js($clean_name); ?>');
foreach ($fields as $field) {
    echo '&lt;p&gt;&lt;strong&gt;' . $field['label'] . ':&lt;/strong&gt; ' . $field['value'] . '&lt;/p&gt;';
}</pre>

        <p><strong>Pobieranie zagnieżdżonej grupy:</strong></p>
        <pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;">$nested = yap_get_nested_group('nazwa_pola_nested', get_the_ID(), '<?php echo esc_js($clean_name); ?>');
foreach ($nested as $item) {
    echo $item['nazwa_podpola'];
}</pre>
    </div>
    
    <form id="yap-edit-group-form" method="post">
    <input type="hidden" name="table_name" value="<?php echo esc_attr($table_name); ?>">
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
                    <?php if (strpos($field->generated_name, 'group_meta') === false): ?>
                        <tr>
                            <td><?php echo esc_html($field->id); ?></td>
                            <td><?php echo esc_html($field->generated_name); ?></td>
                            <td>
                                <input type="text" name="field_name[<?php echo esc_attr($field->id); ?>]" value="<?php echo esc_attr($field->user_name); ?>">
                            </td>
                            <td>
                                <select name="field_type[<?php echo esc_attr($field->id); ?>]" class="field-type">
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
                                    <div class="yap-image-field-wrapper-edit">
                                        <input type="hidden" name="field_value[<?php echo esc_attr($field->id); ?>]" value="<?php echo esc_attr($field->field_value); ?>" class="yap-image-id-edit" data-field-id="<?php echo esc_attr($field->id); ?>">
                                        <button type="button" class="button yap-upload-image-button-edit" data-field-id="<?php echo esc_attr($field->id); ?>">
                                            <?php echo $image_url ? 'Zmień obraz' : 'Wybierz obraz'; ?>
                                        </button>
                                        <?php if ($image_url): ?>
                                            <img src="<?php echo esc_url($image_url); ?>" class="yap-image-preview-edit" data-field-id="<?php echo esc_attr($field->id); ?>" style="display: block; max-width: 100px; margin-top: 5px;">
                                            <button type="button" class="button yap-remove-image-button-edit" data-field-id="<?php echo esc_attr($field->id); ?>" style="margin-top: 5px;">Usuń obraz</button>
                                        <?php else: ?>
                                            <img src="" class="yap-image-preview-edit" data-field-id="<?php echo esc_attr($field->id); ?>" style="display: none; max-width: 100px; margin-top: 5px;">
                                            <button type="button" class="button yap-remove-image-button-edit" data-field-id="<?php echo esc_attr($field->id); ?>" style="display: none; margin-top: 5px;">Usuń obraz</button>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <input type="text" name="field_value[<?php echo esc_attr($field->id); ?>]" value="<?php echo esc_attr($field->field_value); ?>">
                                <?php endif; ?>
                                <input type="hidden" name="field_id[]" value="<?php echo esc_attr($field->id); ?>">
                            </td>
                            <td>
                                <a href="#" class="delete-field" data-id="<?php echo esc_attr($field->id); ?>">Usuń</a>
                            </td>
                        </tr>
                        <?php if ($field->field_type == 'nested_group' && !empty($field->nested_field_ids)): ?>
                            <tr>
                                <td colspan="6">
                                    <?php
                                    $nested_field_ids = json_decode($field->nested_field_ids, true);
                                    if (is_array($nested_field_ids)) {
                                        foreach ($nested_field_ids as $nested_table_name) {
                                            yap_display_nested_group($nested_table_name, $field->id);
                                        }
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>Dodaj nowe pole do głównej grupy</h2>
        <table class="form-table">
            <tr>
                <th><label for="new_field_name">Nazwa pola:</label></th>
                <td><input type="text" id="new_field_name" name="new_field_name"></td>
            </tr>
            <tr>
                <th><label for="new_field_type">Typ pola:</label></th>
                <td>
                    <select id="new_field_type" name="new_field_type">
                        <option value="short_text">Krótki tekst</option>
                        <option value="long_text">Długi tekst</option>
                        <option value="number">Liczba</option>
                        <option value="image">Obraz</option>
                        <option value="nested_group">Zagnieżdżona grupa</option>
                    </select>
                </td>
            </tr>
            <tr id="field_value_row">
                <th><label for="new_field_value">Wartość początkowa:</label></th>
                <td>
                    <input type="text" id="new_field_value" name="new_field_value">
                    <p id="image_field_info" class="description" style="display: none; color: #0073aa;">
                        ℹ️ Obraz można wybrać po utworzeniu pola - pojawi się przycisk "Wybierz obraz" w tabeli powyżej.
                    </p>
                </td>
            </tr>
        </table>
        
        <button type="submit" name="yap_add_field" class="button button-secondary">Dodaj pole</button>
        <button type="submit" name="yap_update_group" class="button button-primary">Zaktualizuj Grupę</button>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle main field type changes
    $('#new_field_type').on('change', function() {
        var fieldType = $(this).val();
        var valueRow = $('#field_value_row');
        var valueInput = $('#new_field_value');
        var imageInfo = $('#image_field_info');
        
        if (fieldType === 'nested_group') {
            valueInput.prop('disabled', true).val('').attr('placeholder', 'Niedostępne dla zagnieżdżonej grupy');
            valueRow.hide();
            imageInfo.hide();
        } else if (fieldType === 'image') {
            valueInput.val('').hide();
            imageInfo.show();
        } else {
            valueInput.prop('disabled', false).attr('placeholder', '').show();
            valueRow.show();
            imageInfo.hide();
        }
    });
});
</script>
