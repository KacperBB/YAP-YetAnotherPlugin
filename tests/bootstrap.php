<?php
/**
 * PHPUnit Bootstrap file
 * 
 * Initializes WordPress test environment for YAP plugin tests
 * 
 * @package YetAnotherPlugin
 */

// Get the WordPress test directory
$_tests_dir = getenv('WP_TESTS_DIR');

// Check if test directory exists
if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
    // Load the main plugin file
    require dirname(dirname(__FILE__)) . '/yetanotherplugin.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Load helper classes
require_once dirname(__FILE__) . '/helpers/class-test-helpers.php';

echo "✅ Test environment loaded successfully\n";
echo "Testing plugin: Yet Another Plugin (YAP)\n";
echo "Test file: tests/test-visual-builder.php\n";
