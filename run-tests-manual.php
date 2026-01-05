<?php
/**
 * Manual Test Runner for YAP Plugin
 * 
 * This is a simple manual test runner that can be accessed via HTTP
 * to verify field rendering without complex PHPUnit setup
 * 
 * Usage: Visit in browser or run: php run-tests-manual.php
 * 
 * @package YetAnotherPlugin
 */

// Determine if we're running via CLI or HTTP
$is_cli = php_sapi_name() === 'cli';

// Set up basic output
if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YAP Plugin - Manual Test Runner</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        h2 { color: #667eea; margin-top: 30px; }
        .test-group { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #667eea; border-radius: 4px; }
        .test { margin: 10px 0; padding: 10px; background: white; border: 1px solid #e0e0e0; border-radius: 4px; }
        .test.pass { border-left: 4px solid #48bb78; background: #f0fdf4; }
        .test.fail { border-left: 4px solid #f56565; background: #fef2f2; }
        .test.skip { border-left: 4px solid #ed8936; background: #fffbf0; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 3px; font-weight: bold; font-size: 12px; }
        .status.pass { background: #48bb78; color: white; }
        .status.fail { background: #f56565; color: white; }
        .status.skip { background: #ed8936; color: white; }
        .details { margin-top: 10px; padding: 10px; background: #f5f5f5; border-radius: 3px; font-size: 12px; color: #666; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: Monaco, monospace; }
        .summary { padding: 15px; background: #f0fdf4; border: 1px solid #48bb78; border-radius: 4px; margin: 20px 0; }
        .error { color: #f56565; font-weight: bold; }
        .success { color: #48bb78; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 YAP Plugin - Manual Test Runner</h1>';
}

// Define test functions
class YAP_Manual_Tests {
    
    public static $results = [];
    public static $plugin_path = '';
    
    public static function init() {
        // Get plugin path
        self::$plugin_path = dirname(__FILE__);
        
        if (php_sapi_name() !== 'cli') {
            echo '<div class="test-group">';
            echo '<h2>Test Execution</h2>';
            echo '<p>Running manual tests for YAP plugin...</p>';
        }
        
        self::test_file_exists();
        self::test_css_updated();
        self::test_color_picker_html();
        self::test_color_picker_classes();
        self::test_admin_file_syntax();
        
        self::print_summary();
    }
    
    public static function test_file_exists() {
        $files = [
            'includes/admin.php',
            'includes/css/admin/admin-style.css',
            'yetanotherplugin.php',
        ];
        
        $name = 'Critical Files Exist';
        $all_exist = true;
        $missing = [];
        
        foreach ($files as $file) {
            $path = self::$plugin_path . '/' . $file;
            if (!file_exists($path)) {
                $all_exist = false;
                $missing[] = $file;
            }
        }
        
        if ($all_exist) {
            self::add_result('pass', $name, 'All critical files present');
        } else {
            self::add_result('fail', $name, 'Missing files: ' . implode(', ', $missing));
        }
    }
    
    public static function test_css_updated() {
        $css_path = self::$plugin_path . '/includes/css/admin/admin-style.css';
        $content = file_get_contents($css_path);
        
        $name = 'CSS Color Picker Styling Added';
        
        $checks = [
            'input[type="color"]' => strpos($content, 'input[type="color"]') !== false,
            '.yap-color-picker-wrapper' => strpos($content, '.yap-color-picker-wrapper') !== false,
            '.yap-color-value' => strpos($content, '.yap-color-value') !== false,
            'shadcn' => strpos($content, 'shadcn/ui') !== false || strpos($content, 'Color Picker') !== false,
        ];
        
        $passed = count(array_filter($checks)) === count($checks);
        
        if ($passed) {
            self::add_result('pass', $name, 'All color picker CSS rules added');
        } else {
            $missing = array_keys(array_filter($checks, fn($v) => !$v));
            self::add_result('fail', $name, 'Missing CSS: ' . implode(', ', $missing));
        }
    }
    
    public static function test_color_picker_html() {
        $admin_path = self::$plugin_path . '/includes/admin.php';
        $content = file_get_contents($admin_path);
        
        $name = 'Color Picker HTML Updated';
        
        // Check for new color picker wrapper implementation
        $has_wrapper = strpos($content, 'yap-color-picker-wrapper') !== false;
        $has_display = strpos($content, 'yap-color-value') !== false;
        $has_js = strpos($content, 'data-color-display') !== false;
        
        if ($has_wrapper && $has_display && $has_js) {
            self::add_result('pass', $name, 'Color picker HTML structure updated with wrapper and display');
        } else {
            $missing = [];
            if (!$has_wrapper) $missing[] = 'wrapper div';
            if (!$has_display) $missing[] = 'display span';
            if (!$has_js) $missing[] = 'JavaScript handler';
            self::add_result('fail', $name, 'Missing elements: ' . implode(', ', $missing));
        }
    }
    
    public static function test_color_picker_classes() {
        $admin_path = self::$plugin_path . '/includes/admin.php';
        $content = file_get_contents($admin_path);
        
        $name = 'Color Picker Class Names Correct';
        
        $classes = [
            'yap-color-picker-wrapper',
            'yap-color-value',
            'yap-repeater-color',
        ];
        
        $all_found = true;
        $missing_classes = [];
        
        foreach ($classes as $class) {
            if (strpos($content, $class) === false) {
                $all_found = false;
                $missing_classes[] = $class;
            }
        }
        
        if ($all_found) {
            self::add_result('pass', $name, 'All CSS classes present in HTML');
        } else {
            self::add_result('fail', $name, 'Missing classes: ' . implode(', ', $missing_classes));
        }
    }
    
    public static function test_admin_file_syntax() {
        $admin_path = self::$plugin_path . '/includes/admin.php';
        
        $name = 'Admin PHP Syntax Valid';
        
        // Try to parse the file
        $output = [];
        $return_var = 0;
        exec('php -l ' . escapeshellarg($admin_path), $output, $return_var);
        
        if ($return_var === 0) {
            self::add_result('pass', $name, 'PHP syntax is valid');
        } else {
            $error = implode("\n", $output);
            self::add_result('fail', $name, 'Syntax error: ' . substr($error, 0, 100));
        }
    }
    
    public static function add_result($status, $name, $details = '') {
        self::$results[] = [
            'status' => $status,
            'name' => $name,
            'details' => $details,
        ];
        
        if (php_sapi_name() !== 'cli') {
            $status_html = '<span class="status ' . $status . '">' . strtoupper($status) . '</span>';
            echo '<div class="test ' . $status . '">';
            echo $status_html . ' <strong>' . htmlspecialchars($name) . '</strong>';
            if ($details) {
                echo '<div class="details">' . htmlspecialchars($details) . '</div>';
            }
            echo '</div>';
        }
    }
    
    public static function print_summary() {
        if (php_sapi_name() !== 'cli') {
            echo '</div>'; // Close test-group
        }
        
        $pass_count = count(array_filter(self::$results, fn($r) => $r['status'] === 'pass'));
        $fail_count = count(array_filter(self::$results, fn($r) => $r['status'] === 'fail'));
        $total_count = count(self::$results);
        
        if (php_sapi_name() !== 'cli') {
            $summary_class = $fail_count === 0 ? 'success' : 'error';
            echo '<div class="summary">';
            echo '<h2>📊 Test Summary</h2>';
            echo '<p>';
            echo '<strong>Total Tests:</strong> ' . $total_count . ' | ';
            echo '<span class="success">✓ Passed: ' . $pass_count . '</span> | ';
            echo '<span class="error">✗ Failed: ' . $fail_count . '</span>';
            echo '</p>';
            if ($fail_count === 0) {
                echo '<p class="success">✅ All tests passed! Color picker styling is ready.</p>';
            } else {
                echo '<p class="error">❌ Some tests failed. Please review the details above.</p>';
            }
            echo '</div>';
            echo '</div></body></html>';
        } else {
            echo "\n========================================\n";
            echo "Test Summary\n";
            echo "========================================\n";
            echo "Total: $total_count | Pass: $pass_count | Fail: $fail_count\n";
            if ($fail_count === 0) {
                echo "\n✅ All tests passed!\n";
            } else {
                echo "\n❌ Some tests failed:\n";
                foreach (self::$results as $result) {
                    if ($result['status'] === 'fail') {
                        echo "  - " . $result['name'] . ": " . $result['details'] . "\n";
                    }
                }
            }
        }
    }
}

// Run tests
YAP_Manual_Tests::init();
