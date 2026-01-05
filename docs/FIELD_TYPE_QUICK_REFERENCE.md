# Field Type Registry - Quick Reference

> Szybkie komendy i snippety do typów pół

---

## 📦 Registry API

### Rejestracja Typu

```javascript
// Rejestruj nowy typ
FieldTypeRegistry.register('mytype', MyFieldType);

// Sprawdź czy zainstalowany
if (FieldTypeRegistry.has('mytype')) { ... }

// Pobierz typ
const Type = FieldTypeRegistry.get('mytype');

// Pobierz wszystkie
const all = FieldTypeRegistry.getAll();
```

### Tworzenie Pól

```javascript
// Stwórz z domyślami
const field = FieldTypeRegistry.createField('text');

// Stwórz z overridami
const field = FieldTypeRegistry.createField('text', {
    name: 'email',
    label: 'Email',
    required: true
});

// Pobierz domyślne wartości
const defaults = FieldTypeRegistry.getDefaults('text');
```

---

## 🔧 Field Type API

### Definiuj Typ

```javascript
class MyType extends BaseFieldType {
    // Wymagane
    static get type() { return 'mytype'; }
    static defaults() { return { ...super.defaults(), ... }; }
    static renderAdmin(field, value) { return '...'; }
    
    // Opcjonalne
    static settingsSchema() { return [...]; }
    static renderPreview(field) { return '...'; }
    static validate(value, field) { return {valid, error}; }
    static sanitize(value) { return value; }
    static format(value, field) { return value; }
}

FieldTypeRegistry.register('mytype', MyType);
```

---

## 🎯 Najczęstsze Operacje

### 1. Renderuj Field w UI

```javascript
const field = FieldTypeRegistry.createField('text', { label: 'Name' });
const html = FieldTypeRegistry.get('text').renderAdmin(field, 'John');
document.getElementById('form').innerHTML += html;
```

### 2. Waliduj Wartość

```javascript
const field = FieldTypeRegistry.createField('email', { label: 'Email' });
const result = FieldTypeRegistry.get('email').validate('user@example.com', field);

if (result.valid) {
    console.log('OK!');
} else {
    console.log('Error:', result.error);
}
```

### 3. Oczyszcz Dane

```javascript
const field = FieldTypeRegistry.createField('text');
const clean = FieldTypeRegistry.get('text').sanitize(userInput);
```

### 4. Formatuj do Wyświetlenia

```javascript
const field = FieldTypeRegistry.createField('date', { date_format: 'YYYY-MM-DD' });
const formatted = FieldTypeRegistry.get('date').format('2024-01-15', field);
console.log(formatted); // "2024-01-15"
```

---

## 📋 Built-in Types

| Type | HTML | Validation |
|------|------|-----------|
| `text` | `<input type="text" />` | length, pattern |
| `textarea` | `<textarea />` | length, pattern |
| `select` | `<select />` | in options list |
| `checkbox` | `<input type="checkbox" />` | boolean |
| `radio` | `<input type="radio" />` | in options |
| `email` | `<input type="email" />` | email regex |
| `number` | `<input type="number" />` | min/max/step |

---

## 💡 Custom Type Template

```javascript
class ColorFieldType extends BaseFieldType {
    // 1. Type ID
    static get type() { return 'color'; }
    
    // 2. Defaults
    static defaults() {
        return {
            ...super.defaults(),
            type: 'color',
            enable_alpha: false,
            default_value: '#000000'
        };
    }
    
    // 3. Settings Schema
    static settingsSchema() {
        const base = super.settingsSchema();
        base[1].fields.push({
            name: 'enable_alpha',
            label: 'Enable Alpha',
            type: 'checkbox'
        });
        return base;
    }
    
    // 4. Admin Rendering
    static renderAdmin(field, value = '') {
        return `
            <input 
                type="color" 
                name="${field.name}" 
                value="${value || field.default_value}"
                ${field.required ? 'required' : ''}
            >
        `;
    }
    
    // 5. Validation
    static validate(value, field) {
        const base = super.validate(value, field);
        if (!base.valid) return base;
        
        if (!/^#[0-9A-F]{6}$/i.test(value)) {
            return { valid: false, error: 'Invalid color format' };
        }
        return { valid: true };
    }
    
    // 6. Sanitize
    static sanitize(value) {
        return /^#[0-9A-F]{6}$/i.test(value) ? value : '#000000';
    }
}

// Register
FieldTypeRegistry.register('color', ColorFieldType);
```

---

## ⚙️ Settings Schema Structure

```javascript
[
    // Panel 0: Basic
    {
        label: 'Basic',
        fields: [
            {
                name: 'label',
                label: 'Field Label',
                type: 'text',
                required: true,
                hint: 'Shown to users'
            },
            {
                name: 'description',
                label: 'Description',
                type: 'textarea',
                hint: 'Help text'
            }
        ]
    },
    // Panel 1: Type-specific
    {
        label: 'Options',
        fields: [
            {
                name: 'min_length',
                label: 'Minimum Length',
                type: 'number',
                default: 0
            },
            {
                name: 'allow_special',
                label: 'Allow Special Chars',
                type: 'checkbox'
            }
        ]
    }
]
```

---

## 🧪 Testing

```javascript
// Test rendering
const html = TextFieldType.renderAdmin({name: 'test'}, 'value');
console.assert(html.includes('<input'), 'Should have input');

// Test validation
const valid = TextFieldType.validate('test', {});
console.assert(valid.valid === true, 'Should be valid');

// Test invalid validation
const invalid = TextFieldType.validate('', {required: true});
console.assert(invalid.valid === false, 'Should be invalid');

// Test sanitization
const clean = TextFieldType.sanitize('  test  ');
console.assert(clean === 'test', 'Should be trimmed');
```

---

## 🐛 Debugging

```javascript
// Enable debug mode
FieldTypeRegistry.debug = true;

// Check registry state
console.log(FieldTypeRegistry.getAll());

// Test type directly
const Type = FieldTypeRegistry.get('text');
console.log(Type.defaults());
console.log(Type.settingsSchema());

// Test field creation
const field = FieldTypeRegistry.createField('text', {name: 'test'});
console.log(field);

// Test validation
const result = Type.validate('test', field);
console.log(result);

// Test rendering
const html = Type.renderAdmin(field, 'value');
console.log(html);
```

---

## 📂 File Structure

```
includes/js/field-types/
├── registry.js           # Core system
├── field-types.js        # 7 built-in types
└── examples.js           # Custom type examples

docs/
├── CUSTOM_FIELD_TYPES.md # Full guide
└── FIELD_TYPE_REGISTRY_INTEGRATION.md # Integration guide
```

---

## 🔗 Related Files

- [Complete Guide](./CUSTOM_FIELD_TYPES.md) - Full documentation
- [Integration Guide](./FIELD_TYPE_REGISTRY_INTEGRATION.md) - How to integrate
- [Visual Builder Docs](./VISUAL_BUILDER_DOCS.md) - Builder context
- [Examples](../includes/js/field-types/examples.js) - Custom type samples

---

## ✅ Checklist: Creating Custom Type

- [ ] Extends `BaseFieldType`
- [ ] Has `type` getter
- [ ] Implements `defaults()`
- [ ] Implements `renderAdmin()`
- [ ] Has `settingsSchema()` (optional but recommended)
- [ ] Has `validate()` (optional)
- [ ] Has `sanitize()` (optional but recommended)
- [ ] Registered with `FieldTypeRegistry.register()`
- [ ] Tested in console
- [ ] Added to enqueue.php if custom file

---

## 🚀 Common Patterns

### Pattern 1: Extend Existing Type

```javascript
class StrictEmailType extends EmailFieldType {
    static defaults() {
        const base = super.defaults();
        base.pattern = '^[a-z0-9._%+-]+@company\\.com$';
        return base;
    }
}

FieldTypeRegistry.register('company_email', StrictEmailType);
```

### Pattern 2: Conditional Rendering

```javascript
static renderAdmin(field, value = '') {
    if (field.use_large_textarea) {
        return `<textarea rows="10" name="${field.name}">${value}</textarea>`;
    }
    return `<textarea rows="3" name="${field.name}">${value}</textarea>`;
}
```

### Pattern 3: Complex Validation

```javascript
static validate(value, field) {
    // Check basic
    const base = super.validate(value, field);
    if (!base.valid) return base;
    
    // Check length
    if (value.length < field.min) {
        return { valid: false, error: 'Too short' };
    }
    
    // Check pattern
    if (field.pattern && !new RegExp(field.pattern).test(value)) {
        return { valid: false, error: 'Invalid format' };
    }
    
    // Check custom rule
    if (field.custom_check && !field.custom_check(value)) {
        return { valid: false, error: 'Custom rule failed' };
    }
    
    return { valid: true };
}
```

### Pattern 4: Async Validation

```javascript
static async validate(value, field) {
    const base = super.validate(value, field);
    if (!base.valid) return base;
    
    try {
        const response = await fetch('/api/check', {
            method: 'POST',
            body: JSON.stringify({value})
        });
        const data = await response.json();
        
        if (!data.available) {
            return { valid: false, error: 'Already taken' };
        }
    } catch (error) {
        return { valid: false, error: 'Check failed' };
    }
    
    return { valid: true };
}
```

---

## 💎 Pro Tips

1. **Always call super first**
   ```javascript
   static validate(value, field) {
       const base = super.validate(value, field);
       if (!base.valid) return base;
       // Your validation here
   }
   ```

2. **Use field metadata for flexibility**
   ```javascript
   // Instead of hardcoding, use field config
   const width = field.input_width || '100%';
   return `<input style="width: ${width};" ... >`;
   ```

3. **Test edge cases**
   ```javascript
   // Empty string, null, undefined, special chars
   const cases = ['', null, undefined, '<script>', '   '];
   cases.forEach(value => {
       const result = MyType.validate(value, field);
       console.log(`"${value}" → ${result.valid}`);
   });
   ```

4. **Document your type**
   ```javascript
   /**
    * Custom email field type
    * 
    * Extends EmailFieldType with company-specific validation.
    * Only accepts @company.com addresses.
    * 
    * Usage:
    * const field = FieldTypeRegistry.createField('company_email');
    */
   class CompanyEmailType extends EmailFieldType { ... }
   ```

---

## 🎓 Learning Path

1. Start: Read [Quick Reference](#) (this file)
2. Understand: Read [Complete Guide](./CUSTOM_FIELD_TYPES.md)
3. Integrate: Follow [Integration Guide](./FIELD_TYPE_REGISTRY_INTEGRATION.md)
4. Create: Use [Examples](../includes/js/field-types/examples.js) as template
5. Test: Use patterns from [Testing section](#🧪-testing)
6. Deploy: Add to enqueue.php and commit

---

**Version:** 1.0.0  
**Last Updated:** 2024  
**Status:** ✅ Production Ready
