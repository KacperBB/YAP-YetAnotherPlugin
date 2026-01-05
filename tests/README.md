# YAP Unit Tests

Comprehensive unit test suite for Yet Another Plugin (YAP) - WordPress Custom Fields Management System.

## Overview

This test suite validates:
- **Visual Builder Core:** Field type detection, schema creation, conditional logic
- **Flexible Content:** Layout management, block differentiation, data persistence
- **Repeater Integration:** Block option differentiation when FC is nested in repeaters
- **Complete Workflows:** End-to-end field group creation and management

## Test Structure

```
tests/
├── test-visual-builder.php       # Main test suite (8 test classes, 40+ methods)
├── bootstrap.php                  # WordPress test environment initialization
└── helpers/
    └── class-test-helpers.php    # Test utilities and mock data generators
```

### Test Classes

1. **YAP_Visual_Builder_Tests**
   - Singleton pattern validation
   - Field type detection and registration
   - Schema creation and export
   - Flexible Content layout definition
   - Block differentiation and slug generation
   - Conditional logic and field dependencies

2. **YAP_Flexible_Content_Tests**
   - Layout storage and retrieval
   - Section data validation
   - Block identification by slug
   - Field set uniqueness between layouts
   - Field type validation
   - Rendering options and configuration

3. **YAP_Repeater_Flexible_Content_Tests** ⭐ **CRITICAL**
   - Block option differentiation in repeater context
   - Block type retrieval from repeater data
   - Field-level configuration persistence
   - Data isolation between blocks
   - Layout-specific field options

4. **YAP_Visual_Builder_Integration_Tests**
   - Complete workflow: Create group → Add fields → Configure FC
   - Schema export and import
   - Multi-layout scenarios
   - Field dependency resolution

## Installation

### Prerequisites
- PHP 7.4+
- WordPress 5.0+
- Composer
- PHPUnit 9.5+

### Setup

1. Install dependencies:
```bash
composer install
```

2. Configure database for tests (edit phpunit.xml):
```xml
<env name="DB_NAME" value="wordpress_test"/>
<env name="DB_USER" value="root"/>
<env name="DB_PASSWORD" value=""/>
<env name="DB_HOST" value="localhost"/>
```

3. Create test database:
```sql
CREATE DATABASE wordpress_test;
GRANT ALL PRIVILEGES ON wordpress_test.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

## Running Tests

### All Tests
```bash
# Using Composer
composer run test

# Using PHPUnit directly
phpunit

# Using Windows batch script
run-tests.bat

# Using Linux/Mac bash script
./run-tests.sh
```

### Specific Test Class
```bash
phpunit --filter YAP_Visual_Builder_Tests

phpunit --filter YAP_Flexible_Content_Tests

# CRITICAL: Block differentiation in repeater
phpunit --filter YAP_Repeater_Flexible_Content_Tests
```

### Specific Test Method
```bash
# Test block slug generation
phpunit --filter test_slug_generation_from_layout_name

# Test block differentiation (CRITICAL)
phpunit --filter test_block_options_differentiation_in_repeater
```

### With Coverage Report
```bash
# HTML report
composer run test:coverage

# Console output
phpunit --coverage-text
```

### Watch Mode (Monitor file changes)
```bash
composer run test:watch
```

## Critical Tests - Block Differentiation

When Flexible Content is added to a Repeater field, blocks must maintain type-specific field options. These tests validate this behavior:

```php
// ✅ Each layout should have distinct options
test_block_differentiation_in_flexible_content()

// ✅ Blocks in repeater should differentiate by type
test_block_options_differentiation_in_repeater()

// ✅ Retrieved block should have correct options
test_retrieving_blocks_by_type_in_repeater()

// ✅ Blocks shouldn't interfere with each other
test_block_data_isolation()
```

Expected behavior:
- Hero Section block → has {title, image, cta_button}
- Testimonials block → has {quote, author, author_image}
- 3 Columns block → has {col_1_title, col_2_title, col_3_title}

## Test Data

The test suite includes realistic data based on YAP's naming conventions:

### Logical Block Names (Slugs)
- `hero_section` - Hero banner with image and CTA
- `testimonials` - Customer testimonial with quote and author
- `features` - Feature list or benefits section
- `columns_3` - 3-column layout grid
- `cta_banner` - Call-to-action section

### Test Helper Methods
```php
// Create flexible content group
YAP_Test_Helpers::create_flexible_group($config)

// Get default test layouts
YAP_Test_Helpers::get_default_layouts()

// Create section with data
YAP_Test_Helpers::create_section('hero_section', $data)

// Validate layout configuration
YAP_Test_Helpers::validate_layout($layout)

// Generate mock blocks
YAP_Test_Helpers::generate_mock_blocks($count)
```

## Expected Test Results

### Passing Tests (Current)
- ✅ Slug generation from layout names
- ✅ Field type validation
- ✅ Layout name uniqueness
- ✅ Schema creation
- ✅ Flexible content layout definition

### Tests Requiring Implementation
- ⏳ Block differentiation in repeater (CRITICAL)
- ⏳ Block option differentiation
- ⏳ Field isolation in repeater blocks
- ⏳ Block type retrieval

## Debugging Tests

### Enable Verbose Output
```bash
phpunit --verbose

phpunit -vvv  # More verbose
```

### Stop on First Failure
```bash
phpunit --stop-on-failure
```

### Run Single Test with Debugging
```bash
phpunit --filter test_block_options_differentiation_in_repeater --debug
```

### PHP Error Reporting
Tests are configured with:
```php
error_reporting(E_ALL);
ini_set('display_errors', '1');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', false);  // Don't log to file
define('WP_DEBUG_DISPLAY', true);
```

## Continuous Integration

For GitHub Actions or similar CI systems:

```yaml
- name: Run PHPUnit Tests
  run: |
    composer install
    composer run test:coverage
```

## Test Coverage

Target coverage for critical areas:
- `includes/visual-builder.php` - 90%+
- `includes/flexible-content.php` - 90%+
- `includes/repeater.php` - 85%+
- Block differentiation logic - 100%

Generate coverage report:
```bash
phpunit --coverage-html coverage/
```

Open `coverage/index.html` in browser.

## Contributing

When adding new tests:

1. Create test class extending `WP_UnitTestCase`
2. Follow naming convention: `test_*` for test methods
3. Use `YAP_Test_Helpers` for common operations
4. Document expected behavior in comment above test method
5. Run full suite before committing

## Troubleshooting

### "WordPress not loaded" Error
- Verify phpunit.xml WP_PATH
- Check WordPress installation exists at path
- Run: `composer install`

### "Database connection failed"
- Verify test database exists
- Check DB credentials in phpunit.xml
- Ensure MySQL service is running
- Create database: `CREATE DATABASE wordpress_test;`

### "Plugin not loaded in tests"
- Check tests/bootstrap.php `define('YAP_PLUGIN_DIR', ...)`
- Verify plugin file exists at: `YAP_PLUGIN_DIR . '/yetanotherplugin.php'`
- Run: `require_once YAP_PLUGIN_DIR . '/yetanotherplugin.php';`

### PHPUnit Not Found
```bash
composer install
composer run test
```

## Resources

- [PHPUnit Documentation](https://phpunit.readthedocs.io/)
- [WordPress Testing Guide](https://develop.wordpress.org/handbook/testing/)
- [Brain Monkey Mocking](https://github.com/Brain-WP/BrainMonkey)

## License

GPL-2.0-or-later

---

**Status:** Test infrastructure complete. Ready for execution and block differentiation implementation.
