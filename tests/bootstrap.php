<?php

namespace Code_Snippets\Tests;

use WP_UnitTestCase;

$tests_dir = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
require_once $tests_dir . '/includes/functions.php';
require $tests_dir . '/includes/bootstrap.php';

class TestCase extends WP_UnitTestCase {
	// Put convenience methods here

}
