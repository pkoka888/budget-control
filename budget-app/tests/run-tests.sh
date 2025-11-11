#!/bin/bash

# Automated Testing Script for Budget App
# This script runs all tests and generates reports

set -e  # Exit on any error

echo "=== Budget App Automated Testing ==="
echo "Starting test execution at $(date)"
echo

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# Check if PHPUnit is installed
if ! command -v phpunit &> /dev/null; then
    print_status $RED "Error: PHPUnit is not installed or not in PATH"
    print_status $YELLOW "Please install PHPUnit:"
    echo "  composer require --dev phpunit/phpunit"
    echo "  or download from https://phpunit.de/"
    exit 1
fi

# Check if PHP is available
if ! command -v php &> /dev/null; then
    print_status $RED "Error: PHP is not installed or not in PATH"
    exit 1
fi

# Create necessary directories
mkdir -p tests/results
mkdir -p tests/coverage

print_status $BLUE "Setting up test environment..."

# Clean up any existing test database
if [ -f "tests/test_database.db" ]; then
    rm tests/test_database.db
fi

print_status $BLUE "Running PHPUnit tests..."

# Run tests with different configurations
echo "Running unit tests..."
phpunit --configuration phpunit.xml --testsuite="Budget App Test Suite" \
        --log-junit tests/results/junit.xml \
        --coverage-html tests/coverage/html \
        --coverage-text tests/coverage/coverage.txt \
        --verbose

# Check test results
if [ $? -eq 0 ]; then
    print_status $GREEN "✓ All tests passed!"
else
    print_status $RED "✗ Some tests failed!"
    exit 1
fi

echo
print_status $BLUE "Generating test reports..."

# Generate summary report
echo "=== Test Execution Summary ===" > tests/results/summary.txt
echo "Execution Date: $(date)" >> tests/results/summary.txt
echo "PHPUnit Version: $(phpunit --version | head -n 1)" >> tests/results/summary.txt
echo "PHP Version: $(php --version | head -n 1)" >> tests/results/summary.txt
echo >> tests/results/summary.txt

# Parse JUnit results
if [ -f "tests/results/junit.xml" ]; then
    # Count tests, failures, errors
    TESTS=$(grep -c '<testcase' tests/results/junit.xml 2>/dev/null || echo "0")
    FAILURES=$(grep -c '<failure' tests/results/junit.xml 2>/dev/null || echo "0")
    ERRORS=$(grep -c '<error' tests/results/junit.xml 2>/dev/null || echo "0")

    echo "Test Results:" >> tests/results/summary.txt
    echo "  Total Tests: $TESTS" >> tests/results/summary.txt
    echo "  Failures: $FAILURES" >> tests/results/summary.txt
    echo "  Errors: $ERRORS" >> tests/results/summary.txt
    echo "  Success Rate: $(( (TESTS - FAILURES - ERRORS) * 100 / TESTS ))%" >> tests/results/summary.txt
fi

# Parse coverage if available
if [ -f "tests/coverage/coverage.txt" ]; then
    echo >> tests/results/summary.txt
    echo "Code Coverage:" >> tests/results/summary.txt
    grep -E "(Lines|Functions|Branches|Paths)" tests/coverage/coverage.txt >> tests/results/summary.txt 2>/dev/null || echo "Coverage data not available" >> tests/results/summary.txt
fi

# Performance tests (if any)
echo >> tests/results/summary.txt
echo "Performance Tests:" >> tests/results/summary.txt
echo "  Memory Peak: $(php -r "echo number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB';")" >> tests/results/summary.txt
echo "  Execution Time: $(date +%s) seconds" >> tests/results/summary.txt

# Display summary
echo
print_status $BLUE "=== Test Summary ==="
cat tests/results/summary.txt

echo
print_status $GREEN "Test execution completed successfully!"
echo
echo "Generated files:"
echo "  - Test results: tests/results/junit.xml"
echo "  - Coverage report: tests/coverage/html/index.html"
echo "  - Summary: tests/results/summary.txt"
echo
echo "To view coverage report, open: tests/coverage/html/index.html"

# Clean up test database
if [ -f "tests/test_database.db" ]; then
    rm tests/test_database.db
    print_status $BLUE "Cleaned up test database"
fi

print_status $GREEN "All tests completed at $(date)"