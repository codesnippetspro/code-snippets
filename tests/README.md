# PHPUnit Testing Setup

## Quick Start

### 1. Install WordPress Test Suite

Run the install script with your database credentials:

```bash
bash tests/install-wp-tests.sh wordpress_test root password localhost latest
```

**For Local by Flywheel users**, your database credentials are typically:
- **DB Name**: Choose any name like `wordpress_test` or `wp_phpunit_test`
- **DB User**: `root`
- **DB Password**: `root`
- **DB Host**: `localhost`

Example for Local:
```bash
bash tests/install-wp-tests.sh wp_phpunit_test root root localhost latest
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

Or run PHPUnit directly from the src directory:
```bash
cd src && ../vendor/bin/phpunit -c ../phpunit.xml
```

## What Gets Installed

The `install-wp-tests.sh` script will:
1. Download WordPress core to `/tmp/wordpress/`
2. Download the WordPress test library to `/tmp/wordpress-tests-lib/`
3. Create a test database (if it doesn't exist)
4. Configure the test environment

## Troubleshooting

### "Could not find includes/functions.php"
Run the install script to download the WordPress test suite.

### Database connection errors
Verify your database credentials and that MySQL is running.

### Permission errors
Make sure the install script is executable:
```bash
chmod +x tests/install-wp-tests.sh
```

## Writing Tests

Tests should be placed in the `tests/` directory with the naming pattern `test-*.php`.

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

