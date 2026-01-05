/**
 * YAP Field Type Settings
 * Type-specific settings and validation for each field type
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    window.YAPFieldSettings = {
        
        /**
         * Get type-specific settings HTML
         */
        getTypeSpecificSettings(field) {
            const type = field.type;
            
            switch(type) {
                case 'email':
                    return this.getEmailSettings(field);
                case 'url':
                    return this.getUrlSettings(field);
                case 'tel':
                    return this.getTelSettings(field);
                case 'number':
                    return this.getNumberSettings(field);
                case 'date':
                    return this.getDateSettings(field);
                case 'time':
                    return this.getTimeSettings(field);
                case 'datetime':
                    return this.getDateTimeSettings(field);
                case 'color':
                    return this.getColorSettings(field);
                case 'select':
                    return this.getSelectSettings(field);
                case 'checkbox':
                    return this.getCheckboxSettings(field);
                case 'radio':
                    return this.getRadioSettings(field);
                case 'textarea':
                    return this.getTextareaSettings(field);
                case 'group':
                    return this.getGroupSettings(field);
                case 'repeater':
                    return this.getRepeaterSettings(field);
                case 'wysiwyg':
                    return this.getWysiwygSettings(field);
                case 'image':
                case 'file':
                    return this.getFileSettings(field);
                case 'gallery':
                    return this.getGallerySettings(field);
                default:
                    return '';
            }
        },
        
        /**
         * Email field settings
         */
        getEmailSettings(field) {
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ Email Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-email-validate" ${field.validate_email !== false ? 'checked' : ''}>
                            Waliduj email przy opuszczeniu pola (onblur)
                        </label>
                        <p class="description">Wyświetl komunikat błędu jeśli email jest nieprawidłowy</p>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Komunikat błędu walidacji</label>
                        <input type="text" class="yap-setting-email-error-msg" 
                               value="${field.email_error_message || 'Proszę wprowadzić poprawny adres email'}"
                               placeholder="Proszę wprowadzić poprawny adres email">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-email-conditional-error" ${field.conditional_on_error ? 'checked' : ''}>
                            Włącz conditional logic dla błędnej walidacji
                        </label>
                        <p class="description">Dodaj automatyczny warunek pokazujący komunikat błędu</p>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Dozwolone domeny (opcjonalnie)</label>
                        <input type="text" class="yap-setting-email-domains" 
                               value="${field.allowed_domains || ''}"
                               placeholder="np. gmail.com, yahoo.com (oddziel przecinkami)">
                        <p class="description">Pozostaw puste aby akceptować wszystkie domeny</p>
                    </div>
                </div>
            `;
        },
        
        /**
         * URL field settings
         */
        getUrlSettings(field) {
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ URL Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-url-validate" ${field.validate_url !== false ? 'checked' : ''}>
                            Waliduj URL przy opuszczeniu pola
                        </label>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Dozwolone protokoły</label>
                        <select class="yap-setting-url-protocols" multiple style="height: 80px;">
                            <option value="http" ${(field.allowed_protocols || ['http', 'https']).includes('http') ? 'selected' : ''}>http://</option>
                            <option value="https" ${(field.allowed_protocols || ['http', 'https']).includes('https') ? 'selected' : ''}>https://</option>
                            <option value="ftp" ${(field.allowed_protocols || []).includes('ftp') ? 'selected' : ''}>ftp://</option>
                            <option value="mailto" ${(field.allowed_protocols || []).includes('mailto') ? 'selected' : ''}>mailto:</option>
                        </select>
                    </div>
                </div>
            `;
        },
        
        /**
         * Tel field settings
         */
        getTelSettings(field) {
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ Phone Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>Format telefonu</label>
                        <input type="text" class="yap-setting-tel-format" 
                               value="${field.phone_format || ''}"
                               placeholder="np. +48 XXX-XXX-XXX">
                        <p class="description">Użyj X dla cyfr, reszta to separatory</p>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Domyślny kierunkowy</label>
                        <input type="text" class="yap-setting-tel-prefix" 
                               value="${field.default_prefix || '+48'}"
                               placeholder="+48">
                    </div>
                </div>
            `;
        },
        
        /**
         * Number field settings
         */
        getNumberSettings(field) {
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ Number Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>Minimalna wartość</label>
                        <input type="number" class="yap-setting-number-min" 
                               value="${field.min_value || ''}"
                               placeholder="Brak limitu">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Maksymalna wartość</label>
                        <input type="number" class="yap-setting-number-max" 
                               value="${field.max_value || ''}"
                               placeholder="Brak limitu">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Krok (step)</label>
                        <input type="number" class="yap-setting-number-step" 
                               value="${field.step || '1'}"
                               placeholder="1">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-number-decimals" ${field.allow_decimals ? 'checked' : ''}>
                            Zezwalaj na liczby dziesiętne
                        </label>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Prefiks (np. $, zł)</label>
                        <input type="text" class="yap-setting-number-prefix" 
                               value="${field.number_prefix || ''}"
                               placeholder="$">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Sufiks (np. kg, m)</label>
                        <input type="text" class="yap-setting-number-suffix" 
                               value="${field.number_suffix || ''}"
                               placeholder="kg">
                    </div>
                </div>
            `;
        },
        
        /**
         * Date field settings
         */
        getDateSettings(field) {
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ Date Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>Format daty (display)</label>
                        <select class="yap-setting-date-display-format">
                            <option value="Y-m-d" ${field.date_display_format === 'Y-m-d' ? 'selected' : ''}>YYYY-MM-DD (2024-12-31)</option>
                            <option value="d-m-Y" ${field.date_display_format === 'd-m-Y' ? 'selected' : ''}>DD-MM-YYYY (31-12-2024)</option>
                            <option value="d.m.Y" ${field.date_display_format === 'd.m.Y' ? 'selected' : ''}>DD.MM.YYYY (31.12.2024)</option>
                            <option value="d/m/Y" ${field.date_display_format === 'd/m/Y' ? 'selected' : ''}>DD/MM/YYYY (31/12/2024)</option>
                            <option value="m/d/Y" ${field.date_display_format === 'm/d/Y' ? 'selected' : ''}>MM/DD/YYYY (12/31/2024)</option>
                            <option value="F j, Y" ${field.date_display_format === 'F j, Y' ? 'selected' : ''}>December 31, 2024</option>
                        </select>
                        <p class="description">Format wyświetlania daty dla użytkownika</p>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Format zapisu (save)</label>
                        <select class="yap-setting-date-save-format">
                            <option value="Y-m-d" ${(field.date_save_format || 'Y-m-d') === 'Y-m-d' ? 'selected' : ''}>YYYY-MM-DD (ISO 8601)</option>
                            <option value="timestamp" ${field.date_save_format === 'timestamp' ? 'selected' : ''}>Unix Timestamp</option>
                        </select>
                        <p class="description">Format zapisu w bazie danych</p>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Minimalna data</label>
                        <input type="date" class="yap-setting-date-min" 
                               value="${field.min_date || ''}">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Maksymalna data</label>
                        <input type="date" class="yap-setting-date-max" 
                               value="${field.max_date || ''}">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-date-first-day" ${field.first_day_monday ? 'checked' : ''}>
                            Tydzień zaczyna się od poniedziałku
                        </label>
                    </div>
                </div>
            `;
        },
        
        /**
         * Time field settings
         */
        getTimeSettings(field) {
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ Time Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>Format czasu</label>
                        <select class="yap-setting-time-format">
                            <option value="24" ${(field.time_format || '24') === '24' ? 'selected' : ''}>24 godzinny (23:59)</option>
                            <option value="12" ${field.time_format === '12' ? 'selected' : ''}>12 godzinny (11:59 PM)</option>
                        </select>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Krok minut</label>
                        <select class="yap-setting-time-step">
                            <option value="1" ${(field.time_step || '15') === '1' ? 'selected' : ''}>1 minuta</option>
                            <option value="5" ${field.time_step === '5' ? 'selected' : ''}>5 minut</option>
                            <option value="15" ${field.time_step === '15' ? 'selected' : ''}>15 minut</option>
                            <option value="30" ${field.time_step === '30' ? 'selected' : ''}>30 minut</option>
                            <option value="60" ${field.time_step === '60' ? 'selected' : ''}>1 godzina</option>
                        </select>
                    </div>
                </div>
            `;
        },
        
        /**
         * DateTime field settings
         */
        getDateTimeSettings(field) {
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ DateTime Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>Format datetime</label>
                        <select class="yap-setting-datetime-format">
                            <option value="Y-m-d H:i:s" ${(field.datetime_format || 'Y-m-d H:i:s') === 'Y-m-d H:i:s' ? 'selected' : ''}>YYYY-MM-DD HH:MM:SS (2024-12-31 23:59:59)</option>
                            <option value="d.m.Y H:i" ${field.datetime_format === 'd.m.Y H:i' ? 'selected' : ''}>DD.MM.YYYY HH:MM (31.12.2024 23:59)</option>
                            <option value="d/m/Y g:i A" ${field.datetime_format === 'd/m/Y g:i A' ? 'selected' : ''}>DD/MM/YYYY h:mm AM/PM (31/12/2024 11:59 PM)</option>
                        </select>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Strefa czasowa</label>
                        <select class="yap-setting-datetime-timezone">
                            <option value="local" ${(field.timezone || 'local') === 'local' ? 'selected' : ''}>Lokalny czas użytkownika</option>
                            <option value="UTC" ${field.timezone === 'UTC' ? 'selected' : ''}>UTC</option>
                            <option value="Europe/Warsaw" ${field.timezone === 'Europe/Warsaw' ? 'selected' : ''}>Europa/Warszawa</option>
                        </select>
                    </div>
                </div>
            `;
        },
        
        /**
         * Color picker settings
         */
        getColorSettings(field) {
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ Color Picker Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>Typ pickera</label>
                        <select class="yap-setting-color-type">
                            <option value="native" ${(field.color_type || 'native') === 'native' ? 'selected' : ''}>Natywny HTML5 color picker</option>
                            <option value="text" ${field.color_type === 'text' ? 'selected' : ''}>Text input z podglądem</option>
                            <option value="palette" ${field.color_type === 'palette' ? 'selected' : ''}>Paleta kolorów</option>
                        </select>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Format koloru</label>
                        <select class="yap-setting-color-format">
                            <option value="hex" ${(field.color_format || 'hex') === 'hex' ? 'selected' : ''}>HEX (#FF5733)</option>
                            <option value="rgb" ${field.color_format === 'rgb' ? 'selected' : ''}>RGB (rgb(255, 87, 51))</option>
                            <option value="rgba" ${field.color_format === 'rgba' ? 'selected' : ''}>RGBA (rgba(255, 87, 51, 1))</option>
                        </select>
                    </div>
                    
                    <div class="yap-setting-group yap-color-palette-group" style="display: ${field.color_type === 'palette' ? 'block' : 'none'};">
                        <label>Paleta kolorów (po jednym na linię)</label>
                        <textarea class="yap-setting-color-palette" rows="5" placeholder="#FF5733\n#33FF57\n#3357FF">${field.color_palette || '#FF5733\n#33FF57\n#3357FF\n#F333FF\n#33FFF3\n#FFF333'}</textarea>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-color-alpha" ${field.enable_alpha ? 'checked' : ''}>
                            Włącz kanał alpha (przezroczystość)
                        </label>
                    </div>
                </div>
            `;
        },
        
        /**
         * Select field settings
         */
        getSelectSettings(field) {
            const options = field.choices || [];
            const optionsHTML = options.map((opt, i) => `
                <div class="yap-select-option-row" data-index="${i}">
                    <input type="text" class="yap-option-label" value="${opt.label || ''}" placeholder="Etykieta">
                    <input type="text" class="yap-option-value" value="${opt.value || ''}" placeholder="Wartość">
                    <button type="button" class="button yap-remove-option">✕</button>
                </div>
            `).join('');
            
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ Select Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-select-multiple" ${field.multiple ? 'checked' : ''}>
                            Pozwól na wielokrotny wybór (multiple)
                        </label>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-select-searchable" ${field.searchable !== false ? 'checked' : ''}>
                            Pole wyszukiwania (searchable)
                        </label>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Placeholder dla pustego selecta</label>
                        <input type="text" class="yap-setting-select-placeholder" 
                               value="${field.select_placeholder || 'Wybierz opcję...'}"
                               placeholder="Wybierz opcję...">
                    </div>
                    
                    <hr>
                    
                    <div class="yap-setting-group">
                        <label><strong>Opcje do wyboru</strong></label>
                        <div class="yap-select-options-container">
                            ${optionsHTML || '<p class="yap-no-options">Brak opcji. Kliknij "Dodaj opcję" poniżej.</p>'}
                        </div>
                        <button type="button" class="button yap-add-option">➕ Dodaj opcję</button>
                        <button type="button" class="button yap-bulk-add-options" style="margin-left: 8px;">📋 Wstaw wiele opcji</button>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Importuj opcje (JSON)</label>
                        <textarea class="yap-setting-select-json" rows="3" placeholder='[{"label":"Opcja 1","value":"opt1"},{"label":"Opcja 2","value":"opt2"}]'></textarea>
                        <button type="button" class="button yap-import-options">📥 Importuj</button>
                    </div>
                </div>
            `;
        },
        
        /**
         * Checkbox field settings
         */
        getCheckboxSettings(field) {
            const options = field.choices || [];
            const optionsHTML = options.map((opt, i) => `
                <div class="yap-checkbox-option-row" data-index="${i}">
                    <input type="text" class="yap-option-label" value="${opt.label || ''}" placeholder="Etykieta">
                    <input type="text" class="yap-option-value" value="${opt.value || ''}" placeholder="Wartość">
                    <label><input type="checkbox" class="yap-option-default" ${opt.default ? 'checked' : ''}> Domyślnie zaznaczony</label>
                    <button type="button" class="button yap-remove-option">✕</button>
                </div>
            `).join('');
            
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ Checkbox Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>Layout</label>
                        <select class="yap-setting-checkbox-layout">
                            <option value="vertical" ${(field.layout || 'vertical') === 'vertical' ? 'selected' : ''}>Pionowy</option>
                            <option value="horizontal" ${field.layout === 'horizontal' ? 'selected' : ''}>Poziomy</option>
                        </select>
                    </div>
                    
                    <hr>
                    
                    <div class="yap-setting-group">
                        <label><strong>Opcje checkboxów</strong></label>
                        <div class="yap-checkbox-options-container">
                            ${optionsHTML || '<p class="yap-no-options">Brak opcji.</p>'}
                        </div>
                        <button type="button" class="button yap-add-checkbox-option">➕ Dodaj checkbox</button>
                    </div>
                </div>
            `;
        },
        
        /**
         * Radio field settings
         */
        getRadioSettings(field) {
            const options = field.choices || [];
            const optionsHTML = options.map((opt, i) => `
                <div class="yap-radio-option-row" data-index="${i}">
                    <input type="text" class="yap-option-label" value="${opt.label || ''}" placeholder="Etykieta">
                    <input type="text" class="yap-option-value" value="${opt.value || ''}" placeholder="Wartość">
                    <label><input type="radio" name="yap-default-radio" class="yap-option-default" ${opt.default ? 'checked' : ''}> Domyślny</label>
                    <button type="button" class="button yap-remove-option">✕</button>
                </div>
            `).join('');
            
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ Radio Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>Layout</label>
                        <select class="yap-setting-radio-layout">
                            <option value="vertical" ${(field.layout || 'vertical') === 'vertical' ? 'selected' : ''}>Pionowy</option>
                            <option value="horizontal" ${field.layout === 'horizontal' ? 'selected' : ''}>Poziomy</option>
                        </select>
                    </div>
                    
                    <hr>
                    
                    <div class="yap-setting-group">
                        <label><strong>Opcje radio</strong></label>
                        <div class="yap-radio-options-container">
                            ${optionsHTML || '<p class="yap-no-options">Brak opcji.</p>'}
                        </div>
                        <button type="button" class="button yap-add-radio-option">➕ Dodaj opcję radio</button>
                    </div>
                </div>
            `;
        },
        
        /**
         * Textarea settings
         */
        getTextareaSettings(field) {
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ Textarea Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>Liczba wierszy (rows)</label>
                        <input type="number" class="yap-setting-textarea-rows" 
                               value="${field.rows || '4'}"
                               min="1" max="20">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Maksymalna długość (max length)</label>
                        <input type="number" class="yap-setting-textarea-maxlength" 
                               value="${field.maxlength || ''}"
                               placeholder="Brak limitu">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-textarea-counter" ${field.show_counter ? 'checked' : ''}>
                            Pokaż licznik znaków
                        </label>
                    </div>
                </div>
            `;
        },
        
        /**
         * Group field settings
         */
        getGroupSettings(field) {
            const subFieldsCount = field.sub_fields ? field.sub_fields.length : 0;
            
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ Group Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>Layout grupy</label>
                        <select class="yap-setting-group-layout">
                            <option value="block" ${(field.group_layout || 'block') === 'block' ? 'selected' : ''}>Blok (pionowy)</option>
                            <option value="table" ${field.group_layout === 'table' ? 'selected' : ''}>Tabela (rzędowa)</option>
                            <option value="row" ${field.group_layout === 'row' ? 'selected' : ''}>Wiersz (poziomy)</option>
                        </select>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-group-wrapper" ${field.show_wrapper !== false ? 'checked' : ''}>
                            Pokaż ramkę grupy
                        </label>
                        <p class="description">Wizualne wyróżnienie grupy pól</p>
                    </div>
                    
                    <hr>
                    
                    <div class="yap-setting-group">
                        <div class="yap-info-box" style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea;">
                            <h4 style="margin: 0 0 10px 0; color: #667eea; display: flex; align-items: center; gap: 8px;">
                                <span class="dashicons dashicons-info"></span>
                                Zagnieżdżanie pól w grupie
                            </h4>
                            <p style="margin: 0 0 10px 0; font-size: 13px; line-height: 1.6;">
                                <strong>Group</strong> służy do grupowania pól logicznie powiązanych. Pola dodajesz metodą <strong>Drag & Drop</strong>.
                            </p>
                            <ol style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.8;">
                                <li>Zamknij ten modal</li>
                                <li>Przeciągnij pola z lewej strony na <strong>szare pole wewnątrz grupy</strong></li>
                                <li>Pola zostaną zagnieżdżone w tej grupie</li>
                                <li>Możesz je sortować, edytować i usuwać</li>
                                <li>Grupa może zawierać dowolne typy pól (tekst, select, checkbox, etc.)</li>
                            </ol>
                            <div style="margin-top: 12px; padding: 10px; background: white; border-radius: 6px; font-size: 13px;">
                                📦 Aktualnie w grupie: <strong>${subFieldsCount} ${subFieldsCount === 1 ? 'pole' : subFieldsCount < 5 ? 'pola' : 'pól'}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },
        
        /**
         * Repeater settings
         */
        getRepeaterSettings(field) {
            const subFields = field.sub_fields || [];
            const subFieldsHTML = subFields.map((sf, i) => `
                <div class="yap-repeater-subfield" data-index="${i}">
                    <strong>${sf.label || sf.name}</strong> <em>(${sf.type})</em>
                    <button type="button" class="button yap-remove-subfield">✕</button>
                </div>
            `).join('');
            
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ Repeater Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>Minimalna liczba wierszy</label>
                        <input type="number" class="yap-setting-repeater-min" 
                               value="${field.min_rows || '0'}"
                               min="0">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Maksymalna liczba wierszy</label>
                        <input type="number" class="yap-setting-repeater-max" 
                               value="${field.max_rows || ''}"
                               placeholder="Bez limitu">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Layout</label>
                        <select class="yap-setting-repeater-layout">
                            <option value="table" ${(field.repeater_layout || 'table') === 'table' ? 'selected' : ''}>Tabela</option>
                            <option value="block" ${field.repeater_layout === 'block' ? 'selected' : ''}>Blok</option>
                            <option value="row" ${field.repeater_layout === 'row' ? 'selected' : ''}>Wiersz</option>
                        </select>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Tekst przycisku "Dodaj"</label>
                        <input type="text" class="yap-setting-repeater-btn-label" 
                               value="${field.button_label || 'Dodaj wiersz'}"
                               placeholder="Dodaj wiersz">
                    </div>
                    
                    <hr>
                    
                    <div class="yap-setting-group">
                        <div class="yap-info-box" style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea;">
                            <h4 style="margin: 0 0 10px 0; color: #667eea; display: flex; align-items: center; gap: 8px;">
                                <span class="dashicons dashicons-info"></span>
                                Dodawanie pól do repeatera
                            </h4>
                            <p style="margin: 0 0 10px 0; font-size: 13px; line-height: 1.6;">
                                Pola do repeatera dodajesz metodą <strong>Drag & Drop</strong> bezpośrednio na canvasie Visual Buildera.
                            </p>
                            <ol style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.8;">
                                <li>Zamknij ten modal</li>
                                <li>Przeciągnij pola z lewej strony na <strong>szare pole wewnątrz repeatera</strong></li>
                                <li>Pola pojawią się w środku jako sub-fields</li>
                                <li>Możesz je sortować, edytować i usuwać</li>
                            </ol>
                            <div style="margin-top: 12px; padding: 10px; background: white; border-radius: 6px; font-size: 13px;">
                                📊 Aktualnie w repeaterze: <strong>${field.sub_fields ? field.sub_fields.length : 0} ${(field.sub_fields ? field.sub_fields.length : 0) === 1 ? 'pole' : (field.sub_fields ? field.sub_fields.length : 0) < 5 ? 'pola' : 'pól'}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },
        
        /**
         * WYSIWYG settings
         */
        getWysiwygSettings(field) {
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ WYSIWYG Editor Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>Wysokość edytora (px)</label>
                        <input type="number" class="yap-setting-wysiwyg-height" 
                               value="${field.editor_height || '300'}"
                               min="100">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-wysiwyg-media" ${field.media_upload !== false ? 'checked' : ''}>
                            Włącz przycisk upload mediów
                        </label>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-wysiwyg-visual" ${field.tabs === 'visual' || field.tabs === 'all' || !field.tabs ? 'checked' : ''}>
                            Zakładka "Visual"
                        </label>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-wysiwyg-text" ${field.tabs === 'text' || field.tabs === 'all' || !field.tabs ? 'checked' : ''}>
                            Zakładka "Text" (HTML)
                        </label>
                    </div>
                </div>
            `;
        },
        
        /**
         * File/Image settings
         */
        getFileSettings(field) {
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ ${field.type === 'image' ? 'Image' : 'File'} Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>Dozwolone typy plików</label>
                        <input type="text" class="yap-setting-file-types" 
                               value="${field.allowed_types || (field.type === 'image' ? 'jpg, jpeg, png, gif, svg, webp' : '')}"
                               placeholder="${field.type === 'image' ? 'jpg, jpeg, png, gif' : 'pdf, doc, docx, xls, xlsx'}">
                        <p class="description">Oddziel przecinkami (bez kropek)</p>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Maksymalny rozmiar (MB)</label>
                        <input type="number" class="yap-setting-file-maxsize" 
                               value="${field.max_size || '5'}"
                               step="0.1">
                    </div>
                    
                    ${field.type === 'image' ? `
                    <div class="yap-setting-group">
                        <label>Minimalna szerokość (px)</label>
                        <input type="number" class="yap-setting-image-min-width" 
                               value="${field.min_width || ''}"
                               placeholder="Brak limitu">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Maksymalna szerokość (px)</label>
                        <input type="number" class="yap-setting-image-max-width" 
                               value="${field.max_width || ''}"
                               placeholder="Brak limitu">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Rozmiar podglądu</label>
                        <select class="yap-setting-image-preview-size">
                            <option value="thumbnail" ${(field.preview_size || 'thumbnail') === 'thumbnail' ? 'selected' : ''}>Miniaturka (150x150)</option>
                            <option value="medium" ${field.preview_size === 'medium' ? 'selected' : ''}>Średni (300x300)</option>
                            <option value="large" ${field.preview_size === 'large' ? 'selected' : ''}>Duży (1024x1024)</option>
                            <option value="full" ${field.preview_size === 'full' ? 'selected' : ''}>Pełny rozmiar</option>
                        </select>
                    </div>
                    ` : ''}
                </div>
            `;
        },
        
        /**
         * Gallery settings
         */
        getGallerySettings(field) {
            return `
                <div class="yap-type-specific-settings">
                    <h4>⚙️ Gallery Settings</h4>
                    
                    <div class="yap-setting-group">
                        <label>Minimalna liczba zdjęć</label>
                        <input type="number" class="yap-setting-gallery-min" 
                               value="${field.min_images || '0'}"
                               min="0">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Maksymalna liczba zdjęć</label>
                        <input type="number" class="yap-setting-gallery-max" 
                               value="${field.max_images || ''}"
                               placeholder="Bez limitu">
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>Rozmiar miniaturek</label>
                        <select class="yap-setting-gallery-thumb-size">
                            <option value="thumbnail" ${(field.thumb_size || 'thumbnail') === 'thumbnail' ? 'selected' : ''}>Miniaturka (150x150)</option>
                            <option value="medium" ${field.thumb_size === 'medium' ? 'selected' : ''}>Średni (300x300)</option>
                        </select>
                    </div>
                    
                    <div class="yap-setting-group">
                        <label>
                            <input type="checkbox" class="yap-setting-gallery-sortable" ${field.sortable !== false ? 'checked' : ''}>
                            Możliwość sortowania (drag & drop)
                        </label>
                    </div>
                </div>
            `;
        },
        
        /**
         * Bind type-specific events
         */
        bindTypeSpecificEvents(field) {
            const self = this;
            const type = field.type;
            
            // Email
            if (type === 'email') {
                $('.yap-setting-email-validate').on('change', function() {
                    field.validate_email = $(this).is(':checked');
                });
                $('.yap-setting-email-error-msg').on('input', function() {
                    field.email_error_message = $(this).val();
                });
                $('.yap-setting-email-conditional-error').on('change', function() {
                    field.conditional_on_error = $(this).is(':checked');
                });
                $('.yap-setting-email-domains').on('input', function() {
                    field.allowed_domains = $(this).val();
                });
            }
            
            // URL
            if (type === 'url') {
                $('.yap-setting-url-validate').on('change', function() {
                    field.validate_url = $(this).is(':checked');
                });
                $('.yap-setting-url-protocols').on('change', function() {
                    field.allowed_protocols = $(this).val();
                });
            }
            
            // Tel
            if (type === 'tel') {
                $('.yap-setting-tel-format').on('input', function() {
                    field.phone_format = $(this).val();
                });
                $('.yap-setting-tel-prefix').on('input', function() {
                    field.default_prefix = $(this).val();
                });
            }
            
            // Number
            if (type === 'number') {
                $('.yap-setting-number-min').on('input', function() {
                    field.min_value = $(this).val();
                });
                $('.yap-setting-number-max').on('input', function() {
                    field.max_value = $(this).val();
                });
                $('.yap-setting-number-step').on('input', function() {
                    field.step = $(this).val();
                });
                $('.yap-setting-number-decimals').on('change', function() {
                    field.allow_decimals = $(this).is(':checked');
                });
                $('.yap-setting-number-prefix').on('input', function() {
                    field.number_prefix = $(this).val();
                });
                $('.yap-setting-number-suffix').on('input', function() {
                    field.number_suffix = $(this).val();
                });
            }
            
            // Date
            if (type === 'date') {
                $('.yap-setting-date-display-format').on('change', function() {
                    field.date_display_format = $(this).val();
                });
                $('.yap-setting-date-save-format').on('change', function() {
                    field.date_save_format = $(this).val();
                });
                $('.yap-setting-date-min').on('change', function() {
                    field.min_date = $(this).val();
                });
                $('.yap-setting-date-max').on('change', function() {
                    field.max_date = $(this).val();
                });
                $('.yap-setting-date-first-day').on('change', function() {
                    field.first_day_monday = $(this).is(':checked');
                });
            }
            
            // Time
            if (type === 'time') {
                $('.yap-setting-time-format').on('change', function() {
                    field.time_format = $(this).val();
                });
                $('.yap-setting-time-step').on('change', function() {
                    field.time_step = $(this).val();
                });
            }
            
            // DateTime
            if (type === 'datetime') {
                $('.yap-setting-datetime-format').on('change', function() {
                    field.datetime_format = $(this).val();
                });
                $('.yap-setting-datetime-timezone').on('change', function() {
                    field.timezone = $(this).val();
                });
            }
            
            // Color
            if (type === 'color') {
                $('.yap-setting-color-type').on('change', function() {
                    field.color_type = $(this).val();
                    $('.yap-color-palette-group').toggle(field.color_type === 'palette');
                });
                $('.yap-setting-color-format').on('change', function() {
                    field.color_format = $(this).val();
                });
                $('.yap-setting-color-palette').on('input', function() {
                    field.color_palette = $(this).val();
                });
                $('.yap-setting-color-alpha').on('change', function() {
                    field.enable_alpha = $(this).is(':checked');
                });
            }
            
            // Select
            if (type === 'select') {
                this.bindSelectEvents(field);
            }
            
            // Checkbox
            if (type === 'checkbox') {
                this.bindCheckboxEvents(field);
            }
            
            // Radio
            if (type === 'radio') {
                this.bindRadioEvents(field);
            }
            
            // Textarea
            if (type === 'textarea') {
                $('.yap-setting-textarea-rows').on('input', function() {
                    field.rows = $(this).val();
                });
                $('.yap-setting-textarea-maxlength').on('input', function() {
                    field.maxlength = $(this).val();
                });
                $('.yap-setting-textarea-counter').on('change', function() {
                    field.show_counter = $(this).is(':checked');
                });
            }
            
            // Group
            if (type === 'group') {
                $('.yap-setting-group-layout').on('change', function() {
                    field.group_layout = $(this).val();
                });
                $('.yap-setting-group-wrapper').on('change', function() {
                    field.show_wrapper = $(this).is(':checked');
                });
            }
            
            // Repeater
            if (type === 'repeater') {
                this.bindRepeaterEvents(field);
            }
            
            // WYSIWYG
            if (type === 'wysiwyg') {
                $('.yap-setting-wysiwyg-height').on('input', function() {
                    field.editor_height = $(this).val();
                });
                $('.yap-setting-wysiwyg-media').on('change', function() {
                    field.media_upload = $(this).is(':checked');
                });
                $('.yap-setting-wysiwyg-visual, .yap-setting-wysiwyg-text').on('change', function() {
                    const visual = $('.yap-setting-wysiwyg-visual').is(':checked');
                    const text = $('.yap-setting-wysiwyg-text').is(':checked');
                    field.tabs = visual && text ? 'all' : visual ? 'visual' : 'text';
                });
            }
            
            // File/Image
            if (type === 'file' || type === 'image') {
                $('.yap-setting-file-types').on('input', function() {
                    field.allowed_types = $(this).val();
                });
                $('.yap-setting-file-maxsize').on('input', function() {
                    field.max_size = $(this).val();
                });
                if (type === 'image') {
                    $('.yap-setting-image-min-width').on('input', function() {
                        field.min_width = $(this).val();
                    });
                    $('.yap-setting-image-max-width').on('input', function() {
                        field.max_width = $(this).val();
                    });
                    $('.yap-setting-image-preview-size').on('change', function() {
                        field.preview_size = $(this).val();
                    });
                }
            }
            
            // Gallery
            if (type === 'gallery') {
                $('.yap-setting-gallery-min').on('input', function() {
                    field.min_images = $(this).val();
                });
                $('.yap-setting-gallery-max').on('input', function() {
                    field.max_images = $(this).val();
                });
                $('.yap-setting-gallery-thumb-size').on('change', function() {
                    field.thumb_size = $(this).val();
                });
                $('.yap-setting-gallery-sortable').on('change', function() {
                    field.sortable = $(this).is(':checked');
                });
            }
        },
        
        /**
         * Bind select field events
         */
        bindSelectEvents(field) {
            const self = this;
            
            $('.yap-setting-select-multiple').on('change', function() {
                field.multiple = $(this).is(':checked');
            });
            
            $('.yap-setting-select-searchable').on('change', function() {
                field.searchable = $(this).is(':checked');
            });
            
            $('.yap-setting-select-placeholder').on('input', function() {
                field.select_placeholder = $(this).val();
            });
            
            // Add option
            $(document).on('click', '.yap-add-option', function() {
                if (!field.choices) field.choices = [];
                field.choices.push({ label: '', value: '' });
                self.refreshSelectOptions(field);
            });
            
            // Remove option
            $(document).on('click', '.yap-remove-option', function() {
                const index = $(this).closest('.yap-select-option-row').data('index');
                field.choices.splice(index, 1);
                self.refreshSelectOptions(field);
            });
            
            // Update option
            $(document).on('input', '.yap-option-label, .yap-option-value', function() {
                const $row = $(this).closest('.yap-select-option-row');
                const index = $row.data('index');
                const label = $row.find('.yap-option-label').val();
                const value = $row.find('.yap-option-value').val();
                field.choices[index] = { label, value };
            });
            
            // Bulk add
            $(document).on('click', '.yap-bulk-add-options', function() {
                const text = prompt('Wpisz opcje (po jednej na linię):\nFormat: Etykieta | Wartość\nLub samo: Etykieta (wartość = etykieta w lowercase)');
                if (text) {
                    const lines = text.split('\n').filter(l => l.trim());
                    lines.forEach(line => {
                        const parts = line.split('|').map(p => p.trim());
                        const label = parts[0];
                        const value = parts[1] || label.toLowerCase().replace(/\s+/g, '_');
                        if (!field.choices) field.choices = [];
                        field.choices.push({ label, value });
                    });
                    self.refreshSelectOptions(field);
                }
            });
            
            // Import JSON
            $(document).on('click', '.yap-import-options', function() {
                const json = $('.yap-setting-select-json').val().trim();
                try {
                    const options = JSON.parse(json);
                    if (Array.isArray(options)) {
                        field.choices = options;
                        self.refreshSelectOptions(field);
                        alert('✅ Zaimportowano ' + options.length + ' opcji');
                    } else {
                        alert('❌ JSON musi być tablicą');
                    }
                } catch(e) {
                    alert('❌ Błędny format JSON');
                }
            });
        },
        
        /**
         * Refresh select options UI
         */
        refreshSelectOptions(field) {
            const options = field.choices || [];
            const optionsHTML = options.map((opt, i) => `
                <div class="yap-select-option-row" data-index="${i}">
                    <input type="text" class="yap-option-label" value="${opt.label || ''}" placeholder="Etykieta">
                    <input type="text" class="yap-option-value" value="${opt.value || ''}" placeholder="Wartość">
                    <button type="button" class="button yap-remove-option">✕</button>
                </div>
            `).join('');
            
            $('.yap-select-options-container').html(optionsHTML || '<p class="yap-no-options">Brak opcji.</p>');
        },
        
        /**
         * Bind checkbox events
         */
        bindCheckboxEvents(field) {
            const self = this;
            
            $('.yap-setting-checkbox-layout').on('change', function() {
                field.layout = $(this).val();
            });
            
            $(document).on('click', '.yap-add-checkbox-option', function() {
                if (!field.choices) field.choices = [];
                field.choices.push({ label: '', value: '', default: false });
                self.refreshCheckboxOptions(field);
            });
            
            $(document).on('click', '.yap-remove-option', function() {
                const index = $(this).closest('.yap-checkbox-option-row').data('index');
                field.choices.splice(index, 1);
                self.refreshCheckboxOptions(field);
            });
            
            $(document).on('input', '.yap-option-label, .yap-option-value', function() {
                const $row = $(this).closest('.yap-checkbox-option-row');
                const index = $row.data('index');
                const label = $row.find('.yap-option-label').val();
                const value = $row.find('.yap-option-value').val();
                const defaultChecked = $row.find('.yap-option-default').is(':checked');
                field.choices[index] = { label, value, default: defaultChecked };
            });
            
            $(document).on('change', '.yap-option-default', function() {
                const $row = $(this).closest('.yap-checkbox-option-row');
                const index = $row.data('index');
                field.choices[index].default = $(this).is(':checked');
            });
        },
        
        /**
         * Refresh checkbox options UI
         */
        refreshCheckboxOptions(field) {
            const options = field.choices || [];
            const optionsHTML = options.map((opt, i) => `
                <div class="yap-checkbox-option-row" data-index="${i}">
                    <input type="text" class="yap-option-label" value="${opt.label || ''}" placeholder="Etykieta">
                    <input type="text" class="yap-option-value" value="${opt.value || ''}" placeholder="Wartość">
                    <label><input type="checkbox" class="yap-option-default" ${opt.default ? 'checked' : ''}> Domyślnie zaznaczony</label>
                    <button type="button" class="button yap-remove-option">✕</button>
                </div>
            `).join('');
            
            $('.yap-checkbox-options-container').html(optionsHTML || '<p class="yap-no-options">Brak opcji.</p>');
        },
        
        /**
         * Bind radio events
         */
        bindRadioEvents(field) {
            const self = this;
            
            $('.yap-setting-radio-layout').on('change', function() {
                field.layout = $(this).val();
            });
            
            $(document).on('click', '.yap-add-radio-option', function() {
                if (!field.choices) field.choices = [];
                field.choices.push({ label: '', value: '', default: false });
                self.refreshRadioOptions(field);
            });
            
            $(document).on('click', '.yap-remove-option', function() {
                const index = $(this).closest('.yap-radio-option-row').data('index');
                field.choices.splice(index, 1);
                self.refreshRadioOptions(field);
            });
            
            $(document).on('input', '.yap-option-label, .yap-option-value', function() {
                const $row = $(this).closest('.yap-radio-option-row');
                const index = $row.data('index');
                const label = $row.find('.yap-option-label').val();
                const value = $row.find('.yap-option-value').val();
                const defaultChecked = $row.find('.yap-option-default').is(':checked');
                field.choices[index] = { label, value, default: defaultChecked };
            });
            
            $(document).on('change', '.yap-option-default', function() {
                const $row = $(this).closest('.yap-radio-option-row');
                const index = $row.data('index');
                // Uncheck all others
                field.choices.forEach((c, i) => c.default = (i === index));
                self.refreshRadioOptions(field);
            });
        },
        
        /**
         * Refresh radio options UI
         */
        refreshRadioOptions(field) {
            const options = field.choices || [];
            const optionsHTML = options.map((opt, i) => `
                <div class="yap-radio-option-row" data-index="${i}">
                    <input type="text" class="yap-option-label" value="${opt.label || ''}" placeholder="Etykieta">
                    <input type="text" class="yap-option-value" value="${opt.value || ''}" placeholder="Wartość">
                    <label><input type="radio" name="yap-default-radio" class="yap-option-default" ${opt.default ? 'checked' : ''}> Domyślny</label>
                    <button type="button" class="button yap-remove-option">✕</button>
                </div>
            `).join('');
            
            $('.yap-radio-options-container').html(optionsHTML || '<p class="yap-no-options">Brak opcji.</p>');
        },
        
        /**
         * Bind repeater events
         */
        bindRepeaterEvents(field) {
            const self = this;
            
            $('.yap-setting-repeater-min').on('input', function() {
                field.min_rows = $(this).val();
            });
            
            $('.yap-setting-repeater-max').on('input', function() {
                field.max_rows = $(this).val();
            });
            
            $('.yap-setting-repeater-layout').on('change', function() {
                field.repeater_layout = $(this).val();
            });
            
            $('.yap-setting-repeater-btn-label').on('input', function() {
                field.button_label = $(this).val();
            });
            
            // Add subfield
            $(document).on('click', '.yap-add-subfield', function() {
                const type = $('.yap-subfield-type-select').val();
                const name = $('.yap-subfield-name-input').val().trim();
                
                if (!name) {
                    alert('Wprowadź nazwę pola');
                    return;
                }
                
                if (!field.sub_fields) field.sub_fields = [];
                
                field.sub_fields.push({
                    name: name.toLowerCase().replace(/\s+/g, '_'),
                    label: name,
                    type: type
                });
                
                $('.yap-subfield-name-input').val('');
                self.refreshRepeaterSubfields(field);
            });
            
            // Remove subfield
            $(document).on('click', '.yap-remove-subfield', function() {
                const index = $(this).closest('.yap-repeater-subfield').data('index');
                field.sub_fields.splice(index, 1);
                self.refreshRepeaterSubfields(field);
            });
        },
        
        /**
         * Refresh repeater subfields UI
         */
        refreshRepeaterSubfields(field) {
            const subFields = field.sub_fields || [];
            const subFieldsHTML = subFields.map((sf, i) => `
                <div class="yap-repeater-subfield" data-index="${i}">
                    <strong>${sf.label || sf.name}</strong> <em>(${sf.type})</em>
                    <button type="button" class="button yap-remove-subfield">✕</button>
                </div>
            `).join('');
            
            $('.yap-repeater-subfields-container').html(subFieldsHTML || '<p class="yap-no-subfields">Brak pól.</p>');
        }
    };

})(jQuery);
