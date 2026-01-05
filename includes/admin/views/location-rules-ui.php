<div class="yap-location-rules-section">
    <h2>📍 Gdzie wyświetlić tę grupę pól?</h2>
    <p class="description">Określ gdzie ta grupa pól powinna być wyświetlana. Możesz dodać wiele reguł.</p>
    <p class="description" style="background: #e7f3ff; padding: 8px 12px; border-left: 3px solid #0073aa; margin-bottom: 15px;">
        💡 <strong>Wskazówka:</strong> W menu taksonomii znajdziesz osobne opcje dla każdej taksonomii (Kategorie, Tagi, itp.). Po wybraniu zobaczysz listę konkretnych kategorii/tagów.
    </p>
    
    <div class="yap-location-rules-groups">
        <!-- Grupa reguł 0 -->
        <div class="yap-location-rule-group" data-group-index="0">
            <div class="yap-location-rules">
                <div class="yap-location-rule" data-rule-index="0">
                    <select name="location_rules[0][0][type]" class="yap-location-type">
                        <option value="">Wybierz lokalizację...</option>
                        <optgroup label="Post">
                            <option value="post_type">Typ posta</option>
                            <option value="post">Konkretny post</option>
                            <option value="page">Konkretna strona</option>
                            <option value="page_template">Szablon strony</option>
                        </optgroup>
                        <optgroup label="Taksonomie">
                            <?php 
                            $location_types = YAP_Location_Rules::get_location_types();
                            foreach ($location_types as $type_key => $type_data) {
                                if (strpos($type_key, 'taxonomy_') === 0) {
                                    echo '<option value="' . esc_attr($type_key) . '">' . esc_html($type_data['label']) . '</option>';
                                }
                            }
                            ?>
                        </optgroup>
                        <optgroup label="Użytkownicy">
                            <option value="user_role">Rola użytkownika</option>
                            <option value="user">Konkretny użytkownik</option>
                        </optgroup>
                        <optgroup label="Inne">
                            <option value="attachment">Załączniki</option>
                            <option value="comment">Komentarze</option>
                            <option value="widget">Widgety</option>
                            <option value="nav_menu">Menu</option>
                            <option value="options_page">Strona opcji</option>
                        </optgroup>
                    </select>
                    
                    <select name="location_rules[0][0][operator]" class="yap-location-operator">
                        <option value="==">jest równe</option>
                        <option value="!=">nie jest równe</option>
                    </select>
                    
                    <select name="location_rules[0][0][value]" class="yap-location-value">
                        <option value="">Najpierw wybierz typ lokalizacji</option>
                    </select>
                    
                    <button type="button" class="button yap-add-location-rule" title="Dodaj regułę (AND)">
                        <span class="dashicons dashicons-plus"></span> AND
                    </button>
                    <button type="button" class="button yap-remove-location-rule" title="Usuń regułę">
                        <span class="dashicons dashicons-minus"></span>
                    </button>
                </div>
            </div>
            
            <button type="button" class="button yap-add-location-rule-group">
                <span class="dashicons dashicons-plus-alt"></span> Dodaj grupę reguł (OR)
            </button>
        </div>
    </div>
</div>

<style>
.yap-location-rules-section {
    background: #fff;
    border: 1px solid #ddd;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
}

.yap-location-rules-section h2 {
    margin-top: 0;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.yap-location-rule-group {
    background: #f9f9f9;
    border: 2px dashed #ddd;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 6px;
    position: relative;
}

.yap-location-rule-group::before {
    content: 'Grupa reguł (wszystkie muszą być spełnione)';
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    margin-bottom: 10px;
    text-transform: uppercase;
}

.yap-location-rule {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
    background: white;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
}

.yap-location-type,
.yap-location-operator,
.yap-location-value {
    flex: 1;
    min-width: 150px;
}

.yap-location-type {
    min-width: 200px;
}

.yap-add-location-rule,
.yap-remove-location-rule {
    flex-shrink: 0;
}

.yap-add-location-rule-group {
    margin-top: 10px;
}

.yap-remove-rule-group {
    position: absolute;
    top: 10px;
    right: 10px;
}

.yap-location-rule-and {
    text-align: center;
    font-weight: 600;
    color: #0073aa;
    padding: 5px 0;
}

.yap-location-rule-or {
    text-align: center;
    font-weight: 600;
    color: #d63638;
    padding: 10px 0;
    position: relative;
}

.yap-location-rule-or::before,
.yap-location-rule-or::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 40%;
    height: 2px;
    background: #d63638;
}

.yap-location-rule-or::before {
    left: 0;
}

.yap-location-rule-or::after {
    right: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Dane dla różnych typów lokalizacji
    const locationChoices = <?php echo json_encode(YAP_Location_Rules::get_location_types()); ?>;
    
    console.log('📦 Location Choices:', locationChoices);
    console.log('📋 Taxonomy types:', Object.keys(locationChoices).filter(k => k.startsWith('taxonomy_')));
    
    // Zmiana typu lokalizacji
    $(document).on('change', '.yap-location-type', function() {
        const $rule = $(this).closest('.yap-location-rule');
        const $valueSelect = $rule.find('.yap-location-value');
        const type = $(this).val();
        
        if (!type) {
            $valueSelect.html('<option value="">Najpierw wybierz typ lokalizacji</option>');
            return;
        }
        
        // Backward compatibility: stary typ "taxonomy" przekieruj na taxonomy_category
        if (type === 'taxonomy') {
            console.warn('⚠️ Stary typ "taxonomy" - używam domyślnie taxonomy_category');
            type = 'taxonomy_category';
            $(this).val(type); // Zaktualizuj select
        }
        
        // Sprawdź czy typ istnieje w locationChoices
        if (!locationChoices[type]) {
            console.error('❌ Nieznany typ lokalizacji:', type);
            $valueSelect.html('<option value="">Błąd: nieznany typ lokalizacji</option>');
            return;
        }
        
        const choices = locationChoices[type].choices;
        
        if (choices === 'ajax') {
            // Dla postów/stron/użytkowników - można by dodać AJAX search
            $valueSelect.html('<option value="">Wpisz ID...</option>');
        } else if (typeof choices === 'object') {
            let options = '<option value="">Wybierz...</option>';
            for (const [value, label] of Object.entries(choices)) {
                options += `<option value="${value}">${label}</option>`;
            }
            $valueSelect.html(options);
        }
    });
    

    
    // Funkcja pomocnicza: Generuj opcje dla location type select
    function generateLocationTypeOptions() {
        let html = '<option value="">Wybierz lokalizację...</option>';
        
        // Post
        html += '<optgroup label="Post">';
        html += '<option value="post_type">Typ posta</option>';
        html += '<option value="post">Konkretny post</option>';
        html += '<option value="page">Konkretna strona</option>';
        html += '<option value="page_template">Szablon strony</option>';
        html += '</optgroup>';
        
        // Taksonomie - dynamicznie z locationChoices
        html += '<optgroup label="Taksonomie">';
        for (const [typeKey, typeData] of Object.entries(locationChoices)) {
            if (typeKey.startsWith('taxonomy_')) {
                html += `<option value="${typeKey}">${typeData.label}</option>`;
            }
        }
        html += '</optgroup>';
        
        // Użytkownicy
        html += '<optgroup label="Użytkownicy">';
        html += '<option value="user_role">Rola użytkownika</option>';
        html += '<option value="user">Konkretny użytkownik</option>';
        html += '</optgroup>';
        
        // Inne
        html += '<optgroup label="Inne">';
        html += '<option value="attachment">Załączniki</option>';
        html += '<option value="comment">Komentarze</option>';
        html += '<option value="widget">Widgety</option>';
        html += '<option value="nav_menu">Menu</option>';
        html += '<option value="options_page">Strona opcji</option>';
        html += '</optgroup>';
        
        return html;
    }
    
    // Dodaj regułę (AND)
    $(document).on('click', '.yap-add-location-rule', function() {
        const $group = $(this).closest('.yap-location-rule-group');
        const groupIndex = $group.data('group-index');
        const $rules = $group.find('.yap-location-rules');
        const ruleIndex = $rules.find('.yap-location-rule').length;
        
        // Stwórz nową regułę od zera zamiast klonowania
        const $newRule = $(`
            <div class="yap-location-rule" data-rule-index="${ruleIndex}">
                <select name="location_rules[${groupIndex}][${ruleIndex}][type]" class="yap-location-type">
                    ${generateLocationTypeOptions()}
                </select>
                
                <select name="location_rules[${groupIndex}][${ruleIndex}][operator]" class="yap-location-operator">
                    <option value="==">jest równe</option>
                    <option value="!=">nie jest równe</option>
                </select>
                
                <select name="location_rules[${groupIndex}][${ruleIndex}][value]" class="yap-location-value">
                    <option value="">Najpierw wybierz typ lokalizacji</option>
                </select>
                
                <button type="button" class="button yap-add-location-rule" title="Dodaj regułę (AND)">
                    <span class="dashicons dashicons-plus"></span> AND
                </button>
                <button type="button" class="button yap-remove-location-rule" title="Usuń regułę">
                    <span class="dashicons dashicons-minus"></span>
                </button>
            </div>
        `);
        
        // Dodaj separator AND
        $rules.append('<div class="yap-location-rule-and">AND</div>');
        $rules.append($newRule);
        
        console.log('✅ Dodano nową regułę z indeksem:', ruleIndex);
    });
    
    // Usuń regułę
    $(document).on('click', '.yap-remove-location-rule', function() {
        const $rule = $(this).closest('.yap-location-rule');
        const $group = $rule.closest('.yap-location-rule-group');
        
        if ($group.find('.yap-location-rule').length > 1) {
            const $prev = $rule.prev();
            if ($prev.hasClass('yap-location-rule-and')) {
                $prev.remove();
            }
            $rule.remove();
        } else {
            alert('Musisz mieć przynajmniej jedną regułę.');
        }
    });
    
    // Dodaj grupę reguł (OR)
    $(document).on('click', '.yap-add-location-rule-group', function() {
        const $groups = $('.yap-location-rules-groups');
        const groupIndex = $groups.find('.yap-location-rule-group').length;
        
        // Stwórz nową grupę od zera
        const $newGroup = $(`
            <div class="yap-location-rule-group" data-group-index="${groupIndex}">
                <div class="yap-location-rules">
                    <div class="yap-location-rule" data-rule-index="0">
                        <select name="location_rules[${groupIndex}][0][type]" class="yap-location-type">
                            ${generateLocationTypeOptions()}
                        </select>
                        
                        <select name="location_rules[${groupIndex}][0][operator]" class="yap-location-operator">
                            <option value="==">jest równe</option>
                            <option value="!=">nie jest równe</option>
                        </select>
                        
                        <select name="location_rules[${groupIndex}][0][value]" class="yap-location-value">
                            <option value="">Najpierw wybierz typ lokalizacji</option>
                        </select>
                        
                        <button type="button" class="button yap-add-location-rule" title="Dodaj regułę (AND)">
                            <span class="dashicons dashicons-plus"></span> AND
                        </button>
                        <button type="button" class="button yap-remove-location-rule" title="Usuń regułę">
                            <span class="dashicons dashicons-minus"></span>
                        </button>
                    </div>
                </div>
                
                <button type="button" class="button yap-add-location-rule-group">
                    <span class="dashicons dashicons-plus-alt"></span> Dodaj grupę reguł (OR)
                </button>
            </div>
        `);
        
        // Dodaj separator OR
        $groups.append('<div class="yap-location-rule-or">OR</div>');
        $groups.append($newGroup);
        
        console.log('✅ Dodano nową grupę reguł z indeksem:', groupIndex);
    });

});
</script>
