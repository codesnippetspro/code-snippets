# PHPUnit Testing Setup

## Quick Start

### 1. Install WordPress Test Suite (recommended)

Run the setup script (downloads WordPress + the WP test suite into the repo, and creates the test DB if needed):

```bash
npm run test:setup:php
```

Defaults used by `test:setup:php`:
- **DB Name**: `code_snippets_phpunit`
- **DB User**: `root`
- **DB Password**: *(empty)*
- **DB Host**: `127.0.0.1`
- **WP Version**: `latest`

Override defaults via env vars (example):
```bash
WP_PHPUNIT_DB_NAME=wp_phpunit_test \
WP_PHPUNIT_DB_USER=root \
WP_PHPUNIT_DB_PASS=root \
WP_PHPUNIT_DB_HOST=127.0.0.1 \
WP_PHPUNIT_WP_VERSION=latest \
npm run test:setup:php
```

### 2. Run Tests

Run all tests:
```bash
npm run test:php
```

Run tests with detailed output:
```bash
npm run test:php:watch
```

Or run PHPUnit directly:
```bash
WP_TESTS_DIR=./.wp-tests-lib WP_DEVELOP_DIR=./.wp-core src/vendor/bin/phpunit -c phpunit.xml
```

## What Gets Installed

The `test:setup:php` script will:
1. Download WordPress core to `./.wp-core/`
2. Download the WordPress test library to `./.wp-tests-lib/`
3. Create a test database (if it doesn't exist)
4. Generate `./.wp-tests-lib/wp-tests-config.php`

## Troubleshooting

### "Could not find includes/functions.php"
Run `npm run test:setup:php` to download the WordPress test suite.

### Database connection errors
Verify your database credentials and that MySQL is running.

### Permission errors
Make sure the install script is executable:
```bash
chmod +x tests/install-wp-tests.sh
```

### Missing `svn`
The WordPress test suite download uses `svn export`. Install Subversion if you don't already have it.

## Writing Tests

Tests should be placed in `tests/phpunit/` with the naming pattern `test-*.php`.

Example test:
```php
<?php
namespace Code_Snippets\Tests;

class My_Test extends TestCase {

    public function test_something() {
        $this->assertTrue( true );
    }
}
```

### Guidelines / caveats
- Keep tests isolated: create your own fixtures and clean up after each test where possible.
- Prefer plugin APIs (`save_snippet`, `delete_snippet`, etc.) over direct SQL so behavior matches runtime (and keeps flat-file mode in sync).
- Avoid depending on UI strings/markup in PHPUnit tests—assert on behavior, data, and registered WP objects (e.g. `WP_Admin_Bar` nodes).
