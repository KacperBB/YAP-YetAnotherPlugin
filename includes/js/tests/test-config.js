/**
 * YAP Test Configuration
 * 
 * Globalna konfiguracja dla testów Visual Buildera
 * 
 * @package YetAnotherPlugin
 * @since 1.5.0
 */

const YAPTestConfig = {
    // Ustawienia czasowe
    timing: {
        modalOpenDelay: 100,      // ms do czasu sprawdzenia czy modal się otworzył
        eventBindingDelay: 50,    // ms czekania na binding eventów
        saveDelay: 400,           // ms czekania na zapisanie zmian
        fieldEditDelay: 500,      // ms między edytowaniem pól
        tabSwitchDelay: 200       // ms czekania na przełączenie taba
    },
    
    // Selektory CSS dla elementów
    selectors: {
        fieldItem: '.yap-field-item',
        fieldEdit: '.yap-field-edit',
        fieldLabel: '.yap-field-label',
        fieldType: '.yap-field-type',
        
        modal: '#yap-field-settings-modal',
        modalShow: '.yap-modal-show',
        modalSave: '.yap-settings-save',
        modalCancel: '.yap-settings-cancel',
        
        // Pola formularza w modalu
        settingName: '.yap-setting-name',
        settingLabel: '.yap-setting-label',
        settingPlaceholder: '.yap-setting-placeholder',
        settingDefault: '.yap-setting-default',
        settingDescription: '.yap-setting-description',
        settingClass: '.yap-setting-class',
        settingRequired: '.yap-setting-required',
        
        // Taby
        settingsTab: '.yap-settings-tab',
        settingsPanel: '.yap-settings-panel',
        
        // Logika warunkowa
        conditionalCheckbox: '.yap-setting-conditional',
        conditionalRules: '.yap-conditional-rules',
        
        // Sub-fields
        subFieldItem: '.yap-sub-field-item',
        subFieldEdit: '.yap-sub-field-edit'
    },
    
    // Typy pól do testowania
    fieldTypes: {
        text: 'text',
        number: 'number',
        select: 'select',
        checkbox: 'checkbox',
        radio: 'radio',
        textarea: 'textarea',
        date: 'date',
        time: 'time',
        color: 'color',
        email: 'email',
        url: 'url',
        flexible_content: 'flexible_content',
        group: 'group',
        repeater: 'repeater'
    },
    
    // Przykładowe wartości dla testów
    testValues: {
        names: [
            'field_basic',
            'field_required',
            'field_with_class',
            'field_conditional',
            'field_styled'
        ],
        
        labels: [
            'Basic Field',
            'Required Field',
            'Styled Field',
            'Conditional Field',
            'Test Field'
        ],
        
        placeholders: [
            'Enter text here...',
            'Type something...',
            'Provide a value...',
            'This is a placeholder',
            'Fill in this field'
        ],
        
        descriptions: [
            'This is a basic field description',
            'This field is required',
            'Custom styled field with CSS',
            'This field appears conditionally',
            'Test field description'
        ],
        
        cssClasses: [
            'custom-field',
            'wide-field',
            'highlight',
            'bold-text',
            'custom-field wide-field'
        ],
        
        defaultValues: [
            'default_value',
            'initial_text',
            'placeholder_value',
            'example_value',
            'test_default'
        ]
    },
    
    // Warunki do testowania logiki warunkowej
    conditions: {
        equals: {
            operator: 'equals',
            description: 'Field equals value',
            value: 'test_value'
        },
        notEquals: {
            operator: 'not_equals',
            description: 'Field not equals value',
            value: 'test_value'
        },
        contains: {
            operator: 'contains',
            description: 'Field contains text',
            value: 'test'
        },
        greaterThan: {
            operator: 'greater_than',
            description: 'Field greater than value',
            value: '10'
        },
        lessThan: {
            operator: 'less_than',
            description: 'Field less than value',
            value: '100'
        },
        isChecked: {
            operator: 'is_checked',
            description: 'Field is checked',
            value: ''
        }
    },
    
    // Ustawienia logowania
    logging: {
        enabled: true,
        level: 'info',  // 'debug', 'info', 'warn', 'error'
        showTimestamp: false,
        showStackTrace: false
    },
    
    // Ustawienia debugowania
    debug: {
        enabled: true,
        logElementSelection: true,
        logEventBinding: true,
        logValueChanges: true,
        logModalState: true
    },
    
    // Timeout dla testów asynchronicznych
    asyncTimeout: 5000,
    
    // Maksymalna liczba retries dla flaky testów
    maxRetries: 2,
    
    /**
     * Pobierz selektor CSS
     */
    getSelector(key) {
        return this.selectors[key] || null
    },
    
    /**
     * Pobierz wartość testową
     */
    getTestValue(category, index = 0) {
        if (this.testValues[category] && this.testValues[category][index]) {
            return this.testValues[category][index]
        }
        return null
    },
    
    /**
     * Pobierz losową wartość testową
     */
    getRandomTestValue(category) {
        if (this.testValues[category]) {
            const array = this.testValues[category]
            return array[Math.floor(Math.random() * array.length)]
        }
        return null
    },
    
    /**
     * Log z konfiguracją
     */
    log(message, level = 'info') {
        if (!this.logging.enabled) return
        
        const timestamp = this.logging.showTimestamp ? `[${new Date().toISOString()}] ` : ''
        const prefix = {
            'debug': '🔍',
            'info': 'ℹ️',
            'warn': '⚠️',
            'error': '❌'
        }[level] || '📝'
        
        console.log(`${prefix} ${timestamp}${message}`)
    }
};

// Eksportuj do globalnego zakresu
window.YAPTestConfig = YAPTestConfig;

// Zaladuj dodatkowe moduły testów jeśli nie są załadowane
document.addEventListener('DOMContentLoaded', function() {
    // YAPBuilderTests i YAPAdvancedTests będą załadowane przez test-runner.html
    // Lub ręcznie załaduj je:
    // const scripts = [
    //     '/path/to/visual-builder-field-editing.test.js',
    //     '/path/to/visual-builder-advanced.test.js'
    // ];
});

console.log('%cYAP Test Configuration załadowana', 'color: #0073aa; font-weight: bold;');
