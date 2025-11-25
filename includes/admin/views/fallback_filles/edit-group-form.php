<div class="wrap">
    <h1>Edytuj Grupę: <?php echo esc_html($table_name); ?></h1>
    <form method="post">
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
                    <tr>
                        <td><?php echo esc_html($field->id); ?></td>
                        <td><?php echo esc_html($field->generated_name); ?></td>
                        <td>
                            <input type="text" name="field_name[]" value="<?php echo esc_attr($field->user_name); ?>">
                        </td>
                        <td>
                            <select name="field_type[]" class="field-type" data-field-id="<?php echo esc_attr($field->id); ?>">
                                <option value="short_text" <?php selected($field->field_type, 'short_text'); ?>>Krótki tekst</option>
                                <option value="long_text" <?php selected($field->field_type, 'long_text'); ?>>Długi tekst</option>
                                <option value="number" <?php selected($field->field_type, 'number'); ?>>Liczba</option>
                                <option value="image" <?php selected($field->field_type, 'image'); ?>>Obraz</option>
                                <option value="nested_group" <?php selected($field->field_type, 'nested_group'); ?>>Zagnieżdżona grupa</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="field_value[]" value="<?php echo esc_attr($field->field_value); ?>">
                            <input type="hidden" name="field_id[]" value="<?php echo esc_attr($field->id); ?>">
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=yap-edit-group&table=' . urlencode($table_name) . '&delete_field=' . $field->id); ?>" onclick="return confirm('Czy na pewno chcesz usunąć to pole?');">Usuń</a>
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
                <?php endforeach; ?>
            </tbody>
        </table>
        <input type="submit" name="yap_update_group" value="Zaktualizuj Grupę" class="button button-primary">
    </form>
</div>