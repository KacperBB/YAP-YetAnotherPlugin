# Changelog

All notable changes to Yet Another Plugin (YAP) will be documented in this file.

## [1.0.7] - 2025-11-25

### Added
- **Hierarchical Nested Groups View**: Nested groups now display under their parent groups with visual hierarchy
- **Toggle Filter**: Checkbox to show/hide nested groups in the groups list
- **Visual Indicators**: 
  - Icons (📦 for main groups, 📁 for nested)
  - "Zagnieżdżona" badge for nested groups
  - Indentation and special background for child groups
- **Recursive Rendering**: All levels of nested groups are now properly displayed
- **AJAX Live Refresh**: Groups list refreshes without page reload
- **AJAX Delete**: Delete groups without page reload with toast notifications

### Changed
- Improved nested group form positioning in edit view
- Enhanced CSS with modern gradients and animations
- Better checkbox styling (simple border instead of toggle switch)
- Nested groups now render properly within table structure

### Fixed
- Nested groups forms now appear directly under their respective groups
- All nested group levels now show in the list (not just first level)
- Proper hierarchical relationship detection between parent and child groups

## [1.0.6] - 2025-11-25

### Added
- Modern UI redesign with sidebar layout
- CSS scoped to YAP pages only
- Toast notification system

### Fixed
- CSS no longer affects other WordPress admin pages

## [1.0.5] - 2025-11-25

### Fixed
- Data tables no longer editable (read-only statistics only)
- Empty duplicate entries in data groups resolved

## [1.0.4] - 2025-11-25

### Added
- Complete UI modernization with modern CSS
- Card-based layouts with gradients and shadows
- Smooth animations and transitions
- "Wszystkie" options for post types and categories

## [1.0.3] - 2025-11-25

### Fixed
- Image preview now displays after update
- Nested groups preservation during group update

## [1.0.2] - 2025-11-25

### Added
- WordPress Media Library integration for image fields
- Image selector in edit forms and nested groups
- Image preview in metaboxes

### Changed
- Image and nested_group field values auto-clear via JavaScript

## [1.0.1] - 2025-11-25

### Fixed
- AJAX field creation now working
- JavaScript loading issues resolved

## [1.0.0] - 2025-11-24

### Added
- Initial release
- Dynamic table generation per field group
- Unlimited nested field groups
- Post type and category filtering
- Custom field types: short text, long text, number, image, nested group
- Template functions for frontend display
