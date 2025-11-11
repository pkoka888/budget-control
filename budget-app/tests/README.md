# Budget App Testing Infrastructure

This directory contains comprehensive automated testing infrastructure for the Budget App's Month 1 AI-powered personal assistant features.

## Overview

The testing infrastructure covers:
- **JSON Normalization System** - Tests for parsing and normalizing bank history data from multiple Czech banks
- **Categorization Service** - Tests for rule-based categorization and ML-ready feature extraction
- **Aggregate Service** - Tests for building recurring income/expense aggregates and balance summaries
- **Data Ingestion Pipeline** - End-to-end tests for the complete data processing workflow

## Test Structure

```
tests/
├── JsonNormalizerTest.php      # JSON normalization system tests
├── CategorizationServiceTest.php # Categorization and tagging tests
├── AggregateServiceTest.php    # Aggregate data building tests
├── bootstrap.php               # Test environment setup
├── run-tests.sh               # Automated test execution script
├── phpunit.xml               # PHPUnit configuration
├── README.md                 # This file
├── results/                  # Test results and reports
└── coverage/                 # Code coverage reports
```

## Running Tests

### Automated Test Execution

Run all tests with the automated script:

```bash
cd budget-app
./tests/run-tests.sh
```

This will:
- Set up the test environment
- Run all PHPUnit tests
- Generate coverage reports
- Create test result summaries
- Clean up test databases

### Manual Test Execution

Run tests manually with PHPUnit:

```bash
cd budget-app
phpunit --configuration phpunit.xml
```

### Specific Test Suites

Run individual test files:

```bash
# JSON Normalizer tests
phpunit tests/JsonNormalizerTest.php

# Categorization Service tests
phpunit tests/CategorizationServiceTest.php

# Aggregate Service tests
phpunit tests/AggregateServiceTest.php
```

## Test Coverage

The tests cover:

### JSON Normalizer (`JsonNormalizerTest.php`)
- **Bank Format Detection**: ČSOB, Česká spořitelna, Komerční banka, Generic formats
- **Data Normalization**: Account and transaction data parsing
- **Merchant Extraction**: Automatic merchant name identification
- **Category Suggestion**: Rule-based category assignment
- **Validation**: Data integrity and error handling
- **Date/Time Handling**: Multiple Czech date formats
- **Error Handling**: Graceful failure and warning collection

### Categorization Service (`CategorizationServiceTest.php`)
- **Rule-Based Categorization**: Explicit categorization rules
- **Merchant History Learning**: Pattern recognition from past transactions
- **Pattern Matching**: Czech banking terminology and merchant identification
- **Tag Generation**: Automatic tagging for online, cash, international transactions
- **Feature Extraction**: ML-ready feature vectors for future enhancement
- **Rule Management**: Creating and managing categorization rules
- **Statistics**: Categorization performance metrics

### Aggregate Service (`AggregateServiceTest.php`)
- **Recurring Income Detection**: Monthly salary and regular income patterns
- **Recurring Expense Analysis**: Rent, utilities, subscription identification
- **Balance Aggregation**: Account balance tracking over time
- **Monthly Summaries**: Income/expense summaries with budget comparisons
- **Category Trends**: Spending pattern analysis
- **Cash Flow Events**: Surplus/deficit period identification
- **Cohort Analysis**: Merchant spending pattern clustering
- **Performance Metrics**: Consistency scoring and budget impact analysis

## Test Data

The tests use realistic Czech banking data including:

- **ČSOB Format**: Complete account and transaction structures
- **Česká spořitelna Format**: Alternative field mappings
- **Komerční banka Format**: Different JSON structure
- **Czech Terminology**: Proper Czech banking terms and merchant names
- **Currency Handling**: CZK with proper decimal formatting
- **Date Formats**: Multiple Czech date format variations

## Performance Testing

Tests include performance benchmarks for:
- Large transaction dataset processing
- Memory usage monitoring
- Execution time validation
- Database query optimization

## Code Coverage

The test suite aims for >80% code coverage across:
- Service layer classes
- Data processing logic
- Error handling paths
- Edge cases and validation

## Continuous Integration

The test infrastructure is designed for CI/CD integration:

```yaml
# Example GitHub Actions workflow
- name: Run Tests
  run: |
    cd budget-app
    ./tests/run-tests.sh

- name: Upload Coverage
  uses: codecov/codecov-action@v3
  with:
    file: ./budget-app/tests/coverage/coverage.xml
```

## Test Reports

After running tests, reports are generated in:

- **JUnit XML**: `tests/results/junit.xml` - For CI/CD integration
- **HTML Coverage**: `tests/coverage/html/index.html` - Interactive coverage report
- **Text Summary**: `tests/results/summary.txt` - Human-readable summary
- **Coverage Text**: `tests/coverage/coverage.txt` - CLI coverage summary

## Mock Data Helpers

The `bootstrap.php` file provides helper functions for creating test data:

- `createMockUser()` - Standard user data
- `createMockTransaction()` - Sample transaction
- `createMockAccount()` - Account data
- `createMockCsobJson()` - ČSOB format JSON
- `createMockCeskaSporitelnaJson()` - Česká spořitelna format
- `createMockKomercniBankaJson()` - Komerční banka format

## Extending Tests

When adding new features:

1. Create new test methods in existing test classes
2. Add mock data helpers to `bootstrap.php`
3. Update this README with new test coverage areas
4. Ensure >80% coverage for new code

## Dependencies

- **PHPUnit**: Testing framework
- **PHP 8.0+**: Language runtime
- **SQLite**: Test database (in-memory)

Install dependencies:

```bash
composer require --dev phpunit/phpunit
```

## Troubleshooting

### Common Issues

1. **PHPUnit not found**: Ensure PHPUnit is installed and in PATH
2. **Permission denied**: Make sure `run-tests.sh` is executable
3. **Database errors**: Check SQLite extension is enabled in PHP
4. **Memory issues**: Large test datasets may require increasing PHP memory limit

### Debug Mode

Run tests with verbose output:

```bash
phpunit --configuration phpunit.xml --verbose --debug
```

### Single Test Debugging

Run a specific test method:

```bash
phpunit --filter testNormalizeCsobFormat tests/JsonNormalizerTest.php