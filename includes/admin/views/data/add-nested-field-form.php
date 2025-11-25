<h2>Dodaj nowe zagnieżdżone pole (Data)</h2>
<form>
    <label for="nested-field-name">Nazwa pola:</label>
    <input type="text" id="nested-field-name" name="nested_field_name">
    <label for="nested-field-type">Typ pola:</label>
    <select id="nested-field-type" name="nested_field_type">
        <option value="short_text">Krótki tekst</option>
        <option value="long_text">Długi tekst</option>
        <option value="number">Liczba</option>
        <option value="image">Obraz</option>
        <option value="nested_group">Zagnieżdżona grupa</option>
    </select>
    <input type="text" id="nested-field-value" name="nested_field_value" placeholder="Wartość pola">
    <button type="button" id="add-nested-field" data-nested-table="<?php echo esc_attr($table_name); ?>">Dodaj Zagnieżdżone Pole</button>
</form>