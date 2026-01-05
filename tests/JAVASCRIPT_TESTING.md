# JavaScript Testing Guide

For testing Visual Builder JavaScript functionality, particularly the Flexible Content UI interactions.

## Test Framework Setup

### Option 1: Jest + WordPress Test Utilities

```bash
npm install --save-dev jest @wordpress/scripts @testing-library/jest-dom
```

### Option 2: QUnit (WordPress Standard)

```bash
npm install --save-dev qunit
```

## JavaScript Test Examples

### Test File: tests/js/flexible-content.test.js

```javascript
/**
 * Tests for Flexible Content JavaScript
 */

describe('Flexible Content UI', () => {
    
    describe('Layout Loading', () => {
        test('should load layouts on modal open', () => {
            // Arrange
            const mockLayouts = [
                { name: 'hero_section', label: 'Hero Section' },
                { name: 'testimonials', label: 'Testimonials' }
            ];
            
            // Act
            window.YAP.FlexibleContent.loadLayouts();
            
            // Assert
            expect(jQuery('.fc-layout-item')).toHaveLength(2);
        });
    });
    
    describe('Slug Generation', () => {
        test('should auto-generate slug from label', () => {
            // Arrange
            const $input = jQuery('<input id="layout-label" value="Hero Section">');
            
            // Act
            $input.trigger('keyup');
            
            // Assert
            const slug = jQuery('#layout-slug').val();
            expect(slug).toBe('hero_section');
        });
        
        test('should handle Polish characters', () => {
            // Arrange
            const $input = jQuery('<input id="layout-label" value="Sekcja Bohatera">');
            
            // Act
            $input.trigger('keyup');
            
            // Assert
            const slug = jQuery('#layout-slug').val();
            expect(slug).toBe('sekcja_bohatera');
        });
    });
    
    describe('Block Management in Repeater', () => {
        test('should maintain field options per block type', () => {
            // Arrange
            const heroBlock = {
                layout: 'hero_section',
                fields: ['title', 'image', 'cta_button']
            };
            
            const testimonialBlock = {
                layout: 'testimonials',
                fields: ['quote', 'author', 'author_image']
            };
            
            // Act
            window.YAP.FlexibleContent.addBlock(heroBlock);
            window.YAP.FlexibleContent.addBlock(testimonialBlock);
            
            // Assert
            const $heroFields = jQuery('[data-layout="hero_section"] .fc-field');
            const $testimonialFields = jQuery('[data-layout="testimonials"] .fc-field');
            
            expect($heroFields).toHaveLength(3);
            expect($testimonialFields).toHaveLength(3);
            expect($heroFields.text()).not.toContain('quote');
            expect($testimonialFields.text()).toContain('quote');
        });
    });
});
```

## Running JavaScript Tests

```bash
# Using npm
npm test

# Using Jest watch mode
npm test -- --watch

# With coverage
npm test -- --coverage
```

## Key Testing Scenarios

1. **Modal Loading**
   - ✅ Layouts load on modal open
   - ✅ Layouts display correct labels
   - ✅ Modal shows spinner during load

2. **Slug Generation**
   - ✅ Auto-generates from label on keyup
   - ✅ Handles Polish characters
   - ✅ Validates slug format
   - ✅ Shows validation errors

3. **Repeater Integration**
   - ✅ Blocks maintain their layout type
   - ✅ Each block shows correct fields
   - ✅ Adding new blocks preserves previous block options
   - ✅ Deleting block doesn't affect others

4. **Drag & Drop**
   - ✅ Sortable works correctly
   - ✅ Placeholder animates
   - ✅ Drop position is correct

5. **Form Submission**
   - ✅ Block data serializes correctly
   - ✅ Nested field data is preserved
   - ✅ Sub-field values are collected

## Test Utilities

```javascript
// Helper to wait for AJAX
function waitForAjax() {
    return new Promise(resolve => {
        jQuery(document).ajaxComplete(() => resolve());
    });
}

// Helper to simulate modal open
function openModal() {
    jQuery('#flexible-layouts-modal').modal('show');
    return waitForAjax();
}

// Helper to add block
function addBlock(layoutName) {
    jQuery('[data-layout="' + layoutName + '"]').trigger('click');
}

// Helper to get block fields
function getBlockFields(layoutName) {
    return jQuery('[data-layout="' + layoutName + '"] .fc-field').map((i, el) => {
        return jQuery(el).data('field-name');
    }).get();
}
```

## Coverage Goals

- Visual Builder UI: 85%+
- Flexible Content JS: 90%+
- Repeater JS handlers: 80%+

Generate coverage:
```bash
npm test -- --coverage --coverageReporters=html
```

---

*Note: JavaScript tests complement PHPUnit backend tests. Focus on UI interactions and data binding.*
