# PHPUnit Testing Guide

## Setup

1. **Install PHPUnit** (if not already installed via Composer):
   ```bash
   cd inventoryProjBrgy/inventoryProjBrgy
   composer require --dev phpunit/phpunit
   ```

2. **Environment Configuration**:
   - Ensure `.env.local` exists in the api directory with all required variables
   - Database `mimds` should be accessible with configured credentials

## Running Tests

### Run All Tests
```bash
vendor/bin/phpunit
```

### Run Specific Test File
```bash
vendor/bin/phpunit tests/HealthCheckTest.php
```

### Run with Code Coverage
```bash
vendor/bin/phpunit --coverage-html=coverage
```

### Run Tests in Watch Mode
```bash
vendor/bin/phpunit --watch
```

## Test Structure

Tests are located in the `tests/` directory and organized by functionality:

- `HealthCheckTest.php` - Verify system connectivity and configuration
- Add more test files for CRUD operations, JWT validation, event processing, etc.

## Bootstrap Configuration

The `phpunit-bootstrap.php` file:
- Loads environment variables from `.env.local`
- Establishes database connection for tests
- Provides helper functions:
  - `create_test_token($username, $role)` - Generate JWT for testing
  - `cleanup_test_data()` - Clean up test data after tests

## Writing Tests

Each test class should:
1. Extend `PHPUnit\Framework\TestCase`
2. Use `setUp()` to initialize test fixtures
3. Use `tearDown()` to clean up after tests
4. Write test methods named `test*`

Example:
```php
<?php
namespace Tests;

use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase
{
    public function testValidCredentialsReturnJwt()
    {
        // Your test code here
    }
}
```

## Integration with CI/CD

### Newman (Postman Collection)
```bash
npx newman run api/postman_collection.json -e api/postman_environment.json --reporters json,cli
```

### GitHub Actions Example
See `.github/workflows/api-tests.yml` for automated testing on each push.

## Notes

- Tests use `.env.local` environment variables
- Database transactions are not rolled back automatically; use `cleanup_test_data()` in tearDown()
- Place test files in `tests/` directory with `Test.php` suffix
- All test namespaces should be `Tests\` to match autoloader configuration
