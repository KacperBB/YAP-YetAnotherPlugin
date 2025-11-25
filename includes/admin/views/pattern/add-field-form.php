<h2>Dodaj nowe pole (Pattern)</h2>
<form method="post">
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
        <tr id="field_value_row">
            <th><label for="new_field_value">Wartość pola:</label></th>
            <td><input type="text" id="new_field_value" name="new_field_value"></td>
        </tr>
    </table>
    <input type="submit" name="yap_add_field" value="Dodaj pole" class="button button-secondary">
</form>
<script>
jQuery(document).ready(function($) {
    // Handle field type changes
    $('#new_field_type').on('change', function() {
        var fieldType = $(this).val();
        var valueRow = $('#field_value_row');
        var valueInput = $('#new_field_value');
        
        if (fieldType === 'nested_group') {
            valueInput.prop('disabled', true).val('').attr('placeholder', 'Niedostępne dla zagnieżdżonej grupy');
            valueRow.hide();
        } else if (fieldType === 'image') {
            // Show media uploader for images
            valueRow.hide();
            // Image selection will be handled by media uploader in metabox
        } else {
            valueInput.prop('disabled', false).attr('placeholder', '');
            valueRow.show();
        }
    });
});
</script>