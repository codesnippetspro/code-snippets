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
WP_TESTS_DIR=./.wp-tests-lib src/vendor/bin/phpunit -c phpunit.xml
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

Make sure the installation script is executable:

```bash
chmod +x scripts/install-wp-tests.sh
```

### Missing `svn`

The WordPress test suite download uses `svn export`. Install Subversion if you don't already have it.

## Writing Tests

Tests should be placed in `tests/unit/` using roughly the same PSR-4 namespace structure as the PHP source files.

Example test:

```php
<?php
namespace Code_Snippets\Controller;

use Code_Snippets\UnitTestCase;

class Example_Controller_Test extends UnitTestCase {

    public function test_something() {
        $this->assertTrue( true );
    }
}
```

### Guidelines / caveats

- Keep tests isolated: create your own fixtures and clean up after each test where possible.
- Prefer plugin APIs (`save_snippet`, `delete_snippet`, etc.) over direct SQL so behavior matches runtime (and keeps
  flat-file mode in sync).
- Avoid depending on UI strings/markup in PHPUnit tests—assert on behavior, data, and registered WP objects (e.g.
  `WP_Admin_Bar` nodes).
- Ideally try to maintain a direct mapping between a source class and its testing class. If a testing class is becoming
  too large, consider whether the source class could be broken up into smaller concerns.

---

# Playwright E2E Testing

## Setup

Prerequisites:

- Docker (required for `wp-env`)
- Node.js/npm

Install JavaScript dependencies:

```bash
npm ci
```

Install Playwright browsers (once):

```bash
npx playwright install
```

Start the WordPress environment:

```bash
npx wp-env start
```

Optional (recommended when switching branches / after failures): reset the WP env:

```bash
npx wp-env clean all
npx wp-env start
```

Prepare the environment for E2E (cleans stale flat-file artifacts, ensures plugin active, etc.):

```bash
npm run test:setup:playwright
```

## Run tests

Run everything:

```bash
npm run test:playwright
```

Run a single project:

```bash
npm run test:playwright -- --project=chromium-db-snippets
```

Run the file-based snippets project (includes flat-file setup):

```bash
npm run test:playwright -- --project=chromium-file-based-snippets
```

Run with HTML reporter but don’t auto-open the report:

```bash
PW_TEST_HTML_REPORT_OPEN=never npm run test:playwright
```

## Debugging failures

- Traces are saved under `test-results/` on failures. View one with:

```bash
npx playwright show-trace test-results/**/trace.zip
```

## Writing Playwright tests

Guidelines / caveats:

- Prefer resilient locators (`getByRole`, `getByLabel`, stable ids) over fragile CSS selectors.
- Use `wpCli()` for setup/fixtures when possible (fast + deterministic).
- Always clean up created snippets/pages (prefer the helper methods so file-based mode stays in sync).
- Avoid leaking global state between tests (e.g. Safe Mode, mu-plugins, settings toggles).
- Keep per-test timeouts explicit only when needed (and use constants rather than magic numbers).
