#!/usr/bin/env bash
# Run PHPUnit tests for YetAnotherPlugin
# 
# Usage:
#   ./run-tests.sh                  # Run all tests
#   ./run-tests.sh --filter test    # Run specific test
#   ./run-tests.sh --coverage       # Generate coverage report

PLUGIN_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Check if PHPUnit is installed
if ! command -v phpunit &> /dev/null; then
    echo "PHPUnit is not installed. Installing..."
    composer install
fi

# Run tests
echo "Running YAP Unit Tests..."
echo "========================"

phpunit "$@" --configuration "$PLUGIN_DIR/phpunit.xml"

echo ""
echo "Test execution complete."
