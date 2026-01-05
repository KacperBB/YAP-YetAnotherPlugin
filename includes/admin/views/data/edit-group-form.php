<div class="wrap">
    <h1>Edytuj Grupę: <?php echo esc_html($table_name); ?> (Data)</h1>
    <form method="post">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Wygenerowana Nazwa</th>
                    <th>Nazwa Pola</th>
                    <th>Typ Pola</th>
                    <th>Wartość Pola</th>
                    <th>Powiązany Post</th>
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
                                <input type="text" name="field_name[]" value="<?php echo esc_attr($field->user_name); ?>">
                            </td>
                            <td>
                                <select name="field_type[]" class="field-type" data-field-id="<?php echo esc_attr($field->id); ?>">
                                    <optgroup label="Podstawowe">
                                        <option value="short_text" <?php selected($field->field_type, 'short_text'); ?>>Krótki tekst</option>
                                        <option value="long_text" <?php selected($field->field_type, 'long_text'); ?>>Długi tekst</option>
                                        <option value="number" <?php selected($field->field_type, 'number'); ?>>Liczba</option>
                                        <option value="wysiwyg" <?php selected($field->field_type, 'wysiwyg'); ?>>WYSIWYG Editor</option>
                                        <option value="oembed" <?php selected($field->field_type, 'oembed'); ?>>oEmbed</option>
                                    </optgroup>
                                    <optgroup label="Wybór">
                                        <option value="select" <?php selected($field->field_type, 'select'); ?>>Select</option>
                                        <option value="checkbox" <?php selected($field->field_type, 'checkbox'); ?>>Checkbox</option>
                                        <option value="radio" <?php selected($field->field_type, 'radio'); ?>>Radio</option>
                                        <option value="true_false" <?php selected($field->field_type, 'true_false'); ?>>True/False</option>
                                    </optgroup>
                                    <optgroup label="Data i czas">
                                        <option value="date" <?php selected($field->field_type, 'date'); ?>>Data</option>
                                        <option value="datetime" <?php selected($field->field_type, 'datetime'); ?>>Data i czas</option>
                                        <option value="time" <?php selected($field->field_type, 'time'); ?>>Czas</option>
                                    </optgroup>
                                    <optgroup label="Media">
                                        <option value="image" <?php selected($field->field_type, 'image'); ?>>Obraz</option>
                                        <option value="file" <?php selected($field->field_type, 'file'); ?>>Plik</option>
                                        <option value="gallery" <?php selected($field->field_type, 'gallery'); ?>>Galeria</option>
                                    </optgroup>
                                    <optgroup label="Relacje">
                                        <option value="post_object" <?php selected($field->field_type, 'post_object'); ?>>Post Object</option>
                                        <option value="relationship" <?php selected($field->field_type, 'relationship'); ?>>Relationship</option>
                                        <option value="taxonomy" <?php selected($field->field_type, 'taxonomy'); ?>>Taxonomy</option>
                                        <option value="user" <?php selected($field->field_type, 'user'); ?>>User</option>
                                    </optgroup>
                                    <optgroup label="Zaawansowane">
                                        <option value="color" <?php selected($field->field_type, 'color'); ?>>Color Picker</option>
                                        <option value="range" <?php selected($field->field_type, 'range'); ?>>Range</option>
                                        <option value="google_map" <?php selected($field->field_type, 'google_map'); ?>>Google Map</option>
                                        <option value="repeater" <?php selected($field->field_type, 'repeater'); ?>>Repeater</option>
                                        <option value="flexible_content" <?php selected($field->field_type, 'flexible_content'); ?>>Flexible Content</option>
                                        <option value="nested_group" <?php selected($field->field_type, 'nested_group'); ?>>Zagnieżdżona grupa</option>
                                    </optgroup>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="field_value[]" value="<?php echo esc_attr($field->field_value); ?>">
                                <input type="hidden" name="field_id[]" value="<?php echo esc_attr($field->id); ?>">
                            </td>
                            <td>
                                <?php 
                                $post_id = intval($field->associated_id);
                                $post = get_post($post_id);
                                if ($post): ?>
                                    <a href="<?php echo get_edit_post_link($post->ID); ?>" title="<?php echo esc_attr($post->post_title); ?>">
                                        ID: <?php echo esc_html($post->ID); ?>
                                    </a>
                                <?php else: ?>
                                    Brak powiązanego posta
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="<?php echo admin_url('admin.php?page=yap-edit-group&table=' . urlencode($table_name) . '&delete_field=' . $field->id); ?>" onclick="return confirm('Czy na pewno chcesz usunąć to pole?');">Usuń</a>
                            </td>
                        </tr>
                        <?php if ($field->field_type == 'nested_group' && !empty($field->nested_field_ids)): ?>
                            <tr>
                                <td colspan="7">
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
        <input type="submit" name="yap_update_group" value="Zaktualizuj Grupę" class="button button-primary">
    </form>
</div>
