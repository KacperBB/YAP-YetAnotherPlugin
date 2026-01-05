# 🧪 Custom Templates v1.1.0 - Testing Instructions

## 📋 Pre-Test Checklist

- [ ] Code is deployed/saved
- [ ] Browser is open to WordPress admin
- [ ] You have access to Visual Builder
- [ ] Browser DevTools available (F12)

---

## 🔧 Setup: Clear Cache & Load New Version

### Step 1: Hard Refresh Browser
```
Windows/Linux: Ctrl + Shift + R
Mac: Cmd + Shift + R
```

**Why?** The script version changed from 1.0.1 → 1.1.0, so we need to force load the new code.

### Step 2: Verify in Console
Open DevTools: **F12 → Console tab**

Run:
```javascript
CustomTemplates.getAll()
```

Should return an object (empty {} if no templates created yet).

---

## ✅ Test 1: Modal Opens Correctly

### Steps:
1. Go to WordPress admin: **YAP → Visual Builder**
2. **Add any field** (e.g., Text field)
3. Click on the field in the canvas
4. In the settings modal, look for **"🎨 Stwórz Template"** button
5. Click the button

### Expected Result:
✅ Modal dialog opens with:
- Title: "➕ Stwórz Custom Template"
- Form fields visible
- Icon picker grid with emoji buttons displayed
- Preview box on the right side

### If it fails:
```
❌ Modal doesn't open
→ Check console (F12) for JavaScript errors
→ Hard refresh again
→ Check if jQuery is loaded: type jQuery in console

❌ Icon picker not visible
→ Check CSS loaded: Inspect element → Styles tab
→ Look for .yap-icon-picker-grid styles
```

---

## ✅ Test 2: Icon Picker Works

### Steps:
1. Modal is open (from Test 1)
2. **Click an emoji button** (e.g., 📧)

### Expected Result:
✅ 
- Button gets selected (border highlight + background color)
- Icon input field updates
- Preview box updates immediately
- Icon appears larger in preview

### Visual feedback:
```
Before click:         After clicking 📧:
.yap-icon-picker-btn → .yap-icon-picker-btn.active
Border: gray          Border: blue (--yap-primary)
Background: white     Background: light purple
```

### If it fails:
```
❌ Button doesn't highlight
→ Check CSS .yap-icon-picker-btn.active styles
→ Inspect element on button

❌ Preview doesn't update
→ Check JavaScript console for errors
→ Look for event handler in custom-templates.js line ~158
```

---

## ✅ Test 3: Manual Icon Input

### Steps:
1. Modal is open
2. Find the **Icon input field** (has placeholder "🎨")
3. **Clear the field** (Ctrl+A, Delete)
4. **Type or paste emoji:** 🏠 (or any emoji)

### Expected Result:
✅
- Icon appears immediately in preview box
- Field shows your typed emoji
- Preview updates in real-time

### Test emojis:
- 🏠 (house)
- 📧 (email)  
- 🎯 (target)
- ⭐ (star)

### If it fails:
```
❌ Preview doesn't update
→ JavaScript error in console
→ Check `#templateIcon` input handler at line ~164

❌ Emoji doesn't display
→ Browser/OS doesn't support that emoji
→ Try different emoji
→ Common ones always work: 📧 🏠 ⭐
```

---

## ✅ Test 4: Form Validation

### Steps:
1. Modal is open
2. **Leave fields empty** (or partially filled)
3. Click **"Stwórz Template"** button

### Expected Result:
✅ Alert appears: **"Nazwa, etykieta i ikona są wymagane!"**

### Test each field:
```javascript
Test 1: Leave Template Name empty
→ Alert should appear ✓

Test 2: Leave Icon empty  
→ Alert should appear ✓

Test 3: Leave Label empty
→ Alert should appear ✓

Test 4: Fill all three
→ Alert should NOT appear ✓
→ Template should save
```

### If it fails:
```
❌ Alert doesn't appear
→ Check validation code at line ~168
→ Check button click handler
→ Verify jQuery is working
```

---

## ✅ Test 5: Template Creation

### Steps:
1. Modal is open
2. **Fill form:**
   - **Template Name:** `test_contact`
   - **Icon:** Click 📧 or type your own
   - **Label:** `Contact Form`
   - **Description:** (optional) `Test template`
3. Click **"➕ Stwórz Template"**

### Expected Result:
✅
- Modal closes
- Success notification appears: **"Template "Contact Form" stworzony!"**
- Toast notification visible for 3-5 seconds
- No console errors

### Check in console:
```javascript
CustomTemplates.getAll()

// Should show:
{
  "custom_1704067834567": {
    "name": "test_contact",
    "label": "Contact Form",
    "icon": "📧",
    "description": "Test template",
    ...
  }
}
```

### If it fails:
```
❌ Modal doesn't close
→ Check if save completed
→ Run CustomTemplates.getAll() in console
→ Check for errors

❌ Toast doesn't show
→ YAPBuilderExt might not be loaded
→ Check console for warnings

❌ Template not in localStorage
→ Check tab: DevTools → Application → localStorage
→ Look for key "yap_custom_templates"
```

---

## ✅ Test 6: Template Appears in Sidebar

### Steps:
1. Created template in Test 5
2. **Look at the left sidebar** in Visual Builder
3. Scroll down to find **"Custom Templates 🎨"** section

### Expected Result:
✅ New section visible with:
- Header: "Custom Templates 🎨"
- Your template listed: `📧 Contact Form`
- Icon displays correctly
- Draggable (cursor changes to grab cursor)

### Visual:
```
📝 Text Fields
   ├─ Text
   ├─ Email
   ...

Custom Templates 🎨      ← New section
├─ 📧 Contact Form      ← Your template with icon!
```

### If it fails:
```
❌ Section doesn't appear
→ Refresh page
→ Run: CustomTemplates.refreshFieldSelector()
→ Check console for errors

❌ Icon shows as box/placeholder
→ Browser doesn't support emoji
→ Try different emoji
→ Use text fallback

❌ Can't see template name
→ Check CSS for label styling
→ Width might be too narrow
```

---

## ✅ Test 7: Drag & Drop Template

### Steps:
1. Template visible in sidebar (from Test 6)
2. **Click and drag** template to canvas
3. Release mouse on canvas area

### Expected Result:
✅
- Dragging cursor shows (grab icon)
- Template gets "dragging" class (visual feedback)
- Fields appear on canvas
- New group created with template fields

### Check canvas:
- Original field still there
- New group added below with template's fields

### If it fails:
```
❌ Can't drag template
→ Browser might not support drag & drop
→ Try Firefox or Chrome
→ Check drag handler at line ~346

❌ Fields don't appear
→ Check JavaScript errors
→ Verify YAPBuilder is initialized
→ Run: CustomTemplates.addToSchema('template_id') in console

❌ Wrong fields appear
→ Check template data: CustomTemplates.getTemplate('id')
→ Verify fields array is correct
```

---

## ✅ Test 8: Edit Template Icon

### Steps:
1. Template created (from Test 5)
2. In Visual Builder canvas, **click on a field from that template**
3. Settings modal opens
4. Click **"🎨 Stwórz Template"** again
5. **Change the Icon** to different emoji (e.g., 🏠)
6. Click **"💾 Zaktualizuj"** (not "Stwórz")

### Expected Result:
✅
- Modal recognizes it's an edit (button says "Zaktualizuj")
- Icon selected field pre-filled
- Form fields pre-filled with template data
- Successfully updates
- Icon changes in sidebar

### Check in sidebar:
- Before: `📧 Contact Form`
- After: `🏠 Contact Form`

### If it fails:
```
❌ Edit button not showing
→ Template not found in localStorage
→ Check: CustomTemplates.getTemplate(id)

❌ Icon doesn't update
→ Refresh page
→ Check localStorage for updated icon
```

---

## ✅ Test 9: Multiple Drags

### Steps:
1. Template in sidebar (from Test 6)
2. **Drag template 3 times** to canvas
3. Each time release on canvas

### Expected Result:
✅
- Template can be dragged multiple times
- Each drag creates new fields on canvas
- All dragged instances are separate

### Canvas should show:
```
Contact Form 1
├─ [template fields]

Contact Form 2
├─ [template fields]

Contact Form 3
├─ [template fields]
```

### If it fails:
```
❌ Can only drag once
→ Check drag & drop unbinding
→ Might need to refresh selector

❌ Same fields get updated
→ Check if fields getting unique IDs
→ FieldStabilization might not be working
→ Check: FieldStabilization.generateShortId()
```

---

## ✅ Test 10: Browser Refresh Persistence

### Steps:
1. Created template (from Test 5)
2. **Hard refresh page:** Ctrl+Shift+R
3. Open Visual Builder again
4. Check if template still there

### Expected Result:
✅
- Template still in sidebar
- Icon still correct
- Can still drag it
- localStorage preserved data

### Check:
```javascript
// In console after refresh
CustomTemplates.getAll()
// Should show template with icon
```

### If it fails:
```
❌ Template disappeared
→ Check localStorage wasn't cleared
→ DevTools → Application → localStorage → yap_custom_templates
→ Should have your template data

❌ Icon lost
→ Check if icon field was saved
→ Might need to edit and resave
```

---

## 📊 Test Summary Form

### Copy & fill as you test:

```
Test 1: Modal Opens
Result: [ ] Pass [ ] Fail

Test 2: Icon Picker Works
Result: [ ] Pass [ ] Fail

Test 3: Manual Icon Input
Result: [ ] Pass [ ] Fail

Test 4: Form Validation
Result: [ ] Pass [ ] Fail

Test 5: Template Creation
Result: [ ] Pass [ ] Fail

Test 6: Sidebar Display
Result: [ ] Pass [ ] Fail

Test 7: Drag & Drop
Result: [ ] Pass [ ] Fail

Test 8: Edit Template
Result: [ ] Pass [ ] Fail

Test 9: Multiple Drags
Result: [ ] Pass [ ] Fail

Test 10: Persistence
Result: [ ] Pass [ ] Fail

Overall Status: [ ] All Pass [ ] Some Fail

Issues Found:
- 
- 
- 

Notes:
```

---

## 🐛 Common Issues & Fixes

### Issue: jQuery $ undefined error
```javascript
❌ TypeError: $ is not a function

✅ Fix: All $ replaced with jQuery
✅ Already fixed in custom-templates.js
```

### Issue: Templates don't show custom icons
```
❌ All show 🎨 generic icon

✅ Check if icon field saved:
   CustomTemplates.getTemplate('id')
   
✅ Edit template and change icon again
```

### Issue: Icon picker grid doesn't show
```
❌ Blank space where icons should be

✅ Check CSS loaded:
   - F12 → Elements tab
   - Inspect .yap-icon-picker-grid
   - Should have display: grid
   
✅ Check screen width (might be too narrow)
```

### Issue: Drag doesn't work after test 7
```
❌ Can't drag anymore

✅ Run in console:
   CustomTemplates.refreshFieldSelector()
   
✅ Or refresh page
```

---

## 📝 Notes for Testing

1. **Browser Console** - Keep it open throughout testing
   - Look for red errors
   - Look for yellow warnings
   - Check Network tab for failed requests

2. **localStorage Inspection**
   - F12 → Application tab
   - Storage → localStorage
   - Find "yap_custom_templates" key
   - Can see all template data

3. **Performance** - Should be no lag
   - Icon picker should be instant
   - Drag should be smooth
   - No freezing or jank

4. **Visual Polish** - Should look professional
   - Icons should be clear
   - Colors should match theme
   - Spacing should be consistent
   - No broken elements

---

## ✅ Final Sign-Off

When all 10 tests pass:

```javascript
// Run final verification
console.log('✅ All tests passed!');
console.log(CustomTemplates.getAll());
console.log('Templates stored:', Object.keys(CustomTemplates.getAll()).length);
```

**Expected output:**
- No console errors
- Templates array contains your created templates
- Each template has: id, name, label, icon, fields, etc.

---

## 📞 Need Help?

If tests fail:

1. **Check console for errors** (F12)
2. **Verify files were updated:**
   - Check `/includes/js/custom-templates.js` has icon picker code
   - Check `/includes/css/admin/admin-style.css` has new CSS
   - Check version in visual-builder.php is 1.1.0

3. **Clear all browser cache:**
   - Ctrl+Shift+Delete (Chrome/Firefox)
   - Or: Settings → Privacy → Clear browsing data

4. **Test in incognito mode** to rule out cache issues

---

**Ready to test?** Start with Test 1! 🚀
