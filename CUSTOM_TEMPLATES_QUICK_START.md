# 🎨 Custom Templates - Quick Start Demo

## 5-Minute Setup

### Step 1: Open Visual Builder
```
WordPress Admin → YAP → Visual Builder
```

### Step 2: Create a Test Field
- Add any field (e.g., Text field)
- Label it something like "My Test Field"

### Step 3: Create Template from Field
1. Click on the field you just created
2. Look for **"🎨 Stwórz Template"** button in the modal
3. Click it → Template creation dialog opens

### Step 4: Fill Template Form

```
┌─────────────────────────────────────────────────────┐
│ ➕ Stwórz Custom Template                        [×] │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Nazwa szablonu *              Ikona *            │
│  ┌──────────────────────┐  ┌──────────────────┐   │
│  │ test_template        │  │ 🎨 │ [🎨 Preview] │   │
│  └──────────────────────┘  └──────────────────┘   │
│  Będzie widoczna w menu...   Emoji lub symbol...  │
│                                                     │
│  Szybki wybór ikony:                              │
│  ┌───┬───┬───┬───┬───┬───┬───┬───┐              │
│  │ 🎨│ 📝│ 📋│ 📊│ 📈│ 📉│ 💼│ 👤│              │
│  ├───┼───┼───┼───┼───┼───┼───┼───┤              │
│  │ 🏢│ 🏭│ 📞│ 📧│ 🌐│ 🔐│ 🔑│ ⚙️│              │
│  └───┴───┴───┴───┴───┴───┴───┴───┘              │
│  (40+ icons available)                            │
│                                                     │
│  Etykieta (label) *                               │
│  ┌─────────────────────────────────────────────┐ │
│  │ My Test Template                            │ │
│  └─────────────────────────────────────────────┘ │
│  Nazwa wyświetlana w selektorze pól...          │
│                                                     │
│  Opis                                             │
│  ┌─────────────────────────────────────────────┐ │
│  │ This is a test template for demo            │ │
│  └─────────────────────────────────────────────┘ │
│                                                     │
│  Pola w szablonie:                                │
│  ┌─────────────────────────────────────────────┐ │
│  │ 📝 My Test Field (text)                     │ │
│  └─────────────────────────────────────────────┘ │
│                                                     │
├─────────────────────────────────────────────────────┤
│ [Anuluj]                    [➕ Stwórz Template]   │
└─────────────────────────────────────────────────────┘
```

### Step 5: Fill in the Form

| Field | Value | Notes |
|-------|-------|-------|
| **Nazwa szablonu** | `test_template` | Internal name, no spaces |
| **Ikona** | Click 📧 or 🏠 | Or type your own emoji |
| **Etykieta** | `Contact Form` | Will show in selector |
| **Opis** | `Quick contact block` | Optional tooltip |

### Step 6: Click "Stwórz Template"

You should see:
- ✅ Success notification: "Template "Contact Form" stworzony!"
- 📍 New section appears on left: "Custom Templates 🎨"
- 📧 Your template shows with the icon you chose

### Step 7: Use the Template!

**Drag & Drop:**
1. Find your template in "Custom Templates 🎨" section on the left
2. Click and drag it to the canvas
3. Your field(s) appear on the canvas
4. Can drag it multiple times!

**Result:**
```
Canvas:
├─ My Test Field (original)
└─ Contact Form Template    ← New from template
   └─ My Test Field (copy from template)
```

---

## 🎨 Icon Examples

### Office Use:
- 📧 **Email Contact** - `📧 name: email, email, phone`
- 📞 **Phone Support** - `📞 name: name, number, category`
- 📋 **Checklist** - `📋 tasks, status, priority`
- 📊 **Report Data** - `📊 title, metrics, chart`

### Real Estate:
- 🏠 **Property Info** - `🏠 address, price, beds, baths`
- 🏢 **Building** - `🏢 floors, sqft, type, location`
- 📍 **Neighborhood** - `📍 schools, transit, amenities`

### E-commerce:
- 📦 **Product** - `📦 name, price, stock, images`
- 💳 **Payment** - `💳 card, expiry, cvv, address`
- ⭐ **Review** - `⭐ rating, comment, author, date`

### Team:
- 👤 **Employee** - `👤 name, role, email, phone`
- 💼 **Company** - `💼 name, address, contact, size`
- 🌐 **Website** - `🌐 url, category, description`

---

## 🔄 Edit Template

### To Add More Icons to Template:

1. In "Custom Templates 🎨" section, find your template
2. Click the field with that template
3. Click "🎨 Stwórz Template" button again
4. Change the **Ikona** field
5. Click "💾 Zaktualizuj"
6. Icon updates immediately!

---

## 💾 Check in Console

```javascript
// View all your templates
CustomTemplates.getAll()

// Output:
{
  "custom_1704067834567": {
    "id": "custom_1704067834567",
    "name": "test_template",
    "label": "Contact Form",
    "icon": "📧",
    "description": "Quick contact block",
    "fields": [
      {
        "name": "my_test_field",
        "label": "My Test Field",
        "type": "text",
        "icon": "📝",
        ...
      }
    ],
    "created_at": 1704067834567,
    "updated_at": 1704067834567
  }
}
```

---

## ✅ Troubleshooting

### Template doesn't appear in sidebar
- [ ] Hard refresh: Ctrl+Shift+R
- [ ] Check console: `CustomTemplates.getAll()`
- [ ] Click browser reload button

### Icon shows as box/placeholder
- Your browser might not support that emoji
- Try a different emoji from the picker
- Use common emojis: 📧 📞 🏠 💼 ⭐

### Can't drag template to canvas
- [ ] Make sure you're in Visual Builder
- [ ] Try refresh the page
- [ ] Check if dropzone is enabled

### Template lost after refresh
- [ ] If browser clears localStorage
- [ ] Check in DevTools: Application → localStorage → yap_custom_templates
- [ ] Save important templates via export

---

## 🎬 Live Demo Workflow

### Scenario: Creating a Contact Form Template

**Start:**
```
Visual Builder is open with blank canvas
```

**Step 1 - Create Fields:**
1. Add Text field → Label: "Full Name"
2. Add Email field → Label: "Email Address"
3. Add Textarea field → Label: "Message"

**Step 2 - Create Template:**
1. Click on the group/container
2. Click "🎨 Stwórz Template"
3. Fill form:
   - Name: `contact_form`
   - Icon: Click 📧
   - Label: `Contact Form`
   - Description: `Email contact with name and message`
4. Click "➕ Stwórz Template"

**Step 3 - Success:**
- See notification ✅
- "Custom Templates 🎨" appears on left
- Shows: `📧 Contact Form`

**Step 4 - Reuse:**
- Drag `📧 Contact Form` to canvas
- See all 3 fields appear
- Can drag again multiple times!

**Result:**
```
Canvas:
├─ Contact Form 1
│  ├─ Full Name
│  ├─ Email Address
│  └─ Message
│
├─ Contact Form 2
│  ├─ Full Name
│  ├─ Email Address
│  └─ Message
│
└─ Contact Form 3
   ├─ Full Name
   ├─ Email Address
   └─ Message
```

---

## 🚀 Tips & Tricks

### Pro Tips:

1. **Use Descriptive Names**
   - Good: `newsletter_signup`, `address_form`, `product_review`
   - Bad: `template1`, `stuff`, `random`

2. **Match Icon to Purpose**
   - 📧 for email/contact forms
   - 🏠 for address/location fields
   - ⭐ for ratings/reviews
   - 📊 for data/statistics

3. **Descriptive Labels**
   - Helps team members understand what each template is for
   - Use proper capitalization
   - Keep it concise (2-4 words max)

4. **Group Related Templates**
   - By function: Contact, Address, Media
   - By department: Sales, HR, Finance
   - Use icon categories to organize

5. **Document Complex Templates**
   - Use description field for special instructions
   - Mention dependencies or requirements
   - Example: "Requires ACF Pro for repeaters"

---

## 🔗 Related Resources

- [Full Guide](./CUSTOM_TEMPLATES_GUIDE.md)
- [Technical Docs](./CUSTOM_TEMPLATES_UPDATE_v1.1.0.md)
- [Console API](./CUSTOM_TEMPLATES_GUIDE.md#-api-reference)

---

**Happy templating!** 🎨✨
