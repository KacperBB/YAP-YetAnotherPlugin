# 🔧 Drag & Drop Field Reordering - FIX v1.4.7

## Problem Zgłoszony
Po przeciągnięciu pól na inną pozycję, wszystkie zmienione pola pokazywały błąd:
```
❌ Field not found: field_1767648478025
```
I nie można było wejść w opcje tych pól.

## Root Cause Analysis

Problem było multi-layerowy:

### 1. **Context Loss w Sortable Callback**
```javascript
// ❌ BAD - this context lost in arrow function
update: () => {
    this.updateFieldOrder(); // this = undefined!
}
```

### 2. **jQuery .data() Caching Issue**
```javascript
// ❌ BAD - jQuery caches data attributes
const fieldId = $(this).data('field-id');

// ✅ GOOD - Always read fresh from DOM
const fieldId = $(this).attr('data-field-id');
```

### 3. **Schema/DOM Mismatch**
Po sortable reorder, DOM mogł być out-of-sync ze schematem, szczególnie jeśli:
- Pola były zduplikowane
- Pola były usuwane i dodawane
- jQuery event handlers miały cache'owane elementy

## Fixes Applied

### Fix #1: Store Context in initSortable
```javascript
// File: visual-builder.js, line ~82
initSortable() {
    const self = this; // Store context BEFORE callback
    
    $('#yap-drop-zone').sortable({
        update: function() {
            self.updateFieldOrder(); // Use self, not this
        },
        stop: function() {
            self.bindFieldEvents();
            self.reinitializeContainerFields();
        }
    });
}
```

### Fix #2: Use .attr() Instead of .data()
```javascript
// Everywhere we read data-field-id:
const fieldId = $(this).attr('data-field-id'); // Read fresh from DOM
```

### Fix #3: Add Validation in bindFieldEvents
```javascript
// File: visual-builder.js, line ~218
$(document).on('click', '.yap-field-edit', function(e) {
    const fieldId = $(this).attr('data-field-id');
    
    // NEW: Validate field exists
    const field = self.schema.fields.find(f => f.id === fieldId);
    if (!field) {
        console.error('Field not found, resyncing...');
        self.clearCanvas();
        self.schema.fields.forEach(f => self.renderField(f));
        self.bindFieldEvents();
        return;
    }
    
    self.editField(fieldId);
});
```

### Fix #4: Enhanced updateFieldOrder Debug Logging
```javascript
// More detailed logging to catch mismatches:
// - DOM field count vs schema field count
// - Each field lookup with fallback message
// - Final mismatch detection and error
```

## Files Changed

- `visual-builder.php` - Version bumped to 1.4.7
- `visual-builder.js` - 4 major fixes:
  1. Line ~82: initSortable context fix
  2. Line ~220: bindFieldEvents with validation
  3. Line ~1625: updateFieldOrder attr() fix + better logging
  4. Line ~972: editField validation

## Testing

### Test Case: Reorder Multiple Fields
1. Add 3+ fields to canvas
2. Drag field A down one position
3. Drag field C up one position
4. Verify all fields can be edited
5. Check console for any "Field not found" errors

### Test Case: Duplicate + Reorder
1. Add field
2. Duplicate it
3. Reorder both copies
4. Verify both can be edited

### Test Case: Add + Reorder
1. Add field A
2. Add field B
3. Drag A below B
4. Verify both editable

## Expected Behavior After Fix

✅ Drag & drop fields freely  
✅ All reordered fields remain editable  
✅ No "Field not found" errors  
✅ Click edit on any field opens modal  
✅ Console shows detailed reorder logging  

## If Issue Persists

1. **Hard refresh** (Ctrl+Shift+R)
2. **Check console** (F12) for errors
3. **Copy console output** including:
   - "🔄 updateFieldOrder called" logs
   - "Available IDs" list
   - Any "Field not found" errors
4. **Report with**: What you did, what happened, console output

## Technical Details

### Why .data() vs .attr()?
jQuery's .data() method caches data in memory. After DOM manipulation:
- DOM attribute stays in sync
- jQuery .data() cache may be stale

### Why store 'self' context?
In jQuery sortable callbacks, `this` refers to the sortable element, not the YAPBuilder instance.
Solution: Store `const self = this` before the callback.

### Why validate before edit?
If DOM/schema mismatch occurs, auto-fix by:
1. Clearing canvas
2. Re-rendering all fields
3. Re-binding events
This ensures perfect sync.

## Version History

- **v1.4.7** - Drag & drop field reordering fixes
  - Context loss fix
  - jQuery cache fix  
  - Validation + auto-fix
  - Enhanced logging

- **v1.4.6** - Custom Templates System
- **v1.4.5** - History tracking for field operations
- **v1.4.4** - Previous version

## Performance Impact

✅ No performance impact:
- Same number of DOM operations
- Only changed how we read data
- Added validation (minimal overhead)
- Better logging (disable in production if needed)

---

**Fix Deployed:** v1.4.7  
**Status:** ✅ READY TO TEST
