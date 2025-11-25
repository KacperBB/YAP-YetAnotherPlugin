<h2>Dodaj nowe pole</h2>
<form id="add-field-form">
    <input type="hidden" id="table_name" name="table_name" value="<?php echo esc_attr($_GET['table'] ?? ''); ?>">
    <table class="form-table">
        <tr>
            <th><label for="new_field_name">Nazwa pola:</label></th>
            <td><input type="text" id="new_field_name" name="new_field_name" required></td>
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
        <tr>
            <th><label for="new_field_value">Wartość pola:</label></th>
            <td><input type="text" id="new_field_value" name="new_field_value"></td>
        </tr>
    </table>
    <button type="submit" class="button button-secondary">Dodaj pole</button>
</form>